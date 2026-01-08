<?php
require 'db_config.php';
?>

<!DOCTYPE html>
<html>
<head>
  <title>Engineering Notes - Pokhara Engineering College</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .dept-card {
        transition: transform 0.3s;
        cursor: pointer;
    }
    .dept-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }
    .dept-icon {
        font-size: 40px;
        color: #fff;
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin-bottom: 15px;
    }
  </style>
</head>
<body>
<?php include 'student_header.php'; ?>
<div class="container my-5">
    <h1 class="mb-3 text-center">📘 Engineering Notes</h1>
    <p class="text-center mb-5">Find notes, syllabus & past questions for all departments of Pokhara Engineering College.</p>

    <div class="row g-4">
        <?php
        $departments = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");

        // Assign icons for departments
        $icons = [
            'Computer' => 'fa-laptop-code',
            'Civil' => 'fa-building',
            'Architecture' => 'fa-drafting-compass',
            'IT' => 'fa-network-wired'
        ];

        while($d = $departments->fetch_assoc()){
            $icon_class = isset($icons[$d['department_name']]) ? $icons[$d['department_name']] : 'fa-book';
            echo "<div class='col-md-3'>";
            echo "<a href='department_notes.php?dept_id={$d['id']}' style='text-decoration:none; color:inherit;'>";
            echo "<div class='card text-center dept-card p-4 shadow-sm'>";
            echo "<div class='dept-icon bg-primary mx-auto'><i class='fa {$icon_class}'></i></div>";
            echo "<h5>{$d['department_name']}</h5>";
            echo "<p class='small text-muted'>Click to view notes, syllabus & past questions</p>";
            echo "</div>";
            echo "</a>";
            echo "</div>";
        }
        ?>
    </div>
</div>
 <button
        onclick="history.back()"
        class="w-10 h-10 flex items-center justify-center 
               rounded-full bg-gray-200 hover:bg-gray-300 
               text-gray-700 hover:text-gray-900 
               shadow transition"
        title="Go Back">
        ←
    </button>
    <?php include 'footer.php'; ?>
</body>
</html>
