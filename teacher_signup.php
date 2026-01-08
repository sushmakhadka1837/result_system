<?php
session_start();
require 'db_config.php';
require 'otp_mailer.php'; 

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $password = trim($_POST['password']);
    $employee_id = trim($_POST['employee_id']);

    if (!$full_name || !$email || !$password || !$employee_id) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM teachers WHERE email=? OR employee_id=?");
        $stmt->bind_param("ss", $email, $employee_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing) {
            $error = "Email or Employee ID already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $otp = rand(100000, 999999);
            $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

            $stmt = $conn->prepare("INSERT INTO teachers 
                (full_name,email,password,contact,employee_id,otp,otp_expiry) 
                VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssss", $full_name, $email, $hashed, $contact, $employee_id, $otp, $expiry);

            if ($stmt->execute()) {
                $_SESSION['pending_teacher_id'] = $conn->insert_id;
                sendOTP($email, $full_name, $otp, 'teacher');
                header("Location: teacher_otp_verify.php");
                exit;
            } else {
                $error = "Registration failed. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Registration | Academic Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --teacher-primary: #0f172a; /* Slate 900 */
            --teacher-accent: #2563eb;  /* Blue 600 */
            --bg-light: #f8fafc;
        }

        body {
            background: var(--bg-light);
            font-family: 'Inter', system-ui, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .signup-container {
            background: #ffffff;
            width: 100%;
            max-width: 480px;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .header-section {
            background: var(--teacher-primary);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .header-section i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #60a5fa;
        }

        .form-section {
            padding: 40px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 6px;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group-text {
            background-color: #f1f5f9;
            border-right: none;
            color: #94a3b8;
            border-radius: 12px 0 0 12px;
        }

        .form-control {
            background-color: #f1f5f9;
            border-left: none;
            border-radius: 0 12px 12px 0;
            padding: 12px;
            font-size: 0.95rem;
            border-color: #e2e8f0;
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: #cbd5e1;
            box-shadow: none;
        }

        .btn-teacher-signup {
            background: var(--teacher-accent);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            margin-top: 10px;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-teacher-signup:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 25px;
        }

        .footer-links {
            text-align: center;
            margin-top: 30px;
            color: #64748b;
            font-size: 0.9rem;
        }

        .footer-links a {
            color: var(--teacher-accent);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="signup-container">
    <div class="header-section">
        <i class="fas fa-chalkboard-teacher"></i>
        <h3 class="fw-bold mb-1">Teacher Signup</h3>
        <p class="small mb-0 opacity-75">Register to manage classes and results</p>
    </div>

    <div class="form-section">
        <?php if($error): ?>
            <div class="alert alert-error d-flex align-items-center p-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-1">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                    <input type="text" name="full_name" class="form-control" placeholder="Prof. John Doe" required>
                </div>
            </div>

            <div class="mb-1">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="john@university.edu" required>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Contact No</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" name="contact" class="form-control" placeholder="98XXXXXXXX">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Employee ID</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                        <input type="text" name="employee_id" class="form-control" placeholder="T-2024" required>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-teacher-signup">
                CREATE ACCOUNT <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>

        <div class="footer-links">
            Already have an account? <a href="teacher_login.php">Login here</a>
            <div class="mt-3">
                <a href="index.php" class="small text-muted"><i class="fas fa-chevron-left me-1"></i> Back to Homepage</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>