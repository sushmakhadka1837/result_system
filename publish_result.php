<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['teacher_id'])){
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$marks_type_options = ['Unit Test']; // Only UT now

// Grade calculation function
function getGradePoint($marks, $full_marks=50){
    $percentage = ($marks/$full_marks)*100;
    if($percentage >= 90) return ['A', 4.0];
    elseif($percentage >= 85) return ['A-', 3.7];
    elseif($percentage >= 80) return ['B+', 3.3];
    elseif($percentage >= 75) return ['B', 3.0];
    elseif($percentage >= 70) return ['B-', 2.7];
    elseif($percentage >= 65) return ['C+', 2.3];
    elseif($percentage >= 60) return ['C', 2.0];
    elseif($percentage >= 55) return ['C-', 1.7];
    elseif($percentage >= 50) return ['D+', 1.3];
    elseif($percentage >= 45) return ['D', 1.0];
    else return ['F', 0.0];
}

// Fetch assigned subjects
$assigned_subjects = $conn->query("
    SELECT ts.id AS teacher_subject_id, sm.subject_name, ts.batch_year,
           sds.semester, COALESCE(sds.section,'') AS section, d.department_name,
           ts.total_marks, ts.pass_marks, ts.total_attendance
    FROM teacher_subjects ts
    JOIN subjects_department_semester sds ON ts.subject_map_id = sds.id
    JOIN subjects_master sm ON sds.subject_id = sm.id
    JOIN departments d ON sds.department_id = d.id
    WHERE ts.teacher_id = $teacher_id
");

$students_q = null;
$subject = null;

if(isset($_GET['teacher_subject_id'])){
    $teacher_subject_id = intval($_GET['teacher_subject_id']);

    $sub_q = $conn->query("
        SELECT sm.subject_name, sds.department_id, sds.semester,
               COALESCE(sds.section,'') AS section, ts.batch_year,
               ts.total_marks, ts.pass_marks, ts.total_attendance
        FROM teacher_subjects ts
        JOIN subjects_department_semester sds ON ts.subject_map_id = sds.id
        JOIN subjects_master sm ON sds.subject_id = sm.id
        WHERE ts.id = $teacher_subject_id
    ");
    $subject = $sub_q->fetch_assoc();

    $dept = $subject['department_id'];
    $sem = intval($subject['semester']);
    $sec = $subject['section'];
    $batch = intval($subject['batch_year']);
    $total_marks = $subject['total_marks'] ?? 50;
    $pass_marks = $subject['pass_marks'] ?? 22;
    $total_attendance = $subject['total_attendance'] ?? 30;

    // Fetch students
    $students_q = $conn->query("
        SELECT * FROM students
        WHERE department_id=$dept
          AND semester=$sem
          AND batch_year=$batch
          AND (section='$sec' OR ('$sec'='' AND (section IS NULL OR section='Old')))
        ORDER BY full_name ASC
    ");

    // Fetch existing marks
    $marks_result = [];
    $res_q = $conn->query("SELECT * FROM results WHERE subject_id=$teacher_subject_id AND marks_type='Unit Test'");
    while($row = $res_q->fetch_assoc()){
        $marks_result[$row['student_id']] = $row;
    }

    // Save marks
    if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_marks'])){
        $total_marks_post = intval($_POST['total_marks']);
        $pass_marks_post = intval($_POST['pass_marks']);
        $total_attendance_post = intval($_POST['total_attendance']);

        foreach($students_q as $stu){
            $sid = $stu['id'];
            $ut_marks = $_POST['ut_marks'][$sid] ?? 0;
            $attendance_days = $_POST['attendance_days'][$sid] ?? 0;

            list($grade, $grade_point) = getGradePoint($ut_marks, $total_marks_post);
            $status = ($ut_marks >= $pass_marks_post) ? 'PASS' : 'FAIL';

            $marks_type_var = 'Unit Test';

            // Insert or update
            $stmt = $conn->prepare("
                INSERT INTO results
                (student_id, subject_id, marks_type, ut_marks, total_marks, pass_marks, attendance_days, total_attendance, letter_grade, grade_point, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                ut_marks=VALUES(ut_marks), total_marks=VALUES(total_marks), pass_marks=VALUES(pass_marks),
                attendance_days=VALUES(attendance_days), total_attendance=VALUES(total_attendance),
                letter_grade=VALUES(letter_grade), grade_point=VALUES(grade_point), status=VALUES(status)
            ");
            $stmt->bind_param("iissiiissds",
                $sid, $teacher_subject_id, $marks_type_var, $ut_marks,
                $total_marks_post, $pass_marks_post, $attendance_days, $total_attendance_post,
                $grade, $grade_point, $status
            );
            $stmt->execute();
        }

        echo "<script>alert('Marks saved successfully!'); window.location='publish_result.php?teacher_subject_id=$teacher_subject_id';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Publish Result</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{padding-top:70px;background:#f4f6f9;}
.navbar{background:#004085;}
.navbar a{color:white!important;}
.container{background:white;padding:20px;border-radius:10px;}
.pass{color:green;font-weight:bold;}
.fail{color:red;font-weight:bold;}
</style>
<script>
function calculateGrade(input, fullMarks){
    let marks = parseFloat(input.value) || 0;
    let percentage = (marks/fullMarks)*100;
    let grade = 'F';
    if(percentage >= 90) grade='A';
    else if(percentage >= 85) grade='A-';
    else if(percentage >= 80) grade='B+';
    else if(percentage >= 75) grade='B';
    else if(percentage >= 70) grade='B-';
    else if(percentage >= 65) grade='C+';
    else if(percentage >= 60) grade='C';
    else if(percentage >= 55) grade='C-';
    else if(percentage >= 50) grade='D+';
    else if(percentage >= 45) grade='D';
    input.closest('tr').querySelector('.grade').innerText = grade;
}
</script>
</head>
<body>
<nav class="navbar navbar-expand-lg fixed-top">
<div class="container-fluid">
<a class="navbar-brand fw-bold" href="#">Publish Result</a>
<div class="collapse navbar-collapse justify-content-end">
<ul class="navbar-nav">
<li><a href="teacher_dashboard.php" class="nav-link">Dashboard</a></li>
<li><a href="logout.php" class="nav-link">Logout</a></li>
</ul>
</div>
</div>
</nav>

<div class="container mt-3">
<?php if(!isset($_GET['teacher_subject_id'])): ?>
<h4 class="text-center mb-4">Your Assigned Subjects</h4>
<table class="table table-bordered table-striped">
<thead class="table-primary">
<tr><th>#</th><th>Subject Name</th><th>Batch</th><th>Semester</th><th>Section</th><th>Department</th><th>Action</th></tr>
</thead>
<tbody>
<?php if($assigned_subjects->num_rows>0): $i=1; while($sub=$assigned_subjects->fetch_assoc()): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= htmlspecialchars($sub['subject_name']) ?></td>
<td><?= htmlspecialchars($sub['batch_year']) ?></td>
<td><?= htmlspecialchars($sub['semester']) ?></td>
<td><?= htmlspecialchars($sub['section']) ?></td>
<td><?= htmlspecialchars($sub['department_name']) ?></td>
<td><a href="publish_result.php?teacher_subject_id=<?= $sub['teacher_subject_id'] ?>" class="btn btn-sm btn-primary">Enter Marks</a></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="7" class="text-center text-danger">No subjects assigned.</td></tr>
<?php endif; ?>
</tbody>
</table>

<?php else: ?>
<h5 class="mb-3">Enter Marks for <?= htmlspecialchars($subject['subject_name']) ?></h5>

<form method="POST">
<div class="row mb-3">
<div class="col-md-3"><label>Total Marks</label><input type="number" name="total_marks" value="<?= $total_marks ?>" class="form-control" required></div>
<div class="col-md-3"><label>Pass Marks</label><input type="number" name="pass_marks" value="<?= $pass_marks ?>" class="form-control" required></div>
<div class="col-md-3"><label>Total Attendance Days</label><input type="number" name="total_attendance" value="<?= $total_attendance ?>" class="form-control" required></div>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead class="table-success">
<tr>
<th>#</th><th>Student Name</th><th>Symbol No</th><th>Obtain Marks (<?= $total_marks ?>)</th><th>Grade</th><th>Attendance Days</th><th>Status</th>
</tr>
</thead>
<tbody>
<?php
$i=1;
foreach($students_q as $stu):
$sid=$stu['id'];
$mrk=$marks_result[$sid]??[];
$obtain_marks = $mrk['ut_marks']??0;
list($grade,$cgpa)=getGradePoint($obtain_marks,$total_marks);
$status = ($obtain_marks >= $pass_marks)?'PASS':'FAIL';
?>
<tr>
<td><?= $i++ ?></td>
<td><?= htmlspecialchars($stu['full_name']) ?></td>
<td><?= htmlspecialchars($stu['symbol_no']) ?></td>
<td><input type="number" step="0.01" name="ut_marks[<?= $sid ?>]" value="<?= $obtain_marks ?>" class="form-control" max="<?= $total_marks ?>" oninput="calculateGrade(this,<?= $total_marks ?>)">
</td>
<td class="grade"><?= $grade ?></td>
<td><input type="number" name="attendance_days[<?= $sid ?>]" value="<?= $mrk['attendance_days']??0 ?>" class="form-control"></td>
<td class="<?= strtolower($status) ?>"><?= $status ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<button type="submit" name="save_marks" class="btn btn-success">Save Marks</button>
<a href="publish_result.php" class="btn btn-secondary">Back</a>
</form>
<?php endif; ?>
</div>
</body>
</html>
