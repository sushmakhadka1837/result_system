<?php
session_start();
require 'db_config.php';

$dept_id = $_GET['dept_id'] ?? 0;

// Department details
$dept = $conn->query("SELECT * FROM departments WHERE id=$dept_id")->fetch_assoc();

// Fetch semesters of this department
$semesters = $conn->query("SELECT * FROM semesters WHERE department_id=$dept_id ORDER BY semester_order ASC");
?>
<!DOCTYPE html>
<html>
<head>
<title>Publish Department - <?php echo $dept['department_name']; ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-4">
<h3><?php echo $dept['department_name']; ?> Department</h3>
<p class="text-muted">Select semester to view student results and publish status</p>
<hr>

<table class="table table-bordered bg-white">
<thead class="table-dark">
<tr>
<th>Semester</th>
<th>Action</th>
<th>Status</th>
<th>Manage</th>
</tr>
</thead>
<tbody>
<?php 
if($semesters->num_rows > 0){
    while($sem = $semesters->fetch_assoc()){
        // Check publish status
        $check = $conn->query("SELECT published FROM results_publish_status WHERE department_id=$dept_id AND semester_id=".$sem['id']." LIMIT 1")->fetch_assoc();
        $published = $check['published'] ?? 0;
?>
<tr>
<td><?php echo $sem['semester_name']; ?></td>
<td>
<a href="view_results.php?dept_id=<?php echo $dept_id; ?>&sem_id=<?php echo $sem['id']; ?>" class="btn btn-info btn-sm">View Students Marks</a>
</td>
<td>
<?php if($published): ?>
<span class="badge bg-success">Published</span>
<?php else: ?>
<span class="badge bg-secondary">Not Published</span>
<?php endif; ?>
</td>
<td>
<?php if(!$published): ?>
<a href="publish_single.php?dept_id=<?php echo $dept_id; ?>&sem_id=<?php echo $sem['id']; ?>" class="btn btn-success btn-sm">Publish</a>
<?php else: ?>
<a href="unpublish_single.php?dept_id=<?php echo $dept_id; ?>&sem_id=<?php echo $sem['id']; ?>" class="btn btn-warning btn-sm">Unpublish</a>
<?php endif; ?>
</td>
</tr>
<?php } 
} else { ?>
<tr>
<td colspan="4" class="text-center text-danger">No semesters found for this department!</td>
</tr>
<?php } ?>
</tbody>
</table>

<a href="admin_publish_results.php" class="btn btn-secondary">⬅ Back</a>
</div>
</body>
</html>
