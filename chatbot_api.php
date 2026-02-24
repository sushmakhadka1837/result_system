<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db_config.php';
use Dotenv\Dotenv;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = trim((string)($_ENV['GEMINI_API_KEY'] ?? ($_ENV['GOOGLE_API_KEY'] ?? '')));

// Read user input
$input = json_decode(file_get_contents('php://input'), true);
$user_message = trim($input['message'] ?? '');
if ($user_message === '') {
    echo json_encode(['reply' => '❗ Please type your question.']);
    exit;
}

function normalizeText(string $text): string {
    $text = mb_strtolower($text);
    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim((string)$text);
}

function detectSubjectFromMessage(mysqli $conn, string $message): ?array {
    $normalized_message = normalizeText($message);
    if ($normalized_message === '') {
        return null;
    }

    $subjects = [];
    $res = $conn->query("SELECT id, subject_name, subject_code FROM subjects_master");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $subjects[] = $row;
        }
    }

    $best = null;
    $best_score = 0;
    $message_tokens = array_values(array_filter(explode(' ', $normalized_message), fn($token) => mb_strlen($token) >= 3));

    foreach ($subjects as $subject) {
        $subject_name = (string)($subject['subject_name'] ?? '');
        $subject_code = (string)($subject['subject_code'] ?? '');
        $subject_haystack = normalizeText($subject_name . ' ' . $subject_code);
        if ($subject_haystack === '') {
            continue;
        }

        $score = 0;
        if (str_contains($normalized_message, $subject_haystack)) {
            $score += 10;
        }

        $subject_tokens = array_values(array_filter(explode(' ', $subject_haystack), fn($token) => mb_strlen($token) >= 3));
        foreach ($subject_tokens as $token) {
            if (str_contains($normalized_message, $token)) {
                $score += 2;
            }
        }

        foreach ($message_tokens as $token) {
            if (str_contains($subject_haystack, $token)) {
                $score += 1;
            }
        }

        if ($score > $best_score) {
            $best_score = $score;
            $best = $subject;
        }
    }

    return $best_score > 0 ? $best : null;
}

