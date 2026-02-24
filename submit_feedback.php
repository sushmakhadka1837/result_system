<?php
require 'db_config.php';
require 'mail_config.php'; // Updated to use mail_config.php

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['student_name']);
    $email = trim($_POST['student_email']);
    $feedback = trim($_POST['feedback']);

    if($name && $email && $feedback){
        // Validate email format
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            echo "<script>
                alert('Please enter a valid email address!');
                window.history.back();
            </script>";
            exit;
        }

        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        
        // Save to pending feedback table
        $stmt = $conn->prepare("INSERT INTO student_feedback_pending (student_name, student_email, feedback, verification_token, is_verified) VALUES (?,?,?,?,0)");
        $stmt->bind_param("ssss", $name, $email, $feedback, $verification_token);
        
        if($stmt->execute()){
            // Create verification link
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
            $verification_link = $base_url . dirname($_SERVER['PHP_SELF']) . "/verify_feedback.php?token=" . $verification_token;
            
            // Send verification email
            if(sendFeedbackVerification($email, $name, $verification_link)){
                echo "<script>
                    alert('✅ Thank you! Please check your email ($email) to verify your feedback.');
                    window.location='index.php';
                </script>";
            } else {
                echo "<script>
                    alert('Feedback saved but verification email failed to send. Please contact admin.');
                    window.location='index.php';
                </script>";
            }
        } else {
            echo "DB Error: " . $conn->error;
        }
        $stmt->close();
    } else {
        echo "<script>alert('Please fill all required fields'); window.history.back();</script>";
    }
}else{
    header("Location: index.php");
}
?>
