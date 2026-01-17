<?php
session_start();
require 'db_config.php';
error_reporting(E_ALL); ini_set('display_errors',1);

if(!isset($_SESSION['student_id'])) die("SESSION missing");
if(!isset($_POST['receiver_id'])) die("POST missing");

$sender_id = intval($_SESSION['student_id']);
$receiver_id = intval($_POST['receiver_id']);
$message = trim($_POST['message'] ?? '');
$attachment_path = null;

// Handle file upload
if(isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0){
    $upload_dir = "uploads/chat_attachments/";
    if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $file_name = $_FILES['attachment']['name'];
    $file_tmp = $_FILES['attachment']['tmp_name'];
    $file_size = $_FILES['attachment']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowed = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','txt','zip','rar'];
    
    if(in_array($file_ext, $allowed) && $file_size < 10485760){ // 10MB max
        $new_file_name = time().'_'.uniqid().'.'.$file_ext;
        $attachment_path = $upload_dir.$new_file_name;
        move_uploaded_file($file_tmp, $attachment_path);
    }
}

// Either message or attachment must exist
if($message == '' && $attachment_path == null){
    die("Empty message and no attachment");
}

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, sender_type, attachment, is_read) VALUES (?,?,?, 'student', ?,0)");
$stmt->bind_param("iiss",$sender_id,$receiver_id,$message,$attachment_path);
$stmt->execute();
echo "SUCCESS";
