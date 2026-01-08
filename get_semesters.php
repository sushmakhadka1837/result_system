<?php
require 'db_config.php';

$dept_id = intval($_GET['department_id'] ?? 0);

if($dept_id==0){
    // All departments, you can send empty or all
    $semesters = $conn->query("SELECT * FROM semesters ORDER BY department_id, semester_order ASC");
} else {
    $semesters = $conn->query("SELECT * FROM semesters WHERE department_id=$dept_id ORDER BY semester_order ASC");
}

$result = [];
while($row = $semesters->fetch_assoc()){
    $result[] = $row;
}

header('Content-Type: application/json');
echo json_encode($result);
