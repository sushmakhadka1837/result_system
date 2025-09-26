<?php
session_start();
require 'db_config.php';
require 'otp_mailer.php'; // यहाँ sendOTP function छ

// Fetch departments from DB
$departments = [];
$dept_result = $conn->query("SELECT id, department_name, total_semesters FROM departments ORDER BY department_name ASC");
if($dept_result){
    while($row = $dept_result->fetch_assoc()){
        $departments[] = $row;
    }
}

$error = '';
$success = '';

$max_semesters = 8; // default

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $department_id = trim($_POST['department']);
    $batch_year = trim($_POST['batch_year']);
    $semester = trim($_POST['semester']);
    $section = trim($_POST['section']) ?: NULL;
    $faculty = trim($_POST['faculty']);
    $symbol_no = trim($_POST['symbol_no']);

    // Get max semesters for selected department
    foreach($departments as $dept){
        if($dept['id'] == $department_id){
            $max_semesters = (int)$dept['total_semesters'];
            break;
        }
    }

    if(!empty($full_name) && !empty($email) && !empty($phone) && !empty($password) && !empty($department_id) && !empty($batch_year) && !empty($semester) && !empty($faculty) && !empty($symbol_no)) {

        if($semester < 1 || $semester > $max_semesters){
            $error = "Semester must be between 1 and $max_semesters for the selected department.";
        } else {
            // Check existing student
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

                $stmt = $conn->prepare("INSERT INTO students (full_name, email, phone, password, department, batch_year, semester, section, faculty, symbol_no, otp, otp_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiiisssss", $full_name, $email, $phone, $hashed_password, $department_id, $batch_year, $semester, $section, $faculty, $symbol_no, $otp, $otp_expiry);

                if($stmt->execute()){
                    // Store last inserted ID in session for OTP verification
                    $_SESSION['pending_user_id'] = $conn->insert_id;

                    // Send OTP email
                    if(sendOTP($email, $full_name, $otp, 'student')){
                        // Redirect to OTP verification page
                        header("Location: student_otp_verification.php");
                        exit;
                    } else {
                        $success = "Signup successful but failed to send OTP email. Please check your email address.";
                    }
                } else {
                    $error = "Error while registering. Try again!";
                }
            }
        }
    } else {
        $error = "Please fill all required fields.";
    }
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
.signup-card .links { margin-top: 15px; font-size: 14px; }
.signup-card .links a { color: #555; text-decoration: none; }
.signup-card .links a:hover { text-decoration: underline; }
.error { color: red; font-size: 14px; margin-bottom: 10px; }
.success { color: green; font-size: 14px; margin-bottom: 10px; }
</style>
<script>
function updateSemesterOptions() {
    var deptSelect = document.querySelector('select[name="department"]');
    var semSelect = document.querySelector('select[name="semester"]');
    var programs = <?php echo json_encode($departments); ?>;
    var maxSem = 8;

    for (var i = 0; i < programs.length; i++) {
        if (programs[i].id == deptSelect.value) {
            maxSem = parseInt(programs[i].total_semesters);
            break;
        }
    }

    semSelect.innerHTML = '<option value="">Select Semester</option>';
    for (var s = 1; s <= maxSem; s++) {
        semSelect.innerHTML += '<option value="' + s + '">' + s + '</option>';
    }
}
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
        
        <select name="department" onchange="updateSemesterOptions()" required>
            <option value="">Select Department</option>
            <?php foreach($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="batch_year" placeholder="Batch Year (e.g. 2024)" required>

        <select name="semester" required>
            <option value="">Select Semester</option>
        </select>

        <input type="text" name="faculty" placeholder="Faculty" required>

        <input type="text" name="symbol_no" placeholder="Symbol Number" required>

        <select name="section">
            <option value="">None</option>
            <option value="A">A</option>
            <option value="B">B</option>
        </select>

        <button type="submit">Signup</button>
    </form>

    <div class="links">
        <p>Already have an account? <a href="student_login.php">Login here</a></p>
        <p><a href="index.php">Back to Home</a></p>
    </div>
</div>
<script>
updateSemesterOptions(); // Initialize semester options
</script>
</body>
</html>
