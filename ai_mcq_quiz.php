<?php
session_start();
require_once 'db_config.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv') && file_exists(__DIR__ . '/.env')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
            $dotenv->safeLoad();
        } catch (Throwable $e) {
        }
    }
}

if (!isset($_SESSION['student_id']) || ($_SESSION['user_type'] ?? '') !== 'student') {
    header('Location: index.php');
    exit();
}

$student_id = (int)$_SESSION['student_id'];
$quiz_error = '';
$quiz_success = '';
$show_quiz_form = false;
$quiz_questions = [];
$quiz_subject = '';
$quiz_subject_label = '';
$result = null;
$gemini_last_error = '';
$selected_subject_input = 'custom_subject';
$custom_subject_input = '';

function hasTable(mysqli $conn, string $table): bool {
    $safe = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$safe}'");
    return $res && $res->num_rows > 0;
}

function normalizeSubject(string $subject): string {
    $subject = trim($subject);
    if ($subject === '') {
        return '';
    }
    return preg_replace('/\s+/', ' ', $subject);
}

function normalizeOption(string $option): string {
    $option = strtoupper(trim($option));
    return in_array($option, ['A', 'B', 'C', 'D'], true) ? $option : '';
}

function normalizeQuestionKey(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/\s+/', ' ', $text);
    return $text;
}

