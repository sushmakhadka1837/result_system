<?php
require_once 'db_config.php';

$email = "sabina2023@gmail.com";
$symbol = "23010";
$password = "dummy123";

echo "<h3>Testing Login for Dummy Student</h3>";
echo "Email: $email<br>";
echo "Symbol: $symbol<br>";
echo "Password: $password<br><br>";

// Check if student exists
$stmt = $conn->prepare("SELECT id, full_name, email, symbol_no, password, is_verified FROM students WHERE email=? AND symbol_no=?");
$stmt->bind_param("ss", $email, $symbol);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if ($student) {
    echo "✅ Student Found!<br>";
    echo "ID: " . $student['id'] . "<br>";
    echo "Name: " . $student['full_name'] . "<br>";
    echo "Email: " . $student['email'] . "<br>";
    echo "Symbol: " . $student['symbol_no'] . "<br>";
    echo "Is Verified: " . $student['is_verified'] . "<br><br>";
    
    // Test password
    if (password_verify($password, $student['password'])) {
        echo "✅ Password Match! Login should work.<br>";
    } else {
        echo "❌ Password DOES NOT match!<br>";
        echo "Password reset script may not have run yet.<br>";
        echo "<a href='reset_dummy_passwords.php'>Click here to reset passwords</a>";
    }
} else {
    echo "❌ Student NOT found with this email and symbol number!<br>";
    echo "Check if the email or symbol number is correct.";
}
?>
