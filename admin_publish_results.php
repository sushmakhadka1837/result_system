<?php
session_start();
require 'db_config.php';

// Admin check
if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

// Result type to check
$result_type = $_GET['type'] ?? 'ut'; // default UT, or ?type=assessment

// Fetch all departments
$departments = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");

// Check if "Publish All Departments" clicked
if(isset($_POST['publish_all'])){
    $dept_res = $conn->query("SELECT * FROM departments");
    while($d = $dept_res->fetch_assoc()){
        $sem_res = $conn->query("SELECT id FROM semesters WHERE department_id=".$d['id']);
        while($s = $sem_res->fetch_assoc()){
            $conn->query("
                INSERT INTO results_publish_status (department_id, semester_id, result_type, published, published_at)
                VALUES (".$d['id'].", ".$s['id'].", '$result_type', 1, NOW())
                ON DUPLICATE KEY UPDATE published=1, published_at=NOW()
            ");
        }
    }
    $success = "✅ All departments and semesters published successfully for ".strtoupper($result_type)."!";
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
<h3>📢 Publish Student Results (<?= strtoupper($result_type) ?>)</h3>
<hr>

<?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

<form method="post">
<button type="submit" name="publish_all" class="btn btn-danger mb-3">
    🚀 Publish All Departments
</button>
</form>

<table class="table table-bordered bg-white">
<thead>
<tr>
<th>Department</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php while($dept = $departments->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($dept['department_name']) ?></td>

<?php
$dept_id = $dept['id'];

// Total semesters in this department
$total_sem = $conn->query("SELECT COUNT(*) as total FROM semesters WHERE department_id=$dept_id")->fetch_assoc()['total'];

// Published semesters for this result type
$pub_sem = $conn->query("
    SELECT COUNT(*) as pub FROM results_publish_status
    WHERE department_id=$dept_id AND result_type='$result_type' AND published=1
")->fetch_assoc()['pub'];

// Determine status badge
if($pub_sem == 0){
    $status_badge = "<span class='badge bg-warning'>Pending</span>";
} elseif($pub_sem < $total_sem){
    $status_badge = "<span class='badge bg-info'>Partially Published</span>";
} else {
    $status_badge = "<span class='badge bg-success'>Published</span>";
}

echo "<td>$status_badge</td>";
?>

<td>
<a href="publish_department.php?dept_id=<?= $dept['id'] ?>&type=<?= $result_type ?>" class="btn btn-primary btn-sm">
    View Semesters
</a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</body>
</html>
