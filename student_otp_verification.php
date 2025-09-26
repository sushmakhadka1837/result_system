<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['pending_user_id'])) {
    header("Location: signup.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_entered = trim($_POST['otp']);
    $student_id = $_SESSION['pending_user_id'];

    // Get student OTP info
    $stmt = $conn->prepare("SELECT otp, otp_expiry FROM students WHERE id=?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if ($student) {
        if ($otp_entered === $student['otp'] && strtotime($student['otp_expiry']) > time()) {
            // Update student as verified
            $update = $conn->prepare("UPDATE students SET otp=NULL, otp_expiry=NULL, is_verified=1 WHERE id=?");
            $update->bind_param("i", $student_id);
            if ($update->execute()) {
                unset($_SESSION['pending_user_id']); // clear pending user
                $success = "OTP verified successfully! You can now <a href='student_login.php'>Login</a>.";
            } else {
                $error = "Failed to update verification. Try again.";
            }
        } else {
            $error = "Invalid or expired OTP!";
        }
    } else {
        $error = "No student found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Arial, sans-serif; background-color: #f9f9f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
.otp-card { background-color: #fff; padding: 30px 35px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 400px; text-align: center; }
.otp-card h2 { margin-bottom: 20px; color: #333; }
.otp-card input { width: 100%; padding: 10px 12px; margin-bottom: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; text-align: center; }
.otp-card button { width: 100%; padding: 10px; margin-top: 10px; background-color: #007BFF; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; }
.otp-card button:hover { background-color: #0056b3; }
.error { color: red; font-size: 14px; margin-bottom: 10px; }
.success { color: green; font-size: 14px; margin-bottom: 10px; }
</style>
</head>
<body>
<div class="otp-card">
    <h2>OTP Verification</h2>
    <?php
        if($error) echo '<div class="error">'.$error.'</div>';
        if($success) echo '<div class="success">'.$success.'</div>';
    ?>
    <?php if(!$success): ?>
    <form method="POST">
        <input type="text" name="otp" placeholder="Enter OTP" maxlength="6" required>
        <button type="submit">Verify</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
