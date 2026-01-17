<?php
session_start();
require 'db_config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notice Board - Hamro Result</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>


.announcement-section {
    padding: 40px 20px;
    max-width: 900px;
    margin: 40px auto;
}
.announcement-section h2 {
    text-align: center;
    color: #001f4d;
    margin-bottom: 25px;
    font-weight: 700;
}
.announcement-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.announcement-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
.announcement-card h5 {
    color: #0d6efd;
    font-weight: 600;
    margin-bottom: 10px;
}
.announcement-card p {
    color: #333;
    font-size: 1rem;
    line-height: 1.6;
}
.announcement-card small {
    font-size: 0.85rem;
    color: #666;
    display: block;
    margin-top: 10px;
}
</style>
</head>
<body>
<?php include 'header.php';?>
<div class="container announcement-section">
    <h2>📢 Notices</h2>
    <?php
    $notices = $conn->query("
        SELECT n.*, d.department_name, t.full_name AS teacher_name
        FROM notices n
        JOIN departments d ON n.department_id = d.id
        JOIN teachers t ON n.teacher_id = t.id
        ORDER BY n.created_at DESC
    ");

    if($notices->num_rows > 0){
        while($n = $notices->fetch_assoc()){
            echo "
            <div class='announcement-card' onclick=\"location.href='notice_detail.php?id={$n['id']}'\">
                <h5>{$n['title']}</h5>
                <p>".substr($n['message'],0,150)."...</p>
                <small>Department: {$n['department_name']} | By: {$n['teacher_name']}</small>
            </div>
            ";
        }
    } else {
        echo "<p class='text-center'>No notices available.</p>";
    }
    ?>
</div>
<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