function resolveSemesterOrder(mysqli $conn, int $semester_id): int {
    if ($semester_id <= 0) {
        return 0;
    }

    if ($semester_id <= 8) {
        return $semester_id;
    }

    $stmt = $conn->prepare("SELECT semester_order FROM semesters WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $semester_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $order = (int)($row['semester_order'] ?? 0);

    return $order > 0 ? $order : 0;
}

function fetchStudentSubjectPools(mysqli $conn, int $student_id): array {
    $current_subjects = [];
    $upto_subjects = [];

    $student_stmt = $conn->prepare("SELECT department_id, semester_id FROM students WHERE id = ? LIMIT 1");
    $student_stmt->bind_param('i', $student_id);
    $student_stmt->execute();
    $student = $student_stmt->get_result()->fetch_assoc();

    $department_id = (int)($student['department_id'] ?? 0);
    $semester_id_raw = (int)($student['semester_id'] ?? 0);
    $current_semester_order = resolveSemesterOrder($conn, $semester_id_raw);

    if ($current_semester_order <= 0 && $semester_id_raw > 0) {
        $current_semester_order = $semester_id_raw;
    }

    if ($current_semester_order > 8) {
        $current_semester_order = 8;
    }

    if ($department_id > 0 && $current_semester_order > 0) {
        $stmt = $conn->prepare("SELECT DISTINCT subject_name
                               FROM subjects_master
                               LEFT JOIN semesters sem ON sem.id = subjects_master.semester_id
                                         WHERE subjects_master.department_id = ?
                                 AND (
                                       subjects_master.semester_id = ?
                                    OR sem.semester_order = ?
                                    OR sem.id = ?
                                 )
                                 AND subject_name NOT LIKE '%Internship%'
                                 AND subject_name NOT LIKE '%Project%'
                               ORDER BY subject_name ASC");
        $stmt->bind_param('iiii', $department_id, $current_semester_order, $current_semester_order, $semester_id_raw);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $name = normalizeSubject((string)($row['subject_name'] ?? ''));
            if ($name !== '') {
                $current_subjects[$name] = true;
            }
        }

        $stmt = $conn->prepare("SELECT DISTINCT subject_name
                               FROM subjects_master
                                                             LEFT JOIN semesters sem ON sem.id = subjects_master.semester_id
                                         WHERE subjects_master.department_id = ?
                                                                 AND (
                                                                             (subjects_master.semester_id BETWEEN 1 AND ?)
                                                                        OR (sem.semester_order BETWEEN 1 AND ?)
                                                                 )
                                 AND subject_name NOT LIKE '%Internship%'
                                 AND subject_name NOT LIKE '%Project%'
                                                             ORDER BY subject_name ASC");
        $stmt->bind_param('iii', $department_id, $current_semester_order, $current_semester_order);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $name = normalizeSubject((string)($row['subject_name'] ?? ''));
            if ($name !== '') {
                $upto_subjects[$name] = true;
            }
        }
    }

    if (empty($current_subjects) && $department_id > 0) {
        $stmt = $conn->prepare("SELECT DISTINCT subject_name FROM subjects_master WHERE department_id = ? ORDER BY subject_name ASC");
        $stmt->bind_param('i', $department_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $name = normalizeSubject((string)($row['subject_name'] ?? ''));
            if ($name !== '') {
                $current_subjects[$name] = true;
                $upto_subjects[$name] = true;
            }
        }
    }

    if (empty($upto_subjects)) {
        $upto_subjects = $current_subjects;
    }

    return [
        'current_subjects' => array_keys($current_subjects),
        'upto_subjects' => array_keys($upto_subjects),
        'current_semester_order' => $current_semester_order
    ];
}

function getGeminiApiKey(): string {
    $candidates = [
        defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '',
        getenv('GEMINI_API_KEY') ?: '',
        getenv('GOOGLE_API_KEY') ?: '',
        $_ENV['GEMINI_API_KEY'] ?? '',
        $_ENV['GOOGLE_API_KEY'] ?? ''
    ];

    foreach ($candidates as $key) {
        $trimmed = trim((string)$key);
        if ($trimmed !== '') {
            return $trimmed;
        }
    }

    return '';
}

function countQuizQuestions(mysqli $conn, string $subject, array $all_subject_pool = []): int {
    if ($subject === 'all_subjects') {
        if (!empty($all_subject_pool)) {
            $escaped_subjects = array_map(static fn($s) => "'" . $conn->real_escape_string($s) . "'", $all_subject_pool);
            $in_clause = implode(',', $escaped_subjects);
            $sql = "SELECT COUNT(*) AS cnt FROM mcq_question_bank WHERE subject_name IN ({$in_clause})";
            $res = $conn->query($sql);
            return (int)($res->fetch_assoc()['cnt'] ?? 0);
        }

        $res = $conn->query("SELECT COUNT(*) AS cnt FROM mcq_question_bank");
        return (int)($res->fetch_assoc()['cnt'] ?? 0);
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM mcq_question_bank WHERE subject_name = ?");
    $stmt->bind_param('s', $subject);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int)($row['cnt'] ?? 0);
}

function generateQuestionsFromGemini(string $subject, int $count, array $all_subject_pool = []): array {
    global $gemini_last_error;
    $gemini_last_error = '';

    $apiKey = getGeminiApiKey();
    if ($apiKey === '') {
        $gemini_last_error = 'Gemini API key not configured.';
        return [];
    }

    if ($subject === 'all_subjects') {
        $subject_list = !empty($all_subject_pool) ? implode(', ', $all_subject_pool) : 'Engineering mixed subjects';
        $prompt = "Generate {$count} multiple-choice questions from these subjects only: {$subject_list}. "
            . "Distribute questions across the listed subjects. "
            . "Difficulty must match engineering license/entrance exam level (conceptual + numerical + application). "
            . "Return ONLY valid JSON array. "
            . "Each item must contain keys: subject_name, question, option_a, option_b, option_c, option_d, correct_option, explanation. "
            . "subject_name must be exactly one item from the provided subject list. "
            . "correct_option must be one of A,B,C,D.";
    } else {
        $prompt = "Generate {$count} multiple-choice questions for {$subject}. "
            . "Difficulty must match engineering license/entrance exam level (conceptual + numerical + application). "
            . "Return ONLY valid JSON array. "
            . "Each item must contain keys: question, option_a, option_b, option_c, option_d, correct_option, explanation. "
            . "correct_option must be one of A,B,C,D. Keep questions concise and exam-style.";
    }

    $payload = [
        'contents' => [[
            'parts' => [[
                'text' => $prompt
            ]]
        ]],
        'generationConfig' => [
            'temperature' => 0.5,
            'maxOutputTokens' => 8192
        ]
    ];

    $candidate_models = [
        'gemini-2.5-flash',
        'gemini-2.0-flash'
    ];

    $response = '';
    $status = 0;
    foreach ($candidate_models as $model_name) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model_name . ':generateContent?key=' . urlencode($apiKey);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = (string)curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 200 && $status < 300 && $response !== '') {
            break;
        }

        $decoded_error = json_decode($response, true);
        $api_message = trim((string)($decoded_error['error']['message'] ?? ''));
        if ($api_message !== '') {
            $gemini_last_error = $api_message;
        } else {
            $gemini_last_error = 'Gemini API request failed with HTTP ' . $status . '.';
        }
    }

    if ($response === '' || $status < 200 || $status >= 300) {
        if ($gemini_last_error === '') {
            $gemini_last_error = 'Gemini API unavailable right now.';
        }
        return [];
    }

    $decoded = json_decode($response, true);
    $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (!is_string($text) || trim($text) === '') {
        $gemini_last_error = 'Gemini returned empty response.';
        return [];
    }

    $text = trim($text);
    
    // Try multiple cleanup strategies
    $cleaned = $text;
    
    // Remove markdown code blocks
    $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
    $cleaned = preg_replace('/\s*```$/i', '', $cleaned);
    
    // Try to extract JSON array if embedded in text
    if (preg_match('/\[\s*\{.*\}\s*\]/s', $cleaned, $matches)) {
        $cleaned = $matches[0];
    }
    
    // Remove any leading/trailing non-JSON characters
    $cleaned = preg_replace('/^[^[{]*/', '', $cleaned);
    $cleaned = preg_replace('/[^}\]]*$/', '', $cleaned);

    $arr = json_decode($cleaned, true);
    if (!is_array($arr)) {
        $snippet = mb_substr($text, 0, 200);
        $gemini_last_error = 'Invalid JSON response. Preview: ' . $snippet;
        return [];
    }

    $questions = [];
    foreach ($arr as $item) {
        if (!is_array($item)) {
            continue;
        }
        $subject_name = $subject;
        if ($subject === 'all_subjects') {
            $parsed_subject = normalizeSubject((string)($item['subject_name'] ?? ''));
            if ($parsed_subject !== '' && in_array($parsed_subject, $all_subject_pool, true)) {
                $subject_name = $parsed_subject;
            } elseif (!empty($all_subject_pool)) {
                $subject_name = $all_subject_pool[array_rand($all_subject_pool)];
            } else {
                $subject_name = 'All Subjects';
            }
        }

        $question = trim((string)($item['question'] ?? ''));
        $a = trim((string)($item['option_a'] ?? ''));
        $b = trim((string)($item['option_b'] ?? ''));
        $c = trim((string)($item['option_c'] ?? ''));
        $d = trim((string)($item['option_d'] ?? ''));
        $correct = normalizeOption((string)($item['correct_option'] ?? ''));
        $explanation = trim((string)($item['explanation'] ?? ''));

        if ($question === '' || $a === '' || $b === '' || $c === '' || $d === '' || $correct === '') {
            continue;
        }

        $questions[] = [
            'subject_name' => $subject_name,
            'question_text' => $question,
            'option_a' => $a,
            'option_b' => $b,
            'option_c' => $c,
            'option_d' => $d,
            'correct_option' => $correct,
            'explanation' => $explanation
        ];
    }

    return $questions;
}

