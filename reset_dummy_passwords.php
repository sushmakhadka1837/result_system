<?php
require_once 'db_config.php';

// Password to set for all dummy students
$new_password = "dummy123";
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update all dummy students (batch 2023, 2024, 2025)
$sql = "UPDATE students 
        SET password = '$hashed_password' 
        WHERE batch_year IN (2023, 2024, 2025)";

if ($conn->query($sql)) {
    $affected = $conn->affected_rows;
    echo "✅ SUCCESS: Password reset for $affected dummy students.<br>";
    echo "📝 New Password: <strong>dummy123</strong><br><br>";
    echo "Affected batches: 2023, 2024, 2025<br>";
    echo "You can now login with any dummy student email and password: dummy123";
} else {
    echo "❌ ERROR: " . $conn->error;
}

$conn->close();
?>
