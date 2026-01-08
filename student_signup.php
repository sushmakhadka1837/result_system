<?php
session_start();
// common.php vitra getCurrentSemester($batch_year) function huna parcha
require 'common.php'; 
require 'db_config.php';
require 'otp_mailer.php'; 

// 1. Fetch all departments
$departments = [];
$dept_result = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
if($dept_result){
    while($row = $dept_result->fetch_assoc()){
        $departments[] = $row;
    }
}

// 2. AJAX Fetch Semesters
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

$error = '';
$success = '';

// 3. Handle Registration
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $full_name     = trim($_POST['full_name']);
    $email         = trim($_POST['email']);
    $phone         = trim($_POST['phone']);
    $password      = trim($_POST['password']);
    $department_id = trim($_POST['department']);
    $semester_id   = trim($_POST['semester']);
    $batch_year    = trim($_POST['batch_year']);
    $symbol_no     = trim($_POST['symbol_no']);
    $dob           = trim($_POST['dob']);
    $faculty       = trim($_POST['faculty']);
    $section       = trim($_POST['section']) ?: NULL;

    if(empty($full_name) || empty($email) || empty($batch_year)){
        $error = "Please fill all required fields!";
    } else {
        // Check duplicate
        $stmt = $conn->prepare("SELECT id FROM students WHERE email=? OR symbol_no=? OR phone=?");
        $stmt->bind_param("sss", $email, $symbol_no, $phone);
        $stmt->execute();
        if($stmt->get_result()->num_rows > 0){
            $error = "Email, Symbol Number, or Phone already registered!";
        } else {
            // Get Dept & Sem names
            $dept_q = $conn->query("SELECT department_name FROM departments WHERE id=$department_id");
            $d_name = $dept_q->fetch_assoc()['department_name'];
            
            $sem_q = $conn->query("SELECT semester_name FROM semesters WHERE id=$semester_id");
            $s_name = $sem_q->fetch_assoc()['semester_name'];

            // logic from common.php
            $auto_current_semester = getCurrentSemester($batch_year);

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $otp = rand(100000, 999999);
            $otp_expiry = date("Y-m-d H:i:s", strtotime('+10 minutes'));

            // Insert including current_semester
            $ins = $conn->prepare("INSERT INTO students (full_name, email, phone, password, department, department_id, batch_year, semester, semester_id, section, faculty, symbol_no, dob, otp, otp_expiry, current_semester, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            
            // 16 parameters (s s s s s i i s i s s s s i s i)
            $ins->bind_param("sssssiississsssi", 
                $full_name, $email, $phone, $hashed_password, $d_name, $department_id, 
                $batch_year, $s_name, $semester_id, $section, $faculty, $symbol_no, 
                $dob, $otp, $otp_expiry, $auto_current_semester
            );

            if($ins->execute()){
                $_SESSION['pending_user_id'] = $conn->insert_id;
                if(sendOTP($email, $full_name, $otp, 'student')){
                    header("Location: student_otp_verification.php");
                    exit;
                } else {
                    $success = "Registered but OTP failed. Contact support.";
                }
            } else {
                $error = "Database Error: " . $conn->error;
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
    <title>Student Registration | Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 40px 0; }
        .signup-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 100%; max-width: 750px; overflow: hidden; border: none; }
        .card-header-gradient { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 30px; text-align: center; }
        .form-content { padding: 40px; }
        .form-label { font-weight: 600; font-size: 0.8rem; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control, .form-select { padding: 12px; border-radius: 8px; border: 1px solid #dee2e6; font-size: 0.95rem; }
        .btn-register { background: #2a5298; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; width: 100%; margin-top: 20px; transition: 0.3s; }
        .btn-register:hover { background: #1e3c72; transform: translateY(-2px); }
        .input-group-text { background-color: #f8f9fa; border-color: #dee2e6; color: #6c757d; }
    </style>
</head>
<body>

<div class="signup-card">
    <div class="card-header-gradient">
        <h3 class="mb-1">Create Student Account</h3>
        <p class="mb-0 opacity-75">Join the academic resource network</p>
    </div>

    <div class="form-content">
        <?php if($error) echo '<div class="alert alert-danger py-2 small"><i class="fa fa-circle-exclamation me-2"></i>'.$error.'</div>'; ?>
        
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-user-circle"></i></span>
                        <input type="text" name="full_name" class="form-control" placeholder="Ram Bahadur" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="name@college.com" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <hr class="my-3 opacity-25">

                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-select" required>
                        <option value="">Choose Department</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select" required>
                        <option value="">Select Department First</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Batch Year</label>
                    <input type="number" name="batch_year" class="form-control" placeholder="2023" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Symbol No</label>
                    <input type="text" name="symbol_no" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Faculty</label>
                    <input type="text" name="faculty" class="form-control" placeholder="e.g. Science & Tech" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Section (Optional)</label>
                    <select name="section" class="form-select">
                        <option value="">None</option>
                        <option value="A">Section A</option>
                        <option value="B">Section B</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-register">
                REGISTER NOW <i class="fa fa-paper-plane ms-2"></i>
            </button>
            
            <div class="text-center mt-4">
                <span class="text-muted small">Already registered?</span> 
                <a href="login.php" class="text-decoration-none fw-bold small ms-1" style="color: #2a5298;">Login Here</a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    $('select[name="department"]').change(function(){
        var deptId = $(this).val();
        var semSelect = $('select[name="semester"]');
        semSelect.html('<option value="">Fetching...</option>');
        
        if(deptId){
            $.get('<?= $_SERVER['PHP_SELF'] ?>', {get_semesters:1, department_id:deptId}, function(data){
                var options = '<option value="">Select Semester</option>';
                data.forEach(function(sem){
                    options += '<option value="'+sem.id+'">'+sem.semester_name+'</option>';
                });
                semSelect.html(options);
            }, 'json');
        } else {
            semSelect.html('<option value="">Select Department First</option>');
        }
    });
});
</script>
</body>
</html>