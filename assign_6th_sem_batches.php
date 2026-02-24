<?php
require 'db_config.php';

echo "🔧 Quick Batch Assignment for 6th Semester\n\n";

// Get all 6th semester subjects
$result = $conn->query("
    SELECT sds.id, sds.subject_id, sm.subject_name, sm.subject_code, sds.batch_year
    FROM subjects_department_semester sds
    JOIN subjects_master sm ON sds.subject_id = sm.id
    WHERE sm.semester_id = 6
    ORDER BY sm.subject_name
");

echo "6th Semester ma yo subjects chan:\n";
echo str_repeat("=", 80) . "\n\n";

$assignments = [];

while($row = $result->fetch_assoc()) {
    $current = $row['batch_year'] === null ? 'NULL' : ($row['batch_year'] == 1 ? 'Old(1)' : 'New(2)');
    echo "ID: {$row['id']} | {$row['subject_name']} ({$row['subject_code']}) | Current: $current\n";
    
    // Auto-detect based on common patterns
    $name = strtolower($row['subject_name']);
    $code = strtolower($row['subject_code']);
    
    // New batch subjects (2023 onwards)
    if(strpos($code, 'cse') !== false || strpos($code, 'math') !== false) {
        $suggested = 2; // New batch
        $reason = "Common/New syllabus subject";
    }
    // Old batch specific subjects
    elseif(strpos($code, 'ce') !== false || strpos($code, 'eg') !== false) {
        $suggested = 1; // Old batch
        $reason = "Old syllabus code pattern";
    }
    else {
        $suggested = 'NULL'; // Both batches
        $reason = "Common subject";
    }
    
    echo "   → Suggested: ";
    if($suggested === 'NULL') {
        echo "Both batches (NULL) - $reason\n";
    } elseif($suggested == 1) {
        echo "Old Batch (1) - $reason\n";
    } else {
        echo "New Batch (2) - $reason\n";
    }
    
    echo "   Enter: 1=Old, 2=New, 0=Both, ENTER=Use Suggested: ";
    $handle = fopen("php://stdin", "r");
    $input = trim(fgets($handle));
    
    if($input === '') {
        $batch = $suggested;
    } elseif($input == '1') {
        $batch = 1;
    } elseif($input == '2') {
        $batch = 2;
    } else {
        $batch = 'NULL';
    }
    
    $assignments[] = [
        'id' => $row['id'],
        'name' => $row['subject_name'],
        'batch' => $batch
    ];
    
    echo "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📝 Review your assignments:\n\n";

foreach($assignments as $a) {
    $batch_str = $a['batch'] === 'NULL' ? 'Both(NULL)' : ($a['batch'] == 1 ? 'Old(1)' : 'New(2)');
    echo "{$a['name']} → $batch_str\n";
}

echo "\nConfirm? (y/n): ";
$handle = fopen("php://stdin", "r");
$confirm = trim(fgets($handle));

if(strtolower($confirm) === 'y') {
    foreach($assignments as $a) {
        if($a['batch'] === 'NULL') {
            $conn->query("UPDATE subjects_department_semester SET batch_year = NULL WHERE id = {$a['id']}");
        } else {
            $conn->query("UPDATE subjects_department_semester SET batch_year = {$a['batch']} WHERE id = {$a['id']}");
        }
    }
    echo "\n✅ All assignments saved!\n";
} else {
    echo "\n❌ Cancelled.\n";
}

echo "\n📊 Final Statistics:\n";
$old = $conn->query("SELECT COUNT(*) as c FROM subjects_department_semester sds JOIN subjects_master sm ON sds.subject_id = sm.id WHERE sm.semester_id = 6 AND sds.batch_year = 1")->fetch_assoc()['c'];
$new = $conn->query("SELECT COUNT(*) as c FROM subjects_department_semester sds JOIN subjects_master sm ON sds.subject_id = sm.id WHERE sm.semester_id = 6 AND sds.batch_year = 2")->fetch_assoc()['c'];
$both = $conn->query("SELECT COUNT(*) as c FROM subjects_department_semester sds JOIN subjects_master sm ON sds.subject_id = sm.id WHERE sm.semester_id = 6 AND sds.batch_year IS NULL")->fetch_assoc()['c'];

echo "Old Batch (1): $old subjects\n";
echo "New Batch (2): $new subjects\n";
echo "Both (NULL): $both subjects\n";

$conn->close();
?>
