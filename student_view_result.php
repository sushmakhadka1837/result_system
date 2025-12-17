<?php
session_start();
require 'db_config.php';
require 'vendor/autoload.php'; // Composer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Fetch student details
$student = $conn->query("SELECT * FROM students WHERE id=$student_id")->fetch_assoc();
$dept_id = $student['department_id'] ?? 0;
$current_semester = $student['semester'] ?? 0;

// Check if semester is valid
if($current_semester == 0) {
    die("Student semester not set.");
}

// Fetch subjects for student's semester
$subjects = $conn->query("SELECT * FROM subjects_master WHERE department_id=$dept_id AND semester_id=$current_semester ORDER BY subject_name ASC");

// Fetch results
$results_arr = [];
$result_query = $conn->query("SELECT * FROM results WHERE student_id=$student_id");
while($row = $result_query->fetch_assoc()){
    $results_arr[$row['subject_id']] = $row;
}

// Check if result is published for this semester
$publish_check = $conn->query("SELECT published FROM results_publish_status WHERE department_id=$dept_id AND semester_id=$current_semester LIMIT 1")->fetch_assoc();
$is_published = $publish_check['published'] ?? 0;

// Send email notification if result is published
if($is_published && !isset($_SESSION['result_notified'])){
    try {
        $mail = new PHPMailer(true);
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com'; // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_email@example.com'; // Your SMTP username
        $mail->Password   = 'your_password'; // Your SMTP password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Recipient
        $mail->setFrom('your_email@example.com', 'Result System');
        $mail->addAddress($student['email'], $student['name'] ?? 'Student');

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Result Published for Semester ".$current_semester;
        $mail->Body    = "Dear ".htmlspecialchars($student['name'] ?? 'Student').",<br><br>Your result for Semester ".$current_semester." has been published.<br>Check it at <a href='http://yourdomain.com/student_view_result.php'>your result portal</a>.<br><br>Regards,<br>Admin";

        $mail->send();
        $_SESSION['result_notified'] = true; // Prevent multiple notifications
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Result</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Hamro Result</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
<h3>Result Sheet</h3>
<p><strong>Student Name:</strong> <?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></p>
<p><strong>Symbol No:</strong> <?php echo htmlspecialchars($student['symbol_no'] ?? 'N/A'); ?></p>
<p><strong>Semester:</strong> <?php echo $current_semester; ?></p>
<p><strong>Faculty:</strong> <?php echo htmlspecialchars($student['faculty'] ?? 'N/A'); ?></p>

<table class="table table-bordered">
<thead class="table-dark">
<tr>
    <th>S.No</th>
    <th>Code No.</th>
    <th>Course Title</th>
    <th>Credit</th>
    <th>Marks</th>
    <th>Attendance Days</th>
</tr>
</thead>
<tbody>
<?php
$i=1;
while($sub = $subjects->fetch_assoc()){
    $res = $results_arr[$sub['id']] ?? null;

    // Marks calculation
    $marks_display = 0;
    $attendance_display = '';

    if($res){
        if($res['marks_type'] == 'Unit Test'){
            $marks_display = $res['ut_marks'] ?? 0;
            $attendance_display = isset($res['attendance_days']) ? $res['attendance_days'].' days' : '';
        } else { // Assessment or other types
            $marks_display = ($res['assignment'] ?? 0) 
                           + ($res['internal_project'] ?? 0) 
                           + ($res['internal_presentation'] ?? 0) 
                           + ($res['internal_other'] ?? 0) 
                           + ($res['practical'] ?? 0) 
                           + ($res['theory'] ?? 0);
            $attendance_display = isset($res['attendance_days']) ? $res['attendance_days'].' days' : '';
        }
    }

?>
<tr>
    <td><?php echo $i++; ?></td>
    <td><?php echo htmlspecialchars($sub['subject_code']); ?></td>
    <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
    <td><?php echo $sub['credit_hours']; ?></td>
    <td><?php echo $marks_display; ?></td>
    <td><?php echo $attendance_display; ?></td>
</tr>
<?php } ?>
</tbody>

</table>
<a href="download_result.php" class="btn btn-primary mb-3">Download Result as PDF</a>

<?php if(!$is_published): ?>
<div class="alert alert-warning">Result not yet published for this semester.</div>
<?php endif; ?>

</div>
</body>
</html>
