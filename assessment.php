<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['teacher_id'])){
    header("Location: login.php");
    exit;
}

// 1. Fetch IDs from URL (Strictly using Master ID)
$ts_id = intval($_GET['ts_id'] ?? 0); 
$master_subject_id = intval($_GET['subject_id'] ?? 0); 
$batch = intval($_GET['batch'] ?? 0);
$sem = intval($_GET['sem'] ?? 0);
$dept = intval($_GET['dept'] ?? 0);

if(!$ts_id || !$master_subject_id) die("Invalid Subject Mapping");

// Fetch teacher_subject for setting details
$sub_q = $conn->query("SELECT * FROM teacher_subjects WHERE id=$ts_id");
$sub = $sub_q->fetch_assoc();

// Fetch subject info from subjects_master
$subject_q = $conn->query("SELECT * FROM subjects_master WHERE id=$master_subject_id");
$subject_data = $subject_q->fetch_assoc();

/* ================= NEW STUDENT FETCH LOGIC (ELECTIVE vs REGULAR) ================= */
$check_elective = $conn->query("SELECT id FROM student_electives WHERE elective_option_id = $master_subject_id LIMIT 1");

if($check_elective->num_rows > 0) {
    // ELECTIVE CASE: Only registered students
    $students_sql = "SELECT s.id, s.full_name, s.symbol_no 
                     FROM students s
                     JOIN student_electives se ON s.id = se.student_id
                     WHERE se.elective_option_id = $master_subject_id 
                     AND s.batch_year = $batch 
                     AND s.department_id = $dept
                     ORDER BY s.full_name ASC";
} else {
    // REGULAR CASE: All students
    $students_sql = "SELECT id, full_name, symbol_no 
                     FROM students 
                     WHERE batch_year = $batch 
                     AND department_id = $dept 
                     ORDER BY full_name ASC";
}
$students_q = $conn->query($students_sql);
/* ================================================================================= */

// 3. Fetch existing results (Using Master Subject ID)
$marks_result = [];
$res_q = $conn->query("SELECT * FROM results WHERE subject_id=$master_subject_id AND semester_id=$sem");
while($row = $res_q->fetch_assoc()) $marks_result[$row['student_id']] = $row;

