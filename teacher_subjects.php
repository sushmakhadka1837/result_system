<?php
session_start();
require 'db_config.php';

$teacher_id = $_SESSION['teacher_id'] ?? 0;
if(!$teacher_id){
    echo "Teacher not logged in.";
    exit;
}

// Fetch teacher info
$teacher = $conn->query("SELECT full_name FROM teachers WHERE id=$teacher_id")->fetch_assoc();

// Fetch assigned subjects
$query = "SELECT ts.id AS assign_id, sm.subject_name, sm.subject_code, ts.semester_id, ts.batch_year, d.department_name
          FROM teacher_subjects ts
          JOIN subjects_master sm ON ts.subject_map_id = sm.id
          JOIN departments d ON ts.department_id = d.id
          WHERE ts.teacher_id = ?
          ORDER BY ts.batch_year ASC, ts.semester_id ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assigned Subjects</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3>Assigned Subjects for <?= $teacher['full_name'] ?></h3>

    <?php if($result && $result->num_rows > 0): ?>
        <table class="table table-bordered table-hover align-middle mt-3">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Subject Name</th>
                    <th>Code</th>
                    <th>Semester</th>
                    <th>Batch</th>
                    <th>Department</th>
                    <th>Students</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                while($row = $result->fetch_assoc()){
                    $batch = ($row['batch_year'] <= 2022 || $row['batch_year'] == NULL) ? "Old Batch" : "New Batch";
                    $code = !empty($row['subject_code']) ? $row['subject_code'] : "-";
                    echo "<tr>
                            <td>{$i}</td>
                            <td>{$row['subject_name']}</td>
                            <td>{$code}</td>
                            <td>{$row['semester_id']}</td>
                            <td>{$batch}</td>
                            <td>{$row['department_name']}</td>
                            <td>
                                <a href='subject_students.php?assign_id={$row['assign_id']}' class='btn btn-sm btn-primary'>Show Students</a>
                            </td>
                          </tr>";
                    $i++;
                }
                ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning mt-3">No subjects assigned yet.</div>
    <?php endif; ?>

    <a href="teacher_dashboard.php" class="btn btn-secondary mt-3">Back to Profile</a>
</div>
</body>
</html>