function saveGeneratedQuestions(mysqli $conn, array $questions, string $subject, int $admin_id = 0): void {
    if (empty($questions)) {
        return;
    }

    $insert = $conn->prepare("INSERT INTO mcq_question_bank (subject_name, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, created_by_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $exists_stmt = $conn->prepare("SELECT id FROM mcq_question_bank WHERE subject_name = ? AND question_text = ? LIMIT 1");
    $seen_in_batch = [];

    foreach ($questions as $q) {
        $question_text = $q['question_text'];
        $option_a = $q['option_a'];
        $option_b = $q['option_b'];
        $option_c = $q['option_c'];
        $option_d = $q['option_d'];
        $correct = $q['correct_option'];
        $explanation = $q['explanation'];
        $subject_name = normalizeSubject((string)($q['subject_name'] ?? ''));
        if ($subject_name === '') {
            $subject_name = $subject === 'all_subjects' ? 'All Subjects' : $subject;
        }

        $question_key = $subject_name . '::' . normalizeQuestionKey($question_text);
        if (isset($seen_in_batch[$question_key])) {
            continue;
        }

        $exists_stmt->bind_param('ss', $subject_name, $question_text);
        $exists_stmt->execute();
        $exists_row = $exists_stmt->get_result()->fetch_assoc();
        if ($exists_row) {
            $seen_in_batch[$question_key] = true;
            continue;
        }

        $insert->bind_param('ssssssssi', $subject_name, $question_text, $option_a, $option_b, $option_c, $option_d, $correct, $explanation, $admin_id);
        $insert->execute();
        $seen_in_batch[$question_key] = true;
    }
}

function fetchQuizQuestions(mysqli $conn, string $subject, int $limit, array $all_subject_pool = []): array {
    $fetch_limit = max($limit * 3, 200);

    if ($subject === 'all_subjects') {
        if (!empty($all_subject_pool)) {
            $escaped_subjects = array_map(static fn($s) => "'" . $conn->real_escape_string($s) . "'", $all_subject_pool);
            $in_clause = implode(',', $escaped_subjects);
            $sql = "SELECT id, subject_name, question_text, option_a, option_b, option_c, option_d, correct_option, explanation
                    FROM mcq_question_bank
                    WHERE subject_name IN ({$in_clause})
                    ORDER BY RAND()
                    LIMIT ?";
            $stmt = $conn->prepare($sql);
        } else {
            $stmt = $conn->prepare("SELECT id, subject_name, question_text, option_a, option_b, option_c, option_d, correct_option, explanation FROM mcq_question_bank ORDER BY RAND() LIMIT ?");
        }
        $stmt->bind_param('i', $fetch_limit);
    } else {
        $stmt = $conn->prepare("SELECT id, subject_name, question_text, option_a, option_b, option_c, option_d, correct_option, explanation FROM mcq_question_bank WHERE subject_name = ? ORDER BY RAND() LIMIT ?");
        $stmt->bind_param('si', $subject, $fetch_limit);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    $seen = [];
    while ($row = $res->fetch_assoc()) {
        $key = normalizeQuestionKey((string)($row['question_text'] ?? ''));
        if ($key === '' || isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $row['id'] = (int)$row['id'];
        $rows[] = $row;
        if (count($rows) >= $limit) {
            break;
        }
    }

    return $rows;
}

function evaluateAndSaveAttempt(mysqli $conn, int $student_id, string $subject, array $questions, array $submitted): array {
    $total = count($questions);
    $correct = 0;
    $details = [];

    foreach ($questions as $index => $q) {
        $qid = (int)$q['id'];
        $answerKey = 'q_' . $qid;
        $selected = normalizeOption((string)($submitted[$answerKey] ?? ''));
        $actual = normalizeOption((string)$q['correct_option']);
        $is_correct = $selected !== '' && $selected === $actual;

        if ($is_correct) {
            $correct++;
        }

        $details[] = [
            'index' => $index + 1,
            'question_id' => $qid,
            'subject_name' => $q['subject_name'],
            'question_text' => $q['question_text'],
            'selected_option' => $selected,
            'correct_option' => $actual,
            'is_correct' => $is_correct,
            'explanation' => $q['explanation']
        ];
    }

    $wrong = $total - $correct;
    $percent = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    $attempt_detail_json = json_encode($details, JSON_UNESCAPED_UNICODE);
    $insert = $conn->prepare("INSERT INTO mcq_quiz_attempts (student_id, selected_subject, total_questions, correct_answers, wrong_answers, score_percent) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param('isiiid', $student_id, $subject, $total, $correct, $wrong, $percent);
    $insert->execute();

    return [
        'total' => $total,
        'correct' => $correct,
        'wrong' => $wrong,
        'percent' => $percent,
        'details' => $details
    ];
}

function getTodayAttemptSummary(mysqli $conn, int $student_id): ?array {
    $stmt = $conn->prepare("SELECT selected_subject, total_questions, correct_answers, score_percent, attempted_at
                            FROM mcq_quiz_attempts
                            WHERE student_id = ? AND DATE(attempted_at) = CURDATE()
                            ORDER BY attempted_at DESC
                            LIMIT 1");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row ?: null;
}

$table_ready = hasTable($conn, 'mcq_question_bank') && hasTable($conn, 'mcq_quiz_attempts');
$subject_pool_info = fetchStudentSubjectPools($conn, $student_id);
$subject_options = $subject_pool_info['current_subjects'];
$all_subject_pool = $subject_pool_info['upto_subjects'];
$current_semester_order = (int)($subject_pool_info['current_semester_order'] ?? 0);
$has_subject_options = !empty($subject_options);
$today_attempt = $table_ready ? getTodayAttemptSummary($conn, $student_id) : null;
$daily_attempt_used = $today_attempt !== null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['mcq_quiz_questions'], $_SESSION['mcq_quiz_subject'], $_SESSION['mcq_quiz_started_at']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_quiz'])) {
    if (!$table_ready) {
        $quiz_error = 'Quiz table not found. Please run create_mcq_quiz_tables.sql first.';
    } elseif ($daily_attempt_used) {
        $quiz_error = 'Daily quiz limit reached. You can attempt again tomorrow.';
    } else {
        $selected_subject_input = normalizeSubject((string)($_POST['quiz_mode'] ?? 'custom_subject'));
        $custom_subject_input = normalizeSubject((string)($_POST['custom_subject_name'] ?? ''));

        if ($selected_subject_input === 'all_subjects') {
            $selected_subject = 'all_subjects';
        } else {
            $selected_subject = $custom_subject_input;
            if ($selected_subject === '') {
                $quiz_error = 'Please enter a subject name.';
            }
        }

        if ($quiz_error === '') {
            $api_key = getGeminiApiKey();
            $available_before = countQuizQuestions($conn, $selected_subject, $all_subject_pool);
            $questions = fetchQuizQuestions($conn, $selected_subject, 50, $all_subject_pool);
            $attempt_rounds = 0;
            $ai_generated_count = 0;

            while (count($questions) < 50 && $attempt_rounds < 6) {
                if ($api_key === '') {
                    break;
                }

                $attempt_rounds++;
                $missing = 50 - count($questions);
                $batch_size = min(15, $missing);
                $before_gen = count($questions);
                $generated = generateQuestionsFromGemini($selected_subject, $batch_size, $all_subject_pool);

                if (empty($generated)) {
                    // If generation failed, wait briefly and retry once more
                    if ($attempt_rounds < 3) {
                        sleep(2);
                        $generated = generateQuestionsFromGemini($selected_subject, $batch_size, $all_subject_pool);
                    }
                    
                    if (empty($generated)) {
                        break;
                    }
                }

                saveGeneratedQuestions($conn, $generated, $selected_subject);
                $questions = fetchQuizQuestions($conn, $selected_subject, 50, $all_subject_pool);
                $ai_generated_count += (count($questions) - $before_gen);
            }

            $min_required = 20; // Reduced from 50 to be more flexible
            if (count($questions) < $min_required) {
                $available_after = countQuizQuestions($conn, $selected_subject, $all_subject_pool);
                if ($api_key === '') {
                    $quiz_error = "Only {$available_after} questions available. Need at least {$min_required}. Add Gemini API key for AI generation.";
                } else {
                    $extra_reason = trim((string)$gemini_last_error);
                    if ($extra_reason !== '') {
                        $quiz_error = "Generated {$available_after} questions. Need at least {$min_required}. AI Error: {$extra_reason}";
                    } else {
                        $quiz_error = "Generated {$available_after} questions. Need at least {$min_required}. Please try a different subject or retry.";
                    }
                }
            } else {
                $_SESSION['mcq_quiz_questions'] = $questions;
                $_SESSION['mcq_quiz_subject'] = $selected_subject;
                $_SESSION['mcq_quiz_started_at'] = time();
                $_SESSION['mcq_ai_generated'] = $ai_generated_count;
                $quiz_questions = $questions;
                $show_quiz_form = true;
                $quiz_subject = $selected_subject;
                
                $total_ques = count($questions);
                // Show AI generation stats
                if ($ai_generated_count > 0 && $api_key !== '') {
                    if ($total_ques >= 50) {
                        $quiz_success = "✨ Perfect! Gemini AI generated {$ai_generated_count} fresh questions. Quiz ready with {$total_ques} MCQs!";
                    } else {
                        $quiz_success = "✨ Gemini AI generated {$ai_generated_count} new questions. Quiz ready with {$total_ques} MCQs (partial quiz mode).";
                    }
                } elseif ($api_key === '') {
                    $quiz_success = "⚠️ Using {$total_ques} cached questions. Add Gemini API key in .env for AI generation.";
                } else {
                    $quiz_success = "Quiz ready with {$total_ques} questions from database.";
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    if (!$table_ready) {
        $quiz_error = 'Quiz table not found. Please run create_mcq_quiz_tables.sql first.';
    } elseif ($daily_attempt_used) {
        $quiz_error = 'Today\'s quiz attempt is already recorded. Please try again tomorrow.';
    } else {
        $session_questions = $_SESSION['mcq_quiz_questions'] ?? [];
        $session_subject = normalizeSubject((string)($_SESSION['mcq_quiz_subject'] ?? ''));

        if (empty($session_questions) || $session_subject === '') {
            $quiz_error = 'Quiz session expired. Please start again.';
        } else {
            $result = evaluateAndSaveAttempt($conn, $student_id, $session_subject, $session_questions, $_POST);
            $quiz_success = 'Quiz evaluated and record saved successfully.';
            $quiz_subject = $session_subject;
            unset($_SESSION['mcq_quiz_questions'], $_SESSION['mcq_quiz_subject'], $_SESSION['mcq_quiz_started_at']);
        }
    }
}

$quiz_subject_label = ($quiz_subject === 'all_subjects') ? 'All Subjects' : $quiz_subject;

$history = [];
if ($table_ready) {
    $history_stmt = $conn->prepare("SELECT selected_subject, total_questions, correct_answers, wrong_answers, score_percent, attempted_at FROM mcq_quiz_attempts WHERE student_id = ? ORDER BY attempted_at DESC LIMIT 10");
    $history_stmt->bind_param('i', $student_id);
    $history_stmt->execute();
    $history_res = $history_stmt->get_result();
    while ($row = $history_res->fetch_assoc()) {
        $history[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI MCQ Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<?php include 'student_header.php'; ?>

<div class="container py-4">
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <div>
                    <h4 class="mb-1 fw-bold"><i class="fas fa-sparkles me-2"></i>AI-Powered MCQ Quiz</h4>
                    <p class="mb-0 small opacity-90">Gemini AI generates fresh exam-level questions instantly</p>
                </div>
                <a href="student_dashboard.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
            </div>
        </div>
    </div>

    <!-- API Status Card -->
    <div class="card shadow-sm border-0 mb-3" style="border-left: 4px solid #667eea !important;">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-robot text-primary"></i>
                        <strong class="text-dark">Google Gemini 2.0 Flash</strong>
                        <span class="badge bg-success">Free Tier Active</span>
                    </div>
                    <div class="small text-muted">
                        Automatically generates engineering MCQs on-demand • Supports all technical subjects
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-end">
                        <div class="small text-muted mb-1">API Rate Limits</div>
                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                            <span class="badge bg-primary">15 req/min</span>
                            <span class="badge bg-info">1500 req/day</span>
                            <span class="badge bg-secondary">1M tokens/mo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$table_ready): ?>
        <div class="alert alert-warning">Please run <strong>create_mcq_quiz_tables.sql</strong> first.</div>
    <?php endif; ?>

    <?php if ($quiz_error !== ''): ?>
        <div class="alert alert-danger d-flex align-items-center">
            <i class="fas fa-exclamation-circle me-2 fs-5"></i>
            <div><?= htmlspecialchars($quiz_error) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($quiz_success !== ''): ?>
        <div class="alert alert-success d-flex align-items-center">
            <i class="fas fa-check-circle me-2 fs-5"></i>
            <div><?= $quiz_success ?></div>
        </div>
    <?php endif; ?>

    <?php if ($daily_attempt_used): ?>
        <div class="alert alert-info">
            Daily limit used: <?= htmlspecialchars($today_attempt['attempted_at']) ?>
            (Score: <?= number_format((float)($today_attempt['score_percent'] ?? 0), 2) ?>%).
            You can take next quiz tomorrow.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Start New AI Quiz</h6>
            <form method="post" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Quiz Mode</label>
                    <select name="quiz_mode" id="quiz_mode" class="form-select" required>
                        <option value="custom_subject" <?= $selected_subject_input === 'custom_subject' ? 'selected' : '' ?>>Type Subject Name</option>
                        <option value="all_subjects" <?= $selected_subject_input === 'all_subjects' ? 'selected' : '' ?>>All Subjects (Sem 1<?= $current_semester_order > 0 ? ' to ' . $current_semester_order : '' ?> Mixed)</option>
                    </select>
                    <input type="text" name="custom_subject_name" id="custom_subject_name" class="form-control mt-2" placeholder="Enter subject name (e.g., Soil Mechanics, Fluid Mechanics, Engineering Mathematics)" value="<?= htmlspecialchars($custom_subject_input) ?>">
                    <div class="alert alert-info p-2 mt-2 mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Gemini AI Free Tier:</strong> 15 requests/min • 1,500 requests/day • 1M tokens/month
                        <span class="badge bg-primary ms-2">Currently Active</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="start_quiz" class="btn btn-primary w-100" <?= (!$table_ready || $daily_attempt_used) ? 'disabled' : '' ?>>
                        <i class="fas fa-play me-2"></i>Start MCQ Quiz
                    </button>
                </div>
            </form>
            <small class="text-muted d-block mt-2">
                <i class="fas fa-info-circle me-1"></i>AI generates up to 50 MCQs on your chosen subject. 
                Minimum 20 questions required. Quiz works with partial results if API is slow.
                <strong>Daily limit: 1 quiz/day.</strong>
            </small>
        </div>
    </div>

    <?php if ($show_quiz_form && !empty($quiz_questions)): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0">Quiz: <?= htmlspecialchars($quiz_subject_label) ?></h6>
                    <div class="d-flex gap-2">
                        <?php 
                        $ai_gen = (int)($_SESSION['mcq_ai_generated'] ?? 0);
                        $db_ques = count($quiz_questions) - $ai_gen;
                        ?>
                        <?php if ($ai_gen > 0): ?>
                            <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-sparkles me-1"></i>AI Generated: <?= $ai_gen ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($db_ques > 0): ?>
                            <span class="badge bg-info">Database: <?= $db_ques ?></span>
                        <?php endif; ?>
                        <span class="badge bg-success">Total: <?= count($quiz_questions) ?> Q</span>
                    </div>
                </div>

                <form method="post">
                    <?php foreach ($quiz_questions as $i => $q): ?>
                        <?php $qid = (int)$q['id']; ?>
                        <div class="border rounded p-3 mb-3 bg-white">
                            <div class="fw-semibold mb-2"><?= ($i + 1) ?>. <?= htmlspecialchars($q['question_text']) ?></div>
                            <div class="small text-muted mb-2">Subject: <?= htmlspecialchars($q['subject_name']) ?></div>

                            <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                                <?php $optionKey = 'option_' . strtolower($opt); ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="radio" name="q_<?= $qid ?>" id="q_<?= $qid ?>_<?= $opt ?>" value="<?= $opt ?>">
                                    <label class="form-check-label" for="q_<?= $qid ?>_<?= $opt ?>">
                                        <strong><?= $opt ?>.</strong> <?= htmlspecialchars($q[$optionKey] ?? '') ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" name="submit_quiz" class="btn btn-success px-4">
                        <i class="fas fa-check-circle me-2"></i>Submit & Analyze
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if (is_array($result)): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Evaluation Report</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><div class="p-3 bg-light rounded"><small>Total</small><div class="fw-bold fs-5"><?= (int)$result['total'] ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 bg-light rounded"><small>Correct</small><div class="fw-bold fs-5 text-success"><?= (int)$result['correct'] ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 bg-light rounded"><small>Wrong</small><div class="fw-bold fs-5 text-danger"><?= (int)$result['wrong'] ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 bg-light rounded"><small>Score %</small><div class="fw-bold fs-5 text-primary"><?= number_format((float)$result['percent'], 2) ?>%</div></div></div>
                </div>

                <h6 class="fw-semibold">Question Analysis</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Question</th>
                                <th>Your</th>
                                <th>Correct</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($result['details'] as $row): ?>
                            <tr>
                                <td><?= (int)$row['index'] ?></td>
                                <td><?= htmlspecialchars($row['question_text']) ?></td>
                                <td><?= htmlspecialchars($row['selected_option'] !== '' ? $row['selected_option'] : '-') ?></td>
                                <td><?= htmlspecialchars($row['correct_option']) ?></td>
                                <td>
                                    <?php if ($row['is_correct']): ?>
                                        <span class="badge bg-success">Correct</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Wrong</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h6 class="fw-bold mb-3">My Recent Quiz Records</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Subject</th>
                            <th>Total</th>
                            <th>Correct</th>
                            <th>Wrong</th>
                            <th>Score</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($history)): ?>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= htmlspecialchars($h['selected_subject'] === 'all_subjects' ? 'All Subjects' : $h['selected_subject']) ?></td>
                                <td><?= (int)$h['total_questions'] ?></td>
                                <td class="text-success fw-semibold"><?= (int)$h['correct_answers'] ?></td>
                                <td class="text-danger fw-semibold"><?= (int)$h['wrong_answers'] ?></td>
                                <td class="fw-bold"><?= number_format((float)$h['score_percent'], 2) ?>%</td>
                                <td><?= htmlspecialchars($h['attempted_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No quiz record yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    (function() {
        const modeSelect = document.getElementById('quiz_mode');
        const customInput = document.getElementById('custom_subject_name');
        if (!modeSelect || !customInput) return;

        const syncCustomSubjectField = () => {
            const useCustom = modeSelect.value === 'custom_subject';
            customInput.style.display = useCustom ? 'block' : 'none';
            customInput.required = useCustom;
            if (!useCustom) {
                customInput.value = '';
            }
        };

        modeSelect.addEventListener('change', syncCustomSubjectField);
        syncCustomSubjectField();
    })();
</script>
</body>
</html>
