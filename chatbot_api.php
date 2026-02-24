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

// Get student context for AI
$session_student_id = (int)($_SESSION['student_id'] ?? 0);
$student_context = "";

if ($session_student_id > 0) {
    $stmt = $conn->prepare("SELECT s.full_name, s.symbol_no, s.current_semester, d.department_name 
                            FROM students s 
                            LEFT JOIN departments d ON s.department_id = d.id 
                            WHERE s.id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $session_student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($student) {
            $student_context = "\nCurrent Student Context:\n";
            $student_context .= "Name: {$student['full_name']}\n";
            $student_context .= "Symbol No: {$student['symbol_no']}\n";
            $student_context .= "Department: {$student['department_name']}\n";
            $student_context .= "Current Semester: {$student['current_semester']}\n";
            
            // Get recent results
            $results_stmt = $conn->prepare("SELECT sm.subject_name, r.ut_obtain, r.ut_grade, 
                                            r.total_obtained, r.letter_grade, r.semester_id 
                                            FROM results r 
                                            INNER JOIN subjects_master sm ON r.subject_id = sm.id 
                                            WHERE r.student_id = ? 
                                            ORDER BY r.semester_id DESC, sm.id ASC 
                                            LIMIT 10");
            if ($results_stmt) {
                $results_stmt->bind_param('i', $session_student_id);
                $results_stmt->execute();
                $results = $results_stmt->get_result();
                
                if ($results->num_rows > 0) {
                    $student_context .= "\nRecent Results:\n";
                    while ($row = $results->fetch_assoc()) {
                        $ut = $row['ut_obtain'] ? "{$row['ut_obtain']} ({$row['ut_grade']})" : "N/A";
                        $final = $row['total_obtained'] ? "{$row['total_obtained']} ({$row['letter_grade']})" : "N/A";
                        $student_context .= "- {$row['subject_name']} (Sem {$row['semester_id']}): UT=$ut, Final=$final\n";
                    }
                }
                $results_stmt->close();
            }
        }
    }
}

if ($apiKey === '') {
    echo json_encode(['reply' => '⚠️ Gemini API key not configured. Please add GEMINI_API_KEY to .env file.']);
    exit;
}

// ----------------- AI system prompt -----------------
$messages = [
     [
          'role' => 'system',
          'content' => "
You are a friendly AI Result Assistant for Pokhara Engineering College students.

Your responsibilities:
1. Greet warmly when students say hi/hello/namaste
2. Answer questions about UT (Unit Test) results & Assessment marks
3. Provide study tips, exam strategies, and academic advice
4. Calculate and explain GPA (UT weight=40%, Assessment=60%, GPA scale 0-4.0, Pass=2.0)
5. Give subject-specific advice based on student performance
{$student_context}

Response style:
- Be friendly, supportive, and conversational in Nepali-English mix
- For greetings: respond warmly and briefly introduce yourself (2-3 lines max)
- For academic questions: Give 4-6 actionable bullet points + short 2-3 line summary
- Keep total response under 120 words
- Use student's actual data when available (check context above)
- If query is vague, ask ONE clarifying question
- Avoid Hindi, use only Nepali-English mix

Examples:
User: 'hello' → 'Namaste! 👋 Ma timro Result Assistant ho. Timilai UT/Assessment results, GPA calculation, ra study tips lai help garna sakxu. Ke janna chahanxau?'
User: 'my ut marks' → Use actual data from context above
User: 'study tips for math' → Give specific math study strategies
"
     ],
    ['role' => 'user', 'content' => $user_message]
];

try {
    $version_model_map = [
        'v1beta' => ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash'],
        'v1' => ['gemini-2.5-flash', 'gemini-2.0-flash']
    ];

    $callGemini = function (string $prompt_text, string $system_instruction) use ($apiKey, $version_model_map) {
        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $system_instruction]
                ]
            ],
            'contents' => [[
                'parts' => [[
                    'text' => $prompt_text
                ]]
            ]],
            'generationConfig' => [
                'temperature' => 0.7,
                'topP' => 0.9,
                'maxOutputTokens' => 400
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

    $system_instruction = $messages[0]['content'];
    $user_query = $messages[1]['content'];
    
    $first_try = $callGemini($user_query, $system_instruction);
    $bot_reply = $first_try['text'];
    $last_error = $first_try['error'];

    // Check if response quality is good
    $ends_with_punct = (bool)preg_match('/[.!?।]$/u', trim($bot_reply));
    $word_count = preg_match_all('/\S+/u', trim($bot_reply));

    // Retry with clearer prompt if needed
    if ($bot_reply === '' || !$ends_with_punct || $word_count < 15) {
        $retry_prompt = $user_query . "\n\n(Please give a complete, clear response with proper punctuation)";
        $retry_try = $callGemini($retry_prompt, $system_instruction);
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

// Enforce a hard word limit to avoid truncated outputs
$max_words = 150;
$plain_reply = trim(strip_tags($bot_reply));
$words = preg_split('/\s+/', $plain_reply, -1, PREG_SPLIT_NO_EMPTY);
if (is_array($words) && count($words) > $max_words) {
    $plain_reply = implode(' ', array_slice($words, 0, $max_words)) . '...';
    $bot_reply = $plain_reply;
}

echo json_encode(['reply' => nl2br($bot_reply)]);
exit;
