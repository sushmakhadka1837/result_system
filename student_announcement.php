<?php
session_start();
require 'db_config.php';

$student_id = $_SESSION['student_id'];

// Fetch student info
$stmt = $conn->prepare("SELECT s.*, d.department_name FROM students s LEFT JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch only internal notices for this student
$stmt2 = $conn->prepare("
    SELECT n.*, t.full_name AS teacher_name,
    CASE WHEN n.department_id=0 THEN 'All Departments' ELSE d.department_name END AS department_name
    FROM notices n
    JOIN teachers t ON n.teacher_id=t.id
    LEFT JOIN departments d ON n.department_id=d.id
    WHERE n.notice_type='internal'
      AND (n.department_id=? OR n.department_id=0)
      AND (n.semester=? OR n.semester='all')
    ORDER BY n.created_at DESC
");
$stmt2->bind_param("is", $student['department_id'], $student['semester']);
$stmt2->execute();
$internal_notices = $stmt2->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Internal Notices</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#f4f6f8; font-family:'Segoe UI', sans-serif; }
.notice-card {
    border-left: 4px solid red; /* internal = red */
    background: #fff;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.2s;
}
.notice-card:hover { background:#eef2fb; transform: translateY(-2px); }
.notice-meta { font-size:0.8rem; color:#555; margin-top:5px; }
.notice-message { font-size:0.85rem; color:#333; line-height:1.3; }
</style>
</head>
<body>



<div class="container mt-4">
    <h2>📢 Internal Notices</h2>

    <?php if($internal_notices->num_rows > 0): ?>
        <?php while($row = $internal_notices->fetch_assoc()): ?>
            <div class="notice-card" onclick="location.href='view_notice.php?id=<?= $row['id'] ?>'">
                <h4><?= htmlspecialchars($row['title']) ?></h4>
                <p class="notice-message"><?= nl2br(substr(htmlspecialchars($row['message']),0,200)) ?>...</p>
                <div class="notice-meta">
                    📅 <?= date("M d, Y", strtotime($row['created_at'])) ?> • 
                    👨‍🏫 <?= htmlspecialchars($row['teacher_name']) ?> • 
                    Dept: <?= $row['department_name'] ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No internal notices available.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
