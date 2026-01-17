<?php
session_start();
require 'db_config.php';

// GET bata data line
if(isset($_GET['ts_id']) && isset($_GET['status']) && isset($_GET['teacher_id'])){
    $ts_id = intval($_GET['ts_id']);
    $status = intval($_GET['status']); 
    $tid = intval($_GET['teacher_id']);

    // Database update garne
    $stmt = $conn->prepare("UPDATE teacher_subjects SET mark_lock = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $ts_id);
    
    if($stmt->execute()){
        // SAFAL BHAYE PACHI: assign_subjects.php ma firta pathaune
        header("Location: assign_subjects.php?teacher_id=$tid&msg=StatusUpdated");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Required parameters missing.";
}
?>