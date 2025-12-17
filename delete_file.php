<?php
require 'db_config.php';

$id = intval($_GET['id']);

$file = $conn->query("SELECT file_path FROM notes WHERE id=$id")->fetch_assoc();

if($file){
    unlink($file['file_path']);
    $conn->query("DELETE FROM notes WHERE id=$id");
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
