<?php
require_once 'db_config.php';

echo "=== Verifying Semester 6 Subjects for Batch 2023, Computer ===\n\n";

// Check what's currently in results for this student
$result = $conn->query("
SELECT s.id, s.subject_name, s.subject_code, r.ut_obtain, r.ut_grade
FROM results r
JOIN subjects_master s ON r.subject_id = s.id
WHERE r.student_id = (SELECT id FROM students WHERE symbol_no = '23010' LIMIT 1)
AND r.semester_id = 6
ORDER BY s.subject_name
");

echo "Current Results in DB for Sabina (23010), Semester 6:\n";
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo $row['id'] . ": " . $row['subject_name'] . " (" . $row['subject_code'] . ")\n";
    }
} else {
    echo "No results found for this student in semester 6\n";
}

echo "\n=== Checking subjects_master for Semester 6 ===\n";
$result = $conn->query("
SELECT id, subject_name, subject_code, department_id
FROM subjects_master
WHERE semester_id = 6
AND department_id = 2
ORDER BY subject_name
");

echo "\nAll Semester 6 subjects available for Computer (Dept 2):\n";
while($row = $result->fetch_assoc()) {
    echo $row['id'] . ": " . $row['subject_name'] . " (" . $row['subject_code'] . ")\n";
}

echo "\n=== Checking Student Info ===\n";
$result = $conn->query("SELECT id, full_name, batch_year, semester_id FROM students WHERE symbol_no = '23010'");
if($row = $result->fetch_assoc()) {
    echo "Found: " . $row['full_name'] . ", ID: " . $row['id'] . ", Batch: " . $row['batch_year'] . ", Current Semester: " . $row['semester_id'] . "\n";
}
?>
