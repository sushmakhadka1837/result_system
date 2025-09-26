<?php
session_start();
require 'db_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email.";
    } else {
        // Check if admin exists
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            // Generate reset token
            $token = bin2hex(random_bytes(16));
            $stmt2 = $conn->prepare("UPDATE admins SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $stmt2->bind_param("si", $token, $admin['id']);
            if ($stmt2->execute()) {
                // Show link on screen (testing)
                $resetLink = "http://localhost/result_system/reset_password.php?token=$token";
                $success = "Are you sure you want to reset password<br><a href='$resetLink'>reset password</a>";
            } else {
                $error = "Database error: " . $stmt2->error;
            }

        } else {
            $error = "No account found with this email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot Password</title>
<style>
body { background:#121417; font-family:Arial, sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; color:#dddde1; }
.wrapper { background:#1e222b; padding:40px; border-radius:14px; width:100%; max-width:400px; box-shadow:0 12px 40px rgba(0,0,0,0.8); }
h2 { text-align:center; margin-bottom:24px; color:#a5a7bc; }
input[type="email"] { width:100%; padding:14px 16px; margin-bottom:18px; border-radius:10px; border:1.5px solid #444b70; background:#1b1f2a; color:#dddde1; }
button { width:100%; padding:16px; border-radius:12px; background:#7687ff; border:none; font-weight:700; color:#1b1f2a; cursor:pointer; }
button:hover { background:#5e6bee; color:#f0f0f5; }
.error { background:#f5625d; padding:10px; border-radius:8px; margin-bottom:20px; text-align:center; }
.success {  padding:10px; border-radius:8px; margin-bottom:20px; text-align:center; }
</style>
</head>
<body>
<div class="wrapper">
    <h2>Forgot Password</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php else: ?>
    <form method="post" action="">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Reset Link</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
