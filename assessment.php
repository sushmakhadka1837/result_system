<?php
session_start();
require 'db_config.php';

// Teacher authentication check
if(!isset($_SESSION['teacher_id'])){
    header("Location: login.php");
    exit;
}


$ts_id = intval($_GET['ts_id'] ?? 0); 
$master_subject_id = intval($_GET['subject_id'] ?? 0); 
$batch = intval($_GET['batch'] ?? 0);
$sem = intval($_GET['sem'] ?? 0);
$dept = intval($_GET['dept'] ?? 0);

if(!$ts_id || !$master_subject_id) die("Invalid Subject Mapping");


$sub_q = $conn->query("SELECT * FROM teacher_subjects WHERE id=$ts_id");
$sub = $sub_q->fetch_assoc();

$subject_q = $conn->query("SELECT * FROM subjects_master WHERE id=$master_subject_id");
$subject_data = $subject_q->fetch_assoc();

/* ================= STUDENT FETCH LOGIC (ELECTIVE vs REGULAR) ================= */
$check_elective = $conn->query("SELECT id FROM student_electives WHERE elective_option_id = $master_subject_id LIMIT 1");

if($check_elective->num_rows > 0) {
    $students_sql = "SELECT s.id, s.full_name, s.symbol_no 
                     FROM students s
                     JOIN student_electives se ON s.id = se.student_id
                     WHERE se.elective_option_id = $master_subject_id 
                     AND s.batch_year = $batch 
                     AND s.department_id = $dept
                     ORDER BY s.full_name ASC";
} else {
    $students_sql = "SELECT id, full_name, symbol_no 
                     FROM students 
                     WHERE batch_year = $batch 
                     AND department_id = $dept 
                     ORDER BY full_name ASC";
}
$students_q = $conn->query($students_sql);

// 3. Existing marks fetch garne (UT marks admin le upload gareko hunchha)
$marks_result = [];
$res_q = $conn->query("SELECT * FROM results WHERE subject_id=$master_subject_id AND semester_id=$sem");
while($row = $res_q->fetch_assoc()) $marks_result[$row['student_id']] = $row;

