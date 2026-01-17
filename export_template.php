<?php
session_start();
require_once 'db_config.php';

$dept_id    = intval($_GET['department_id'] ?? 0);
$batch_year = intval($_GET['batch'] ?? 0);
$semester   = intval($_GET['semester'] ?? 0);
$section    = trim($_GET['section'] ?? '');

if ($dept_id && $batch_year && $semester) {
    // Syllabus Flag logic
    $flag_condition = ($batch_year > 2022) ? "sds.syllabus_flag = 1" : "sds.syllabus_flag IS NULL";

    // PROJECT HATAUNE LOGIC: sm.subject_type != 'Project' thapiyo
    $sql_sub = "SELECT sm.id, sm.subject_name, sm.subject_code, sm.is_elective 
                FROM subjects_master sm
                JOIN subjects_department_semester sds ON sm.id = sds.subject_id
                WHERE sds.department_id = $dept_id 
                AND sds.semester = $semester 
                AND $flag_condition
                AND sm.subject_type != 'Project'"; // Yo line le Project hataidinchha

    $res_sub = $conn->query($sql_sub);
    $subjects = [];
    while($row = $res_sub->fetch_assoc()) {
        $subjects[$row['id']] = $row;
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="UT_Template_S'.$semester.'.csv"');
    $output = fopen('php://output', 'w');
    
    // 1. Header Row
    $header_row = ['Student_ID', 'Symbol_No', 'Student_Name'];
    foreach($subjects as $s) { 
        // Example: Physics (PHY101) (FM:50 PM:22)
        // Import logic ma preg_match miluna ko lagi format strictly follow garnu: (CODE) (FM:50 PM:22)
        $header_row[] = $s['subject_name'] . " (" . $s['subject_code'] . ")" . ($s['is_elective'] ? " (Elective)" : "") . " (FM:50 PM:22)"; 
    }
    fputcsv($output, $header_row);

    // 2. Student List
    $query = "SELECT id, symbol_no, full_name FROM students 
              WHERE batch_year = $batch_year AND department_id = $dept_id";
    if ($section !== "") {
        $query .= " AND section = '" . $conn->real_escape_string($section) . "'";
    }
    $query .= " ORDER BY symbol_no ASC";
    
    $st_res = $conn->query($query);
    while($st = $st_res->fetch_assoc()) {
        $row_data = [$st['id'], $st['symbol_no'], $st['full_name']];
        
        foreach($subjects as $sid => $sub) {
            if($sub['is_elective'] == 1) {
                // Elective check
                $el_check = $conn->query("SELECT id FROM student_electives 
                                         WHERE student_id = {$st['id']} 
                                         AND elective_option_id = $sid 
                                         AND semester_id = $semester");
                
                if($el_check->num_rows > 0) {
                    $row_data[] = ""; 
                } else {
                    $row_data[] = "-"; // Non-opted electives ma dash aaucha
                }
            } else {
                $row_data[] = ""; 
            }
        }
        fputcsv($output, $row_data);
    }
    fclose($output);
    exit;
}