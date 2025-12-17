<?php
session_start();
require 'db_config.php';

$user_type = $_SESSION['teacher_id'] ? 'teacher' : ($_SESSION['student_id'] ? 'student' : '');
$user_id = $_SESSION['teacher_id'] ?? $_SESSION['student_id'] ?? 0;

$response = ['status'=>'error','message'=>'Invalid user','photo'=>''];
if(!$user_type || !$user_id){ echo json_encode($response); exit; }

$table = $user_type=='teacher' ? 'teachers' : 'students';
$uploadDir = 'uploads/';

// Remove photo
if(isset($_POST['remove'])){
    $old = $conn->query("SELECT profile_photo FROM $table WHERE id=$user_id")->fetch_assoc()['profile_photo'];
    if($old && file_exists($uploadDir.$old)) unlink($uploadDir.$old);
    $conn->query("UPDATE $table SET profile_photo=NULL WHERE id=$user_id");
    echo json_encode(['status'=>'success','message'=>'Photo removed','photo'=>'']); exit;
}

// Upload new photo
if(isset($_FILES['profile_photo'])){
    $file = $_FILES['profile_photo'];
    $allowed = ['image/jpeg','image/png','image/gif'];
    if(!in_array($file['type'],$allowed)){ $response['message']='Only JPG, PNG, GIF allowed'; echo json_encode($response); exit; }
    if($file['size']>2*1024*1024){ $response['message']='File size <= 2MB'; echo json_encode($response); exit; }

    $ext = pathinfo($file['name'],PATHINFO_EXTENSION);
    $newName = $user_type.'_'.$user_id.'_'.time().'.'.$ext;
    if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);
    $uploadPath = $uploadDir.$newName;

    if(move_uploaded_file($file['tmp_name'],$uploadPath)){
        $old = $conn->query("SELECT profile_photo FROM $table WHERE id=$user_id")->fetch_assoc()['profile_photo'];
        if($old && file_exists($uploadDir.$old)) unlink($uploadDir.$old);
        $conn->query("UPDATE $table SET profile_photo='$newName' WHERE id=$user_id");
        $response = ['status'=>'success','message'=>'Profile photo updated','photo'=>$uploadPath];
    } else { $response['message']='Upload failed'; }
}
echo json_encode($response);
