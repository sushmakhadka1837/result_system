<?php
require_once 'db_config.php';

echo "=== All subjects in subjects_department_semester ===\n";
$result = $conn->query("SELECT s.id, s.subject_name, sds.department_id, sds.batch_year, sds.semester, sds.syllabus_flag FROM subjects_department_semester sds JOIN subjects_master s ON sds.subject_id = s.id ORDER BY sds.batch_year, sds.department_id, sds.semester");
$count = 0;
while($row = $result->fetch_assoc()) {
    echo $row['id'] . ": " . $row['subject_name'] . " (Dept: " . $row['department_id'] . ", Batch: " . $row['batch_year'] . ", Sem: " . $row['semester'] . ")\n";
    $count++;
}
echo "\nTotal: " . $count . "\n\n";

echo "=== Subjects_master (all available) ===\n";
$result = $conn->query("SELECT id, subject_name, department_id, semester_id FROM subjects_master LIMIT 20");
while($row = $result->fetch_assoc()) {
    echo $row['id'] . ": " . $row['subject_name'] . " (Dept: " . $row['department_id'] . ", Sem: " . $row['semester_id'] . ")\n";
}
?>
