<?php
session_start();
require 'db_config.php';

// Only admin can access
if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

$dept_id = $_GET['dept_id'] ?? 0;
$sem_id  = $_GET['sem_id'] ?? 0;

if(!$dept_id || !$sem_id){
    die("Department or Semester not specified!");
}

// Fetch department details
$dept = $conn->query("SELECT * FROM departments WHERE id=$dept_id")->fetch_assoc();

// Fetch all students in this department and semester
$students = [];
$stmt = $conn->prepare("SELECT id, full_name, symbol_no FROM students WHERE department_id=? AND semester=? ORDER BY full_name ASC");
$stmt->bind_param("ii", $dept_id, $sem_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()){
    $students[] = $row;
}

// Fetch all teacher_subjects for this dept & semester (subjects assigned)
$subjects = [];
$sub_q = $conn->prepare("
    SELECT ts.id AS teacher_subject_id, sm.subject_name, ts.marks_type 
    FROM teacher_subjects ts
    JOIN subjects_department_semester sds ON ts.subject_map_id = sds.id
    JOIN subjects_master sm ON sds.subject_id = sm.id
    WHERE ts.department_id=? AND ts.semester_id=?
    ORDER BY sm.subject_name ASC
");
$sub_q->bind_param("ii", $dept_id, $sem_id);
$sub_q->execute();
$res = $sub_q->get_result();
while($row = $res->fetch_assoc()){
    $subjects[] = $row;
}

// Fetch results for all students
$results = [];
if(!empty($students)){
    $student_ids = array_column($students, 'id');
    $placeholders = implode(",", $student_ids);

    $res_q = $conn->query("
        SELECT r.*, r.subject_id AS teacher_subject_id, ts.marks_type, s.subject_name
        FROM results r
        JOIN teacher_subjects ts ON ts.id = r.subject_id
        JOIN subjects_master s ON s.id = ts.subject_map_id
        WHERE r.student_id IN ($placeholders)
    ");
    while($row = $res_q->fetch_assoc()){
        $results[$row['student_id']][$row['teacher_subject_id']] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($dept['department_name']) ?> - Semester <?= $sem_id ?> Results</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{padding-top:70px;background:#f4f6f9;}
.table th, .table td{vertical-align:middle;}
</style>
</head>
<body>
<div class="container mt-4">
<h3><?= htmlspecialchars($dept['department_name']) ?> - Semester <?= $sem_id ?> Results</h3>
<?php if(!empty($subjects)): ?>
<table class="table table-bordered table-striped bg-white">
<thead class="table-dark">
<tr>
<th>Student</th>
<th>Symbol No</th>
<?php foreach($subjects as $sub): ?>
<th><?= htmlspecialchars($sub['subject_name']) ?></th>
<?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php foreach($students as $stu): ?>
<tr>
<td><?= htmlspecialchars($stu['full_name']) ?></td>
<td><?= htmlspecialchars($stu['symbol_no']) ?></td>
<?php foreach($subjects as $sub): 
    $res = $results[$stu['id']][$sub['teacher_subject_id']] ?? null;
?>
<td>
<?php if($res): ?>
    <?php if($sub['marks_type']=='Unit Test'): ?>
        <?= $res['ut_marks'] ?>
    <?php else: ?>
        <?= $res['assignment'] ?> / <?= $res['practical'] ?> / <?= $res['other'] ?> / <?= $res['attendance_marks'] ?> / <?= $res['internal_total'] ?> / <?= $res['external_marks'] ?> / <?= $res['final_total'] ?>
    <?php endif; ?>
<?php else: ?>
No Data
<?php endif; ?>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p class="text-danger">No subjects found for this department & semester.</p>
<?php endif; ?>
<a href="admin_dashboard.php" class="btn btn-secondary">⬅ Back</a>
</div>
</body>
</html>
