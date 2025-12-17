<?php
session_start();
require 'db_config.php';

if(isset($_SESSION['student_id'])){
    header("Location: student_dashboard.php");
    exit;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email']);
    $symbol_no = trim($_POST['symbol_no']);
    $password = trim($_POST['password']);

    if(!empty($email) && !empty($symbol_no) && !empty($password)){
        // Fetch student record
        $stmt = $conn->prepare("SELECT * FROM students WHERE email=? AND symbol_no=?");
        $stmt->bind_param("ss", $email, $symbol_no);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if($student && password_verify($password, $student['password'])){
            // OTP check
            if($student['is_verified'] == 0){
                $_SESSION['pending_user_id'] = $student['id'];
                header("Location: student_otp_verification.php");
                exit;
            }

            // Set session
            $_SESSION['student_id']   = $student['id'];
            $_SESSION['student_name'] = $student['full_name'];
            $_SESSION['user_type']    = 'student';

            // Redirect to dashboard
            header("Location: student_dashboard.php");
            exit;

        } else {
            $error = "Invalid Email, Symbol Number, or Password!";
        }

    } else {
        $error = "Please fill all fields.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background-color: #fff; padding: 30px 35px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 320px; }
        .login-box h2 { margin-bottom: 20px; color: #333; text-align: center; }
        .login-box input[type="email"], .login-box input[type="text"], .login-box input[type="password"] { width: 100%; padding: 10px 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .login-box button { width: 100%; padding: 10px; margin-top: 15px; background-color: #4CAF50; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; }
        .login-box button:hover { background-color: #45a049; }
        .login-box .links { margin-top: 15px; text-align: center; font-size: 14px; }
        .login-box .links a { text-decoration: none; color: #555; }
        .login-box .links a:hover { text-decoration: underline; }
        .error { color: red; font-size: 14px; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Student Login</h2>
        <?php if($error) echo '<div class="error">'.$error.'</div>'; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="symbol_no" placeholder="Symbol Number" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <div class="links">
            <p><a href="student_forgot_password.php">Forgot Password?</a></p>
            <p>New student? <a href="student_signup.php">Signup here</a></p>
            <p><a href="index.php">Back to Home</a></p>
        </div>
    </div>
</body>
</html>