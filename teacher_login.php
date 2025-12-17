<?php
session_start();
require 'db_config.php';
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
        $error="Email not verified.";
    }
    elseif(!password_verify($password,$teacher['password'])) {
        $error="Invalid password.";
    }
    else{
        // ✅ FIXED SESSION VALUES
        $_SESSION['teacher_id']   = $teacher['id'];
        $_SESSION['teacher_name'] = $teacher['full_name'];
        $_SESSION['user_type']    = 'teacher';

        header("Location: teacher_dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Teacher Login</title>
<style>
body{
    font-family:Arial; 
    background:#f4f4f4; 
    display:flex; 
    justify-content:center; 
    align-items:center; 
    height:100vh; 
    margin:0;
}
.card{
    background:#fff; 
    padding:25px; 
    border-radius:10px; 
    box-shadow:0 4px 15px rgba(0,0,0,0.1); 
    width:300px;
}
input{
    width:100%; 
    padding:10px; 
    margin:8px 0; 
    border:1px solid #ccc; 
    border-radius:6px;
}
button{
    width:100%; 
    padding:10px; 
    background:#2563eb; 
    color:#fff; 
    border:none; 
    border-radius:6px; 
    cursor:pointer;
}
button:hover{
    background:#1d4ed8;
}
.error{
    color:red; 
    font-size:14px;
}
</style>
</head>
<body>
<div class="card">
<h2>Teacher Login</h2>

<?php if($error) echo "<div class='error'>$error</div>"; ?>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>

<p style="font-size:12px; margin-top:8px;">
    Forgot password? <a href="teacher_forgot_password.php">Click here</a>
</p>
<p style="font-size:12px; margin-top:8px;">
    No account? <a href="teacher_signup.php">Signup</a>
</p>
<p style="font-size:12px; margin-top:8px;">
    <a href="index.php">Back to Home</a>
</p>

</div>
</body>
</html>
