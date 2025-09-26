<?php
session_start();
require 'db_config.php';
require 'mail_config.php'; // PHPMailer function import

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email is already registered.";
        } else {
            // Insert new admin
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $verification_code = rand(100000, 999999);

            $insertStmt = $conn->prepare(
                "INSERT INTO admins (email, password_hash, verified, verification_code) VALUES (?, ?, 0, ?)"
            );
            $insertStmt->bind_param("sss", $email, $password_hash, $verification_code);

            if ($insertStmt->execute()) {
                // Send OTP email
                $name = explode('@', $email)[0]; // Temporary name for email
                if (sendOTP($email, $name, $verification_code)) {
                    $_SESSION['pending_admin_email'] = $email;
                    header('Location: verify_email.php');
                    exit;
                } else {
                    $error = "Failed to send verification email. Please try again.";
                }
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Signup</title>
<style>
body { background:#121417; color:#dddde1; font-family:Arial; display:flex; justify-content:center; align-items:center; height:100vh; }
.signup-wrapper { background:#1e222b; padding:40px; border-radius:14px; width:100%; max-width:450px; box-shadow:0 12px 40px rgba(0,0,0,0.8); }
h2 { text-align:center; margin-bottom:24px; color:#a5a7bc; }
label { display:block; margin-bottom:8px; font-weight:600; color:#a5a7bc; }
input[type="email"], input[type="password"] { width:100%; padding:14px 16px; margin-bottom:18px; border-radius:10px; border:1.5px solid #444b70; background:#1b1f2a; color:#dddde1; }
button { width:100%; padding:16px; border-radius:12px; background:#7687ff; border:none; font-weight:700; color:#1b1f2a; cursor:pointer; }
button:hover { background:#5e6bee; color:#f0f0f5; }
.error-msg { background:#f5625d; padding:10px; border-radius:8px; margin-bottom:20px; text-align:center; }
.success-msg { background:#4CAF50; padding:10px; border-radius:8px; margin-bottom:20px; text-align:center; }
.login-link { display:block; text-align:center; margin-top:18px; color:#7c7f9a; text-decoration:none; }
.login-link:hover { text-decoration:underline; color:#a3a6cc; }
</style>
</head>
<body>
<div class="signup-wrapper">
  <h2>Admin Signup</h2>

  <?php if ($error): ?>
    <div class="error-msg"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="success-msg"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <label for="email">Email</label>
    <input type="email" name="email" id="email" placeholder="admin@example.com" required />

    <label for="password">Password</label>
    <input type="password" name="password" id="password" placeholder="Enter password" required />

    <label for="confirm_password">Confirm Password</label>
    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required />

    <button type="submit">Register</button>
  </form>

  <a href="admin_login.php" class="login-link">Already have an account? Login here</a>
</div>
</body>
</html>
