<?php
require_once 'db_config.php';

echo "=== Checking subjects_department_semester table ===\n";
$result = $conn->query("SELECT COUNT(*) as count FROM subjects_department_semester");
$row = $result->fetch_assoc();
echo "Total records in subjects_department_semester: " . $row['count'] . "\n\n";

echo "Sample records (first 10):\n";
$result = $conn->query("SELECT subject_id, department_id, semester, batch_year, syllabus_flag FROM subjects_department_semester LIMIT 10");
while($row = $result->fetch_assoc()) {
    echo "Subject: " . $row['subject_id'] . ", Dept: " . $row['department_id'] . ", Sem: " . $row['semester'] . ", Batch: " . $row['batch_year'] . ", Syllabus: " . ($row['syllabus_flag'] === null ? 'NULL' : $row['syllabus_flag']) . "\n";
}

echo "\n=== Checking what's available in subjects_master ===\n";
$result = $conn->query("SELECT COUNT(*) as count FROM subjects_master");
$row = $result->fetch_assoc();
echo "Total subjects in subjects_master: " . $row['count'] . "\n";

echo "\n=== Checking UT results table ===\n";
$result = $conn->query("SELECT DISTINCT batch_year FROM students ORDER BY batch_year");
echo "Batches in students table: ";
while($row = $result->fetch_assoc()) {
    echo $row['batch_year'] . " ";
}
echo "\n";
?>
