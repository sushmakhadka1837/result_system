<?php
require_once 'db_config.php';

echo "=== Populating batch_year in subjects_department_semester ===\n\n";

// First, clear empty batch_year records and assign them batch 2023 as default
$update1 = $conn->query("UPDATE subjects_department_semester SET batch_year = 2023 WHERE batch_year = ''");
echo "1. Updated empty batch_year to 2023: " . $conn->affected_rows . " records\n\n";

// Now populate batch 2022 for old curriculum (copy from 2023)
$copy_batch_2022 = $conn->query("
    INSERT IGNORE INTO subjects_department_semester 
    (subject_id, department_id, semester, batch_year, syllabus_flag, section)
    SELECT subject_id, department_id, semester, 2022, NULL, section
    FROM subjects_department_semester
    WHERE batch_year = 2023
");
echo "2. Copied subjects to batch 2022: " . $conn->affected_rows . " records\n\n";

// Verify counts
$result = $conn->query("SELECT batch_year, COUNT(*) as count FROM subjects_department_semester GROUP BY batch_year");
echo "After population:\n";
while($row = $result->fetch_assoc()) {
    echo "  Batch " . ($row['batch_year'] === '' ? 'EMPTY' : $row['batch_year']) . ": " . $row['count'] . " subjects\n";
}

// Test the query now
echo "\n=== Testing query after population ===\n";
$dept_id = 2;
$batch = 2023;
$sem = 6;
$syllabus_bit = ($batch > 2022) ? 1 : null;
$syllabus_condition = ($syllabus_bit === 1) ? "sds.syllabus_flag = 1" : "sds.syllabus_flag IS NULL";

$sub_res = $conn->query("
    SELECT DISTINCT sm.id, sm.subject_name, sm.subject_code, sm.is_elective 
    FROM subjects_master sm
    INNER JOIN subjects_department_semester sds ON sm.id = sds.subject_id
    WHERE sds.department_id = $dept_id 
    AND sm.semester_id = $sem
    AND sds.batch_year = $batch
    AND $syllabus_condition
    AND sm.subject_type != 'Project'
    ORDER BY sm.is_elective ASC, sm.id ASC
");

echo "Batch 2023, Dept 2, Sem 6 subjects found: " . $sub_res->num_rows . "\n";
while($row = $sub_res->fetch_assoc()) {
    echo "  - " . $row['subject_name'] . " (" . $row['subject_code'] . ")\n";
}

// Test for old batch
echo "\n=== Testing for Batch 2022 (old) ===\n";
$batch = 2022;
$syllabus_bit = ($batch > 2022) ? 1 : null;
$syllabus_condition = ($syllabus_bit === 1) ? "sds.syllabus_flag = 1" : "sds.syllabus_flag IS NULL";

$sub_res = $conn->query("
    SELECT DISTINCT sm.id, sm.subject_name, sm.subject_code, sm.is_elective 
    FROM subjects_master sm
    INNER JOIN subjects_department_semester sds ON sm.id = sds.subject_id
    WHERE sds.department_id = $dept_id 
    AND sm.semester_id = $sem
    AND sds.batch_year = $batch
    AND $syllabus_condition
    AND sm.subject_type != 'Project'
    ORDER BY sm.is_elective ASC, sm.id ASC
");

echo "Batch 2022, Dept 2, Sem 6 subjects found: " . $sub_res->num_rows . "\n";
while($row = $sub_res->fetch_assoc()) {
    echo "  - " . $row['subject_name'] . " (" . $row['subject_code'] . ")\n";
}
?>
