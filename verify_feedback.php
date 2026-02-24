<?php
require 'db_config.php';
require 'mail_config.php';

// Get verification token from URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if(empty($token)){
    die("<script>alert('Invalid verification link!'); window.location='index.php';</script>");
}

// Check if token exists and is not yet verified
$stmt = $conn->prepare("SELECT id, student_name, student_email, feedback, is_verified, created_at FROM student_feedback_pending WHERE verification_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    die("<script>alert('Invalid or expired verification link!'); window.location='index.php';</script>");
}

$pending = $result->fetch_assoc();

// Check if already verified
if($pending['is_verified'] == 1){
    die("<script>alert('This feedback has already been verified!'); window.location='index.php';</script>");
}

// Check if link has expired (24 hours)
$created_time = strtotime($pending['created_at']);
$current_time = time();
$time_diff = ($current_time - $created_time) / 3600; // in hours

if($time_diff > 24){
    die("<script>alert('Verification link has expired! Please submit your feedback again.'); window.location='index.php';</script>");
}

// Email is verified - move to main feedback table
$insert_stmt = $conn->prepare("INSERT INTO student_feedback (student_name, student_email, feedback, verified_at) VALUES (?,?,?, NOW())");
$insert_stmt->bind_param("sss", $pending['student_name'], $pending['student_email'], $pending['feedback']);

if($insert_stmt->execute()){
    // Mark as verified in pending table
    $update_stmt = $conn->prepare("UPDATE student_feedback_pending SET is_verified = 1, verified_at = NOW() WHERE verification_token = ?");
    $update_stmt->bind_param("s", $token);
    $update_stmt->execute();
    
    // Send thank you email
    sendFeedbackThankYou($pending['student_email'], $pending['student_name']);
    
    // Redirect to success page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Feedback Verified | Hamro Result</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Poppins', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .success-container {
                background: white;
                padding: 50px 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
                max-width: 500px;
                width: 100%;
                animation: slideUp 0.6s ease;
            }
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .success-icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 25px;
                animation: scaleIn 0.8s ease;
            }
            @keyframes scaleIn {
                from {
                    transform: scale(0);
                }
                to {
                    transform: scale(1);
                }
            }
            .success-icon i {
                font-size: 40px;
                color: white;
            }
            h1 {
                color: #1e293b;
                font-size: 28px;
                margin-bottom: 15px;
                font-weight: 700;
            }
            p {
                color: #64748b;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 25px;
            }
            .btn-home {
                display: inline-block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 14px 40px;
                border-radius: 30px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            }
            .btn-home:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            }
            .email-sent {
                background: #f0f9ff;
                padding: 15px;
                border-radius: 10px;
                margin: 20px 0;
                border-left: 4px solid #0ea5e9;
            }
            .email-sent p {
                color: #0c4a6e;
                font-size: 14px;
                margin: 0;
            }
        </style>
    </head>
    <body>
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1>Feedback Verified Successfully! ✅</h1>
            <p>Hello <strong><?= htmlspecialchars($pending['student_name']) ?></strong>,</p>
            <p>Thank you for verifying your feedback. Your valuable input has been received and will help us improve our system!</p>
            
            <div class="email-sent">
                <p><i class="fas fa-envelope"></i> A confirmation email has been sent to <strong><?= htmlspecialchars($pending['student_email']) ?></strong></p>
            </div>
            
            <a href="index.php" class="btn-home">
                <i class="fas fa-home"></i> Return to Home
            </a>
        </div>
    </body>
    </html>
    <?php
    
} else {
    echo "<script>alert('Error saving feedback. Please try again.'); window.location='index.php';</script>";
}

$stmt->close();
$insert_stmt->close();
$conn->close();
?>
