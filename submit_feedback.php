<?php
require 'db_config.php';
require 'vendor/autoload.php'; // Composer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['student_name']);
    $email = trim($_POST['student_email']);
    $feedback = trim($_POST['feedback']);

    if($name && $feedback){
        // Save feedback in DB
        $stmt = $conn->prepare("INSERT INTO student_feedback (student_name, student_email, feedback) VALUES (?,?,?)");
        $stmt->bind_param("sss", $name, $email, $feedback);
        if($stmt->execute()){
            // Send Gmail notification
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'youradmin@gmail.com'; // Admin Gmail
                $mail->Password = 'your_app_password'; // Gmail App Password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('youradmin@gmail.com', 'Result Hub Admin');
                $mail->addAddress('youradmin@gmail.com'); // Admin Gmail

                $mail->isHTML(true);
                $mail->Subject = 'New Student Feedback Submitted';
                $mail->Body = "
                    <h3>New Feedback Received</h3>
                    <p><strong>Name:</strong> {$name}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Feedback:</strong><br>{$feedback}</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }

            echo "<script>
                alert('Thank you for your feedback!');
                window.location='index.php';
            </script>";

        } else {
            echo "DB Error: " . $conn->error;
        }
    } else {
        echo "<script>alert('Please fill all required fields'); window.history.back();</script>";
    }
}else{
    header("Location: index.php");
}
