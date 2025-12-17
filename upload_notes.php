<?php
require 'db_config.php';

$batch = $_POST['batch'];
$dept = $_POST['department'];
$sem = $_POST['semester'];
$sub = $_POST['subject'];
$title = $_POST['title'];

$filename = time() . "_" . $_FILES['pdf_file']['name'];
$path = "uploads/notes/" . $filename;

move_uploaded_file($_FILES['pdf_file']['tmp_name'], $path);

$conn->query("
    INSERT INTO notes 
    (batch_type, department_id, semester_id, subject_id, title, file_path)
    VALUES 
    ('$batch', '$dept', '$sem', '$sub', '$title', '$path')
");

echo "<script>alert('Notes Uploaded Successfully!'); window.location='admin_dashboard.php';</script>";
?>
