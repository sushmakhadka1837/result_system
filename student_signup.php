<?php
session_start();
require 'db_config.php';
require 'otp_mailer.php'; // sendOTP function

// Fetch all departments
$departments = [];
$dept_result = $conn->query("SELECT id, department_name, total_semesters FROM departments ORDER BY department_name ASC");
if($dept_result){
    while($row = $dept_result->fetch_assoc()){
        $departments[] = $row;
    }
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $department_id = trim($_POST['department']);
    $batch_year = trim($_POST['batch_year']);
    $semester_id = trim($_POST['semester']); // now stores semester_id
    $section = trim($_POST['section']) ?: NULL;
    $faculty = trim($_POST['faculty']);
    $symbol_no = trim($_POST['symbol_no']);
    $dob = trim($_POST['dob']);

    // Get department name
    $department_name = '';
    foreach($departments as $dept){
        if($dept['id'] == $department_id){
            $department_name = $dept['department_name'];
            break;
        }
    }

    if(!empty($full_name) && !empty($email) && !empty($phone) && !empty($password) && !empty($department_id) && !empty($batch_year) && !empty($semester_id) && !empty($faculty) && !empty($symbol_no) && !empty($dob)) {

        // Check if student exists
        $stmt = $conn->prepare("SELECT * FROM students WHERE email=? OR symbol_no=? OR phone=?");
        $stmt->bind_param("sss", $email, $symbol_no, $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            $error = "Email, Symbol Number, or Phone already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $otp = rand(100000, 999999);
            $otp_expiry = date("Y-m-d H:i:s", strtotime('+10 minutes'));

            // Insert student
            $stmt = $conn->prepare("
                INSERT INTO students (full_name, email, phone, password, department, department_id, batch_year, semester, section, faculty, symbol_no, dob, otp, otp_expiry) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssssiisssssss", 
                $full_name, 
                $email, 
                $phone, 
                $hashed_password, 
                $department_name, 
                $department_id, 
                $batch_year, 
                $semester_id, 
                $section, 
                $faculty, 
                $symbol_no, 
                $dob, 
                $otp, 
                $otp_expiry
            );

            if($stmt->execute()){
                $_SESSION['pending_user_id'] = $conn->insert_id;
                if(sendOTP($email, $full_name, $otp, 'student')){
                    header("Location: student_otp_verification.php");
                    exit;
                } else {
                    $success = "Signup successful but failed to send OTP email. Please check your email address.";
                }
            } else {
                $error = "Error while registering. Try again!";
            }
        }
    } else {
        $error = "Please fill all required fields.";
    }
}

// Fetch semesters via AJAX
if(isset($_GET['get_semesters']) && isset($_GET['department_id'])){
    $dept_id = (int)$_GET['department_id'];
    $semesters = [];
    $res = $conn->query("SELECT id, semester_name FROM semesters WHERE department_id=$dept_id ORDER BY semester_order ASC");
    if($res){
        while($row = $res->fetch_assoc()){
            $semesters[] = $row;
        }
    }
    header('Content-Type: application/json');
    echo json_encode($semesters);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Signup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Arial, sans-serif; background-color: #f9f9f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
.signup-card { background-color: #fff; padding: 30px 35px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 400px; text-align: center; }
.signup-card h2 { margin-bottom: 20px; color: #333; }
.signup-card input, .signup-card select { width: 100%; padding: 10px 12px; margin-bottom: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
.signup-card button { width: 100%; padding: 10px; margin-top: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; }
.signup-card button:hover { background-color: #45a049; }
.error { color: red; font-size: 14px; margin-bottom: 10px; }
.success { color: green; font-size: 14px; margin-bottom: 10px; }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updateSemesterOptions() {
    var deptId = $('select[name="department"]').val();
    var semSelect = $('select[name="semester"]');
    semSelect.html('<option value="">Loading...</option>');
    if(deptId){
        $.get('<?= $_SERVER['PHP_SELF'] ?>', {get_semesters:1, department_id:deptId}, function(data){
            var options = '<option value="">Select Semester</option>';
            data.forEach(function(sem){
                options += '<option value="'+sem.id+'">'+sem.semester_name+'</option>';
            });
            semSelect.html(options);
        }, 'json');
    } else {
        semSelect.html('<option value="">Select Semester</option>');
    }
}

$(document).ready(function(){
    $('select[name="department"]').change(updateSemesterOptions);
});
</script>
</head>
<body>
<div class="signup-card">
    <h2>Student Signup</h2>

    <?php
        if($error) echo '<div class="error">'.$error.'</div>';
        if($success) echo '<div class="success">'.$success.'</div>';
    ?>

    <form method="POST" action="">
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <select name="department" required>
            <option value="">Select Department</option>
            <?php foreach($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="semester" required>
            <option value="">Select Semester</option>
        </select>

        <input type="text" name="batch_year" placeholder="Batch Year (e.g. 2024)" required>
        <input type="text" name="faculty" placeholder="Faculty" required>
        <input type="text" name="symbol_no" placeholder="Symbol Number" required>
        <input type="date" name="dob" placeholder="Date of Birth" required>

        <select name="section">
            <option value="">None</option>
            <option value="A">A</option>
            <option value="B">B</option>
        </select>

        <button type="submit">Signup</button>
    </form>
</div>
</body>
</html>
