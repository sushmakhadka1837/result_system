<?php
session_start();
require 'db_config.php';
require 'vendor/autoload.php'; // Dompdf autoload

use Dompdf\Dompdf;

if(!isset($_SESSION['student_id'])){
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

/* ===============================
   Fetch Student Details
================================ */
$student_q = $conn->prepare("
    SELECT s.full_name, s.symbol_no, s.batch_year, 
           d.department_name, sem.semester_name
    FROM students s
    JOIN departments d ON s.department_id = d.id
    JOIN semesters sem ON s.semester_id = sem.id
    WHERE s.id = ?
");
$student_q->bind_param("i", $student_id);
$student_q->execute();
$student = $student_q->get_result()->fetch_assoc();

if(!$student){
    die("Student not found.");
}

/* ===============================
   Fetch UT Results
================================ */
$results_q = $conn->prepare("
    SELECT r.subject_code, r.ut_obtain, r.ut_grade, r.credit_hours, sm.subject_name
    FROM results r
    LEFT JOIN subjects_master sm ON r.subject_code = sm.subject_code
    WHERE r.student_id = ?
    ORDER BY r.subject_code
");
$results_q->bind_param("i", $student_id);
$results_q->execute();
$results = $results_q->get_result();

/* ===============================
   GPA Mapping Function
================================ */
function gradePoint($grade){
    switch($grade){
        case 'A': return 4.0;
        case 'A-': return 3.7;
        case 'B+': return 3.3;
        case 'B': return 3.0;
        case 'B-': return 2.7;
        case 'C+': return 2.3;
        case 'C': return 2.0;
        case 'C-': return 1.7;
        case 'D+': return 1.3;
        case 'D': return 1.0;
        case 'F': return 0.0;
        default: return 0.0;
    }
}

/* ===============================
   HTML Marksheet Design
================================ */
$total_credit = 0;
$total_points = 0;
$has_fail = false;

$html = '
<style>
body{ font-family: DejaVu Sans; }
h2{text-align:center; color:#007bff;}
table{ width:100%; border-collapse:collapse; margin-top:15px;}
th,td{ border:1px solid #000; padding:8px; text-align:center;}
.header{ margin-bottom:20px;}
</style>

<h2>Pokhara Engineering College</h2>
<p style="text-align:center;">Official UT Marksheet</p>

<div class="header">
<b>Name:</b> '.$student['full_name'].'<br>
<b>Symbol No:</b> '.$student['symbol_no'].'<br>
<b>Department:</b> '.$student['department_name'].'<br>
<b>Semester:</b> '.$student['semester_name'].'<br>
<b>Batch Year:</b> '.$student['batch_year'].'
</div>

<table>
<tr>
    <th>S.No</th>
    <th>Subject Code</th>
    <th>Subject Name</th>
    <th>Credit Hours</th>
    <th>Obtain Marks</th>
    <th>Grade</th>
    <th>Grade Point</th>
</tr>
';

$i = 1;
while($res = $results->fetch_assoc()){
    $credit = floatval($res['credit_hours']);
    $grade = $res['ut_grade'] ?? 'F';
    $gp = gradePoint($grade);

    if($grade == 'F') $has_fail = true;

    $total_credit += $credit;
    $total_points += ($gp * $credit);

    $html .= '
    <tr>
        <td>'.$i.'</td>
        <td>'.$res['subject_code'].'</td>
        <td>'.$res['subject_name'].'</td>
        <td>'.$credit.'</td>
        <td>'.$res['ut_obtain'].'</td>
        <td>'.$grade.'</td>
        <td>'.$gp.'</td>
    </tr>';
    $i++;
}

$html .= '</table>';

$html .= '<p style="margin-top:20px;"><b>Total Credit Hours:</b> '.$total_credit.'</p>';
$html .= '<p><b>SCGPA:</b> '.($total_credit>0 ? ($has_fail ? "N/A (Failed)" : number_format($total_points/$total_credit, 2)) : "N/A").'</p>';

$html .= '<p style="margin-top:40px;"><b>Result Published By:</b> Examination Department<br>
<b>Date:</b> '.date("Y-m-d").'</p>';

/* ===============================
   Generate PDF
================================ */
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

/* ===============================
   Force Download
================================ */
$dompdf->stream("UT_Marksheet_".$student['symbol_no'].".pdf", [
    "Attachment" => true
]);
exit();
