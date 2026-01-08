<?php
session_start();
require 'db_config.php';

/* =========================
   TEACHER VALIDATION
========================= */
$teacher_id = intval($_GET['teacher_id'] ?? 0);
if (!$teacher_id) {
    die("Invalid teacher");
}

$teacher = $conn->query("SELECT full_name FROM teachers WHERE id=$teacher_id")->fetch_assoc();
if (!$teacher) {
    die("Teacher not found");
}

/* =========================
   DELETE ASSIGNED SUBJECT
========================= */
if (isset($_GET['delete_assign_id'])) {
    $del_id = intval($_GET['delete_assign_id']);
    $conn->query("DELETE FROM teacher_subjects WHERE id=$del_id AND teacher_id=$teacher_id");
    header("Location: assign_subjects.php?teacher_id=$teacher_id");
    exit;
}

/* =========================
   UPDATE ASSIGNED SUBJECT
========================= */
$message = "";
if (isset($_POST['update_assignment'])) {

    $assign_id  = intval($_POST['assign_id']);
    $semester   = intval($_POST['semester']);
    $batch_year = intval($_POST['batch_year']);
    $section    = !empty($_POST['section']) ? $_POST['section'] : NULL;

    $conn->query("
        UPDATE teacher_subjects SET
            semester_id = $semester,
            batch_year  = $batch_year,
            section     = " . ($section === NULL ? "NULL" : "'$section'") . "
        WHERE id = $assign_id
        AND teacher_id = $teacher_id
    ");

    $message = "<div class='alert alert-success'>Assignment updated successfully.</div>";
}

/* =========================
   DEPARTMENTS
========================= */
$departments = $conn->query("SELECT id, department_name, total_semesters FROM departments ORDER BY department_name ASC");

/* =========================
   ASSIGN SUBJECTS
========================= */
if (isset($_POST['assign_subjects'])) {

    $department_id = intval($_POST['department_id']);
    $semester      = intval($_POST['semester']);
    $batch_year    = intval($_POST['batch_year']);
    $section       = !empty($_POST['section']) ? $_POST['section'] : NULL;
    $subject_ids   = $_POST['subject_ids'] ?? [];

    if ($batch_year > 2022) {
        $message = "<div class='alert alert-danger'>Only old batch (<=2022) allowed.</div>";
    } else {
        foreach ($subject_ids as $sid) {
            $sid = intval($sid);

            $check = "
                SELECT id FROM teacher_subjects
                WHERE subject_map_id=$sid
                AND department_id=$department_id
                AND semester_id=$semester
                AND batch_year=$batch_year
                AND teacher_id != $teacher_id
            ";
            if ($section === NULL) $check .= " AND section IS NULL";
            else $check .= " AND section='$section'";

            if ($conn->query($check)->num_rows == 0) {
                $conn->query("
                    INSERT INTO teacher_subjects
                    (teacher_id, subject_map_id, department_id, semester_id, batch_year, section, syllabus_type)
                    VALUES
                    ($teacher_id, $sid, $department_id, $semester, $batch_year,
                    " . ($section === NULL ? "NULL" : "'$section'") . ", 'Old')
                ");
            }
        }
        $message = "<div class='alert alert-success'>Subjects assigned successfully.</div>";
    }
}

/* =========================
   FILTER SUBJECTS
========================= */
$subjects = [];
if (isset($_POST['filter'])) {

    $department_id = intval($_POST['department_id']);
    $semester      = intval($_POST['semester']);
    $batch_year    = intval($_POST['batch_year']);
    $section       = !empty($_POST['section']) ? $_POST['section'] : NULL;

    if ($batch_year <= 2022) {
        $subjects = $conn->query("
            SELECT sm.id, sm.subject_name, sm.subject_code
            FROM subjects_department_semester sds
            JOIN subjects_master sm ON sm.id = sds.subject_id
            WHERE sds.department_id=$department_id
            AND sds.semester=$semester
            AND (sds.batch_year IS NULL OR sds.batch_year <= 2022)
        ");
    }
}

/* =========================
   ASSIGNED SUBJECTS
========================= */
$my_subjects = $conn->query("
    SELECT ts.id, sm.subject_name, sm.subject_code,
           d.department_name, ts.semester_id,
           ts.batch_year, ts.section
    FROM teacher_subjects ts
    JOIN subjects_master sm ON sm.id = ts.subject_map_id
    JOIN departments d ON d.id = ts.department_id
    WHERE ts.teacher_id = $teacher_id
    ORDER BY ts.batch_year, ts.semester_id
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assign Subjects</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-light">
<div class="container mt-5">

<h4>Assign Subjects for <strong><?= htmlspecialchars($teacher['full_name']) ?></strong></h4>
<?= $message ?>

<form method="post" class="card p-4 shadow-sm mb-4">
<div class="row g-3">
<div class="col-md-3">
<select name="department_id" id="department" class="form-select" required>
<option value="">Department</option>
<?php while($d = $departments->fetch_assoc()): ?>
<option value="<?= $d['id'] ?>" data-sem="<?= $d['total_semesters'] ?>">
<?= $d['department_name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-2">
<select name="semester" id="semester" class="form-select" required>
<option value="">Semester</option>
</select>
</div>

<div class="col-md-2">
<input type="number" name="batch_year" class="form-control" placeholder="Batch Year" required>
</div>

<div class="col-md-2">
<select name="section" class="form-select">
<option value="">Section</option>
<option>A</option>
<option>B</option>
<option>C</option>
</select>
</div>

<div class="col-md-3">
<button name="filter" class="btn btn-success w-100">Show Subjects</button>
</div>
</div>
</form>

<?php if (!empty($subjects) && $subjects->num_rows > 0): ?>
<form method="post" class="card p-4 shadow-sm">
<input type="hidden" name="department_id" value="<?= $department_id ?>">
<input type="hidden" name="semester" value="<?= $semester ?>">
<input type="hidden" name="batch_year" value="<?= $batch_year ?>">
<input type="hidden" name="section" value="<?= $section ?>">

<table class="table table-bordered">
<thead class="table-dark">
<tr><th>#</th><th>Subject</th><th>Code</th><th>Select</th></tr>
</thead>
<tbody>
<?php $i=1; while($s=$subjects->fetch_assoc()):
$sid=$s['id'];

$chk="SELECT id FROM teacher_subjects WHERE subject_map_id=$sid AND department_id=$department_id AND semester_id=$semester AND batch_year=$batch_year AND teacher_id!=$teacher_id";
if ($section===NULL) $chk.=" AND section IS NULL";
else $chk.=" AND section='$section'";

$assigned=$conn->query($chk)->num_rows>0;
?>
<tr>
<td><?= $i++ ?></td>
<td><?= $s['subject_name'] ?></td>
<td><?= $s['subject_code'] ?></td>
<td>
<?php if($assigned): ?>
<span class="badge bg-danger">Assigned</span>
<?php else: ?>
<input type="checkbox" name="subject_ids[]" value="<?= $sid ?>">
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<button name="assign_subjects" class="btn btn-primary w-100">Assign Selected</button>
</form>
<?php endif; ?>

<h5 class="mt-5">Assigned Subjects</h5>

<table class="table table-bordered bg-white">
<thead class="table-dark">
<tr>
<th>#</th><th>Subject</th><th>Code</th><th>Dept</th>
<th>Semester</th><th>Batch</th><th>Section</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($r=$my_subjects->fetch_assoc()): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= $r['subject_name'] ?></td>
<td><?= $r['subject_code'] ?></td>
<td><?= $r['department_name'] ?></td>

<form method="post">
<td><input type="number" name="semester" value="<?= $r['semester_id'] ?>" class="form-control"></td>
<td><input type="number" name="batch_year" value="<?= $r['batch_year'] ?>" class="form-control"></td>
<td>
<select name="section" class="form-select">
<option value="">None</option>
<option <?= $r['section']=='A'?'selected':'' ?>>A</option>
<option <?= $r['section']=='B'?'selected':'' ?>>B</option>
<option <?= $r['section']=='C'?'selected':'' ?>>C</option>
</select>
</td>
<td class="d-flex gap-1">
<input type="hidden" name="assign_id" value="<?= $r['id'] ?>">
<button name="update_assignment" class="btn btn-warning btn-sm">Change</button>
<a href="?teacher_id=<?= $teacher_id ?>&delete_assign_id=<?= $r['id'] ?>"
onclick="return confirm('Delete this subject?')"
class="btn btn-danger btn-sm">Delete</a>
</td>
</form>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<a href="manage_teachers.php" class="btn btn-secondary mt-3">Back</a>

</div>

<script>
$('#department').on('change', function(){
let sem=$('option:selected',this).data('sem');
$('#semester').html('<option value="">Semester</option>');
for(let i=1;i<=sem;i++){
$('#semester').append('<option value="'+i+'">'+i+'</option>');
}
});
</script>

</body>
</html>
