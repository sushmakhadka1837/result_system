<?php
session_start();
require 'db_config.php';

// Check if teacher is logged in
if(!isset($_SESSION['teacher_id'])){
    header("Location: teacher_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher details
$stmt = $conn->prepare("SELECT id, full_name, email, contact, employee_id FROM teachers WHERE id=?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Fetch assigned subjects for this teacher
$stmt2 = $conn->prepare("SELECT ts.id AS assign_id, s.subject_name, d.department_name, ts.section, ts.semester
                         FROM teacher_subjects ts
                         JOIN subjects s ON ts.subject_id = s.id
                         JOIN departments d ON ts.department_id = d.id
                         WHERE ts.teacher_id = ?");
$stmt2->bind_param("i", $teacher_id);
$stmt2->execute();
$assigned_subjects = $stmt2->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Teacher Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f4f4f9;}
.card-header{background:#2563eb; color:#fff;}
.table th, .table td{vertical-align: middle;}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
<div class="container">
<a class="navbar-brand" href="#">PEC Result Hub</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navbarNav">
<ul class="navbar-nav ms-auto">
<li class="nav-item"><a class="nav-link" href="teacher_dashboard.php">Dashboard</a></li>
<li class="nav-item"><a class="nav-link" href="edit_teacher_profile.php">Profile</a></li>
<li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
</ul>
</div>
</div>
</nav>

<!-- Dashboard Content -->
<div class="container my-5">
<div class="card shadow">
<div class="card-header">
<h4>Welcome, <?php echo htmlspecialchars($teacher['full_name']); ?> 👋</h4>
</div>
<div class="card-body">

<h5>Assigned Subjects</h5>
<?php if($assigned_subjects->num_rows > 0): ?>
<table class="table table-bordered table-striped">
<thead>
<tr>
<th>Subject</th>
<th>Department</th>
<th>Section</th>
<th>Semester</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php while($row = $assigned_subjects->fetch_assoc()): ?>
<tr>
<td><?php echo htmlspecialchars($row['subject_name']); ?></td>
<td><?php echo htmlspecialchars($row['department_name']); ?></td>
<td><?php echo htmlspecialchars($row['section']); ?></td>
<td><?php echo htmlspecialchars($row['semester']); ?></td>
<td><a href="enter_marks.php?assign_id=<?php echo $row['assign_id']; ?>" class="btn btn-success btn-sm">Enter Marks</a></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<p>No subjects assigned yet.</p>
<?php endif; ?>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
 