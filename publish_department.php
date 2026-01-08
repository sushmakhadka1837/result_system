<?php
session_start();
require 'db_config.php';

$dept_id = intval($_GET['dept_id'] ?? 0);
$dept = $conn->query("SELECT * FROM departments WHERE id=$dept_id")->fetch_assoc();
$semesters = $conn->query("SELECT * FROM semesters WHERE department_id=$dept_id ORDER BY semester_order ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Publish Department - <?php echo htmlspecialchars($dept['department_name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-4">
    <h3><?php echo htmlspecialchars($dept['department_name']); ?> Department</h3>
    <p class="text-muted">Semester wise UT & Assessment Result Publishing</p>
    <hr>

    <table class="table table-bordered bg-white">
        <thead class="table-dark">
            <tr>
                <th>Semester</th>
                <th>UT Result</th>
                <th>Assessment Result</th>
            </tr>
        </thead>
        <tbody>

<?php while($sem = $semesters->fetch_assoc()): ?>

<?php
// UT publish status
$ut = $conn->query("
    SELECT published FROM results_publish_status 
    WHERE department_id=$dept_id 
    AND semester_id={$sem['id']} 
    AND result_type='ut'
")->fetch_assoc();

// Assessment publish status
$ass = $conn->query("
    SELECT published FROM results_publish_status 
    WHERE department_id=$dept_id 
    AND semester_id={$sem['id']} 
    AND result_type='assessment'
")->fetch_assoc();

$ut_published = $ut['published'] ?? 0;
$ass_published = $ass['published'] ?? 0;
?>

<tr>
    <td><?php echo htmlspecialchars($sem['semester_name']); ?></td>

    <!-- UT -->
    <td>
        <a href="view_ut_results.php?dept_id=<?php echo $dept_id; ?>&sem_id=<?php echo $sem['id']; ?>" class="btn btn-info btn-sm">View</a>

        <?php if(!$ut_published): ?>
            <a href="publish_single.php?dept_id=<?php echo $dept_id; ?>&sem_id=<?php echo $sem['id']; ?>&type=ut"
               class="btn btn-success btn-sm ms-1">Publish</a>
        <?php else: ?>
            <span class="badge bg-success ms-1">Published</span>
        <?php endif; ?>
    </td>

    <!-- Assessment -->
    <td>
        <a href="view_assessment_result.php?dept_id=<?php echo $dept_id; ?>&sem_id=<?php echo $sem['id']; ?>" class="btn btn-primary btn-sm">View</a>

        <?php if(!$ass_published): ?>
            <a href="publish_single.php?dept_id=<?php echo $dept_id; ?>&sem_id=<?php echo $sem['id']; ?>&type=assessment"
               class="btn btn-success btn-sm ms-1">Publish</a>
        <?php else: ?>
            <span class="badge bg-success ms-1">Published</span>
        <?php endif; ?>
    </td>
</tr>

<?php endwhile; ?>

        </tbody>
    </table>

    <a href="admin_publish_results.php" class="btn btn-secondary">⬅ Back</a>
</div>
</body>
</html>
