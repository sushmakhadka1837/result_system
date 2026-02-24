<?php
require 'db_config.php';

echo "<h2>Subject ID Integrity Check</h2>";

// 1. Check for orphaned records in subjects_department_semester (subject_id doesn't exist in subjects_master)
$orphaned = $conn->query("SELECT sds.id, sds.subject_id, sds.department_id, sds.semester 
                          FROM subjects_department_semester sds
                          LEFT JOIN subjects_master sm ON sds.subject_id = sm.id
                          WHERE sm.id IS NULL");

echo "<h3>Orphaned Records (subject_id in subjects_department_semester but NOT in subjects_master):</h3>";
if ($orphaned && $orphaned->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>SDS ID</th><th>Subject ID</th><th>Department</th><th>Semester</th></tr>";
    while ($row = $orphaned->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['subject_id']}</td><td>{$row['department_id']}</td><td>{$row['semester']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:green; font-weight:bold;'>✓ No orphaned records found - All subject IDs match!</p>";
}

// 2. Check for unused subjects in subjects_master
$unused = $conn->query("SELECT sm.id, sm.subject_name, sm.subject_code
                        FROM subjects_master sm
                        LEFT JOIN subjects_department_semester sds ON sm.id = sds.subject_id
                        WHERE sds.id IS NULL");

echo "<h3>Unused Subjects (exist in subjects_master but NOT referenced in subjects_department_semester):</h3>";
if ($unused && $unused->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Subject ID</th><th>Name</th><th>Code</th></tr>";
    while ($row = $unused->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['subject_name']}</td><td>{$row['subject_code']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:green; font-weight:bold;'>✓ No unused subjects found - All subjects are mapped!</p>";
}

// 3. Summary
$master_count = $conn->query("SELECT COUNT(*) as cnt FROM subjects_master")->fetch_assoc()['cnt'];
$mapping_count = $conn->query("SELECT COUNT(DISTINCT subject_id) as cnt FROM subjects_department_semester")->fetch_assoc()['cnt'];

echo "<h3>Summary:</h3>";
echo "<p><strong>Total subjects in subjects_master:</strong> {$master_count}</p>";
echo "<p><strong>Subjects mapped in subjects_department_semester:</strong> {$mapping_count}</p>";
?>
