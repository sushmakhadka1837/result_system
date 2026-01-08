<?php
session_start();
require 'db_config.php';

$dept_id = intval($_GET['dept_id'] ?? 0);
$sem_id  = intval($_GET['sem_id'] ?? 0);
$type    = $_GET['type'] ?? ''; // 'ut' or 'assessment'

if(!$dept_id || !$sem_id || !in_array($type, ['ut','assessment'])) {
    die("Invalid parameters.");
}

// 1️⃣ Update results_publish_status table
$check = $conn->query("SELECT * FROM results_publish_status 
                       WHERE department_id=$dept_id 
                       AND semester_id=$sem_id 
                       AND result_type='$type'");

if($check->num_rows > 0){
    $conn->query("UPDATE results_publish_status 
                  SET published=1, published_at=NOW() 
                  WHERE department_id=$dept_id AND semester_id=$sem_id AND result_type='$type'");
} else {
    $conn->query("INSERT INTO results_publish_status(department_id, semester_id, result_type, published, published_at) 
                  VALUES($dept_id, $sem_id, '$type', 1, NOW())");
}

// 2️⃣ Update results table ONLY if assessment
if($type === 'assessment'){
    $conn->query("
        UPDATE results r
        JOIN subjects_department_semester sds ON r.subject_id = sds.id
        SET r.published = 1
        WHERE sds.department_id = $dept_id
        AND sds.semester = $sem_id
        AND r.assessment_raw IS NOT NULL
    ");
}

// 3️⃣ Redirect back to department page
header("Location: publish_department.php?dept_id=$dept_id");
exit();