function localAssistantReply(mysqli $conn, string $message, int $session_student_id): string {
    $msg = normalizeText($message);
    if ($msg === '') {
        return '';
    }

    if (preg_match('/^(hi|hello|hey|namaste|namaskar)\b/u', $msg)) {
        return "Namaste 👋 Ma timro Result Assistant ho.\n- Type: 'my ut result'\n- Type: 'roll 21075381 ko ut result'\n- Type: 'image processing ko ut marks kati xa'\nMa possible vaye direct database bata answer dinxu.";
    }

    $is_ut_query = str_contains($msg, 'ut') || str_contains($msg, 'unit test');
    $is_result_query = str_contains($msg, 'result') || str_contains($msg, 'marks') || str_contains($msg, 'score') || str_contains($msg, 'kati');
    if (!$is_ut_query && !$is_result_query) {
        return '';
    }

    $symbol_no = null;
    if (preg_match('/\b(\d{5,12})\b/u', $msg, $m)) {
        $symbol_no = $m[1];
    }

    $student = null;
    if ($symbol_no !== null) {
        $stmt = $conn->prepare("SELECT id, full_name, symbol_no, current_semester FROM students WHERE symbol_no = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $symbol_no);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    } elseif ($session_student_id > 0) {
        $stmt = $conn->prepare("SELECT id, full_name, symbol_no, current_semester FROM students WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $session_student_id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }

    if (!$student) {
        if ($symbol_no !== null) {
            return "Tyo roll/symbol ($symbol_no) ko student record vetiena.";
        }
        return "Please roll/symbol number pathaunu (example: roll 21075381 ko ut result).";
    }

    $subject = detectSubjectFromMessage($conn, $message);
    $student_id = (int)$student['id'];

    if ($subject) {
        $subject_id = (int)$subject['id'];
        $stmt = $conn->prepare("SELECT r.ut_obtain, r.ut_grade, r.semester_id, sm.subject_name FROM results r INNER JOIN subjects_master sm ON r.subject_id = sm.id WHERE r.student_id = ? AND r.subject_id = ? AND r.ut_obtain IS NOT NULL ORDER BY r.semester_id DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('ii', $student_id, $subject_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                $marks = $row['ut_obtain'];
                $grade = trim((string)($row['ut_grade'] ?? ''));
                $sem = (int)($row['semester_id'] ?? 0);
                $grade_text = $grade !== '' ? " (Grade $grade)" : '';
                return "{$student['full_name']} ({$student['symbol_no']}) ko {$row['subject_name']} UT result: $marks$grade_text, Semester $sem.";
            }
        }

        return "{$student['full_name']} ({$student['symbol_no']}) ko {$subject['subject_name']} subject ko UT result vetiena.";
    }

    $stmt = $conn->prepare("SELECT sm.subject_name, r.ut_obtain, r.ut_grade, r.semester_id FROM results r INNER JOIN subjects_master sm ON r.subject_id = sm.id WHERE r.student_id = ? AND r.ut_obtain IS NOT NULL ORDER BY r.semester_id DESC, sm.id ASC LIMIT 6");
    if ($stmt) {
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $lines = [];
        while ($row = $result->fetch_assoc()) {
            $grade = trim((string)($row['ut_grade'] ?? ''));
            $grade_text = $grade !== '' ? " [$grade]" : '';
            $lines[] = "- {$row['subject_name']}: {$row['ut_obtain']}$grade_text (Sem {$row['semester_id']})";
        }
        $stmt->close();

        if (!empty($lines)) {
            return "{$student['full_name']} ({$student['symbol_no']}) ko recent UT results:\n" . implode("\n", $lines);
        }
    }

    return "{$student['full_name']} ({$student['symbol_no']}) ko UT result data vetiena.";
}

$session_student_id = (int)($_SESSION['student_id'] ?? 0);
$local_reply = localAssistantReply($conn, $user_message, $session_student_id);
if ($local_reply !== '') {
    echo json_encode(['reply' => nl2br($local_reply)]);
    exit;
}

if ($apiKey === '') {
    echo json_encode(['reply' => '⚠️ Gemini API key not configured.']);
    exit;
}

// ----------------- AI system prompt -----------------
$messages = [
     [
          'role' => 'system',
          'content' => "
You are an academic AI assistant for a university UT & Assessment Result Management System.

Rules:
- UT weight = 40%
- Assessment weight = 60%
- GPA scale = 0 to 4.0
- Pass GPA = 2.0

Response style:
- Be direct and solution-focused. Avoid repeating greetings.
- Provide 4-6 actionable bullet points first, then a short 2-3 line summary.
- Keep the full response under 120 words.
- If the query is too vague, ask ONE clarifying question instead of giving a partial answer.
- If the student mentions a subject, tailor advice to that subject (topics, practice types, common pitfalls).
- Use Nepali-English mix only; avoid Hindi.
- Do not give generic life advice unless asked.

Responsibilities:
1. If student asks GPA calculation:
    Final CGPA = (UT GPA × 0.4) + (Assessment GPA × 0.6)

2. If target CGPA impossible:
    Explain politely & show maximum achievable CGPA.

3. If student failed a subject or got low grade:
    Provide a realistic improvement plan: weak topic diagnosis, daily/weekly plan, practice strategy, exam strategy.

4. Use simple Nepali-English mix, friendly & supportive.
"
     ],
    ['role' => 'user', 'content' => $user_message]
];

try {
    $version_model_map = [
        'v1beta' => ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash'],
        'v1' => ['gemini-2.5-flash', 'gemini-2.0-flash']
    ];

    $callGemini = function (string $prompt_text) use ($apiKey, $version_model_map) {
        $payload = [
            'contents' => [[
                'parts' => [[
                    'text' => $prompt_text
                ]]
            ]],
            'generationConfig' => [
                'temperature' => 0.5,
                'maxOutputTokens' => 350
            ]
        ];

        $last_error = '';

        foreach ($version_model_map as $api_version => $models_for_version) {
            foreach ($models_for_version as $model_name) {
                $url = 'https://generativelanguage.googleapis.com/' . $api_version . '/models/' . $model_name . ':generateContent?key=' . urlencode($apiKey);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($response === false) {
                    $last_error = $curl_error !== '' ? $curl_error : 'cURL request failed.';
                    continue;
                }

                $decoded = json_decode((string)$response, true);
                $text = trim((string)($decoded['candidates'][0]['content']['parts'][0]['text'] ?? ''));
                if ($status >= 200 && $status < 300 && $text !== '') {
                    return ['text' => $text, 'error' => ''];
                }

                $api_error = trim((string)($decoded['error']['message'] ?? ''));
                if ($api_error !== '') {
                    $api_error_lower = mb_strtolower($api_error);
                    $is_model_mapping_error = str_contains($api_error_lower, 'is not found for api version')
                        || str_contains($api_error_lower, 'not supported for generatecontent');
                    if ($is_model_mapping_error) {
                        if ($last_error === '') {
                            $last_error = 'No compatible Gemini model found for this API key.';
                        }
                        continue;
                    }
                    $last_error = $api_error;
                } elseif ($status > 0) {
                    $last_error = 'Gemini API HTTP ' . $status . ' for model ' . $model_name . ' (' . $api_version . ').';
                }
            }
        }

        return ['text' => '', 'error' => $last_error];
    };

    $prompt_text = $messages[0]['content'] . "\n\nUser Question: " . $messages[1]['content'];
    $first_try = $callGemini($prompt_text);
    $bot_reply = $first_try['text'];
    $last_error = $first_try['error'];

    $ends_with_punct = (bool)preg_match('/[.!?।]$/u', trim($bot_reply));
    $word_count = preg_match_all('/\S+/u', trim($bot_reply));

    if ($bot_reply === '' || !$ends_with_punct || $word_count < 20) {
        $retry_prompt = "Answer with exactly 5 bullet tips and a 1-line summary. End with a complete sentence.\n\n" . $messages[1]['content'];
        $retry_try = $callGemini($retry_prompt);
        $bot_reply = $retry_try['text'];
        if (trim((string)$retry_try['error']) !== '') {
            $last_error = $retry_try['error'];
        }
    }

    if (trim($bot_reply) === '') {
        $normalized_error = mb_strtolower(trim((string)$last_error));
        if ($normalized_error !== '' && str_contains($normalized_error, 'quota exceeded')) {
            $bot_reply = '⚠️ AI service temporary quota exceeded. Please retry after a short time or contact admin to enable Gemini billing/quota.';
        } elseif ($normalized_error !== '' && (str_contains($normalized_error, 'api key') || str_contains($normalized_error, 'permission denied') || str_contains($normalized_error, 'unauthenticated'))) {
            $bot_reply = '⚠️ Gemini API key/config issue detected. Please contact admin to update API key permissions.';
        } else {
            $bot_reply = '❌ Error: Unable to get response from Gemini.';
            if (trim((string)$last_error) !== '') {
                $bot_reply .= ' ' . $last_error;
            }
        }
    }
} catch (Exception $e) {
    $bot_reply = '❌ Error: ' . $e->getMessage();
}

$bot_reply = trim((string)$bot_reply);

if (!preg_match('/[.!?।]$/u', $bot_reply)) {
    $bot_reply .= '.';
}

$user_lower = mb_strtolower($messages[1]['content'] ?? '');
$reply_lower = mb_strtolower($bot_reply);

$math_keywords = ['math', 'mathematics', 'गणित', 'engineering maths', 'engineering mathematics', 'calculus', 'algebra'];
$econ_keywords = ['economics', 'engineering economics', 'eco', 'interest factor', 'pw', 'fw', 'aw', 'irr', 'b/c'];

$asks_math = false;
foreach ($math_keywords as $kw) {
    if (str_contains($user_lower, $kw)) {
        $asks_math = true;
        break;
    }
}

$asks_econ = false;
foreach ($econ_keywords as $kw) {
    if (str_contains($user_lower, $kw)) {
        $asks_econ = true;
        break;
    }
}

$reply_has_math = false;
foreach ($math_keywords as $kw) {
    if (str_contains($reply_lower, $kw)) {
        $reply_has_math = true;
        break;
    }
}

$reply_has_econ = false;
foreach ($econ_keywords as $kw) {
    if (str_contains($reply_lower, $kw)) {
        $reply_has_econ = true;
        break;
    }
}

if (mb_strlen($bot_reply) < 40 || ($asks_math && !$reply_has_math) || ($asks_econ && !$reply_has_econ)) {
    if ($asks_math) {
        $bot_reply = "Engineering Maths formula memory tips:\n" .
            "- Make a one-page formula sheet and review daily.\n" .
            "- Solve 3 short problems per formula (same day).\n" .
            "- Group formulas by topic: calculus, matrices, DE, transforms.\n" .
            "- Write meaning of symbols beside each formula.\n" .
            "- Use 2-minute recall drills without notes.\n" .
            "Summary: Small daily practice + topic grouping builds fast recall.";
    } elseif ($asks_econ) {
        $bot_reply = "Engineering Economics formula tips:\n" .
            "- Make a one-page formula sheet (PW, FW, AW, IRR, B/C).\n" .
            "- Practice 3 short numericals daily using the same formula.\n" .
            "- Group formulas by use-case (cash flow, interest, depreciation).\n" .
            "- Write units/meaning next to each symbol.\n" .
            "- Do quick recall drills (2 minutes, no notes).\n" .
            "Summary: Small daily practice + formula grouping builds fast recall.";
    }
}

// Enforce a hard word limit to avoid truncated outputs.
$max_words = 120;
$plain_reply = trim(strip_tags($bot_reply));
$words = preg_split('/\s+/', $plain_reply, -1, PREG_SPLIT_NO_EMPTY);
if (is_array($words) && count($words) > $max_words) {
    $plain_reply = implode(' ', array_slice($words, 0, $max_words)) . '...';
    $bot_reply = $plain_reply;
}

echo json_encode(['reply' => nl2br($bot_reply)]);
exit;
