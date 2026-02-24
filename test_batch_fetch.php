<?php
session_start();
require_once 'db_config.php';

// Test with batch 2022
$dept_id = 2;  // Adjust if needed
$batch   = 2022;
$sem     = 5;

echo "=== Testing Batch 2022 Marks Fetch ===\n\n";
echo "Dept: $dept_id, Batch: $batch, Sem: $sem\n\n";

// NEW LOGIC
$canonical_batch = ($batch >= 2023) ? 2 : 1;
$batch_code_filter = "sds.batch_year IN ($canonical_batch, NULL, '')";
echo "Canonical Batch: $canonical_batch\n";
echo "Filter: $batch_code_filter\n\n";

// 1. Check subjects
echo "--- CHECKING SUBJECTS ---\n";
$sub_sql = "
    (SELECT sm.id, sm.subject_name, sm.is_elective, sm.credit_hours, sm.subject_code
     FROM subjects_master sm
     JOIN subjects_department_semester sds ON sm.id = sds.subject_id
     WHERE sds.department_id = $dept_id AND sds.semester = $sem 
     AND ($batch_code_filter) AND sm.subject_type != 'Project')
    UNION
    (SELECT DISTINCT sm.id, sm.subject_name, sm.is_elective, sm.credit_hours, sm.subject_code
     FROM subjects_master sm
     JOIN student_electives se ON sm.id = se.elective_option_id
     JOIN subjects_department_semester sds ON sm.id = sds.subject_id
     WHERE se.semester_id = $sem AND se.department_id = $dept_id 
     AND ($batch_code_filter) AND sm.subject_type != 'Project')
    ORDER BY id ASC";

$sub_q = $conn->query($sub_sql);
$subjects_count = 0;
while($s = $sub_q->fetch_assoc()) {
    echo "  Subject: {$s['subject_name']} (ID: {$s['id']})\n";
    $subjects_count++;
}
echo "Total Subjects: $subjects_count\n\n";

// 2. Check students and marks
echo "--- CHECKING STUDENTS & MARKS ---\n";
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
    if($row['subject_id']) {
        $display_data[$sid]['marks'][$row['subject_id']] = [
            'obt' => $row['ut_obtain'],
            'grade' => $row['ut_grade'],
            'pm' => $row['ut_pass_marks']
        ];
    }
}

$total_marks_found = 0;
foreach($display_data as $sid => $stu) {
    $marks_count = count($stu['marks']);
    echo "  {$stu['symbol']} - {$stu['name']}: $marks_count marks\n";
    $total_marks_found += $marks_count;
    foreach($stu['marks'] as $subid => $mark) {
        echo "    └─ Subject $subid: {$mark['obt']} ({$mark['grade']})\n";
    }
}

echo "\nTotal Students: " . count($display_data) . "\n";
echo "Total Marks Found: $total_marks_found\n";
?>
