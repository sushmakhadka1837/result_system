<?php
require_once 'db_config.php';

echo "=== Checking Subject Mappings ===\n\n";

// Get all subjects for dept 2, sem 5
$all_subjects = $conn->query("
    SELECT DISTINCT sm.id, sm.subject_name, sm.subject_code
    FROM subjects_master sm
    WHERE sm.id IN (
        SELECT DISTINCT subject_id FROM subjects_department_semester 
        WHERE department_id = 2 AND semester = 5
        UNION
        SELECT DISTINCT subject_id FROM results 
        WHERE semester_id = 5
    )
    ORDER BY sm.id
");

echo "All subjects for Dept 2, Sem 5:\n";
while($row = $all_subjects->fetch_assoc()) {
    echo "  ID {$row['id']}: {$row['subject_name']} ({$row['subject_code']})\n";
}

echo "\n--- Subject Mapping Details ---\n";
$mapping = $conn->query("
    SELECT sds.id, sds.subject_id, sm.subject_name, sds.batch_year, sds.department_id
    FROM subjects_department_semester sds
    JOIN subjects_master sm ON sds.subject_id = sm.id
    WHERE sds.department_id = 2 AND sds.semester = 5
    ORDER BY sds.batch_year, sds.subject_id
");

while($row = $mapping->fetch_assoc()) {
    echo "  Mapping {$row['id']}: Subject {$row['subject_id']} ({$row['subject_name']}) - Batch {$row['batch_year']}\n";
}

echo "\n--- Marks Distribution ---\n";
$marks_dist = $conn->query("
    SELECT DISTINCT r.subject_id
    FROM results r
    WHERE r.semester_id = 5 AND r.student_id IN (
        SELECT id FROM students WHERE department_id = 2 AND batch_year = 2022
    )
    ORDER BY r.subject_id
");

echo "Subject IDs with marks:\n";
while($row = $marks_dist->fetch_assoc()) {
    $subinfo = $conn->query("SELECT subject_name FROM subjects_master WHERE id = {$row['subject_id']}")->fetch_assoc();
    echo "  ID {$row['subject_id']}: " . ($subinfo['subject_name'] ?? 'Unknown') . "\n";
}
?>
