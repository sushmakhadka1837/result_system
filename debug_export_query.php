<?php
require_once 'db_config.php';

echo "=== Checking subjects_department_semester data ===\n";

// Check for batch 2023, dept 2, sem 6
$result = $conn->query("
SELECT COUNT(*) as count
FROM subjects_department_semester
WHERE department_id = 2 AND batch_year = 2023 AND semester = 6
");
$row = $result->fetch_assoc();
echo "Batch 2023, Dept 2, Sem 6: " . $row['count'] . " records\n";

// Check what batch_year values exist
$result = $conn->query("
SELECT DISTINCT batch_year FROM subjects_department_semester
");
echo "\nBatch years in subjects_department_semester:\n";
while($row = $result->fetch_assoc()) {
    echo "  - " . ($row['batch_year'] === '' ? 'EMPTY' : $row['batch_year']) . "\n";
}

// Count by batch year
$result = $conn->query("
SELECT batch_year, COUNT(*) as count FROM subjects_department_semester GROUP BY batch_year
");
echo "\nCounts by batch_year:\n";
while($row = $result->fetch_assoc()) {
    echo "  - " . ($row['batch_year'] === '' ? 'EMPTY' : $row['batch_year']) . ": " . $row['count'] . "\n";
}

// Try the new query that export_assessment_template is using
echo "\n=== Testing new query for batch 2023, dept 2, sem 6 ===\n";
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

echo "Results found: " . $sub_res->num_rows . "\n";
if($sub_res->num_rows > 0) {
    while($row = $sub_res->fetch_assoc()) {
        echo "  - " . $row['subject_name'] . " (" . $row['subject_code'] . ")\n";
    }
}

// Test old simple query
echo "\n=== Testing old simple query for batch 2023, dept 2, sem 6 ===\n";
$old_res = $conn->query("SELECT DISTINCT sm.id, sm.subject_name, sm.subject_code, sm.is_elective FROM subjects_master sm WHERE sm.semester_id = $sem AND sm.department_id = $dept_id AND sm.subject_type != 'Project' ORDER BY sm.is_elective ASC, sm.id ASC");

echo "Results found: " . $old_res->num_rows . "\n";
if($old_res->num_rows > 0) {
    while($row = $old_res->fetch_assoc()) {
        echo "  - " . $row['subject_name'] . " (" . $row['subject_code'] . ")\n";
    }
}
?>
