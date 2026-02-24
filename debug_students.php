<?php
require 'db_config.php';

echo "<h2>Students in Database</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Full Name</th><th>Email</th><th>Symbol No</th><th>Password Hash</th><th>Is Verified</th></tr>";

$result = $conn->query("SELECT id, full_name, email, symbol_no, password, is_verified FROM students LIMIT 20");
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['full_name']}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>{$row['symbol_no']}</td>";
    echo "<td>" . substr($row['password'], 0, 30) . "...</td>";
    echo "<td>{$row['is_verified']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr><h2>Test Password Hash</h2>";
echo "<p>Test password '123456' hash: " . password_hash('123456', PASSWORD_DEFAULT) . "</p>";
echo "<p>Test password 'password' hash: " . password_hash('password', PASSWORD_DEFAULT) . "</p>";

// Test verify
echo "<hr><h2>Password Verification Test</h2>";
$test_student = $conn->query("SELECT * FROM students LIMIT 1")->fetch_assoc();
if($test_student) {
    echo "<p>Testing student: {$test_student['email']}</p>";
    echo "<p>Password hash in DB: {$test_student['password']}</p>";
    
    // Test common passwords
    $test_passwords = ['password', '123456', 'test123', 'admin', 'student'];
    foreach($test_passwords as $pwd) {
        $verify = password_verify($pwd, $test_student['password']);
        echo "<p>Testing password '$pwd': " . ($verify ? "<strong style='color:green'>✓ MATCH</strong>" : "<span style='color:red'>✗ No match</span>") . "</p>";
    }
}
?>
