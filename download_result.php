<?php
ob_start();
session_start();
require 'db_config.php';
require 'vendor/autoload.php'; // TCPDF

// no 'use TCPDF;' line

$student_id = $_SESSION['student_id'] ?? 0;
if(!$student_id){
    die("Not logged in.");
}

// rest of code...

$student_id = $_SESSION['student_id'];

// Fetch student details
$student = $conn->query("SELECT * FROM students WHERE id=$student_id")->fetch_assoc();
$dept_id = $student['department_id'] ?? 0;
$current_semester = $student['semester'] ?? 0;

// Fetch subjects & results
$subjects = $conn->query("SELECT * FROM subjects_master WHERE department_id=$dept_id AND semester_id=$current_semester ORDER BY subject_name ASC");
$results_arr = [];
$result_query = $conn->query("SELECT * FROM results WHERE student_id=$student_id");
while($row = $result_query->fetch_assoc()){
    $results_arr[$row['subject_id']] = $row;
}

// Create new PDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Hamro Result');
$pdf->SetTitle('Semester '.$current_semester.' Result');
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Result Sheet - Semester '.$current_semester, 0, 1, 'C');
$pdf->Ln(5);

// Student Info
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 6, 'Student Name: '.($student['name'] ?? 'N/A'), 0, 1);
$pdf->Cell(0, 6, 'Symbol No: '.($student['symbol_no'] ?? 'N/A'), 0, 1);
$pdf->Cell(0, 6, 'Semester: '.$current_semester, 0, 1);
$pdf->Cell(0, 6, 'Faculty: '.($student['faculty'] ?? 'N/A'), 0, 1);
$pdf->Ln(5);

// Table Header
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(10, 7, 'S.No', 1);
$pdf->Cell(25, 7, 'Code', 1);
$pdf->Cell(70, 7, 'Course Title', 1);
$pdf->Cell(15, 7, 'Credit', 1);
$pdf->Cell(20, 7, 'Marks', 1);
$pdf->Cell(40, 7, 'Remarks', 1);
$pdf->Ln();

// Table Body
$pdf->SetFont('helvetica', '', 12);
$i = 1;
while($sub = $subjects->fetch_assoc()){
    $res = $results_arr[$sub['id']] ?? null;

    if($res){
        if($res['marks_type'] == 'Unit Test'){
            $marks_display = $res['ut_marks'] ?? 0;
            $remarks = "Attendance: ".$res['attendance_days']." days";
        } else {
            $marks_display = ($res['assignment'] ?? 0)
                           + ($res['internal_project'] ?? 0)
                           + ($res['internal_presentation'] ?? 0)
                           + ($res['internal_other'] ?? 0)
                           + ($res['practical'] ?? 0)
                           + ($res['theory'] ?? 0);
            $remarks = "Attendance: ".$res['attendance_days']." days";
        }
    } else {
        $marks_display = 0;
        $remarks = "";
    }

    $pdf->Cell(10, 7, $i++, 1);
    $pdf->Cell(25, 7, $sub['subject_code'], 1);
    $pdf->Cell(70, 7, $sub['subject_name'], 1);
    $pdf->Cell(15, 7, $sub['credit_hours'], 1);
    $pdf->Cell(20, 7, $marks_display, 1);
    $pdf->Cell(40, 7, $remarks, 1);
    $pdf->Ln();
}

// Output PDF for download
$pdf->Output('Result_Semester_'.$current_semester.'.pdf', 'D');
