<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id'])){
    header("Location: login.php");
    exit();
}

$type = $_GET['type'] ?? '';

if(!in_array($type, ['ut','assessment'])){
    die("Invalid Result Type");
}

$student_id = $_SESSION['student_id'];

// student info
$stu = $conn->query("
    SELECT department_id, semester_id 
    FROM students 
    WHERE id = $student_id
")->fetch_assoc();

$dept_id = $stu['department_id'];
$sem_id  = $stu['semester_id'];

/* ---------- CHECK PUBLISHED ---------- */
$check = $conn->query("
    SELECT published 
    FROM results_publish_status
    WHERE department_id=$dept_id
    AND semester_id=$sem_id
    AND result_type='$type'
    AND published=1
");

if($check->num_rows == 0){
    die("Result not published yet.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo strtoupper($type); ?> Result</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-4">
    <h4 class="mb-3">
        <?php echo strtoupper($type); ?> Result
    </h4>

    <?php if($type == 'ut'): ?>
        <?php include 'student_ut_result.php'; ?>
    <?php else: ?>
        <?php include 'student_assessment_result.php'; ?>
    <?php endif; ?>

    <a href="student_dashboard.php" class="btn btn-secondary mt-3">⬅ Back</a>
</div>

</body>
</html>
