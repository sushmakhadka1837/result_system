<?php
session_start();
require 'db_config.php';

if(isset($_SESSION['teacher_id'])){
    header("Location: teacher_dashboard.php");
    exit;
}

$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM teachers WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();

    if(!$teacher) {
        $error="Email not registered.";
    }
    elseif($teacher['is_verified']==0) {
        $_SESSION['pending_teacher_id'] = $teacher['id'];
        $error="Account not verified. <a href='teacher_otp_verify.php' class='text-info fw-bold'>Verify now</a>";
    }
    elseif(!password_verify($password,$teacher['password'])) {
        $error="Invalid password.";
    }
    else{
        $_SESSION['teacher_id']   = $teacher['id'];
        $_SESSION['teacher_name'] = $teacher['full_name'];
        $_SESSION['user_type']    = 'teacher';
        header("Location: teacher_dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Access | Secure Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a; /* Midnight Blue */
            --accent: #0ea5e9;  /* Sky Blue / Teal */
            --glass: rgba(255, 255, 255, 0.98);
        }

        body {
            background: #f8fafc;
            background-image: radial-gradient(circle at 0% 0%, #e0f2fe 0%, transparent 50%),
                              radial-gradient(circle at 100% 100%, #f0f9ff 0%, transparent 50%);
            font-family: 'Inter', system-ui, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-card {
            background: var(--glass);
            width: 100%;
            max-width: 420px;
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            margin: 0 auto 24px;
            font-size: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.3);
        }

        .title {
            color: var(--primary);
            font-weight: 800;
            text-align: center;
            margin-bottom: 8px;
            font-size: 1.75rem;
            letter-spacing: -0.025em;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 35px;
        }

        .form-label {
            font-weight: 600;
            color: #334155;
            font-size: 0.875rem;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            font-size: 1rem;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
            background: #fff;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            margin-top: 10px;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            background: #1e293b;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        .error-box {
            background: #fff1f2;
            color: #be123c;
            padding: 14px;
            border-radius: 12px;
            font-size: 0.875rem;
            text-align: center;
            margin-bottom: 24px;
            border: 1px solid #ffe4e6;
        }

        .links {
            text-align: center;
            margin-top: 30px;
            font-size: 0.9rem;
            color: #64748b;
        }

        .links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 700;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-icon">
        <i class="fas fa-shield-halved"></i>
    </div>
    
    <h3 class="title">Teacher Login Portal </h3>
    <p class="subtitle">Access your teacher dashboard securely</p>

    <?php if($error): ?>
        <div class="error-box">
            <i class="fas fa-circle-exclamation me-2"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-4">
            <label class="form-label">Official Email</label>
            <input type="email" name="email" class="form-control" placeholder="name@college.edu" required>
        </div>

        <div class="mb-2">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>

        <div class="text-end mb-4">
            <a href="teacher_forgot_password.php" class="text-muted small text-decoration-none">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-submit">
            Sign In <i class="fas fa-arrow-right small"></i>
        </button>
    </form>

    <div class="links">
        New to the portal? <a href="teacher_signup.php">Register Now</a>
        <div class="mt-4 pt-4 border-top">
            <a href="index.php" class="text-muted fw-normal small"><i class="fas fa-house me-1"></i> Return Home</a>
        </div>
    </div>
</div>

</body>
</html>