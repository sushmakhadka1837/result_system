<?php
session_start();
require 'db_config.php';

$teacher_id = $_SESSION['teacher_id'] ?? 0;
if(!$teacher_id){
    exit("Teacher not logged in.");
}

$assign_id = $_GET['assign_id'] ?? 0;
if(!$assign_id){
    exit("Invalid subject.");
}

// Validate that teacher owns this assigned subject
$stmt = $conn->prepare("SELECT * FROM teacher_subjects WHERE id=? AND teacher_id=?");
$stmt->bind_param("ii", $assign_id, $teacher_id);
$stmt->execute();
$ts = $stmt->get_result()->fetch_assoc();

if(!$ts){
    exit("You do not have permission to view this subject.");
}

// Get subject name
$stmt2 = $conn->prepare("SELECT subject_name FROM subjects_master WHERE id=?");
$stmt2->bind_param("i", $ts['subject_map_id']);
$stmt2->execute();
$subject_name = $stmt2->get_result()->fetch_assoc()['subject_name'];

// Fetch students in same department + batch + semester
$stmt3 = $conn->prepare("SELECT id, full_name, email FROM students WHERE department_id=? AND batch_year=? AND semester=?");
$stmt3->bind_param("iii", $ts['department_id'], $ts['batch_year'], $ts['semester_id']);
$stmt3->execute();
$students = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Students List</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
<h3>Students for Subject: <?= htmlspecialchars($subject_name) ?></h3>

<?php if(!empty($students)): ?>
<table class="table table-bordered table-hover mt-3">
<thead class="table-dark">
<tr>
<th>#</th>
<th>Full Name</th>
<th>Email</th>
</tr>
</thead>
<tbody>
<?php $i=1; foreach($students as $st): ?>
<tr>
<td><?= $i ?></td>
<td><?= htmlspecialchars($st['full_name']) ?></td>
<td><?= htmlspecialchars($st['email']) ?></td>
</tr>
<?php $i++; endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="alert alert-warning mt-3">No students found for this subject.</div>
<?php endif; ?>

<a href="teacher_subjects.php" class="btn btn-secondary mt-3">Back to Subjects</a>
</div>
</body>
</html>
