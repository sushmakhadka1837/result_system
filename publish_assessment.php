<?php
session_start();
require 'db_config.php';
require 'ai_functions.php';

$subject_id = intval($_GET['subject_id'] ?? 0);
$students_q = $conn->query("SELECT s.*, r.ut_obtain FROM students s
LEFT JOIN results r ON s.id=r.student_id AND r.subject_id=$subject_id
ORDER BY s.full_name ASC");

if($_SERVER['REQUEST_METHOD']==='POST'){
    $total_days = intval($_POST['total_days']);
    foreach($students_q as $s){
        $sid = $s['id'];
        $ut_ob = $_POST['ut_obtain'][$sid] ?? 0;
        $ut_ai = aiPredictUT($ut_ob);

        $ass_ob = $_POST['assessment'][$sid] ?? 0;
        $ass_ai = aiPredictAssessment15($ass_ob);

        $tutorial = $_POST['tutorial'][$sid] ?? 0;
        $attended = $_POST['attended'][$sid] ?? 0;
        $attendance_marks = ($attended/$total_days)*5;
        $practical = $_POST['practical'][$sid] ?? 0;

        $final_theory = $ut_ai + $ass_ai + $tutorial + $attendance_marks;
        $final_total = $final_theory + $practical;
        list($grade,$gp)=calculateGrade50($final_total);

        $stmt = $conn->prepare("INSERT INTO results
        (student_id, subject_id, ut_ai_marks, assessment_obtain, assessment_ai_marks,
         tutorial_marks, attendance_marks, practical_marks, final_theory, final_total, letter_grade, grade_point)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        ut_ai_marks=?, assessment_obtain=?, assessment_ai_marks=?, tutorial_marks=?, attendance_marks=?, practical_marks=?, final_theory=?, final_total=?, letter_grade=?, grade_point=?");
        $stmt->bind_param("iiiddddddssddddddddd",
            $sid,$subject_id,$ut_ai,$ass_ob,$ass_ai,$tutorial,$attendance_marks,$practical,$final_theory,$final_total,$grade,$gp,
            $ut_ai,$ass_ob,$ass_ai,$tutorial,$attendance_marks,$practical,$final_theory,$final_total,$grade,$gp);
        $stmt->execute();
    }
    echo "<script>alert('Assessment Saved');</script>";
}

?>

<h3>Assessment Entry</h3>
<form method="POST">
<label>Total Attendance Days</label><input type="number" name="total_days" value="60">

<table border="1">
<tr>
<th>Student</th><th>UT Obtain</th><th>AI UT</th><th>Assessment Obtain</th><th>AI Assessment</th>
<th>Tutorial</th><th>Attendance Marks</th><th>Practical</th><th>Final Theory</th><th>Total</th><th>Grade</th>
</tr>
<?php foreach($students_q as $s): 
    $ut_ob = $s['ut_obtain'] ?? 0;
    $ut_ai = aiPredictUT($ut_ob);
    $ass_ai = 0; $tutorial=0; $practical=0; $attended=0;
?>
<tr>
<td><?= $s['full_name'] ?></td>
<td><input type="number" name="ut_obtain[<?= $s['id'] ?>]" value="<?= $ut_ob ?>"></td>
<td><?= $ut_ai ?></td>
<td><input type="number" name="assessment[<?= $s['id'] ?>]" value="<?= $ass_ai ?>"></td>
<td><?= $ass_ai ?></td>
<td><input type="number" name="tutorial[<?= $s['id'] ?>]" value="<?= $tutorial ?>"></td>
<td><input type="number" name="attended[<?= $s['id'] ?>]" value="<?= $attended ?>"></td>
<td><input type="number" name="practical[<?= $s['id'] ?>]" value="<?= $practical ?>"></td>
<td><?= $ut_ai+$ass_ai+$tutorial+$attended ?></td>
<td><?= $ut_ai+$ass_ai+$tutorial+$attended+$practical ?></td>
<td><?= calculateGrade50($ut_ai+$ass_ai+$tutorial+$attended+$practical)[0] ?></td>
</tr>
<?php endforeach; ?>
</table>
<button type="submit">Save Assessment</button>
</form>
