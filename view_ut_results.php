<?php
session_start();
require 'db_config.php';

$dept_id = intval($_GET['dept_id'] ?? 0);
$sem_id  = intval($_GET['sem_id'] ?? 0);

if (!$dept_id || !$sem_id) { die("Invalid Parameters."); }

/* --- 1. GRADE TO POINT MAPPING --- */
function getGradePoint($grade) {
    $grade = strtoupper(trim($grade));
    $points = [
        'A' => 4.0, 'A-' => 3.7, 'B+' => 3.3, 'B' => 3.0, 
        'B-' => 2.7, 'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7, 
        'D+' => 1.3, 'D' => 1.0, 'F' => 0.0
    ];
    return $points[$grade] ?? 0.0;
}

/* --- 2. GET SUBJECTS (Master List) --- */
$subjects = [];
$sub_res = $conn->query("SELECT id, subject_name, subject_code, credit_hours, is_elective 
                         FROM subjects_master 
                         WHERE department_id=$dept_id 
                         AND semester_id=$sem_id 
                         AND subject_name NOT LIKE '%Project%' 
                         ORDER BY is_elective ASC, id ASC");
while($s = $sub_res->fetch_assoc()) {
    $subjects[] = $s;
}

/* --- 3. GET STUDENTS WITH ELECTIVE CHOICES --- */
$students = [];
$stu_res = $conn->query("
    SELECT s.id, s.full_name, s.symbol_no, se.elective_option_id 
    FROM students s
    LEFT JOIN student_electives se ON s.id = se.student_id AND se.semester_id = $sem_id
    WHERE s.department_id = $dept_id AND s.batch_year = 2022 
    ORDER BY s.full_name");

while($st = $stu_res->fetch_assoc()) {
    $students[] = $st;
}

/* --- 4. FETCH RESULTS (Mapped by Subject Code) --- */
$marks_map = [];
$res_query = "SELECT student_id, subject_code, ut_obtain, ut_grade 
              FROM results 
              WHERE semester_id = $sem_id";
$res_data = $conn->query($res_query);

while($row = $res_data->fetch_assoc()) {
    $marks_map[$row['student_id']][$row['subject_code']] = [
        'm' => $row['ut_obtain'],
        'g' => $row['ut_grade']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unit Test Result Sheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 12px; color: #333; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        
        /* Table Header */
        .table thead th { 
            background: #1e293b; 
            color: #f8fafc; 
            text-align: center; 
            vertical-align: middle; 
            border-color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Typography & Colors */
        .student-name { font-weight: 600; color: #0f172a; white-space: nowrap; }
        .marks-val { font-weight: 700; color: #2563eb; }
        .grade-val { font-weight: 600; }
        .text-f { color: #dc2626 !important; font-weight: 800; }
        
        /* Elective Empty Cells */
        .empty-cell { background-color: #f1f5f9; }
        
        /* SGPA Column Styling */
        .sgpa-dark { 
            background-color: #0f172a !important; 
            color: #ffffff !important; 
            font-weight: 800; 
            font-size: 14px;
            text-align: center;
        }
        
        .table-hover tbody tr:hover { background-color: #f8fafc; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-white border-0 py-4">
            <h4 class="text-center fw-bold mb-0" style="color: #1e293b;">
                <i class="bi bi-journal-text"></i> UNIT TEST RESULT SHEET
            </h4>
            <p class="text-center text-muted mb-0">Semester: <?= $sem_id ?> | Batch: 2022</p>
        </div>
        
        <div class="table-responsive p-3">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th rowspan="2" style="min-width: 180px;">Student Name</th>
                        <th rowspan="2">Symbol No.</th>
                        <?php foreach($subjects as $s): ?>
                            <th colspan="2">
                                <?= htmlspecialchars($s['subject_name']) ?><br>
                                <span class="badge bg-secondary mt-1" style="font-size: 9px;"><?= $s['subject_code'] ?></span>
                            </th>
                        <?php endforeach; ?>
                        <th rowspan="2" class="sgpa-dark" style="width: 100px;">SGPA</th>
                    </tr>
                    <tr>
                        <?php foreach($subjects as $s): ?>
                            <th style="font-size: 10px; background: #334155;">Marks</th>
                            <th style="font-size: 10px; background: #334155;">Grade</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $stu): 
                        $total_weighted_points = 0;
                        $total_credits = 0;
                        $failed_any = false;
                    ?>
                    <tr>
                        <td class="student-name px-3"><?= htmlspecialchars($stu['full_name']) ?></td>
                        <td class="text-center fw-bold text-muted"><?= htmlspecialchars($stu['symbol_no']) ?></td>
                        
                        <?php foreach($subjects as $s): 
                            $scode = $s['subject_code'];
                            $credit = floatval($s['credit_hours']);
                            
                            // Elective Logic: Show only if student has selected it
                            $is_active_subject = true;
                            if ($s['is_elective'] == 1) {
                                if ($s['id'] != $stu['elective_option_id']) {
                                    $is_active_subject = false;
                                }
                            }

                            if (!$is_active_subject): ?>
                                <td class="empty-cell"></td>
                                <td class="empty-cell"></td>
                            <?php else: 
                                $data = $marks_map[$stu['id']][$scode] ?? null;
                                $display_m = $data['m'] ?? '-';
                                $display_g = $data['g'] ?? '-';
                                
                                if (strtoupper($display_g) === 'F') $failed_any = true;

                                if ($display_g !== '-' && $display_g !== 'F') {
                                    $gp = getGradePoint($display_g);
                                    $total_weighted_points += ($gp * $credit);
                                    $total_credits += $credit;
                                } elseif ($display_g === 'F') {
                                    $total_credits += $credit;
                                }
                            ?>
                                <td class="text-center marks-val"><?= $display_m ?></td>
                                <td class="text-center grade-val <?= ($display_g == 'F') ? 'text-f' : '' ?>">
                                    <?= $display_g ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <td class="sgpa-dark">
                            <?php 
                                if ($failed_any) {
                                    echo '<span class="text-warning">FAIL</span>';
                                } elseif ($total_credits > 0) {
                                    echo number_format($total_weighted_points / $total_credits, 2);
                                } else {
                                    echo '-';
                                }
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