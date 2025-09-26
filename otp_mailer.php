<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendOTP($to_email, $to_name, $otp) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aahanakhadka6@gmail.com'; // change to your email
        $mail->Password   = 'upxa vjdc wdck ccjw';    // change to your app password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('aahanakhadka6@gmail.com', 'PEC-RESULT');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Student OTP Verification';
        $mail->Body    = "Hello $to_name,<br><br>Your OTP is: <b>$otp</b>.<br>It expires in 10 minutes.<br><br>Thank you!";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}
?>
