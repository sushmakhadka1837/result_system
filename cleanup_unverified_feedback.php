<?php
require 'db_config.php';

echo "🧹 Starting Cleanup of Unverified Feedback...\n\n";

// Delete unverified feedback older than 7 days
$days_old = 7;
$sql = "DELETE FROM student_feedback_pending 
        WHERE is_verified = 0 
        AND TIMESTAMPDIFF(DAY, created_at, NOW()) > $days_old";

if($conn->query($sql)){
    $deleted_count = $conn->affected_rows;
    echo "✅ Deleted $deleted_count unverified feedback(s) older than $days_old days.\n\n";
    
    // Show statistics
    $pending_count = $conn->query("SELECT COUNT(*) as count FROM student_feedback_pending WHERE is_verified = 0")->fetch_assoc()['count'];
    $verified_count = $conn->query("SELECT COUNT(*) as count FROM student_feedback WHERE verified_at IS NOT NULL")->fetch_assoc()['count'];
    
    echo "📊 Current Statistics:\n";
    echo "   - Pending (unverified): $pending_count\n";
    echo "   - Verified (active): $verified_count\n";
    echo "   - Total in system: " . ($pending_count + $verified_count) . "\n";
    
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

$conn->close();
echo "\n✅ Cleanup completed!\n";
?>
