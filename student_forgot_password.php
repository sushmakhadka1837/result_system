<?php
session_start();
require 'db_config.php';
require 'otp_mailer.php';

$error = '';
$success = '';
$stage = isset($_SESSION['reset_user_id']) ? 'reset' : 'request';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? 'send';

    if($action === 'send'){
        $email = trim($_POST['email'] ?? '');
        $symbol = trim($_POST['symbol_no'] ?? '');

        if($email === '' || $symbol === ''){
            $error = "Please enter your email and symbol number.";
        } else {
            $stmt = $conn->prepare("SELECT id, full_name, is_verified FROM students WHERE email=? AND symbol_no=?");
            $stmt->bind_param("ss", $email, $symbol);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();

            if(!$student){
                $error = "No matching student record found.";
            } elseif((int)$student['is_verified'] === 0){
                $error = "Email not verified. Please verify your account first.";
            } else {
                $otp = rand(100000, 999999);
                $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

                $update = $conn->prepare("UPDATE students SET otp=?, otp_expiry=? WHERE id=?");
                $update->bind_param("ssi", $otp, $expiry, $student['id']);

                if($update->execute() && sendOTP($email, $student['full_name'], $otp)){
                    $_SESSION['reset_user_id'] = $student['id'];
                    $stage = 'reset';
                    $success = "We sent an OTP to your email. Enter it below with your new password.";
                } else {
                    $error = "Failed to send OTP. Please try again.";
                }
            }
        }
    }

    if($action === 'reset'){
        if(!isset($_SESSION['reset_user_id'])){
            $error = "Please start the reset process again.";
            $stage = 'request';
        } else {
            $otpInput = trim($_POST['otp'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirm = trim($_POST['confirm'] ?? '');

            if($otpInput === '' || $password === '' || $confirm === ''){
                $error = "All fields are required.";
                $stage = 'reset';
            } elseif($password !== $confirm){
                $error = "Passwords do not match.";
                $stage = 'reset';
            } else {
                $studentId = (int)$_SESSION['reset_user_id'];
                $check = $conn->prepare("SELECT otp, otp_expiry FROM students WHERE id=?");
                $check->bind_param("i", $studentId);
                $check->execute();
                $data = $check->get_result()->fetch_assoc();

                if(!$data || !$data['otp']){
                    $error = "Please request a new OTP.";
                    $stage = 'request';
                    unset($_SESSION['reset_user_id']);
                } elseif($data['otp'] !== $otpInput){
                    $error = "Invalid OTP.";
                    $stage = 'reset';
                } elseif(strtotime($data['otp_expiry']) < time()){
                    $error = "OTP expired. Request a new one.";
                    $stage = 'request';
                    unset($_SESSION['reset_user_id']);
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $updatePwd = $conn->prepare("UPDATE students SET password=?, otp=NULL, otp_expiry=NULL WHERE id=?");
                    $updatePwd->bind_param("si", $hashed, $studentId);

                    if($updatePwd->execute()){
                        unset($_SESSION['reset_user_id']);
                        $success = "Password reset successfully. You can now log in.";
                        $stage = 'done';
                    } else {
                        $error = "Failed to reset password. Please try again.";
                        $stage = 'reset';
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --bg-color: #f4f7f6;
        }
        body {
            background-color: var(--bg-color);
            background-image: radial-gradient(circle at 20% 30%, rgba(30, 60, 114, 0.05) 0%, transparent 50%),
                              radial-gradient(circle at 80% 70%, rgba(42, 82, 152, 0.05) 0%, transparent 50%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .reset-card {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 460px;
            overflow: hidden;
        }
        .card-header-brand {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 32px 24px;
            text-align: center;
            color: white;
        }
        .card-header-brand i {
            font-size: 2.8rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        .card-body { padding: 34px 32px; }
        .form-label { font-weight: 600; color: #444; font-size: 0.9rem; }
        .input-group-text { background-color: #f8f9fa; border-right: none; color: #6c757d; }
        .form-control { border-left: none; background-color: #f8f9fa; }
        .form-control:focus { background-color: #fff; box-shadow: none; border-color: #dee2e6; }
        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control { border-color: var(--secondary-color); background-color: #fff; }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            width: 100%;
            font-weight: 700;
            letter-spacing: 0.4px;
        }
        .btn-primary:hover { box-shadow: 0 5px 15px rgba(30, 60, 114, 0.25); }
        .links-container { margin-top: 18px; text-align: center; font-size: 0.9rem; }
        .links-container a { color: var(--secondary-color); text-decoration: none; font-weight: 600; }
        .links-container a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="reset-card">
    <div class="card-header-brand">
        <i class="fas fa-key"></i>
        <h3 class="fw-bold mb-1">Reset Password</h3>
        <p class="small mb-0 opacity-75">We will email you an OTP to reset</p>
    </div>

    <div class="card-body">
        <?php if($error): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if($success && $stage !== 'done'): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <div><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <?php if($stage === 'request'): ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="send">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="name@college.com" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Symbol Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                        <input type="text" name="symbol_no" class="form-control" placeholder="Symbol No." required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Send OTP</button>
            </form>
        <?php elseif($stage === 'reset'): ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="reset">
                <div class="mb-3">
                    <label class="form-label">Enter OTP</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                        <input type="text" name="otp" class="form-control" placeholder="6-digit code" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="confirm" class="form-control" placeholder="Repeat password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
        <?php else: ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <div><?= htmlspecialchars($success) ?></div>
            </div>
            <a class="btn btn-primary" href="student_login.php">Back to Login</a>
        <?php endif; ?>

        <div class="links-container">
            <p class="text-muted">Remembered your password? <a href="student_login.php">Go to Login</a></p>
            <a href="index.php" class="text-muted small"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
        </div>
    </div>
</div>

</body>
</html>
