<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

// 1. URL bata parameters line (Strictly using Master ID for results)
$ts_id = intval($_GET['ts_id'] ?? 0); 
$master_id = intval($_GET['subject_id'] ?? 0); // Yo subjects_master ko ID ho (e.g. 104, 109, 111)
$batch = intval($_GET['batch'] ?? 0);
$sem = intval($_GET['sem'] ?? 0);
$dept = intval($_GET['dept'] ?? 0);

if(!$ts_id || !$master_id) die("Error: Missing Subject Information.");

// 2. Subject details line
$subject_q = $conn->query("SELECT * FROM subjects_master WHERE id=$master_id");
$subject_data = $subject_q->fetch_assoc();

// 3. Teacher Subject settings (Full/Pass marks ko lagi)
$ts_q = $conn->query("SELECT * FROM teacher_subjects WHERE id=$ts_id");
$ts_data = $ts_q->fetch_assoc();

// 4. STUDENT SELECTION LOGIC (Elective vs Regular)
// Check garne yo subject elective ho ki haina
$check_elective = $conn->query("SELECT id FROM student_electives WHERE elective_option_id = $master_id LIMIT 1");

if($check_elective->num_rows > 0) {
    // ELECTIVE CASE: Sirf yo subject choose gareko student matra tanne
    $students_sql = "SELECT s.id, s.full_name, s.symbol_no 
                     FROM students s
                     JOIN student_electives se ON s.id = se.student_id
                     WHERE se.elective_option_id = $master_id 
                     AND s.batch_year = $batch 
                     AND s.department_id = $dept
                     ORDER BY s.symbol_no ASC";
} else {
    // REGULAR CASE: Batch ra Dept ko sabai student tanne
    $students_sql = "SELECT id, full_name, symbol_no 
                     FROM students 
                     WHERE batch_year = $batch 
                     AND department_id = $dept 
                     ORDER BY symbol_no ASC";
}
$students_q = $conn->query($students_sql);

// 5. Purano Marks load garne (Matching by Master ID)
$existing_marks = [];
$res_q = $conn->query("SELECT * FROM results WHERE subject_id=$master_id AND semester_id=$sem");
while($r = $res_q->fetch_assoc()) {
    $existing_marks[$r['student_id']] = $r;
}

// Grade Function
function calculateGrade($marks, $full, $pass){
    if($marks === '' || $marks === null) return '-';
    if($marks < $pass) return 'F';
    $p = ($marks/$full)*100;
    if($p>=90) return 'A'; elseif($p>=85) return 'A-';
    elseif($p>=80) return 'B+'; elseif($p>=75) return 'B';
    elseif($p>=70) return 'B-'; elseif($p>=65) return 'C+';
    elseif($p>=60) return 'C'; elseif($p>=55) return 'C-';
    elseif($p>=50) return 'D+'; elseif($p>=45) return 'D';
    else return 'F';
}

