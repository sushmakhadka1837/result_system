<?php
session_start();
require 'db_config.php';
require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check pending_teacher_id
if(!isset($_SESSION['pending_teacher_id'])){
    header("Location: teacher_signup.php");
    exit;
}

$tid = $_SESSION['pending_teacher_id'];

// Generate new OTP
$otp = rand(100000, 999999);
$expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

// Update DB
$stmt = $conn->prepare("UPDATE teachers SET otp=?, otp_expiry=? WHERE id=?");
$stmt->bind_param("ssi", $otp, $expiry, $tid);
$stmt->execute();

// Fetch teacher email and name
$stmt2 = $conn->prepare("SELECT email, full_name FROM teachers WHERE id=?");
$stmt2->bind_param("i", $tid);
$stmt2->execute();
$teacher = $stmt2->get_result()->fetch_assoc();

if($teacher){
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yourgmail@gmail.com';
        $mail->Password = 'yourapppassword'; // Gmail app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('yourgmail@gmail.com', 'OTP Verification');
        $mail->addAddress($teacher['email'], $teacher['full_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "Namaste {$teacher['full_name']},<br><br>Your new OTP is <b>{$otp}</b>. It will expire in 10 minutes.";

        $mail->send();
        echo "OTP resent successfully. Please check your email.";
    } catch (Exception $e) {
        echo "OTP could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    echo "Teacher not found.";
}
?>
