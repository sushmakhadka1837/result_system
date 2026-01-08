<?php
session_start();
require 'db_config.php';

$dept_id     = intval($_GET['dept'] ?? 0);
$sem         = intval($_GET['sem'] ?? 0);
$result_type = $_GET['type'] ?? 'ut'; 

if (!$dept_id || !$sem) {
    die("<h3 style='color:red;text-align:center; margin-top:50px;'>Invalid parameters.</h3>");
}

// Fetch Department Name for Header
$dept_q = $conn->query("SELECT department_name FROM departments WHERE id = $dept_id");
$dept_info = $dept_q->fetch_assoc();

/* ---------- 1. FETCH UNIQUE SUBJECTS ---------- */
$subjects = [];
$sub_q = $conn->prepare("
    SELECT DISTINCT sm.id AS subject_master_id, sm.subject_name, sm.subject_code
    FROM subjects_department_semester sds
    JOIN subjects_master sm ON sds.subject_id = sm.id
    WHERE sds.department_id = ? AND sds.semester_id = ?
");
$sub_q->bind_param("ii", $dept_id, $sem);
$sub_q->execute();
$res = $sub_q->get_result();
while ($row = $res->fetch_assoc()) $subjects[] = $row;

/* ---------- 2. FETCH STUDENTS & ORGANIZED RESULTS ---------- */
$organized_results = [];
$sql = "SELECT s.id as student_id, s.symbol_no, r.subject_id, r.ut_grade, r.grade_point, r.final_theory, r.practical_marks 
        FROM students s 
        LEFT JOIN results r ON s.id = r.student_id 
        WHERE s.department_id = ? AND s.semester = ?
        ORDER BY s.symbol_no ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $dept_id, $sem);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $sid = $row['student_id'];
    $organized_results[$sid]['symbol_no'] = $row['symbol_no'];
    // Result table ko subject_id mathi ko subject_master_id sanga match hunuparchha
    $organized_results[$sid]['marks'][$row['subject_id']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger - Sem <?= $sem ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <style>
        :root { --primary: #4361ee; --dark: #1e293b; --bg: #f8fafc; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        .ledger-container { max-width: 1400px; margin: 2rem auto; padding: 0 15px; }
        .ledger-card { 
            background: white; border-radius: 16px; 
            border: 1px solid #e2e8f0; overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .header-section { 
            background: white; padding: 30px; 
            border-bottom: 2px solid #f1f5f9; text-align: center;
        }
        .clg-name { font-weight: 800; letter-spacing: -0.5px; color: var(--dark); font-size: 1.5rem; }
        .badge-info { background: #eff6ff; color: #3b82f6; padding: 6px 16px; border-radius: 50px; font-weight: 600; font-size: 0.85rem; }

        .table-responsive { padding: 0; }
        .table { margin-bottom: 0; border-collapse: separate; border-spacing: 0; }
        .table thead th { 
            background: #f8fafc; color: #64748b; font-size: 0.75rem;
            text-transform: uppercase; letter-spacing: 0.05em; padding: 15px;
            border-bottom: 2px solid #e2e8f0; text-align: center;
        }
        .table tbody td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        
        .symbol-no { font-weight: 700; color: var(--primary); }
        .grade-badge { 
            padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;
            display: inline-block; min-width: 35px;
        }
        .grade-A { background: #dcfce7; color: #166534; }
        .grade-F { background: #fee2e2; color: #991b1b; }
        .grade-NQ { background: #fef3c7; color: #92400e; }
        
        @media print {
            .btn-print, .header-section button { display: none; }
            .ledger-card { border: none; box-shadow: none; }
            body { background: white; }
        }
    </style>
</head>
<body>

<div class="ledger-container">
    <div class="ledger-card">
        <div class="header-section">
            <h2 class="clg-name mb-1 text-uppercase">Pokhara Engineering College</h2>
            <p class="text-muted mb-3 fw-medium"><?= $dept_info['department_name'] ?> - Result Ledger</p>
            <div class="d-flex justify-content-center gap-2 mb-4">
                <span class="badge-info">Semester: <?= $sem ?></span>
                <span class="badge-info">Exam: <?= strtoupper($result_type) ?></span>
            </div>
            <button class="btn btn-dark px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="fas fa-print me-2"></i> Print Official Ledger
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 60px;">SN</th>
                        <th style="width: 150px;">Symbol No</th>
                        <?php foreach ($subjects as $sub): ?>
                            <th><?= htmlspecialchars($sub['subject_name']) ?></th>
                        <?php endforeach; ?>
                        <th style="background: #f1f5f9; color: var(--dark);">GPA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sn = 1;
                    foreach ($organized_results as $student_id => $data): 
                        $total_points = 0; $count = 0; $is_failed = false;
                    ?>
                    <tr>
                        <td class="text-center text-muted"><?= $sn++ ?></td>
                        <td class="text-center"><span class="symbol-no"><?= htmlspecialchars($data['symbol_no']) ?></span></td>
                        
                        <?php foreach ($subjects as $sub): 
                            $m = $data['marks'][$sub['subject_master_id']] ?? null;
                            
                            if ($result_type == 'ut') {
                                $display = $m['ut_grade'] ?? '-';
                                $gp = $m['grade_point'] ?? 0;
                            } else {
                                $th = $m['final_theory'] ?? 0;
                                $pr = $m['practical_marks'] ?? 0;
                                $display = ($th + $pr) ?: '-';
                                $gp = 0; 
                            }

                            if ($display == 'F') $is_failed = true;
                            if ($gp > 0) { $total_points += $gp; $count++; }
                            
                            // Styling grades
                            $gClass = '';
                            if($display == 'F') $gClass = 'grade-F';
                            else if($display != '-' && $result_type == 'ut') $gClass = 'grade-A';
                        ?>
                            <td class="text-center">
                                <span class="grade-badge <?= $gClass ?>"><?= $display ?></span>
                            </td>
                        <?php endforeach; ?>

                        <td class="text-center fw-bold" style="background: #f8fafc;">
                            <?php 
                                if ($is_failed) echo "<span class='badge grade-F'>NQ</span>";
                                else if ($count > 0) {
                                    $gpa = round($total_points / $count, 2);
                                    echo "<span class='text-primary'>$gpa</span>";
                                } else echo "-";
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>