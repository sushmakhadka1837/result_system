<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id'])){
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$selected_sem = isset($_GET['sem_id']) ? intval($_GET['sem_id']) : 1;

// 1. Student & Dept Info
$student_q = $conn->query("
    SELECT s.*, d.department_name 
    FROM students s
    JOIN departments d ON s.department_id = d.id
    WHERE s.id = $student_id
");
$student = $student_q->fetch_assoc();

// 2. Results Query
$results_sql = "
    SELECT sm.subject_name, sm.subject_code, sm.credit_hours, r.ut_obtain, r.ut_grade
    FROM results r
    INNER JOIN subjects_master sm ON r.subject_id = sm.id 
    WHERE r.student_id = $student_id AND r.semester_id = $selected_sem 
    ORDER BY sm.id ASC
";
$results_q = $conn->query($results_sql);

function gradePoint($grade){
    $grade = strtoupper(trim($grade ?? ''));
    $points = ['A'=>4.0, 'A-'=>3.7, 'B+'=>3.3, 'B'=>3.0, 'B-'=>2.7, 'C+'=>2.3, 'C'=>2.0, 'C-'=>1.7, 'D+'=>1.3, 'D'=>1.0, 'F'=>0.0];
    return $points[$grade] ?? 0.0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UT_Marksheet_<?= $student['symbol_no'] ?>_Sem<?= $selected_sem ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: white; padding: 40px; }
        .marksheet-border { border: 2px solid #333; padding: 20px; position: relative; }
        .header-section { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .college-logo { width: 80px; position: absolute; left: 40px; top: 35px; }
        .table-bordered td, .table-bordered th { border: 1px solid #333 !important; }
        .gpa-box { border: 2px solid #333; padding: 10px 20px; display: inline-block; font-weight: bold; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .marksheet-border { border: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container">
    <div class="marksheet-border">
        <img src="college_logo.png" class="college-logo no-print" alt="">
        
        <div class="header-section">
            <h2 class="fw-bold mb-0">Pokhara University</h2>
            <h4 class="mb-0">Pokhara Engineering College</h4>
            <h5 class="text-uppercase mt-2"><?= htmlspecialchars($student['department_name']) ?></h5>
            <hr style="width: 200px; margin: 10px auto; border: 1px solid #000;">
            <h5 class="fw-bold">UNIT TEST MARKSHEET</h5>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <p class="mb-1"><strong>NAME:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
                <p class="mb-1"><strong>SYMBOL NO:</strong> <?= htmlspecialchars($student['symbol_no']) ?></p>
            </div>
            <div class="col-6 text-end">
                <p class="mb-1"><strong>SEMESTER:</strong> <?= $selected_sem ?></p>
                <p class="mb-1"><strong>DATE:</strong> <?= date('Y-m-d') ?></p>
            </div>
        </div>

        <table class="table table-bordered align-middle">
            <thead class="text-center">
                <tr>
                    <th>S.N</th>
                    <th class="text-start">Course Title</th>
                    <th>Code</th>
                    <th>Credit</th>
                    <th>Obtained</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sn=1; $total_cr=0; $total_gp=0;
                while($row = $results_q->fetch_assoc()):
                    $cr = (float)$row['credit_hours'];
                    $gr = $row['ut_grade'] ?? 'N/A';
                    $gp = gradePoint($gr);
                    $total_cr += $cr;
                    $total_gp += ($cr * $gp);
                ?>
                <tr class="text-center">
                    <td><?= $sn++ ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['subject_name']) ?></td>
                    <td><?= $row['subject_code'] ?></td>
                    <td><?= $cr ?></td>
                    <td><?= number_format($row['ut_obtain'], 2) ?></td>
                    <td class="fw-bold"><?= $gr ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="row mt-4 align-items-center">
            <div class="col-8">
                <p class="small">* Note: This is an internal assessment report for Unit Test only.</p>
                <div class="mt-5 pt-3">
                    <span style="border-top: 1px solid #000; padding-top: 5px;">Controller of Examinations</span>
                </div>
            </div>
            <div class="col-4 text-end">
                <div class="gpa-box">
                    SGPA: <?= ($total_cr > 0) ? number_format($total_gp / $total_cr, 2) : '0.00' ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-4 no-print">
        <button onclick="window.history.back()" class="btn btn-secondary">Go Back</button>
    </div>
</div>

</body>
</html>