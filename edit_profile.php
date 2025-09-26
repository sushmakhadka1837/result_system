<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id'])){
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])){
    if($_FILES['profile_photo']['error'] == 0){
        $uploadDir = 'uploads/';
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = time().'_'.basename($_FILES['profile_photo']['name']);
        $targetFile = $uploadDir . $filename;

        if(move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFile)){
            $stmt = $conn->prepare("UPDATE students SET profile_photo=? WHERE id=?");
            $stmt->bind_param("si", $targetFile, $student_id);
            $stmt->execute();
        }
    }
}
header("Location: student_dashboard.php");
exit();
