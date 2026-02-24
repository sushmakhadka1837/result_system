<?php
session_start();
require_once 'db_config.php';

$f_dept  = isset($_GET['dept']) ? intval($_GET['dept']) : 0;
$f_batch = isset($_GET['batch']) ? intval($_GET['batch']) : 0;
$f_sem   = isset($_GET['sem']) ? intval($_GET['sem']) : 0;

if (!$f_dept || !$f_batch || !$f_sem) {
    die("Invalid Access: Missing Filters.");
}

// 1. Department Name fetch
$dept_res = $conn->query("SELECT department_name FROM departments WHERE id = $f_dept");
$dept_row = $dept_res->fetch_assoc();
$dept_name = $dept_row ? $dept_row['department_name'] : 'N/A';

// Convert student batch to syllabus flag (1=New, NULL/2=Old)
$syllabus_flag = ($f_batch >= 2023) ? 1 : 'NULL';

// 2. Fetch unique subjects (Project excluded) - FILTERED BY BATCH
if($syllabus_flag === 'NULL') {
    $sub_query = "SELECT DISTINCT sub.id, sub.subject_code, sub.subject_name, sub.credit_hours 
                  FROM results r
                  JOIN subjects_master sub ON r.subject_id = sub.id
                  LEFT JOIN subjects_department_semester sds ON sub.id = sds.subject_id
                  WHERE r.semester_id = $f_sem 
                  AND sub.subject_name NOT LIKE '%Project%'
                  AND (sds.syllabus_flag IS NULL OR sds.syllabus_flag = 2)
                  ORDER BY sub.subject_code ASC";
} else {
    $sub_query = "SELECT DISTINCT sub.id, sub.subject_code, sub.subject_name, sub.credit_hours 
                  FROM results r
                  JOIN subjects_master sub ON r.subject_id = sub.id
                  LEFT JOIN subjects_department_semester sds ON sub.id = sds.subject_id
                  WHERE r.semester_id = $f_sem 
                  AND sub.subject_name NOT LIKE '%Project%'
                  AND sds.syllabus_flag = $syllabus_flag
                  ORDER BY sub.subject_code ASC";
}
$sub_result = $conn->query($sub_query);
$subjects = [];
while($s = $sub_result->fetch_assoc()) {
    $subjects[] = $s;
}

// 3. Students List
$stu_query = "SELECT DISTINCT s.id, s.full_name, s.symbol_no 
              FROM students s
              JOIN results r ON s.id = r.student_id
              WHERE s.department_id = $f_dept AND s.batch_year = $f_batch AND r.semester_id = $f_sem
              ORDER BY s.symbol_no ASC";
$students = $conn->query($stu_query);

// 4. Marks Data
$marks_data = [];
$all_marks = $conn->query("SELECT * FROM results WHERE semester_id = $f_sem");
while($m = $all_marks->fetch_assoc()) {
    $marks_data[$m['student_id']][$m['subject_id']] = $m;
}

