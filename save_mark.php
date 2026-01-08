<?php
include 'db.php';
include 'ai_functions.php';

$student = $_POST['student'];
$ut_obtain = $_POST['ut_obtain'];
$assessment = $_POST['assessment'];
$tutorial = $_POST['tutorial'];
$attended = $_POST['attended'];
$total_days = $_POST['total_days'];
$practical = $_POST['practical'];

$ut_ai = ai_ut_marks($ut_obtain,50);
$assessment_ai = ai_assessment_marks($assessment);
$attendance_ai = ai_attendance_marks($attended,$total_days);

$final_theory = $ut_ai + $assessment_ai + $tutorial + $attendance_ai;
$grand_total = $final_theory + $practical;
$grade = calculate_grade($grand_total);

$conn->query("
INSERT INTO marks VALUES(
NULL,'$student',50,'$ut_obtain','$ut_ai',
'$assessment','$assessment_ai',
'$tutorial','$attendance_ai',
'$practical','$final_theory','$grand_total','$grade'
)
");

header("Location: marks_entry.php");
