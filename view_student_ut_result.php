<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id'])){
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// 1. Fetch Student and Department Details
$student_q = $conn->query("
    SELECT s.*, d.department_name 
    FROM students s
    JOIN departments d ON s.department_id = d.id
    WHERE s.id = $student_id
");
$student = $student_q->fetch_assoc();

// 2. Determine Max Semester (Architecture = 10, Others = 8)
$max_sem = (stripos($student['department_name'], 'Architecture') !== false) ? 10 : 8;

// 3. Handle Semester Filter
$selected_sem = isset($_GET['sem_id']) ? intval($_GET['sem_id']) : $student['current_semester'];

// 4. Fetch Results
$results_sql = "
    SELECT 
        sm.subject_name, 
        sm.subject_code, 
        sm.credit_hours, 
        r.ut_obtain, 
        r.ut_grade
    FROM results r
    INNER JOIN subjects_master sm ON r.subject_id = sm.id 
    WHERE r.student_id = $student_id 
    AND r.semester_id = $selected_sem 
    AND sm.subject_type != 'Project'
    ORDER BY sm.id ASC
";
$results_q = $conn->query($results_sql);

if (!function_exists('gradePoint')) {
    function gradePoint($grade){
        $grade = strtoupper(trim($grade ?? ''));
        $points = ['A'=>4.0, 'A-'=>3.7, 'B+'=>3.3, 'B'=>3.0, 'B-'=>2.7, 'C+'=>2.3, 'C'=>2.0, 'C-'=>1.7, 'D+'=>1.3, 'D'=>1.0, 'F'=>0.0];
        return $points[$grade] ?? 0.0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marksheet_<?= $student['symbol_no'] ?>_Sem<?= $selected_sem ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .marksheet-card { background: white; border-radius: 15px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); border: none; position: relative; }
        .header-blue { background: #1a237e; color: white !important; padding: 30px; border-radius: 15px 15px 0 0; }
        .badge-grade { padding: 6px 12px; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>

<div class="d-print-none">
    <?php include 'student_header.php'; ?>
</div>

<div class="container my-5">
    
    <div class="filter-section d-print-none mb-4">
        <div class="card p-4 border-0 shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-5">
                    <form method="GET" id="semForm">
                        <label class="form-label fw-bold text-muted small">SELECT SEMESTER</label>
                        <select name="sem_id" class="form-select border-primary" onchange="this.form.submit()">
                            <?php for($i=1; $i<=$max_sem; $i++): ?>
                                <option value="<?= $i ?>" <?= ($selected_sem == $i) ? 'selected' : '' ?>>
                                    Semester <?= $i ?> <?= ($i == $student['current_semester']) ? '(Current)' : '' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </form>
                </div>
                <div class="col-md-7 text-end pt-4">
                    <a href="download_marksheet.php?sem_id=<?= $selected_sem ?>" class="btn btn-primary shadow-sm px-4">
                        <i class="bi bi-file-earmark-pdf-fill me-2"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="marksheet-card overflow-hidden">
        <div class="header-blue d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1 text-white text-uppercase">Academic Transcript (Internal)</h2>
                <p class="mb-0 opacity-75">Pokhara Engineering College | Unit Test Results</p>
            </div>
            <div class="text-end d-none d-md-block">
                <h5 class="mb-0 fw-bold">Semester: <?= $selected_sem ?></h5>
                <small class="opacity-75">Date: <?= date('Y-m-d') ?></small>
            </div>
        </div>

        <div class="p-4 border-bottom bg-light">
            <div class="row g-3 text-center">
                <div class="col-md-3"> <small class="text-muted d-block small">NAME</small> <strong><?= htmlspecialchars($student['full_name']) ?></strong> </div>
                <div class="col-md-3"> <small class="text-muted d-block small">SYMBOL NO</small> <strong><?= htmlspecialchars($student['symbol_no']) ?></strong> </div>
                <div class="col-md-3"> <small class="text-muted d-block small">DEPARTMENT</small> <strong><?= htmlspecialchars($student['department_name']) ?></strong> </div>
                <div class="col-md-3"> <small class="text-muted d-block small">BATCH</small> <strong><?= $student['batch_year'] ?></strong> </div>
            </div>
        </div>

        <div class="p-4">
            <table class="table table-bordered align-middle">
                <thead class="table-light text-center">
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
                    if($results_q && $results_q->num_rows > 0):
                        while($row = $results_q->fetch_assoc()):
                            $cr = (float)$row['credit_hours'];
                            $gr = $row['ut_grade'] ?? 'N/A';
                            $gp = gradePoint($gr);
                            $total_cr += $cr;
                            $total_gp += ($cr * $gp);
                    ?>
                    <tr class="text-center">
                        <td><?= $sn++ ?></td>
                        <td class="text-start fw-bold"><?= htmlspecialchars($row['subject_name']) ?></td>
                        <td><?= $row['subject_code'] ?></td>
                        <td><?= $cr ?></td>
                        <td class="fw-bold"><?= number_format($row['ut_obtain'], 2) ?></td>
                        <td><span class="badge-grade border"><?= $gr ?></span></td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center py-5">Result Not Published.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if($total_cr > 0): ?>
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    * This is a computer generated internal report.
                </div>
                <div class="p-3 rounded bg-dark text-white text-center shadow" style="min-width: 150px;">
                    <small class="d-block opacity-75">UT GPA</small>
                    <h4 class="mb-0 fw-bold"><?= number_format($total_gp / $total_cr, 2) ?></h4>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="d-print-none">
    <?php include 'target.php'; ?>
     <?php include 'ai_predictive_marks.php'; ?>
 

    <?php include 'footer.php'; ?>
</div>

</body>
</html>