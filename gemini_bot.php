<?php
function askGemini($userMessage, $studentDataJSON) {
    $apiKey = "YOUR_SECRET_API_KEY"; // Replace with your real key
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

    // System Instruction: Defining the bot's job
    $systemInstruction = "You are a Result Assistant. You help students view UT and Semester results for different departments. Use the following data to answer questions: " . $studentDataJSON;

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $systemInstruction . "\n\nUser Question: " . $userMessage]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['candidates'][0]['content']['parts'][0]['text'] ?? "Error: Could not get response.";
}

// EXAMPLE USAGE:
// In your real app, you would fetch this JSON from your MySQL database
$sampleData = '{
    "student_name": "John Doe",
    "dept": "Computer Science",
    "semester": 4,
    "results": {
        "UT_1": {"Math": 85, "OS": 78},
        "Final_Sem": {"Math": 88, "OS": 82}
    }
}';

echo askGemini("What was my Math score in UT 1?", $sampleData);
?>