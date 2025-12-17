<?php
require 'db_config.php';
session_start();

$subject_id = $_POST['subject_id'];
$full = $_POST['full_marks'];
$scaled = $_POST['scaled_marks'];
$pass = $_POST['pass_marks'];
$total_att_days = $_POST['total_attendance_days'];

foreach ($_POST['assignment'] as $sid => $a) {

$assignment = $_POST['assignment'][$sid] ?? 0;
$practical = $_POST['practical'][$sid] ?? 0;
$attendance = $_POST['attendance'][$sid] ?? 0;
$other = $_POST['other'][$sid] ?? 0;

$external = $_POST['external'][$sid] ?? 0;
$attendance_days = $_POST['attendance_days'][$sid] ?? 0;

$internal = $assignment + $practical + $attendance + $other;
$external_scaled = ($external / 100) * $scaled;
$final = $internal + $external_scaled;

// INSERT
$conn->query("
INSERT INTO subject_marks
(student_id, subject_id, assignment, practical, attendance_marks, other, external_marks, external_scaled, internal_total, final_total, attendance_days, full_marks, pass_marks, scaled_marks, total_attendance_days)
VALUES('$sid', '$subject_id', '$assignment', '$practical', '$attendance', '$other', '$external', '$external_scaled', '$internal', '$final', '$attendance_days', '$full', '$pass', '$scaled', '$total_att_days')
");
}

echo "<script>alert('Marks Saved Successfully'); window.location='teacher_dashboard.php';</script>";