// 5. Letter Grade Helper
function getFinalGrade($gpa) {
    if($gpa >= 4.0) return 'A';
    if($gpa >= 3.7) return 'A-';
    if($gpa >= 3.3) return 'B+';
    if($gpa >= 3.0) return 'B';
    if($gpa >= 2.7) return 'B-';
    if($gpa >= 2.3) return 'C+';
    if($gpa >= 2.0) return 'C';
    if($gpa >= 1.7) return 'C-';
    if($gpa >= 1.0) return 'D+';
    return 'F';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tabulation Sheet - Sem <?= $f_sem ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @media print { 
            .no-print { display: none; } 
            body { background: white; padding: 0; }
            @page { size: A4 landscape; margin: 10mm; }
            .print-shadow-none { box-shadow: none !important; border: 1px solid #000 !important; }
        }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #1e293b !important; }
    </style>
</head>
<body class="bg-slate-200 p-4">

    <div class="max-w-[100%] mx-auto bg-white p-6 shadow-2xl print-shadow-none border border-slate-300">
        
        <div class="text-center mb-6 border-b-4 border-slate-900 pb-4">
            <h1 class="text-3xl font-[900] text-slate-900 uppercase tracking-tighter italic"><?= $dept_name ?></h1>
            <div class="flex justify-center gap-10 mt-2">
                <span class="bg-slate-900 text-white px-3 py-1 text-sm font-bold uppercase">Consolidated Tabulation Sheet</span>
                <span class="text-slate-700 font-extrabold text-sm uppercase">Semester: <?= $f_sem ?> | Academic Year: 2081/2026</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead>
                    <tr class="bg-slate-900 text-white border-slate-900">
                        <th rowspan="2" class="p-2 w-24">Symbol No</th>
                        <th rowspan="2" class="p-2 text-left min-w-[180px]">Student Name</th>
                        <?php foreach($subjects as $sub): ?>
                            <th colspan="3" class="p-1 border-l-2 border-slate-700">
                                <div class="text-[10px] font-black uppercase"><?= $sub['subject_name'] ?></div>
                                <div class="text-[9px] font-normal text-slate-300"><?= $sub['subject_code'] ?> (Cr: <?= $sub['credit_hours'] ?>)</div>
                            </th>
                        <?php endforeach; ?>
                        <th rowspan="2" class="p-2 bg-indigo-800 w-28 text-base">SGPA</th>
                    </tr>
                    <tr class="bg-slate-700 text-white text-[9px] uppercase tracking-wider">
                        <?php foreach($subjects as $sub): ?>
                            <th class="p-1 w-10">Theory</th>
                            <th class="p-1 w-10">Prac</th>
                            <th class="p-1 w-10 bg-slate-600 font-bold">GP</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="text-slate-900">
                    <?php while($st = $students->fetch_assoc()): 
                        $weighted_pts = 0;
                        $total_cr = 0;
                        $has_fail = false;
                    ?>
                    <tr class="hover:bg-slate-100 transition-colors border-b border-slate-300">
                        <td class="p-2 text-center font-extrabold bg-slate-50 text-slate-700"><?= $st['symbol_no'] ?></td>
                        <td class="p-2 font-bold uppercase text-slate-900 border-r-2"><?= $st['full_name'] ?></td>
                        
                        <?php foreach($subjects as $sub): 
                            $m = $marks_data[$st['id']][$sub['id']] ?? null;
                            $is_empty = (!$m || ($m['final_theory'] === NULL && $m['practical_marks'] === NULL));

                            if($is_empty): ?>
                                <td class="p-1 text-center text-slate-300">-</td>
                                <td class="p-1 text-center text-slate-300">-</td>
                                <td class="p-1 text-center font-bold text-slate-300">0.00</td>
                            <?php else: 
                                $f_theory = $m['final_theory'] ?? '-';
                                $f_prac   = $m['practical_marks'] ?? '-';
                                $gp = (float)($m['grade_point'] ?? 0.0);
                                $cr = (float)($sub['credit_hours'] ?? 3.0);

                                $weighted_pts += ($gp * $cr);
                                $total_cr += $cr;
                                if(strtoupper(trim($m['letter_grade'] ?? '')) == 'F') $has_fail = true;
                            ?>
                                <td class="p-1 text-center font-medium"><?= $f_theory ?></td>
                                <td class="p-1 text-center font-medium"><?= $f_prac ?></td>
                                <td class="p-1 text-center font-black bg-slate-50 text-indigo-700 border-x border-slate-200"><?= number_format($gp, 2) ?></td>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <td class="p-2 text-center font-black bg-indigo-50 text-indigo-900 border-l-4 border-indigo-200">
                            <?php 
                                if($total_cr > 0) {
                                    $sgpa = $weighted_pts / $total_cr;
                                    if($has_fail) {
                                        echo "<span class='text-red-600 text-xs'>FAIL</span>";
                                    } else {
                                        echo "<span class='text-lg'>" . number_format($sgpa, 2) . "</span>";
                                        echo "<div class='text-[10px] font-extrabold text-indigo-600 tracking-tighter mt-1'>" . getFinalGrade($sgpa) . " Grade</div>";
                                    }
                                } else {
                                    echo "0.00";
                                }
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-between items-end border-t-2 border-slate-200 pt-4">
            <div class="text-[10px] text-slate-500 font-semibold italic">
                * SGPA = Total Weighted Grade Points / Total Credits<br>
                * Note: Project marks and empty elective credits are excluded from calculation.
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-800 text-white font-black px-10 py-3 rounded-lg shadow-xl transition-all uppercase tracking-widest text-xs">
                    Print Tabulation Sheet
                </button>
            </div>
        </div>
    </div>
</body>
</html>