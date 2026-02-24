<?php
session_start();
require 'db_config.php';

// Redirect if already logged in
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

            // ------------------- Activity Tracking -------------------
            $student_id = $student['id'];
            $conn->query("INSERT INTO student_activity (student_id, activity_type) VALUES ($student_id, 'login')");
            // ----------------------------------------------------------

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | Portal</title>
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
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-card {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .card-header-brand {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .card-header-brand i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .card-body {
            padding: 40px 35px;
        }

        .form-label {
            font-weight: 600;
            color: #444;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
            color: #6c757d;
            border-radius: 10px 0 0 10px;
        }

        .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
            padding: 12px;
            font-size: 0.95rem;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            background-color: #fff;
            box-shadow: none;
            border-color: #dee2e6;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: var(--secondary-color);
            background-color: #fff;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 14px;
            width: 100%;
            color: white;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 60, 114, 0.3);
            color: #fff;
        }

        .links-container {
            margin-top: 25px;
            text-align: center;
            font-size: 0.9rem;
        }

        .links-container a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .links-container a:hover {
            text-decoration: underline;
        }

        .alert-custom {
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            border: none;
            background-color: #fff5f5;
            color: #c53030;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="card-header-brand">
        <i class="fas fa-user-graduate"></i>
        <h3 class="fw-bold mb-1">Student Portal</h3>
        <p class="small mb-0 opacity-75">Sign in to access your dashboard</p>
    </div>

    <div class="card-body">
        <?php if($error): ?>
            <div class="alert alert-custom d-flex align-items-center p-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-1">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="name@college.com" required>
                </div>
            </div>

            <div class="mb-1">
                <label class="form-label">Symbol Number</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    <input type="text" name="symbol_no" class="form-control" placeholder="Symbol No." required>
                </div>
            </div>

            <div class="mb-1">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <div class="text-end mb-3">
                <a href="student_forgot_password.php" class="small text-muted text-decoration-none">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-login">
                LOG IN <i class="fas fa-sign-in-alt ms-2"></i>
            </button>
        </form>

        <div class="links-container">
            <p class="text-muted">New student? <a href="student_signup.php">Create Account</a></p>
            <hr class="my-4 opacity-25">
            <a href="index.php" class="text-muted small"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
        </div>
    </div>
</div>

</body>
</html>