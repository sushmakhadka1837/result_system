<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id']) || $_SESSION['user_type']!='student'){
    http_response_code(403);
    exit("Not authorized");
}

$student_id = $_SESSION['student_id'];

$msg_id = $_POST['msg_id'] ?? 0;
$message = trim($_POST['message'] ?? '');

if($msg_id && $message){
    // Check if this message belongs to this student
    $stmt = $conn->prepare("SELECT * FROM messages WHERE id=? AND sender_id=? AND sender_type='student'");
    $stmt->bind_param("ii",$msg_id,$student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows>0){
        $stmt2 = $conn->prepare("UPDATE messages SET message=? WHERE id=?");
        $stmt2->bind_param("si",$message,$msg_id);
        $stmt2->execute();
        $stmt2->close();
        echo "success";
    }
    $stmt->close();
}
?>
