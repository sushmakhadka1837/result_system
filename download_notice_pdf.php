<?php
require 'db_config.php';
require __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php'; // manual include

$notice_id = intval($_GET['id'] ?? 0);
if(!$notice_id) die('Invalid Notice ID');

$stmt = $conn->prepare("
    SELECT n.*, t.full_name AS teacher_name,
    CASE 
        WHEN n.department_id IS NULL OR n.department_id = 0 THEN 'All Departments'
        ELSE d.department_name
    END AS department_name
    FROM notices n
    JOIN teachers t ON n.teacher_id = t.id
    LEFT JOIN departments d ON n.department_id = d.id
    WHERE n.id = ?
");
$stmt->bind_param("i",$notice_id);
$stmt->execute();
$notice = $stmt->get_result()->fetch_assoc();
if(!$notice) die('Notice not found');

$images = !empty($notice['notice_images']) ? json_decode($notice['notice_images'],true) : [];

$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($notice['teacher_name']);
$pdf->SetTitle($notice['title']);
$pdf->AddPage();

$pdf->SetFont('helvetica','B',16);
$pdf->Cell(0,10,$notice['title'],0,1,'C');

$pdf->SetFont('helvetica','',10);
$pdf->Cell(0,6,'Department: '.$notice['department_name'],0,1);
$pdf->Cell(0,6,'Published By: '.$notice['teacher_name'],0,1);
$pdf->Cell(0,6,'Date: '.date('d M, Y', strtotime($notice['created_at'])),0,1);
if($notice['notice_type']=='internal'){
    $pdf->Cell(0,6,'Semester: '.($notice['semester']==0?'All Semesters':$notice['semester']),0,1);
}

$pdf->Ln(5);

if(!empty($notice['message'])){
    $pdf->SetFont('helvetica','',12);
    $pdf->writeHTML(nl2br(htmlspecialchars_decode($notice['message'])), true, false, true, false, '');
}

if(!empty($images)){
    foreach($images as $img){
        if(file_exists($img)){
            $pdf->AddPage();
            $pdf->Image($img,15,30,180,0,'','','',false,300,'',false,false,1,false,false,false);
        }
    }
}

$pdf->Output('notice_'.$notice['id'].'.pdf','D');
