<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
use OpenAI\Client;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['OPENAI_API_KEY'] ?? null;
if (!$apiKey) {
    echo json_encode(['reply' => '❌ OpenAI API key not found']);
    exit;
}

$client = new Client($apiKey);

// Read user input
$input = json_decode(file_get_contents('php://input'), true);
$user_message = trim($input['message'] ?? '');
if ($user_message === '') {
    echo json_encode(['reply' => '❗ Please type your question.']);
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

Responsibilities:
1. If student asks GPA calculation:
   Final CGPA = (UT GPA × 0.4) + (Assessment GPA × 0.6)

2. If target CGPA impossible:
   Explain politely & show maximum achievable CGPA.

3. If student failed a subject:
   - Explain possible reasons
   - Encourage and suggest study plan
   - Offer resources if available

4. Use simple Nepali-English mix, friendly & supportive.
"
    ],
    ['role' => 'user', 'content' => $user_message]
];

try {
    $response = $client->chat()->create([
        'model' => 'gpt-3.5-turbo',
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 700,
    ]);

    $bot_reply = $response->choices[0]->message->content ?? 'No response';
} catch (Exception $e) {
    $bot_reply = '❌ Error: ' . $e->getMessage();
}

echo json_encode(['reply' => nl2br($bot_reply)]);
exit;
