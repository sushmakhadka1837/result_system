<?php
session_start();
require 'db_config.php';
require 'otp_mailer.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT * FROM teachers WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();

    if (!$teacher) {
        $error = "Email not registered.";
    } elseif ($teacher['is_verified'] == 0) {
        $error = "Email not verified.";
    } else {
        $otp = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));
        $stmt = $conn->prepare("UPDATE teachers SET otp=?, otp_expiry=? WHERE id=?");
        $stmt->bind_param("ssi", $otp, $expiry, $teacher['id']);
        $stmt->execute();

        if (sendOTP($email, $teacher['full_name'], $otp, 'teacher')) {
            $success = "OTP sent to your email. Use it to reset password.";
            $_SESSION['reset_teacher_id'] = $teacher['id'];
        } else {
            $error = "Failed to send OTP. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Teacher Forgot Password</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6fb;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}
.card {
    background: #fff;
    padding: 30px 35px;
    border-radius: 10px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    width: 360px;
    text-align: center;
}
.card h2 {
    margin-bottom: 20px;
    color: #111;
}
.card input[type="email"] {
    width: 100%;
    padding: 10px 12px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}
.card button {
    width: 100%;
    padding: 10px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
}
.card button:hover {
    background: #1e40af;
}
.error { color: red; margin-bottom: 12px; font-size: 14px; }
.success { color: green; margin-bottom: 12px; font-size: 14px; }
.links {
    margin-top: 15px;
    font-size: 13px;
}
.links a { text-decoration: none; color: #2563eb; }
.links a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="card">
    <h2>Forgot Password</h2>
    <?php if($error) echo '<div class="error">'.$error.'</div>'; ?>
    <?php if($success) echo '<div class="success">'.$success.'</div>'; ?>

    <form method="POST" action="">
        <input type="email" name="email" placeholder="Enter your registered email" required>
        <button type="submit">Send OTP</button>
    </form>

    <div class="links">
        <p><a href="teacher_login.php">Back to Login</a></p>
    </div>
</div>
</body>
</html>
