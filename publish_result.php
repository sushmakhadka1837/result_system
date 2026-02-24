<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['teacher_id'])){
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// 1. Fetch Teacher Name
$teacher_res = $conn->query("SELECT full_name FROM teachers WHERE id=$teacher_id");
$teacher = $teacher_res->fetch_assoc();

// 2. Fetch Assigned Subjects with necessary IDs for matching
$query = "
    SELECT 
        ts.id AS teacher_subject_id,
        sm.id AS subject_master_id,
        sm.subject_name,
        sm.subject_code,
        sm.credit_hours,
        ts.batch_year,
        ts.semester_id,
        d.department_name,
        ts.department_id
    FROM teacher_subjects ts
    JOIN subjects_master sm ON ts.subject_map_id = sm.id
    JOIN departments d ON ts.department_id = d.id
    WHERE ts.teacher_id = ?
    ORDER BY ts.batch_year DESC, ts.semester_id ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Publish Result - <?= htmlspecialchars($teacher['full_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 30px; background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .main-card { background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: none; }
        .table thead { background: #0b3d91; color: white; }
        .btn-action { min-width: 140px; margin-bottom: 5px; }
    </style>
</head>
<body>
<?php include 'teacher_header.php'; ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Publish Result for: <span class="text-primary"><?= htmlspecialchars($teacher['full_name']) ?></span></h3>
        <a href="teacher_dashboard.php" class="btn btn-outline-dark btn-sm">Back to Dashboard</a>
    </div>

    <div class="main-card p-4">
        <?php if($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle border">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject (Code)</th>
                        <th>Batch</th>
                        <th>Semester</th>
                        <th>Department</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; while($row = $result->fetch_assoc()): 
                        // Marks exist check (Using Master Subject ID)
                        $m_id = $row['subject_master_id'];
                        $check = $conn->query("SELECT id FROM results WHERE subject_id = $m_id LIMIT 1");
                        $has_marks = $check->num_rows > 0;
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($row['subject_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($row['subject_code']) ?></small>
                        </td>
                        <td><span class="badge bg-secondary"><?= $row['batch_year'] ?></span></td>
                        <td>Sem <?= $row['semester_id'] ?></td>
                        <td><?= htmlspecialchars($row['department_name']) ?></td>
                        <td>
                            <?php 
                                // URL Parameters to match Students properly in ut_marks.php
                                $params = "subject_id=" . $row['subject_master_id'] . 
                                          "&ts_id=" . $row['teacher_subject_id'] . 
                                          "&batch=" . $row['batch_year'] . 
                                          "&sem=" . $row['semester_id'] .
                                          "&dept=" . $row['department_id'];
                            ?>

                            <a href="ut_marks.php?<?= $params ?>" class="btn <?= $has_marks ? 'btn-info' : 'btn-success' ?> btn-sm btn-action">
                                <?= $has_marks ? 'View/Edit UT' : 'Enter UT Marks' ?>
                            </a>

                            <a href="assessment.php?<?= $params ?>" class="btn btn-primary btn-sm btn-action">
                                Assessment
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-warning">No subjects have been assigned to you.</div>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>