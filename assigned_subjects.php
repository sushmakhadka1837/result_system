<?php
session_start();
require 'db_config.php';

$teacher_id = $_GET['teacher_id'] ?? 0;

// Fetch teacher info
$teacher = $conn->query("SELECT full_name FROM teachers WHERE id=$teacher_id")->fetch_assoc();

// Fetch all departments
$departments = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");

// Handle Add & Assign Subject
$message = '';
if(isset($_POST['add_assign'])){
    $subject_id = intval($_POST['subject_id']);
    $department_id = intval($_POST['department_id']);
    $semester = intval($_POST['semester']);
    $batch_year = intval($_POST['batch_year']);
    $section = !empty($_POST['section']) ? $_POST['section'] : NULL;
    $syllabus = ($batch_year <= 2022) ? 'Old' : 'New';

    // Check if already assigned
    $check_sql = "SELECT * FROM teacher_subjects 
                  WHERE teacher_id=$teacher_id 
                  AND subject_map_id=$subject_id 
                  AND department_id=$department_id 
                  AND semester_id=$semester 
                  AND batch_year=$batch_year";
    if($section===null){
        $check_sql .= " AND section IS NULL";
    } else {
        $check_sql .= " AND section='$section'";
    }
    $check = $conn->query($check_sql);
    if($check->num_rows == 0){
        $conn->query("INSERT INTO teacher_subjects
                     (teacher_id, subject_map_id, batch_year, department_id, semester_id, section, syllabus_type)
                     VALUES ($teacher_id, $subject_id, $batch_year, $department_id, $semester, ".($section===null?'NULL':"'$section'").", '$syllabus')");
        $message = "<div class='alert alert-success'>Subject assigned successfully!</div>";
    } else {
        $message = "<div class='alert alert-warning'>This subject is already assigned for the selected semester/batch/department.</div>";
    }
}

// Fetch assigned subjects with department
$assigned = $conn->query("
    SELECT ts.*, sm.subject_name, sm.subject_code, ts.semester_id, d.department_name
    FROM teacher_subjects ts
    JOIN subjects_master sm ON sm.id = ts.subject_map_id
    JOIN departments d ON d.id = ts.department_id
    WHERE ts.teacher_id = $teacher_id
    ORDER BY ts.batch_year ASC, ts.semester_id ASC
");

// Fetch all subjects for Add dropdown
$all_subjects = $conn->query("SELECT id, subject_name, subject_code FROM subjects_master ORDER BY subject_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assigned Subjects</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3 class="mb-4">Assigned Subjects for <?= $teacher['full_name'] ?></h3>

    <?= $message ?>

    <!-- Add & Assign Subject -->
    <div class="card p-4 mb-4 shadow-sm">
        <h5>Add & Assign Subject</h5>
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Select Subject</label>
                <select name="subject_id" class="form-select" required>
                    <option value="">-- Select Subject --</option>
                    <?php while($sub = $all_subjects->fetch_assoc()): ?>
                        <option value="<?= $sub['id'] ?>"><?= $sub['subject_name'] ?> (<?= $sub['subject_code'] ?: '-' ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select" required>
                    <option value="">-- Select Department --</option>
                    <?php while($d = $departments->fetch_assoc()): ?>
                        <option value="<?= $d['id'] ?>"><?= $d['department_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Semester</label>
                <input type="number" name="semester" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Batch Year</label>
                <input type="number" name="batch_year" class="form-control" required>
            </div>
            <div class="col-md-1">
                <label class="form-label">Section</label>
                <input type="text" name="section" class="form-control" placeholder="A/B">
            </div>
            <div class="col-md-12">
                <button type="submit" name="add_assign" class="btn btn-primary mt-2">Add & Assign</button>
            </div>
        </form>
    </div>

    <!-- Assigned Subjects Table -->
    <?php if($assigned && $assigned->num_rows > 0): ?>
    <table class="table table-bordered table-hover align-middle bg-white">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Subject Name</th>
                <th>Code</th>
                <th>Semester</th>
                <th>Batch</th>
                <th>Department</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            while($a = $assigned->fetch_assoc()){
                $batch = $a['batch_year'] <= 2022 ? "Old Batch" : "New Batch";
                echo "<tr>
                        <td>{$i}</td>
                        <td>{$a['subject_name']}</td>
                        <td>".(!empty($a['subject_code']) ? $a['subject_code'] : '-')."</td>
                        <td>{$a['semester_id']}</td>
                        <td>{$batch}</td>
                        <td>{$a['department_name']}</td>
                      </tr>";
                $i++;
            }
            ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="alert alert-warning">No subjects assigned to this teacher yet.</div>
    <?php endif; ?>
    <a href="manage_teachers.php" class="btn btn-secondary mt-3">Back</a>
</div>
</body>
</html>
