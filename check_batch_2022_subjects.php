<?php
require_once 'db_config.php';

// Check batch 2022 subjects in subjects_department_semester
$result = $conn->query("
SELECT COUNT(*) as count 
FROM subjects_department_semester 
WHERE department_id = 2 AND batch_year = 2022
");
$row = $result->fetch_assoc();
echo "Batch 2022 subjects in subjects_department_semester: " . $row['count'] . "\n\n";

// Check batch 2023 subjects for comparison
$result = $conn->query("
SELECT COUNT(*) as count 
FROM subjects_department_semester 
WHERE department_id = 2 AND batch_year = 2023
");
$row = $result->fetch_assoc();
echo "Batch 2023 subjects in subjects_department_semester: " . $row['count'] . "\n\n";

// Check what the current export_assessment_template.php would return for batch 2022, dept 2, sem 8
echo "Testing current export_assessment_template.php logic:\n";
echo "Query: SELECT id FROM subjects_master WHERE semester_id = 8 AND department_id = 2\n";
$result = $conn->query("SELECT id, subject_name FROM subjects_master WHERE semester_id = 8 AND department_id = 2 AND subject_type != 'Project' ORDER BY id ASC LIMIT 5");
echo "Results found: " . $result->num_rows . "\n";
while($row = $result->fetch_assoc()) {
    echo "  - " . $row['id'] . ": " . $row['subject_name'] . "\n";
}
?>
