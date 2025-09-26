<?php
session_start();
require 'db_config.php';

// Already logged in? Redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            if ($admin['verified'] == 0) {
                $error = "Please verify your email before logging in.";
            } elseif (password_verify($password, $admin['password_hash'])) {
                // Login success
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_email'] = $admin['email'];

                // Update last login
                $stmt2 = $conn->prepare("UPDATE admins SET last_login_at = NOW() WHERE id = ?");
                $stmt2->bind_param("i", $admin['id']);
                $stmt2->execute();

                // Remember me
                if ($remember_me) {
                    $token = bin2hex(random_bytes(16));
                    $updateStmt = $conn->prepare("UPDATE admins SET remember_token = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $token, $admin['id']);
                    $updateStmt->execute();
                    setcookie("admin_remember", $token, time() + (86400 * 30), "/", "", true, true);
                }

                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = "Invalid password.";
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
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Login</title>
<style>
body { background:#121417; font-family:Arial, sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; color:#dddde1; }
.login-wrapper { background:#1e222b; padding:40px; border-radius:14px; width:100%; max-width:400px; box-shadow:0 12px 40px rgba(0,0,0,0.8); }
h2 { text-align:center; margin-bottom:24px; color:#a5a7bc; }
input[type="email"], input[type="password"] { width:100%; padding:14px 16px; margin-bottom:18px; border-radius:10px; border:1.5px solid #444b70; background:#1b1f2a; color:#dddde1; }
button { width:100%; padding:16px; border-radius:12px; background:#7687ff; border:none; font-weight:700; color:#1b1f2a; cursor:pointer; }
button:hover { background:#5e6bee; color:#f0f0f5; }
.error-msg { background:#f5625d; padding:10px; border-radius:8px; margin-bottom:20px; text-align:center; }
.login-links { text-align:center; margin-top:15px; }
.login-links a { color:#a5a7bc; text-decoration:none; margin:0 5px; }
.login-links a:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="login-wrapper">
  <h2>Admin Login</h2>

  <?php if ($error): ?>
    <div class="error-msg"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <input type="email" name="email" placeholder="Email" required />
    <input type="password" name="password" placeholder="Password" required />
    <label style="display:block; margin-bottom:18px;">
      <input type="checkbox" name="remember_me" /> Remember Me
    </label>
    <button type="submit">Login</button>
  </form>

  <div class="login-links">
    <a href="admin_signup.php">Signup</a> |
    <a href="admin_forget_password.php">Forgot Password?</a>
</div>
</div>
</body>
</html>
