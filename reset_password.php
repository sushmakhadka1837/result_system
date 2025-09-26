<?php
session_start();
require 'db_config.php';

$error = '';
$success = '';
$showForm = false;

$token = $_GET['token'] ?? '';

if (!$token) {
    $error = "Invalid reset link.";
} else {
    // Verify token
    $stmt = $conn->prepare("SELECT * FROM admins WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        $showForm = true;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (empty($password) || empty($confirm)) {
                $error = "Please fill all fields.";
            } elseif ($password !== $confirm) {
                $error = "Passwords do not match.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("UPDATE admins SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                $stmt2->bind_param("si", $hash, $admin['id']);
                $stmt2->execute();

                $success = "Password reset successfully. <a href='admin_login.php'>Login now</a>";
                $showForm = false;
            }
        }
    } else {
        $error = "Reset link expired or invalid.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Password</title>
<style>
body { background:#121417; font-family:Arial, sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; color:#dddde1; }
.wrapper { background:#1e222b; padding:40px; border-radius:14px; width:100%; max-width:400px; box-shadow:0 12px 40px rgba(0,0,0,0.8); }
h2 { text-align:center; margin-bottom:24px; color:#a5a7bc; }
input[type="password"] { width:100%; padding:14px 16px; margin-bottom:18px; border-radius:10px; border:1.5px solid #444b70; background:#1b1f2a; color:#dddde1; }
button { width:100%; padding:16px; border-radius:12px; background:#7687ff; border:none; font-weight:700; color:#1b1f2a; cursor:pointer; }
button:hover { background:#5e6bee; color:#f0f0f5; }
.error { background:#f5625d; padding:10px; border-radius:8px; margin-bottom:20px; text-align:center; }
.success { background:#5df562; padding:10px; border-radius:8px; margin-bottom:20px; text-align:center; }
</style>
</head>
<body>
<div class="wrapper">
    <h2>Reset Password</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($showForm): ?>
    <form method="post" action="">
        <input type="password" name="password" placeholder="New password" required>
        <input type="password" name="confirm_password" placeholder="Confirm password" required>
        <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
