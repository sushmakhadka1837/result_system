<?php
session_start();
require_once 'db_config.php';

$dept_id = intval($_GET['dept_id'] ?? 0);
$batch   = intval($_GET['batch'] ?? 0); 
$sem     = intval($_GET['sem'] ?? 0);

if (!$dept_id || !$batch || !$sem) {
    die("<div style='padding:20px; color:red;'>Invalid Parameters.</div>");
}

// 1. SYLLABUS LOGIC
$batch_code_filter = ($batch >= 2024) ? "sds.batch_year = 1" : "sds.batch_year IS NULL";

/**
 * 2. FIXED SUBJECT QUERY
 * Loop ma subject_id ko sequencing miluna ORDER BY sm.id use gareko chhu.
 */
$subjects = [];
$sub_sql = "
    (SELECT sm.id, sm.subject_name, sm.is_elective, sm.credit_hours, sm.subject_code
     FROM subjects_master sm
     JOIN subjects_department_semester sds ON sm.id = sds.subject_id
     WHERE sds.department_id = $dept_id AND sds.semester = $sem 
     AND $batch_code_filter AND sm.subject_type != 'Project')
    UNION
    (SELECT DISTINCT sm.id, sm.subject_name, sm.is_elective, sm.credit_hours, sm.subject_code
     FROM subjects_master sm
     JOIN student_electives se ON sm.id = se.elective_option_id
     WHERE se.semester_id = $sem AND se.department_id = $dept_id AND sm.subject_type != 'Project')
    ORDER BY id ASC"; // Database ID kai order ma rakha mapping milcha

$sub_q = $conn->query($sub_sql);
while($s = $sub_q->fetch_assoc()) {
    $subjects[$s['id']] = $s;
}

// 3. STUDENT ELECTIVE MAPPING
$electives_map = [];
$el_res = $conn->query("SELECT student_id, elective_option_id FROM student_electives WHERE semester_id = $sem AND department_id = $dept_id");
while($el = $el_res->fetch_assoc()){
    $electives_map[$el['student_id']][] = $el['elective_option_id'];
}

// 4. FETCH RESULTS (Subject ID lai map garna array key ma rakha)
$sql = "SELECT s.id as sid, s.full_name, s.symbol_no, 
               r.subject_id, r.ut_obtain, r.ut_pass_marks, r.ut_grade
        FROM students s
        LEFT JOIN results r ON s.id = r.student_id AND r.semester_id = $sem
        WHERE s.department_id = $dept_id AND s.batch_year = '$batch'
        ORDER BY s.symbol_no ASC";

$res = $conn->query($sql);
$display_data = [];

while($row = $res->fetch_assoc()) {
    $sid = $row['sid'];
    if(!isset($display_data[$sid])) {
        $display_data[$sid] = ['name' => $row['full_name'], 'symbol' => $row['symbol_no'], 'marks' => []];
    }
    // Result table ko marks lai subject_id ko key bhitra rakhne
    if($row['subject_id']) {
        $display_data[$sid]['marks'][$row['subject_id']] = [
            'obt' => $row['ut_obtain'],
            'grade' => $row['ut_grade'],
            'pm' => $row['ut_pass_marks']
        ];
    }
}

function getGradePoint($grade) {
    $points = ['A'=>4.0, 'A-'=>3.7, 'B+'=>3.3, 'B'=>3.0, 'B-'=>2.7, 'C+'=>2.3, 'C'=>2.0, 'C-'=>1.7, 'D+'=>1.3, 'D'=>1.0, 'F'=>0.0];
    return $points[strtoupper(trim($grade))] ?? 0.0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sticky-col { position: sticky; left: 0; background: white; z-index: 10; border-right: 1px solid #ddd; }
        .table-container { max-height: 85vh; overflow: auto; }
    </style>
</head>
<body class="bg-slate-100 p-4">

    <div class="max-w-full bg-white rounded shadow-xl overflow-hidden">
        <div class="bg-slate-800 p-4 text-white text-center font-bold uppercase">
            UT Results Matrix (Batch <?= $batch ?> - Sem <?= $sem ?>)
        </div>
        
        <div class="table-container">
            <table class="w-full text-[11px] text-center border-collapse">
                <thead class="bg-slate-200 sticky top-0 z-20">
                    <tr class="border-b border-slate-300">
                        <th class="p-3 sticky-col bg-slate-200">Symbol</th>
                        <th class="p-3 text-left">Student Name</th>
                        <?php foreach($subjects as $sub_id => $sub): ?>
                            <th class="p-3 border-l border-slate-300">
                                <?= $sub['subject_name'] ?><br>
                                <span class="text-[9px] font-normal text-slate-500">(CH: <?= $sub['credit_hours'] ?>)</span>
                            </th>
                        <?php endforeach; ?>
                        <th class="p-3 border-l bg-blue-100">SGPA</th>
                    </tr>
                </thead>
               

<tbody class="divide-y divide-slate-200">
    <?php foreach($display_data as $sid => $stu): 
        $total_gp = 0; 
        $total_ch = 0;
        $is_failed = false; // Flag to track if student failed in any subject
    ?>
    <tr class="hover:bg-blue-50">
        <td class="p-3 font-mono font-bold sticky-col bg-inherit"><?= $stu['symbol'] ?></td>
        <td class="p-3 text-left font-bold uppercase"><?= $stu['name'] ?></td>
        
        <?php foreach($subjects as $sub_id => $sub_info): ?>
            <?php 
                $is_applicable = ($sub_info['is_elective'] == 0);
                if(!$is_applicable) {
                    $is_applicable = in_array($sub_id, $electives_map[$sid] ?? []);
                }

                $m = $stu['marks'][$sub_id] ?? null;
                
                if($is_applicable) {
                    // Check if marks exist and if the grade is 'F'
                    if($m) {
                        if(strtoupper(trim($m['grade'])) == 'F') {
                            $is_failed = true; // Student failed in this subject
                        }
                        $total_gp += getGradePoint($m['grade']) * $sub_info['credit_hours'];
                        $total_ch += $sub_info['credit_hours'];
                    } else {
                        // Yadi marks nai xaina bhane pani hami SGPA calculate gardainaun (Incomplete)
                        $is_failed = true; 
                    }
                }
            ?>
            <td class="p-3 border-l <?= !$is_applicable ? 'bg-slate-50 text-slate-300' : '' ?>">
                <?php if($is_applicable): ?>
                    <div class="font-bold <?= ($m && $m['obt'] < ($m['pm'] ?? 22)) ? 'text-red-600' : '' ?>">
                        <?= ($m && $m['obt'] !== null) ? number_format($m['obt'], 2) : '-' ?>
                    </div>
                    <div class="text-[9px] text-slate-400 font-bold"><?= $m['grade'] ?? '' ?></div>
                <?php else: ?>
                    <span class="text-[8px] italic">N/A</span>
                <?php endif; ?>
            </td>
        <?php endforeach; ?>

        <td class="p-3 border-l font-bold bg-blue-50/50">
            <?php if($is_failed): ?>
                <span class="text-red-600 font-black">FAIL</span>
            <?php else: ?>
                <span class="text-blue-700">
                    <?= ($total_ch > 0) ? number_format($total_gp / $total_ch, 2) : '0.00' ?>
                </span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
            </table>
        </div>
    </div>

</body>
</html>