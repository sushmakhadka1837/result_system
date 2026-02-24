<?php
require 'db_config.php';

$dept_id = intval($_GET['department_id'] ?? $_GET['dept_id'] ?? 0);

$result = [];

if ($dept_id > 0) {
    $dept_stmt = $conn->prepare("SELECT department_name FROM departments WHERE id = ? LIMIT 1");
    $dept_stmt->bind_param("i", $dept_id);
    $dept_stmt->execute();
    $dept_row = $dept_stmt->get_result()->fetch_assoc();

    $department_name = strtolower(trim($dept_row['department_name'] ?? ''));
    $max_semester = (strpos($department_name, 'arch') !== false) ? 10 : 8;

    $stmt = $conn->prepare("SELECT * FROM semesters WHERE department_id = ? AND semester_order <= ? ORDER BY semester_order ASC");
    $stmt->bind_param("ii", $dept_id, $max_semester);
    $stmt->execute();
    $semesters = $stmt->get_result();

    while ($row = $semesters->fetch_assoc()) {
        $result[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($result);
