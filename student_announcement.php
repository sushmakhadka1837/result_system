<?php
session_start();
require 'db_config.php';
require 'vendor/autoload.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$student_id = $_SESSION['student_id'] ?? 0;
if(!$student_id){ header("Location:index.php"); exit; }

// Fetch student info
$stmt = $conn->prepare("SELECT s.*, d.department_name FROM students s LEFT JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch all relevant notices (internal + general + exam)
$stmt2 = $conn->prepare("
    SELECT n.*, t.full_name AS teacher_name,
    CASE WHEN n.department_id=0 THEN 'All Departments' ELSE d.department_name END AS department_name
    FROM notices n
    JOIN teachers t ON n.teacher_id=t.id
    LEFT JOIN departments d ON n.department_id=d.id
    WHERE (n.department_id=? OR n.department_id=0)
      AND (n.semester=? OR n.semester='all')
    ORDER BY n.created_at DESC
");
$stmt2->bind_param("is", $student['department_id'], $student['semester']);
$stmt2->execute();
$notices = $stmt2->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Announcements</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f8f9fa; font-family:'Segoe UI', sans-serif; }

header, footer { background:#1a73e8; color:#fff; padding:12px 0; text-align:center; }
header h1, footer p { margin:0; font-weight:600; }

.announcement-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.notice-card {
    border-left: 5px solid #1a73e8;
    background:#fff;
    padding:15px;
    border-radius:8px;
    cursor:pointer;
    transition:0.3s;
    box-shadow:0 2px 6px rgba(0,0,0,0.08);
    display:flex;
    flex-direction:column;
    justify-content:space-between;
}
.notice-card:hover { background:#eef2fb; transform: translateY(-3px); box-shadow:0 6px 12px rgba(0,0,0,0.12); }

.notice-title { font-weight:700; font-size:1.1rem; margin-bottom:8px; }
.notice-type { font-size:0.85rem; color:#fff; background:#007bff; padding:2px 6px; border-radius:4px; }

.notice-meta { font-size:0.8rem; color:#555; margin-top:10px; display:flex; flex-wrap:wrap; gap:10px; }
.notice-message { font-size:0.9rem; color:#333; line-height:1.4; margin-top:8px; flex-grow:1; }
.photo-preview img { height:80px; margin-right:5px; margin-top:8px; border-radius:4px; object-fit:cover; }

@media(max-width:576px){
    .photo-preview img { height:60px; }
}
</style>
</head>
<body>

<!-- Header -->
<?php include 'student_header.php'; ?>
<div class="container my-4">
<h2 class="mb-4 text-center">Recent Announcements</h2>

<div class="announcement-grid">
<?php if($notices->num_rows>0): ?>
    <?php while($n=$notices->fetch_assoc()): 
        $images = !empty($n['notice_images']) ? json_decode($n['notice_images'],true) : [];
    ?>
    <div class="notice-card" onclick="location.href='notice_detail.php?id=<?= $n['id'] ?>'">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="notice-title"><?= htmlspecialchars($n['title']) ?></h5>
            <span class="notice-type"><?= ucfirst($n['notice_type']) ?></span>
        </div>

        <?php if($images): ?>
            <div class="photo-preview d-flex flex-wrap">
                <?php foreach(array_slice($images,0,3) as $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" alt="Notice Image">
                <?php endforeach; ?>
            </div>
            <small class="text-primary"><i class="bi bi-camera-fill"></i> Photo Announcement</small>
        <?php else: ?>
            <p class="notice-message"><?= nl2br(substr(htmlspecialchars($n['message']),0,250)) ?>...</p>
        <?php endif; ?>

        <div class="notice-meta">
            <span><i class="bi bi-calendar-event"></i> <?= date("M d, Y", strtotime($n['created_at'])) ?></span>
            <span><i class="bi bi-person-badge"></i> <?= htmlspecialchars($n['teacher_name']) ?></span>
            <span><i class="bi bi-building"></i> <?= htmlspecialchars($n['department_name']) ?></span>
        </div>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>No announcements available.</p>
<?php endif; ?>
</div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
