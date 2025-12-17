<?php
require 'db_config.php';

$notice_id = $_GET['id'] ?? null;
if(!$notice_id){ die("Invalid Notice ID"); }

// Fetch notice with department info
$stmt = $conn->prepare("
    SELECT n.*, t.full_name AS teacher_name, 
    CASE 
        WHEN n.department_id IS NULL OR n.department_id = 0 THEN 'All Departments'
        ELSE d.department_name
    END AS department_name
    FROM notices n
    JOIN teachers t ON n.teacher_id = t.id
    LEFT JOIN departments d ON n.department_id = d.id
    WHERE n.id = ?
");
$stmt->bind_param("i", $notice_id);
$stmt->execute();
$result = $stmt->get_result();
$notice = $result->fetch_assoc();

if(!$notice){ die("Notice not found"); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($notice['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: #f4f6f9; padding: 20px; }
.notice-container { max-width: 800px; margin: auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.notice-title { text-align: center; font-weight: 700; color: #001f4d; margin-bottom: 10px; }
.notice-meta { text-align: center; color: #555; font-size: 0.9rem; margin-bottom: 15px; }
.notice-content { font-size: 1rem; line-height: 1.7; color: #333; }
.download-icon { display: block; text-align: right; margin-bottom: 15px; font-size: 1.5rem; color: #004085; text-decoration: none; }
.download-icon:hover { color: #001f4d; }
</style>
</head>
<body>



<div class="notice-container">
    <a href="download_notice_pdf.php?id=<?= $notice['id'] ?>" class="download-icon" title="Download PDF">
        <i class="bi bi-download"></i>
    </a>

    <div class="notice-title"><?= htmlspecialchars($notice['title']) ?></div>

    <div class="notice-meta">
    <div><strong>Date:</strong> <?= date('d M, Y', strtotime($notice['created_at'])) ?></div>
    <div><strong>Department:</strong> <?= htmlspecialchars($notice['department_name']) ?></div>
    <?php if($notice['notice_type'] === 'internal'): ?>
        <div><strong>Semester:</strong> <?= ($notice['semester']==0) ? 'All Semesters' : htmlspecialchars($notice['semester']); ?></div>
    <?php endif; ?>
    <div><strong>Published By:</strong> <?= htmlspecialchars($notice['teacher_name']) ?></div>
</div>

    <hr>

    <div class="notice-content"><?= nl2br(htmlspecialchars($notice['message'])) ?></div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
