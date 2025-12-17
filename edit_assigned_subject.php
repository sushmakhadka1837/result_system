<?php
session_start();
require 'db_config.php';

$id = $_GET['id'] ?? 0;
$teacher_id = $_GET['teacher_id'] ?? 0;

// Fetch record
$record = $conn->query("SELECT * FROM teacher_subjects WHERE id='$id'")->fetch_assoc();
if (!$record) {
    echo "<script>alert('Record not found!'); window.location='assigned_subjects.php?teacher_id=$teacher_id';</script>";
    exit;
}

// Fetch dropdown data
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$semesters = $conn->query("SELECT DISTINCT semester FROM subjects ORDER BY semester ASC");
$sections = $conn->query("SELECT DISTINCT section_name FROM sections ORDER BY section_name ASC");

if (isset($_POST['update'])) {
    $subject_id = $_POST['subject_id'];
    $batch_year = $_POST['batch_year'];
    $department_id = $_POST['department_id'];
    $semester_id = $_POST['semester_id'];
    $section = $_POST['section'];
    $syllabus_type = $_POST['syllabus_type'];

    $update = $conn->query("
        UPDATE teacher_subjects 
        SET subject_map_id='$subject_id', batch_year='$batch_year', department_id='$department_id', 
            semester_id='$semester_id', section='$section', syllabus_type='$syllabus_type'
        WHERE id='$id'
    ");

    if ($update) {
        echo "<script>alert('Subject updated successfully!'); window.location='assigned_subjects.php?teacher_id=$teacher_id';</script>";
    } else {
        echo "<script>alert('Error updating subject!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Assigned Subject</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5 bg-white p-4 shadow rounded">
    <h3 class="text-center text-primary mb-4">Edit Assigned Subject</h3>

    <form method="POST">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Department</label>
                <select name="department_id" id="department" class="form-select" required>
                    <option value="">-- Select Department --</option>
                    <?php while($d = $departments->fetch_assoc()){ ?>
                        <option value="<?= $d['id'] ?>" <?= $d['id'] == $record['department_id'] ? 'selected' : '' ?>>
                            <?= $d['name'] ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Batch Year</label>
                <input type="number" name="batch_year" class="form-control" value="<?= $record['batch_year'] ?>" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Semester</label>
                <select name="semester_id" id="semester" class="form-select" required>
                    <option value="">-- Select Semester --</option>
                    <?php while($s = $semesters->fetch_assoc()){ ?>
                        <option value="<?= $s['semester'] ?>" <?= $s['semester'] == $record['semester_id'] ? 'selected' : '' ?>>
                            <?= $s['semester'] ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Section</label>
                <select name="section" class="form-select" required>
                    <?php while($sec = $sections->fetch_assoc()){ ?>
                        <option value="<?= $sec['section_name'] ?>" <?= $sec['section_name'] == $record['section'] ? 'selected' : '' ?>>
                            <?= $sec['section_name'] ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Syllabus Type</label>
                <select name="syllabus_type" class="form-select" required>
                    <option value="Regular" <?= $record['syllabus_type'] == 'Regular' ? 'selected' : '' ?>>Regular</option>
                    <option value="Back" <?= $record['syllabus_type'] == 'Back' ? 'selected' : '' ?>>Back</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Subject</label>
            <select name="subject_id" id="subject" class="form-select" required>
                <option value="">-- Select Subject --</option>
            </select>
        </div>

        <div class="text-center">
            <button type="submit" name="update" class="btn btn-primary px-5">Update</button>
            <a href="assigned_subjects.php?teacher_id=<?= $teacher_id ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    function loadSubjects() {
        let dept = $('#department').val();
        let sem = $('#semester').val();
        if (dept && sem) {
            $.ajax({
                url: 'get_subjects.php',
                type: 'POST',
                data: {department_id: dept, semester_id: sem},
                success: function(data) {
                    $('#subject').html(data);
                    $('#subject').val('<?= $record['subject_map_id'] ?>');
                }
            });
        }
    }
    $('#department, #semester').on('change', loadSubjects);
    loadSubjects();
});
</script>

</body>
</html>
