<?php
require_once 'db_config.php';

// Copy assessment subjects from batch 2023 to batch 2022
// For Architecture department
$insert_count = 0;

$copy_res = $conn->query("
    INSERT IGNORE INTO subjects_department_semester 
    (subject_id, department_id, semester, batch_year, syllabus_flag)
    SELECT subject_id, department_id, semester, 2022 as batch_year, NULL as syllabus_flag
    FROM subjects_department_semester
    WHERE department_id = 1 AND batch_year = 2023
");

if($copy_res) {
    echo "✅ Architecture (Dept 1) subjects copied for batch 2022\n";
    echo "Rows affected: " . $conn->affected_rows . "\n\n";
}

// For Computer Science department
$copy_res = $conn->query("
    INSERT IGNORE INTO subjects_department_semester 
    (subject_id, department_id, semester, batch_year, syllabus_flag)
    SELECT subject_id, department_id, semester, 2022 as batch_year, NULL as syllabus_flag
    FROM subjects_department_semester
    WHERE department_id = 2 AND batch_year = 2023
");

if($copy_res) {
    echo "✅ Computer Science (Dept 2) subjects copied for batch 2022\n";
    echo "Rows affected: " . $conn->affected_rows . "\n\n";
}

// For other departments
$copy_res = $conn->query("
    INSERT IGNORE INTO subjects_department_semester 
    (subject_id, department_id, semester, batch_year, syllabus_flag)
    SELECT subject_id, department_id, semester, 2022 as batch_year, NULL as syllabus_flag
    FROM subjects_department_semester
    WHERE batch_year = 2023 AND department_id NOT IN (1, 2)
");

if($copy_res) {
    echo "✅ Other departments subjects copied for batch 2022\n";
    echo "Rows affected: " . $conn->affected_rows . "\n\n";
}

// Verify
echo "Verification:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM subjects_department_semester WHERE batch_year = 2022");
$row = $result->fetch_assoc();
echo "Total subjects for batch 2022: " . $row['count'] . "\n";

$result = $conn->query("SELECT COUNT(*) as count FROM subjects_department_semester WHERE department_id = 2 AND batch_year = 2022");
$row = $result->fetch_assoc();
echo "Computer Dept batch 2022 subjects: " . $row['count'] . "\n";
?>