/* ================= SAVE DATA ================= */
if($_SERVER['REQUEST_METHOD']=='POST'){
    $total_attendance = intval($_POST['total_attendance']);

    foreach($_POST['assessment_raw'] as $sid => $ass_val){
        $sid = intval($sid);
        $ut   = floatval($_POST['ut_obtain'][$sid] ?? 0); 
        $ass  = floatval($ass_val);
        $tut  = floatval($_POST['tutorial'][$sid] ?? 0);
        $att  = intval($_POST['attended'][$sid] ?? 0);
        $prac = floatval($_POST['practical'][$sid] ?? 0);

        // Conversions
        $ut_ai   = round(($ut/50)*5, 2);
        $ass_ai  = round((($ass/100)*15), 2); 
        $att_ai  = ($total_attendance > 0) ? round(($att/$total_attendance)*5, 2) : 0;
        $final_theory = $ut_ai + $ass_ai + $tut + $att_ai;
        $total = $final_theory + $prac;

        // Grade calculation logic
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

        $conn->query("
            INSERT INTO results
            (student_id, subject_id, semester_id, ut_obtain, ut_ai_marks, assessment_raw, assessment_ai_marks, tutorial_marks, attendance_marks, total_attendance_days, practical_marks, final_theory, final_total, letter_grade, grade_point, total_obtained)
            VALUES
            ($sid, $master_subject_id, $sem, $ut, $ut_ai, $ass, $ass_ai, $tut, $att, $total_attendance, $prac, $final_theory, $total, '$grade', $gp, $total)
            ON DUPLICATE KEY UPDATE
                ut_ai_marks=$ut_ai,
                assessment_raw=$ass,
                assessment_ai_marks=$ass_ai,
                tutorial_marks=$tut,
                attendance_marks=$att,
                total_attendance_days=$total_attendance,
                practical_marks=$prac,
                final_theory=$final_theory,
                final_total=$total,
                letter_grade='$grade',
                grade_point=$gp,
                total_obtained=$total
        ");
    }

    $_SESSION['msg'] = "Assessment Saved Successfully";
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Assessment - <?= htmlspecialchars($subject_data['subject_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f3faff; padding: 30px; }
        .container-card { max-width: 1300px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .readonly { background-color: #f8f9fa; font-weight: bold; }
        .table thead { background-color: #007bff; color: white; }
    </style>
</head>
<body>

<div class="container-card">
    <div class="d-flex justify-content-between mb-4">
        <h4>Assessment: <?= htmlspecialchars($subject_data['subject_name']) ?> (<?= $sem ?> Sem)</h4>
        <a href="publish_result.php" class="btn btn-secondary btn-sm">Back</a>
    </div>

    <form method="post">
        <div class="mb-4 bg-light p-3 rounded border w-25">
            <label class="fw-bold">Total Attendance Days</label>
            <input type="number" name="total_attendance" id="total_attendance" value="<?= $sub['total_attendance'] ?? 30 ?>" class="form-control mt-1">
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>UT (50)</th>
                        <th>UT (5)</th>
                        <th>Ass. Raw (100)</th>
                        <th>Ass. (15)</th>
                        <th>Tut. (5)</th>
                        <th>Att. Days</th>
                        <th>Att. (5)</th>
                        <th>Prac. (20)</th>
                        <th>Theory</th>
                        <th>Total</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $students_q->data_seek(0);
                while($stu = $students_q->fetch_assoc()): 
                    $sid = $stu['id'];
                    $ut = $marks_result[$sid]['ut_obtain'] ?? 0;
                    $ass = $marks_result[$sid]['assessment_raw'] ?? 0;
                    $tut = $marks_result[$sid]['tutorial_marks'] ?? 0;
                    $att = $marks_result[$sid]['attendance_marks'] ?? 0;
                    $prac = $marks_result[$sid]['practical_marks'] ?? 0;
                ?>
                    <tr id="row-<?= $sid ?>">
                        <td class="text-start small"><?= htmlspecialchars($stu['full_name']) ?></td>
                        <td><input type="number" step="0.01" class="form-control ut_obtain readonly" name="ut_obtain[<?= $sid ?>]" value="<?= $ut ?>" readonly></td>
                        <td class="ut_ai text-primary fw-bold"></td>
                        <td><input type="number" step="0.01" class="form-control assessment_raw" name="assessment_raw[<?= $sid ?>]" value="<?= $ass ?>"></td>
                        <td class="assessment_ai text-primary fw-bold"></td>
                        <td><input type="number" step="0.01" class="form-control tutorial" name="tutorial[<?= $sid ?>]" value="<?= $tut ?>"></td>
                        <td><input type="number" step="1" class="form-control attended" name="attended[<?= $sid ?>]" value="<?= $att ?>"></td>
                        <td class="attendance_ai text-primary fw-bold"></td>
                        <td><input type="number" step="0.01" class="form-control practical" name="practical[<?= $sid ?>]" value="<?= $prac ?>"></td>
                        <td class="final fw-bold"></td>
                        <td class="total fw-bold bg-light"></td>
                        <td class="grade grade-cell fw-bold text-danger"></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow">Save Assessment Data</button>
        </div>
    </form>
</div>

<script>
function recalc(row){
    let ut = parseFloat(row.querySelector('.ut_obtain').value)||0;
    let ass = parseFloat(row.querySelector('.assessment_raw').value)||0;
    let tut = parseFloat(row.querySelector('.tutorial').value)||0;
    let att = parseFloat(row.querySelector('.attended').value)||0;
    let prac = parseFloat(row.querySelector('.practical').value)||0;
    let total_att = parseFloat(document.getElementById('total_attendance').value)||30;

    let ut_ai = (ut/50)*5;
    let ass_ai = (ass/100)*15;
    let att_ai = (total_att > 0) ? (att/total_att)*5 : 0;
    let final = ut_ai + ass_ai + tut + att_ai;
    let total = final + prac;

    row.querySelector('.ut_ai').innerText = ut_ai.toFixed(2);
    row.querySelector('.assessment_ai').innerText = ass_ai.toFixed(2);
    row.querySelector('.attendance_ai').innerText = att_ai.toFixed(2);
    row.querySelector('.final').innerText = final.toFixed(2);
    row.querySelector('.total').innerText = total.toFixed(2);

    let percent = (total / 50)*100;
    let grade='F';
    if(percent>=90) grade='A';
    else if(percent>=85) grade='A-';
    else if(percent>=80) grade='B+';
    else if(percent>=75) grade='B';
    else if(percent>=70) grade='B-';
    else if(percent>=65) grade='C+';
    else if(percent>=60) grade='C';
    else if(percent>=55) grade='C-';
    else if(percent>=50) grade='D+';
    row.querySelector('.grade').innerText = grade;
}

document.querySelectorAll("input").forEach(i=>{
    i.addEventListener("input",()=>document.querySelectorAll("tbody tr").forEach(tr=>recalc(tr)));
});

window.onload = ()=>document.querySelectorAll("tbody tr").forEach(tr=>recalc(tr));
</script>
</body>
</html>