<?php
session_start();
require 'db_config.php';
require 'otp_mailer.php'; // sendOTP function

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
        // Check if email or employee_id already exists
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
                // Set session with inserted teacher ID
                $_SESSION['pending_teacher_id'] = $conn->insert_id;
                sendOTP($email, $full_name, $otp, 'teacher'); // send email
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
<html>
<head>
<title>Teacher Signup</title>
<style>
/* Basic styling */
body{font-family:Arial,sans-serif;background:#f4f4f9;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
.signup-card{background:#fff;padding:30px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,0.1);width:400px;text-align:center;}
.signup-card input{width:100%;padding:10px;margin-bottom:12px;border:1px solid #ccc;border-radius:6px;}
.signup-card button{width:100%;padding:10px;margin-top:10px;background:#2563eb;color:white;border:none;border-radius:6px;cursor:pointer;}
.signup-card button:hover{background:#1d4ed8;}
.error{color:red;font-size:14px;margin-bottom:10px;}
</style>
</head>
<body>
<div class="signup-card">
<h2>Teacher Signup</h2>
<?php if($error) echo "<div class='error'>$error</div>"; ?>
<form method="POST">
<input type="text" name="full_name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="text" name="contact" placeholder="Contact">
<input type="text" name="employee_id" placeholder="Employee ID" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Signup</button>
</form>
<p>Already have an account? <a href="teacher_login.php">Login here</a></p>
</div>
</body>
</html>
