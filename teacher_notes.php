<?php
session_start();
if(!isset($_SESSION['teacher_id'])){
    header("Location: teacher_login.php");
    exit();
}

require 'db_config.php';
?>

<!DOCTYPE html>
<html>
<head>
  <title>Engineering Notes - Teacher</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .dept-card { transition: 0.3s; cursor:pointer; }
    .dept-card:hover { transform: translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,0.2);}
    .dept-icon { font-size:40px; color:#fff; width:80px; height:80px; display:flex; align-items:center; justify-content:center; border-radius:50%; margin-bottom:15px; background:#28a745; }
  </style>
</head>
<body>
<div class="container my-5">
    <h1 class="mb-3 text-center">📘 Engineering Notes (Teacher)</h1>
    <p class="text-center mb-5">Select your department to upload notes.</p>

    <div class="row g-4">
        <?php
        $departments = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");
        $icons = ['Computer'=>'fa-laptop-code','Civil'=>'fa-building','Architecture'=>'fa-drafting-compass','IT'=>'fa-network-wired'];

        while($d = $departments->fetch_assoc()){
            $icon_class = $icons[$d['department_name']] ?? 'fa-book';
            echo "<div class='col-md-3'>";
            echo "<a href='teacher_upload_notes.php?dept_id={$d['id']}' style='text-decoration:none;color:inherit;'>";
            echo "<div class='card text-center dept-card p-4 shadow-sm'>";
            echo "<div class='dept-icon mx-auto'><i class='fa {$icon_class}'></i></div>";
            echo "<h5>{$d['department_name']}</h5>";
            echo "<p class='small text-muted'>Click to select batch & semester</p>";
            echo "</div></a></div>";
        }
        ?>
    </div>
</div>
</body>
</html>
