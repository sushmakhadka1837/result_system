<?php
require 'db_config.php';

echo "Running feedback verification table migration...\n\n";

// Create pending feedback table
$sql1 = "CREATE TABLE IF NOT EXISTS `student_feedback_pending` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(255) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `feedback` text NOT NULL,
  `verification_token` varchar(64) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `verification_token` (`verification_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if($conn->query($sql1)){
    echo "✅ Table 'student_feedback_pending' created successfully!\n";
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

// Check if student_feedback table exists, if not create it
$check_table = "SHOW TABLES LIKE 'student_feedback'";
$result = $conn->query($check_table);

if($result->num_rows == 0){
    $sql2 = "CREATE TABLE IF NOT EXISTS `student_feedback` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `student_name` varchar(255) NOT NULL,
      `student_email` varchar(255) NOT NULL,
      `feedback` text NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `verified_at` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if($conn->query($sql2)){
        echo "✅ Table 'student_feedback' created successfully!\n";
    } else {
        echo "❌ Error creating table: " . $conn->error . "\n";
    }
} else {
    // Add verified_at column if it doesn't exist
    $check_column = "SHOW COLUMNS FROM student_feedback LIKE 'verified_at'";
    $col_result = $conn->query($check_column);
    
    if($col_result->num_rows == 0){
        $sql3 = "ALTER TABLE `student_feedback` ADD COLUMN `verified_at` timestamp NULL DEFAULT NULL AFTER `created_at`";
        if($conn->query($sql3)){
            echo "✅ Column 'verified_at' added to 'student_feedback' table!\n";
        } else {
            echo "❌ Error adding column: " . $conn->error . "\n";
        }
    } else {
        echo "ℹ️  Column 'verified_at' already exists in 'student_feedback' table.\n";
    }
}

echo "\n✅ Migration completed successfully!\n";
$conn->close();
?>
