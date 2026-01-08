<?php
// Gemini API Configuration
$apiKey = "AIzaSyA1wMFfobpIyLmoADxZQS-0eH6AcztLDNw"; // Afno API key yaha rakha
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

function getGeminiFeedback($subject, $marks, $predicted, $apiUrl) {
    // AI ko lagi prompt ready parne
    $prompt = "As an academic AI advisor, provide a very short (max 20 words) encouraging feedback for a student who scored $marks/50 in Unit Test for $subject. Their predicted final score is $predicted/100. Give a specific tip to improve.";

    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    // AI ko response extract garne
    return $result['candidates'][0]['content']['parts'][0]['text'] ?? "Keep pushing! You're doing well.";
}

// Logic for fetching marks (as per your code)
$student_id = $_SESSION['student_id'];
$ut_total = 50;
$assessment_total = 100;

$results_q = $conn->prepare("SELECT r.subject_id, COALESCE(sm.subject_name, r.subject_code) as subject_name, r.ut_obtain FROM results r LEFT JOIN subjects_master sm ON r.subject_id = sm.id WHERE r.student_id = ?");
$results_q->bind_param("i", $student_id);
$results_q->execute();
$results_data = $results_q->get_result();
?>

<div class="ai-interactive-section">
    <div class="ai-card-wrapper shadow-sm">
        <div class="ai-card-header">
            <div class="d-flex align-items-center">
                <div class="ai-pulse-icon">
                    <i class="fas fa-sparkles"></i>
                </div>
                <div class="ms-3">
                    <h5 class="mb-0 fw-bold">Gemini AI Insight</h5>
                    <small class="text-muted">Real-time performance counseling by Google Gemini</small>
                </div>
            </div>
            <div class="ai-status-tag">Powered by Gemini 1.5</div>
        </div>

        <div class="table-responsive">
            <table class="table ai-styled-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Subject</th>
                        <th class="text-center">Predicted (100)</th>
                        <th class="pe-4">AI Counselor Feedback</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($res = $results_data->fetch_assoc()): 
                    $ut_marks = floatval($res['ut_obtain']);
                    $predicted = round(($ut_marks / $ut_total) * $assessment_total);
                    
                    // Call Gemini Function
                    $ai_feedback = getGeminiFeedback($res['subject_name'], $ut_marks, $predicted, $apiUrl);
                ?>
                    <tr class="ai-row">
                        <td class="ps-4">
                            <span class="sub-title d-block"><?= htmlspecialchars($res['subject_name']) ?></span>
                            <small class="text-muted">Current: <?= $ut_marks ?>/50</small>
                        </td>
                        <td class="text-center">
                            <div class="fw-bold fs-5 text-primary"><?= $predicted ?></div>
                            <div class="mini-progress mx-auto"><div class="bar" style="width:<?= $predicted ?>%"></div></div>
                        </td>
                        <td class="pe-4">
                            <div class="gemini-bubble">
                                <i class="fas fa-quote-left me-2 opacity-50"></i>
                                <span><?= htmlspecialchars($ai_feedback) ?></span>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Modern AI Styling */
.ai-card-wrapper:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
.ai-pulse-icon { background: linear-gradient(135deg, #6366f1, #a855f7); color: white; }
.gemini-bubble {
    background: #f8fbff;
    padding: 12px;
    border-radius: 12px;
    border: 1px solid #e0e7ff;
    font-size: 0.85rem;
    color: #374151;
    font-style: italic;
    position: relative;
}
.ai-row:hover .gemini-bubble { background: #fff; border-color: #6366f1; transform: scale(1.02); transition: 0.3s; }
.mini-progress { width: 60px; height: 4px; background: #eee; border-radius: 10px; overflow: hidden; }
.mini-progress .bar { height: 100%; background: #6366f1; border-radius: 10px; }
</style>