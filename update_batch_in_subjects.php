<?php
require 'db_config.php';

echo "🔧 Updating subjects_department_semester with batch_year...\n\n";

// Get all entries
$result = $conn->query("SELECT id, subject_id, semester FROM subjects_department_semester WHERE batch_year IS NULL");

echo "Found " . $result->num_rows . " entries without batch_year\n\n";

if($result->num_rows > 0) {
    echo "Tapai le specify garnuhos:\n";
    echo "1 = Old Batch (Before 2023)\n";
    echo "2 = New Batch (2023 onwards)\n";
    echo "0 = Both batches (NULL - common for both)\n\n";
    
    while($row = $result->fetch_assoc()) {
        // Get subject details
        $sub_q = $conn->query("SELECT subject_name, subject_code FROM subjects_master WHERE id = " . $row['subject_id']);
        $sub = $sub_q->fetch_assoc();
        
        echo "ID: {$row['id']} | Semester: {$row['semester']} | Subject: {$sub['subject_name']} ({$sub['subject_code']})\n";
        echo "Enter batch (1=Old, 2=New, 0=Both): ";
        
        $handle = fopen("php://stdin", "r");
        $choice = trim(fgets($handle));
        
        if($choice == '1') {
            $batch = 1; // Old batch
        } elseif($choice == '2') {
            $batch = 2; // New batch
        } else {
            $batch = 'NULL'; // Both batches
        }
        
        if($batch === 'NULL') {
            $conn->query("UPDATE subjects_department_semester SET batch_year = NULL WHERE id = " . $row['id']);
        } else {
            $conn->query("UPDATE subjects_department_semester SET batch_year = $batch WHERE id = " . $row['id']);
        }
        
        echo "✅ Updated!\n\n";
    }
    
    echo "✅ All done!\n";
} else {
    echo "ℹ️  All entries already have batch_year set.\n";
}

echo "\n📊 Current Statistics:\n";
$old_count = $conn->query("SELECT COUNT(*) as c FROM subjects_department_semester WHERE batch_year = 1")->fetch_assoc()['c'];
$new_count = $conn->query("SELECT COUNT(*) as c FROM subjects_department_semester WHERE batch_year = 2")->fetch_assoc()['c'];
$both_count = $conn->query("SELECT COUNT(*) as c FROM subjects_department_semester WHERE batch_year IS NULL")->fetch_assoc()['c'];

echo "Old Batch (1): $old_count\n";
echo "New Batch (2): $new_count\n";
echo "Both Batches (NULL): $both_count\n";

$conn->close();
?>
