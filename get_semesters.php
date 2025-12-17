<?php
require 'db_config.php';

$dept_id = intval($_GET['dept_id'] ?? 0);

// Fetch all semesters for this department from semesters table
$query = "
    SELECT id, semester_name
    FROM semesters
    WHERE department_id=$dept_id
    ORDER BY semester_order ASC
";

$result = $conn->query($query);
$semesters = [];

while($row = $result->fetch_assoc()){
    $semesters[] = $row;
}

header('Content-Type: application/json');
echo json_encode($semesters);
