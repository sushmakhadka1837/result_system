<?php
require_once 'db_config.php';

$dept_id = intval($_GET['department_id']);
$batch   = intval($_GET['batch']);
$sem     = intval($_GET['semester']);

// 1. Fetch Students
$stu_res = $conn->query("SELECT id, full_name, symbol_no FROM students WHERE department_id = $dept_id AND batch_year = $batch ORDER BY symbol_no ASC");

// 2. Fetch Subjects (Excluding Project)
$sub_res = $conn->query("SELECT id, subject_name, subject_code, is_elective FROM subjects_master WHERE semester_id = $sem AND department_id = $dept_id AND subject_type != 'Project' ORDER BY is_elective ASC, id ASC");

$sub_list = [];
while($s = $sub_res->fetch_assoc()){ $sub_list[] = $s; }

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="Assessment_Template_S'.$sem.'.csv"');
$output = fopen('php://output', 'w');

// Row 1: Subject Names
$h1 = ['ID', 'Name', 'Symbol'];
// Row 2: Sub-components
$h2 = ['', '', ''];

foreach($sub_list as $sub){
    $h1 = array_merge($h1, [$sub['subject_name'].' ('.$sub['subject_code'].')', '', '', '', '']);
    $h2 = array_merge($h2, ['Ass(100)', 'Tut(5)', 'Prac(20)', 'Total_Days', 'Att_Days']);
}
fputcsv($output, $h1);
fputcsv($output, $h2);

// 3. Data Rows
while($stu = $stu_res->fetch_assoc()){
    $line = [$stu['id'], $stu['full_name'], $stu['symbol_no']];
    $sid = $stu['id'];

    foreach($sub_list as $sub){
        $sub_id = $sub['id'];
        $is_allowed = true;

        if($sub['is_elective'] == 1){
            $el_check = $conn->query("SELECT id FROM student_electives WHERE student_id = $sid AND elective_option_id = $sub_id LIMIT 1");
            if($el_check->num_rows == 0) $is_allowed = false;
        }

        if($is_allowed){
            // Default 30 days or empty for marks
            $line = array_merge($line, ['', '', '', '30', '']);
        } else {
            // Elective naliyeko ma '-' rakhne
            $line = array_merge($line, ['-', '-', '-', '-', '-']);
        }
    }
    fputcsv($output, $line);
}
fclose($output);