// 6. SAVE PROCESS
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $total = floatval($_POST['total_marks']);
    $pass = floatval($_POST['pass_marks']);
    
    // SQL ma hamesha $master_id (104, 109, 111) halne, $ts_id haina
    $stmt = $conn->prepare("INSERT INTO results (student_id, subject_id, semester_id, subject_code, credit_hours, ut_obtain, ut_full_marks, ut_pass_marks, ut_grade, published) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
                            ON DUPLICATE KEY UPDATE 
                            ut_obtain=VALUES(ut_obtain), 
                            ut_grade=VALUES(ut_grade), 
                            ut_full_marks=VALUES(ut_full_marks), 
                            ut_pass_marks=VALUES(ut_pass_marks)");

    foreach($_POST['ut_marks'] as $student_id => $mark_value){
        if($mark_value === '') continue;
        
        $sid = intval($student_id);
        $obtain = floatval($mark_value);
        $grade = calculateGrade($obtain, $total, $pass);
        $s_code = $subject_data['subject_code'];
        $credit = $subject_data['credit_hours'];

        $stmt->bind_param("iiisdddds", $sid, $master_id, $sem, $s_code, $credit, $obtain, $total, $pass, $grade);
        $stmt->execute();
    }
    echo "<script>alert('Marks Updated Successfully!'); window.location.href=window.location.href;</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UT Entry: <?= htmlspecialchars($subject_data['subject_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 30px; margin-top: 20px; }
        .table input { width: 90px; text-align: center; font-weight: bold; }
        .pass-row { background-color: #e8f5e9 !important; }
        .fail-row { background-color: #ffebee !important; }
        .header-box { border-bottom: 2px solid #007bff; margin-bottom: 20px; padding-bottom: 15px; }
    </style>
</head>
<body class="p-4">

<div class="container main-card">
    <div class="header-box d-flex justify-content-between align-items-center">
        <div>
            <h2 class="text-primary mb-0"><?= htmlspecialchars($subject_data['subject_name']) ?></h2>
            <p class="mb-0 text-muted">
                Code: <strong><?= $subject_data['subject_code'] ?></strong> | 
                Batch: <strong><?= $batch ?></strong> | 
                Semester: <strong><?= $sem ?></strong>
            </p>
        </div>
        <a href="publish_result.php" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <form method="POST">
        <div class="row g-3 mb-4 bg-light p-3 rounded shadow-sm border">
            <div class="col-md-3">
                <label class="form-label fw-bold">UT Full Marks</label>
                <input type="number" name="total_marks" id="total_marks" class="form-control" value="<?= $ts_data['total_marks'] ?? 50 ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">UT Pass Marks</label>
                <input type="number" name="pass_marks" id="pass_marks" class="form-control" value="<?= $ts_data['pass_marks'] ?? 22 ?>">
            </div>
            <div class="col-md-6 text-end d-flex align-items-end justify-content-end">
                <span class="badge bg-info p-2">Total Students Found: <?= $students_q->num_rows ?></span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover border align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th width="15%">Symbol No</th>
                        <th width="45%">Student Name</th>
                        <th width="20%">Marks (UT)</th>
                        <th width="20%">Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($students_q->num_rows > 0): ?>
                        <?php while($s = $students_q->fetch_assoc()): 
                            $sid = $s['id'];
                            $m = $existing_marks[$sid]['ut_obtain'] ?? '';
                            $g = $existing_marks[$sid]['ut_grade'] ?? '';
                            // Current pass marks for live coloring
                            $current_pass = $ts_data['pass_marks'] ?? 22;
                            $rowClass = ($m !== '') ? ($m >= $current_pass ? 'pass-row' : 'fail-row') : '';
                        ?>
                        <tr id="row-<?= $sid ?>" class="<?= $rowClass ?>">
                            <td class="text-center fw-bold"><?= $s['symbol_no'] ?></td>
                            <td><?= htmlspecialchars($s['full_name']) ?></td>
                            <td class="d-flex justify-content-center">
                                <input type="number" step="0.01" name="ut_marks[<?= $sid ?>]" 
                                       value="<?= $m ?>" class="form-control mark-input" 
                                       data-sid="<?= $sid ?>">
                            </td>
                            <td class="text-center fw-bold text-uppercase" id="grade-<?= $sid ?>"><?= $g ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center p-4 text-danger">
                                No students found for this subject/elective registration.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($students_q->num_rows > 0): ?>
        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg px-5 shadow">Save All Marks</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
// Live Grade calculation and Row Coloring
document.querySelectorAll('.mark-input').forEach(input => {
    input.addEventListener('input', function() {
        const sid = this.dataset.sid;
        const total = parseFloat(document.getElementById('total_marks').value) || 50;
        const pass = parseFloat(document.getElementById('pass_marks').value) || 22;
        const val = parseFloat(this.value);
        let grade = '-';
        
        if(!isNaN(val)) {
            const p = (val/total)*100;
            if(val < pass) grade = 'F';
            else if(p>=90) grade='A'; else if(p>=85) grade='A-';
            else if(p>=80) grade='B+'; else if(p>=75) grade='B';
            else if(p>=70) grade='B-'; else if(p>=65) grade='C+';
            else if(p>=60) grade='C'; else if(p>=55) grade='C-';
            else if(p>=50) grade='D+'; else if(p>=45) grade='D';
            
            document.getElementById('grade-'+sid).innerText = grade;
            document.getElementById('row-'+sid).className = (val >= pass) ? 'pass-row' : 'fail-row';
        } else {
            document.getElementById('grade-'+sid).innerText = '-';
            document.getElementById('row-'+sid).className = '';
        }
    });
});
</script>
</body>
</html>