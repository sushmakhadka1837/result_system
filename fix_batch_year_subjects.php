<?php
require_once 'db_config.php';

echo "=== Fixing batch_year in subjects_department_semester ===\n";

// First, let's see the schema to understand what we're dealing with
$result = $conn->query("DESCRIBE subjects_department_semester");
echo "Table structure:\n";
while($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\nUpdating empty batch_year values...\n";

// Get departments and their batches from students table
$depts = $conn->query("SELECT DISTINCT department_id, batch_year FROM students ORDER BY department_id, batch_year");
$batch_map = [];
while($row = $depts->fetch_assoc()) {
    if(!isset($batch_map[$row['department_id']])) {
        $batch_map[$row['department_id']] = $row['batch_year'];
    }
}

print_r($batch_map);

// For now, let's populate with 2023 as default for all empty ones
$update_res = $conn->query("UPDATE subjects_department_semester SET batch_year = 2023 WHERE batch_year = ''");
echo "✅ Updated empty batch_year to 2023\n";
echo "Rows affected: " . $conn->affected_rows . "\n\n";

// Now copy for batch 2022
$copy_res = $conn->query("
    INSERT IGNORE INTO subjects_department_semester 
    (subject_id, department_id, semester, batch_year, syllabus_flag)
    SELECT subject_id, department_id, semester, 2022 as batch_year, NULL as syllabus_flag
    FROM subjects_department_semester
    WHERE batch_year = 2023
");
echo "✅ Copied subjects for batch 2022\n";
echo "Rows affected: " . $conn->affected_rows . "\n\n";

// Verify
$result = $conn->query("SELECT COUNT(*) as count FROM subjects_department_semester WHERE batch_year = 2022");
$row = $result->fetch_assoc();
echo "Batch 2022 subjects: " . $row['count'] . "\n";

$result = $conn->query("SELECT COUNT(*) as count FROM subjects_department_semester WHERE batch_year = 2023");
$row = $result->fetch_assoc();
echo "Batch 2023 subjects: " . $row['count'] . "\n";

$result = $conn->query("SELECT COUNT(*) as count FROM subjects_department_semester WHERE batch_year = ''");
$row = $result->fetch_assoc();
echo "Empty batch_year subjects: " . $row['count'] . "\n";
?>
