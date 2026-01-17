<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendOTP($to_email, $to_name, $otp, $user_type = 'user') {
    $mail = new PHPMailer(true);

    // Subjects
    $subjects = [
        'admin'       => 'Admin OTP Verification',
        'teacher'     => 'Teacher OTP Verification',
        'student'     => 'Student OTP Verification',
        'forget_pass' => 'OTP for Password Reset',
        'user'        => 'OTP Verification'
    ];

    // Bodies
    $bodies = [
        'admin'       => "Hello $to_name,<br><br>Your Admin OTP is: <b>$otp</b>.<br>It expires in 10 minutes.<br><br>Thank you!",
        'teacher'     => "Hello $to_name,<br><br>Your Teacher OTP is: <b>$otp</b>.<br>It expires in 10 minutes.<br><br>Thank you!",
        'student'     => "Hello $to_name,<br><br>Your Student OTP is: <b>$otp</b>.<br>It expires in 10 minutes.<br><br>Thank you!",
        'forget_pass' => "Hello $to_name,<br><br>Your OTP for password reset is: <b>$otp</b>.<br>It expires in 10 minutes.<br><br>Thank you!",
        'user'        => "Hello $to_name,<br><br>Your OTP is: <b>$otp</b>.<br>It expires in 10 minutes.<br><br>Thank you!"
    ];

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aahanakhadka6@gmail.com';
        $mail->Password   = 'upxa vjdc wdck ccjw';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('aahanakhadka6@gmail.com', 'Hamro Result');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = $subjects[$user_type] ?? $subjects['user'];
        $mail->Body    = $bodies[$user_type] ?? $bodies['user'];

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}
?>