/* ================= SAVE DATA LOGIC ================= */
/* ================= UPDATED SAVE DATA LOGIC ================= */
if($_SERVER['REQUEST_METHOD']=='POST'){
    $total_attendance = intval($_POST['total_attendance']);

    foreach($_POST['assessment_raw'] as $sid => $ass_val){
        $sid = intval($sid);
        $ut   = floatval($_POST['ut_obtain'][$sid] ?? 0); 
        $ass  = floatval($ass_val);
        $tut  = floatval($_POST['tutorial'][$sid] ?? 0);
        $att  = intval($_POST['attended'][$sid] ?? 0);
        $prac = floatval($_POST['practical'][$sid] ?? 0);

        // Timro formula anusar conversion
        $ut_ai   = round(($ut/50)*5, 2);
        $ass_ai  = round((($ass/100)*15), 2); 
        $att_ai  = ($total_attendance > 0) ? round(($att/$total_attendance)*5, 2) : 0;
        
        $final_theory = $ut_ai + $ass_ai + $tut + $att_ai;
        $total = $final_theory + $prac;

        // Naya Grade Calculation
        $percent = ($total/50)*100;
        if($percent>=90) $grade='A';
        elseif($percent>=85) $grade='A-';
        elseif($percent>=80) $grade='B+';
        elseif($percent>=75) $grade='B';
        elseif($percent>=70) $grade='B-';
        elseif($percent>=65) $grade='C+';
        elseif($percent>=60) $grade='C';
        elseif($percent>=55) $grade='C-';
        elseif($percent>=50) $grade='D+';
        else $grade='F';

        $grade_points = ['A'=>4,'A-'=>3.7,'B+'=>3.3,'B'=>3,'B-'=>2.7,'C+'=>2.3,'C'=>2,'C-'=>1.7,'D+'=>1.3,'F'=>0];
        $gp = $grade_points[$grade];

        // Database Update - UT Marks lai change nagari baki update garne
        $stmt = $conn->prepare("
            INSERT INTO results
            (student_id, subject_id, semester_id, ut_obtain, ut_ai_marks, assessment_raw, assessment_ai_marks, tutorial_marks, attendance_marks, total_attendance_days, practical_marks, final_theory, final_total, letter_grade, grade_point, total_obtained)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                ut_obtain=VALUES(ut_obtain),
                ut_ai_marks=VALUES(ut_ai_marks),
                assessment_raw=VALUES(assessment_raw),
                assessment_ai_marks=VALUES(assessment_ai_marks),
                tutorial_marks=VALUES(tutorial_marks),
                attendance_marks=VALUES(attendance_marks),
                total_attendance_days=VALUES(total_attendance_days),
                practical_marks=VALUES(practical_marks),
                final_theory=VALUES(final_theory),
                final_total=VALUES(final_total),
                letter_grade=VALUES(letter_grade),
                grade_point=VALUES(grade_point),
                total_obtained=VALUES(total_obtained)
        ");
        $stmt->bind_param("iiidddddiidddssd", $sid, $master_subject_id, $sem, $ut, $ut_ai, $ass, $ass_ai, $tut, $att, $total_attendance, $prac, $final_theory, $total, $grade, $gp, $total);
        $stmt->execute();
    }

    $_SESSION['msg'] = "Marks updated successfully! Reflecting on the reports.";
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assessment Entry - <?= htmlspecialchars($subject_data['subject_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .container-card { background: white; border-radius: 20px; box-shadow: 0 4px 25px rgba(0,0,0,0.05); padding: 30px; margin-top: 20px; }
        .table thead { background: #1e293b; color: white; font-size: 11px; text-transform: uppercase; }
        .form-control-sm { border-radius: 6px; text-align: center; font-weight: bold; }
        .readonly-input { background-color: #f1f5f9 !important; border: 1px solid #e2e8f0; }
        .grade-badge { padding: 4px 8px; border-radius: 6px; font-weight: 900; }
    </style>
</head>
<body>

<div class="container-fluid px-4">
    <div class="container-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-black text-slate-800 mb-0"><?= htmlspecialchars($subject_data['subject_name']) ?></h4>
                <span class="badge bg-primary">Semester <?= $sem ?></span>
                <span class="badge bg-secondary">Batch <?= $batch ?></span>
            </div>
            <a href="teacher_dashboard.php" class="btn btn-outline-dark btn-sm fw-bold">← Dashboard</a>
        </div>

        <?php if(isset($_SESSION['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show font-bold">
                <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 border">
                        <label class="small fw-bold text-muted">Total Attendance Days</label>
                        <input type="number" name="total_attendance" id="total_attendance" 
                               value="<?= $marks_result[array_key_first($marks_result)]['total_attendance_days'] ?? 30 ?>" 
                               class="form-control fw-bold border-primary">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle border">
                    <thead>
                        <tr class="text-center">
                            <th class="text-start">Student Name</th>
                            <th>UT (50)</th>
                            <th class="bg-light">UT (5)</th>
                            <th>Ass. (100)</th>
                            <th class="bg-light">Ass. (15)</th>
                            <th>Tut. (5)</th>
                            <th>Att. Days</th>
                            <th class="bg-light">Att. (5)</th>
                            <th>Prac. (20)</th>
                            <th class="table-dark">Theory</th>
                            <th class="table-primary">Total</th>
                            <th class="table-primary">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $students_q->data_seek(0);
                    while($stu = $students_q->fetch_assoc()): 
                        $sid = $stu['id'];
                        $res = $marks_result[$sid] ?? [];
                    ?>
                        <tr class="student-row">
                            <td class="small fw-bold">
                                <?= htmlspecialchars($stu['full_name']) ?>
                                <div class="text-muted" style="font-size: 9px;"><?= $stu['symbol_no'] ?></div>
                            </td>
                            <td>
                                <input type="number" step="0.01" name="ut_obtain[<?= $sid ?>]" 
                                       class="form-control form-control-sm ut_obtain" 
                                       value="<?= $res['ut_obtain'] ?? 0 ?>">
                            </td>
                            <td class="ut_ai fw-bold text-primary">0</td>
                            <td>
                                <input type="number" step="0.01" name="assessment_raw[<?= $sid ?>]" 
                                       class="form-control form-control-sm assessment_raw" 
                                       value="<?= $res['assessment_raw'] ?? 0 ?>">
                            </td>
                            <td class="assessment_ai fw-bold text-primary">0</td>
                            <td>
                                <input type="number" step="0.01" name="tutorial[<?= $sid ?>]" 
                                       class="form-control form-control-sm tutorial" 
                                       value="<?= $res['tutorial_marks'] ?? 0 ?>">
                            </td>
                            <td>
                                <input type="number" step="1" name="attended[<?= $sid ?>]" 
                                       class="form-control form-control-sm attended" 
                                       value="<?= $res['attendance_marks'] ?? 0 ?>">
                            </td>
                            <td class="attendance_ai fw-bold text-primary">0</td>
                            <td>
                                <input type="number" step="0.01" name="practical[<?= $sid ?>]" 
                                       class="form-control form-control-sm practical" 
                                       value="<?= $res['practical_marks'] ?? 0 ?>">
                            </td>
                            <td class="final_theory fw-black">0</td>
                            <td class="final_total fw-black text-primary">0</td>
                            <td class="grade_val fw-black text-danger">F</td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold">Update Marks & Calculate Grades</button>
            </div>
        </form>
    </div>
</div>

<script>
function calculateRow(row) {
    const ut = parseFloat(row.querySelector('.ut_obtain').value) || 0;
    const assRaw = parseFloat(row.querySelector('.assessment_raw').value) || 0;
    const tut = parseFloat(row.querySelector('.tutorial').value) || 0;
    const attDays = parseFloat(row.querySelector('.attended').value) || 0;
    const prac = parseFloat(row.querySelector('.practical').value) || 0;
    const totalAttDays = parseFloat(document.getElementById('total_attendance').value) || 1;

    // Logic Conversions
    const utAi = (ut / 50) * 5;
    const assAi = (assRaw / 100) * 15;
    const attAi = (attDays / totalAttDays) * 5;
    
    const theoryTotal = utAi + assAi + tut + attAi;
    const totalObtained = theoryTotal + prac;

    // Update UI
    row.querySelector('.ut_ai').innerText = utAi.toFixed(2);
    row.querySelector('.assessment_ai').innerText = assAi.toFixed(2);
    row.querySelector('.attendance_ai').innerText = attAi.toFixed(2);
    row.querySelector('.final_theory').innerText = theoryTotal.toFixed(2);
    row.querySelector('.final_total').innerText = totalObtained.toFixed(2);

    // Grading
    const percent = (totalObtained / 50) * 100;
    let grade = 'F';
    if(percent >= 90) grade = 'A';
    else if(percent >= 85) grade = 'A-';
    else if(percent >= 80) grade = 'B+';
    else if(percent >= 75) grade = 'B';
    else if(percent >= 70) grade = 'B-';
    else if(percent >= 65) grade = 'C+';
    else if(percent >= 60) grade = 'C';
    else if(percent >= 55) grade = 'C-';
    else if(percent >= 50) grade = 'D+';
    
    row.querySelector('.grade_val').innerText = grade;
}

// Event Listeners for auto-calculation
document.addEventListener('input', function (e) {
    if (e.target.classList.contains('form-control') || e.target.id === 'total_attendance') {
        document.querySelectorAll('.student-row').forEach(calculateRow);
    }
});

// Initial calculation on page load
window.onload = () => document.querySelectorAll('.student-row').forEach(calculateRow);
</script>

</body>
</html>