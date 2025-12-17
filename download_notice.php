<?php
ob_start();
require 'db_config.php';
require 'vendor/autoload.php'; // TCPDF

use TCPDF;

// Get notice ID
$notice_id = $_GET['id'] ?? null;
if(!$notice_id){
    die("Invalid Notice ID.");
}

// Fetch notice
$stmt = $conn->prepare("
    SELECT n.title, n.message, n.created_at, 
           t.full_name AS teacher_name, d.department_name
    FROM notices n
    JOIN teachers t ON n.teacher_id = t.id
    JOIN departments d ON n.department_id = d.id
    WHERE n.id = ?
");
$stmt->bind_param("i", $notice_id);
$stmt->execute();
$result = $stmt->get_result();
$notice = $result->fetch_assoc();

if(!$notice){
    die("Notice not found.");
}

// Create PDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('PEC Result Hub');
$pdf->SetTitle($notice['title']);
$pdf->AddPage();

// Logo
$pdf->Image('images/logopec.jpg', 80, 10, 50);

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Ln(25);
$pdf->Cell(0, 10, $notice['title'], 0, 1, 'C');

// Meta info
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(2);
$pdf->Cell(0, 6, "Date: ".date('d M, Y', strtotime($notice['created_at'])), 0, 1, 'C');
$pdf->Cell(0, 6, "Department: ".$notice['department_name'], 0, 1, 'C');
$pdf->Cell(0, 6, "Published By: ".$notice['teacher_name'], 0, 1, 'C');
$pdf->Ln(5);

// Notice message
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 6, $notice['message'], 0, 'L', 0, 1, '', '', true);

// Output PDF for download
$pdf->Output('Notice_'.$notice_id.'.pdf', 'D');
