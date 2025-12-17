<?php
require 'db_config.php';

$dept_id = intval($_GET['dept_id'] ?? 0);
$sem_id  = intval($_GET['sem_id'] ?? 0);
$batch   = $_GET['batch'] ?? 'old';

if(!$dept_id || !$sem_id){
    echo json_encode([]);
    exit;
}

// Decide batch year condition
$batch_condition = ($batch == 'old') ? "<=2022" : ">2022";

$sql = "SELECT * FROM subjects_master 
        WHERE department_id = ? 
        AND semester_id = ? 
        AND batch_year $batch_condition 
        ORDER BY subject_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $dept_id, $sem_id);
$stmt->execute();
$res = $stmt->get_result();

$subjects = [];
while($row = $res->fetch_assoc()){
    $subjects[] = [
        'id' => $row['id'],
        'name' => $row['subject_name']
    ];
}

echo json_encode($subjects);
