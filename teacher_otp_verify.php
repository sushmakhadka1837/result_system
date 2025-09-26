<?php
session_start();
require 'db_config.php';
require 'otp_mailer.php'; // sendOTP function

$error = '';

// If pending_teacher_id session missing, redirect
if(!isset($_SESSION['pending_teacher_id'])){
    header("Location: teacher_signup.php");
    exit;
}

$tid = $_SESSION['pending_teacher_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $otp = trim($_POST['otp']);

    // Fetch teacher by ID
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE id=?");
    $stmt->bind_param("i",$tid);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();

    if(!$teacher) {
        $error = "Teacher not found.";
    } elseif($teacher['otp'] != $otp) {
        $error = "Invalid OTP.";
    } elseif(strtotime($teacher['otp_expiry']) < time()) {
        $error = "OTP expired.";
    } else {
        // Mark verified
        $stmt = $conn->prepare("UPDATE teachers SET is_verified=1, otp=NULL, otp_expiry=NULL WHERE id=?");
        $stmt->bind_param("i", $tid);
        $stmt->execute();

        // Remove pending session and set login session
        unset($_SESSION['pending_teacher_id']);
        $_SESSION['teacher_id'] = $teacher['id'];
        $_SESSION['teacher_name'] = $teacher['full_name'];

        header("Location: teacher_dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>OTP Verification</title>
<style>
body{font-family:Arial;background:#f4f4f4;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
.card{background:#fff;padding:25px;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.1);width:300px;}
input{width:100%;padding:10px;margin:8px 0;border:1px solid #ccc;border-radius:6px;}
button{width:100%;padding:10px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;}
button:hover{background:#1d4ed8;}
.error{color:red;font-size:14px;}
</style>
</head>
<body>
<div class="card">
<h2>OTP Verification</h2>
<?php if($error) echo "<div class='error'>$error</div>"; ?>
<form method="POST">
<input type="text" name="otp" placeholder="Enter OTP" required>
<button type="submit">Verify OTP</button>
</form>
<p style="font-size:12px;margin-top:8px;">Didn't get OTP? <a href="resend_teacher_otp.php">Resend</a></p>
</div>
</body>
</html>
