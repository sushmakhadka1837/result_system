<?php
session_start();
require 'db_config.php';

$dept_id = intval($_GET['dept_id'] ?? 0);
$sem_id  = intval($_GET['sem_id'] ?? 0);
$batch   = 2022; 

if (!$dept_id || !$sem_id) { die("Invalid Parameters."); }

/* --- 1. GRADE TO POINT MAPPING --- */
function getGradePoint($grade) {
    $grade = strtoupper(trim($grade ?? ''));
    $points = ['A'=>4.0, 'A-'=>3.7, 'B+'=>3.3, 'B'=>3.0, 'B-'=>2.7, 'C+'=>2.3, 'C'=>2.0, 'C-'=>1.7, 'D+'=>1.3, 'D'=>1.0, 'F'=>0.0];
    return $points[$grade] ?? 0.0;
}

/* --- 2. GET SUBJECTS --- */
$subjects = [];
$sub_res = $conn->query("SELECT id, subject_name, subject_code, credit_hours, is_elective 
                         FROM subjects_master 
                         WHERE department_id=$dept_id AND semester_id=$sem_id 
                         AND subject_name NOT LIKE '%Project%' 
                         ORDER BY is_elective ASC, id ASC");
while($s = $sub_res->fetch_assoc()) { $subjects[] = $s; }

/* --- 3. GET STUDENTS --- */
$students = [];
$stu_res = $conn->query("SELECT id, full_name, symbol_no FROM students 
                         WHERE department_id=$dept_id AND batch_year=$batch ORDER BY full_name");
while($st = $stu_res->fetch_assoc()) { $students[] = $st; }

/* --- 4. FETCH RESULTS --- */
$marks_map = [];
$res_query = "SELECT student_id, subject_id, final_theory, practical_marks, letter_grade FROM results WHERE semester_id=$sem_id";
$res_data = $conn->query($res_query);
while($row = $res_data->fetch_assoc()) {
    $marks_map[$row['student_id']][$row['subject_id']] = $row;
}

/* --- 5. FETCH ELECTIVE CHOICES --- */
$student_choices = [];
$elec_q = $conn->query("SELECT student_id, elective_option_id FROM student_electives 
                        WHERE semester_id=$sem_id AND department_id=$dept_id");
while($r = $elec_q->fetch_assoc()) {
    $student_choices[$r['student_id']][] = $r['elective_option_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assessment Result - Sem <?= $sem_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; font-size: 11px; }
        .table-card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 15px; margin-top: 10px; }
        .table thead th { background: #1e293b; color: #f8fafc; border: 1px solid #334155; text-align: center; vertical-align: middle; }
        .student-col { font-weight: 600; color: #0f172a; text-align: left !important; min-width: 140px; }
        .marks-th { color: #2563eb; }
        .marks-pr { color: #16a34a; }
        .grade-badge { font-weight: 700; color: #1e293b; }
        .not-selected { background: #f8fafc; color: #cbd5e1; font-style: italic; }
        .sgpa-col { background: #e2e8f0 !important; font-weight: 800; color: #0f172a; border: 2px solid #94a3b8 !important; }
    </style>
</head>
<body class="p-3">

<div class="container-fluid">
    <div class="table-card">
        <h5 class="text-center fw-bold mb-3">ASSESSMENT SHEET - SEMESTER <?= $sem_id ?></h5>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th rowspan="2">Student Name</th>
                        <th rowspan="2">Symbol</th>
                        <?php foreach($subjects as $s): ?>
                            <th colspan="3"><?= htmlspecialchars($s['subject_name']) ?></th>
                        <?php endforeach; ?>
                        <th rowspan="2" class="sgpa-col">SGPA</th>
                    </tr>
                    <tr>
                        <?php foreach($subjects as $s): ?>
                            <th style="font-size: 9px;">TH</th>
                            <th style="font-size: 9px;">PR</th>
                            <th style="font-size: 9px;">GR</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $stu): 
                        $sg_pts = 0; $sg_cr = 0;
                        $sid_st = $stu['id'];
                        $choices = $student_choices[$sid_st] ?? [];
                    ?>
                    <tr>
                        <td class="student-col"><?= htmlspecialchars($stu['full_name']) ?></td>
                        <td class="text-center text-muted"><?= $stu['symbol_no'] ?></td>
                        
                        <?php foreach($subjects as $s): 
                            $sub_id = $s['id'];
                            
                            $show_marks = true;
                            if ($s['is_elective'] == 1) {
                                if (!in_array($sub_id, $choices)) { $show_marks = false; }
                            }

                            if (!$show_marks): ?>
                                <td colspan="3" class="text-center not-selected">-</td>
                            <?php else: 
                                $r = $marks_map[$sid_st][$sub_id] ?? null;
                                if($r) {
                                    $gp = getGradePoint($r['letter_grade']);
                                    $sg_pts += ($gp * $s['credit_hours']);
                                    $sg_cr += $s['credit_hours'];
                                }
                            ?>
                                <td class="text-center marks-th"><?= $r['final_theory'] ?? '-' ?></td>
                                <td class="text-center marks-pr"><?= $r['practical_marks'] ?? '-' ?></td>
                                <td class="text-center grade-badge"><?= $r['letter_grade'] ?? '-' ?></td>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <td class="text-center sgpa-col">
                            <?= ($sg_cr > 0) ? number_format($sg_pts / $sg_cr, 2) : '0.00' ?>
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