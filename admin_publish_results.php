<?php
session_start();
require 'db_config.php';

// Admin check
if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

// Fetch all departments
$departments = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");

// Check if "Publish All Departments" clicked
if(isset($_POST['publish_all'])){
    // Loop through departments and semesters
    $dept_res = $conn->query("SELECT * FROM departments");
    while($d = $dept_res->fetch_assoc()){
        $sem_res = $conn->query("SELECT semester_id FROM semesters WHERE department_id=".$d['id']);
        while($s = $sem_res->fetch_assoc()){
            $conn->query("INSERT INTO results_publish_status (department_id, semester_id, published, published_at) VALUES (".$d['id'].", ".$s['semester_id'].", 1, NOW()) ON DUPLICATE KEY UPDATE published=1, published_at=NOW()");
        }
    }
    $success = "✅ All departments and semesters published successfully!";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Publish Student Results - Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-4">
<h3>📢 Publish Student Results</h3>
<hr>

<form method="post">
<button type="submit" name="publish_all" class="btn btn-danger mb-3">🚀 Publish All Departments</button>
</form>

<table class="table table-bordered bg-white">
<thead>
<tr>
<th>Department</th>
<th>Action</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php while($dept = $departments->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($dept['department_name']) ?></td>
<td>
<a href="publish_department.php?dept_id=<?= $dept['id'] ?>" class="btn btn-primary btn-sm">View Semesters</a>
</td>
<td>
<?php 
$check = $conn->query("SELECT published FROM results_publish_status WHERE department_id=".$dept['id']." AND published=1");
echo ($check->num_rows>0) ? "<span class='badge bg-success'>Published</span>" : "<span class='badge bg-warning'>Pending</span>";
?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</body>
</html>
