<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['reset_user_id'])){
    header("Location: forget_password.php");
    exit;
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);

    if($password !== $confirm){
        $error = "Passwords do not match.";
    } else {
        $student_id = $_SESSION['reset_user_id'];
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE students SET password=?, otp=NULL, otp_expiry=NULL WHERE id=?");
        $stmt->bind_param("si", $hashed, $student_id);
        if($stmt->execute()){
            unset($_SESSION['reset_user_id']);
            $success = "Password reset successfully! <a href='student_login.php'>Login now</a>.";
        } else {
            $error = "Failed to reset password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>
</head>
<body>
<h2>Reset Password</h2>
<?php if($error) echo "<div style='color:red;'>$error</div>"; ?>
<?php if($success) echo "<div style='color:green;'>$success</div>"; ?>
<form method="POST">
<input type="password" name="password" placeholder="New Password" required>
<input type="password" name="confirm" placeholder="Confirm Password" required>
<button type="submit">Reset Password</button>
</form>
</body>
</html>
