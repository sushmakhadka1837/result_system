<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['pending_admin_email'])) {
    header('Location: admin_signup.php');
    exit;
}

$error = '';
$email = $_SESSION['pending_admin_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp'] ?? '');

    if (empty($entered_otp)) {
        $error = "Please enter the OTP.";
    } else {
        // Check OTP in database
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND verification_code = ? AND verified = 0");
        $stmt->bind_param("ss", $email, $entered_otp);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            // OTP correct, verify email
            $updateStmt = $conn->prepare("UPDATE admins SET verified = 1, verification_code = NULL WHERE email = ?");
            $updateStmt->bind_param("s", $email);
            $updateStmt->execute();

            unset($_SESSION['pending_admin_email']);

            // Redirect to login page automatically
            header("Location: admin_login.php");
            exit;
        } else {
            $error = "Invalid OTP or email already verified.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify Email</title>
<style>
body { background:#121417; color:#dddde1; font-family:Arial; display:flex; justify-content:center; align-items:center; height:100vh; }
.container { background:#1e222b; padding:40px; border-radius:14px; width:100%; max-width:400px; box-shadow:0 12px 40px rgba(0,0,0,0.8); }
h2 { text-align:center; margin-bottom:25px; color:#a5a7bc; }
input[type="text"] { width:100%; padding:14px 16px; margin-bottom:20px; border-radius:10px; border:1.5px solid #444b70; background:#1b1f2a; color:#dddde1; }
button { width:100%; padding:16px 0; border-radius:12px; background:#7687ff; border:none; color:#1b1f2a; font-weight:700; cursor:pointer; }
button:hover { background:#5e6bee; }
.error { background:#f5625d; padding:10px; border-radius:8px; margin-bottom:20px; text-align:center; }
</style>
</head>
<body>
<div class="container">
  <h2>Verify Your Email</h2>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <input type="text" name="otp" placeholder="Enter OTP" required />
    <button type="submit">Verify</button>
  </form>
</div>
</body>
</html>
