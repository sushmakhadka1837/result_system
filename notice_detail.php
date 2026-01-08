<?php
session_start();
require 'db_config.php';

$notice_id = intval($_GET['id'] ?? 0);
if(!$notice_id){ die("Invalid Notice ID"); }

/* ---------- MARK AS SEEN (STUDENT) ---------- */
if(isset($_SESSION['student_id'])){
    $student_id = $_SESSION['student_id'];
    $conn->query("
        INSERT IGNORE INTO notice_seen (notice_id, student_id)
        VALUES ('$notice_id', '$student_id')
    ");
}

/* ---------- FETCH NOTICE ---------- */
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
$notice = $stmt->get_result()->fetch_assoc();
if(!$notice){ die('Notice not found'); }

/* ---------- DECODE IMAGES ---------- */
$images = !empty($notice['notice_images'])
    ? json_decode($notice['notice_images'], true)
    : [];
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
body{
    font-family: 'Poppins', sans-serif;
    background:#f4f6f9;
    padding:20px;
}

.notice-container{
    max-width:900px;
    margin:auto;
    background:#fff;
    padding:30px 40px;
    border-radius:14px;
    box-shadow:0 8px 24px rgba(0,0,0,0.15);
    position:relative;
}

.notice-logo{
    text-align:center;
    margin-bottom:25px;
}

.notice-logo img{
    height:90px;
}

.download-icon{
    position:absolute;
    top:30px;
    right:40px;
    color:#004085;
    font-size:1.5rem;
    transition:0.2s;
}
.download-icon:hover{
    color:#001f4d;
}

.notice-title{
    text-align:center;
    font-weight:700;
    font-size:1.9rem;
    color:#001f4d;
    margin-bottom:12px;
    margin-top:0;
}

.notice-meta{
    text-align:center;
    color:#555;
    font-size:0.95rem;
    margin-bottom:15px;
}

.notice-meta div{
    margin:3px 0;
}

.notice-content{
    font-size:1.05rem;
    line-height:1.7;
    margin-bottom:25px;
}

.notice-gallery{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:15px;
    margin-bottom:20px;
}

.notice-gallery img{
    width:100%;
    height:auto;
    max-height:450px;
    object-fit:contain;
    border-radius:10px;
    cursor:pointer;
    transition:0.2s;
}

.notice-gallery img:hover{
    transform:scale(1.03);
}

@media(max-width:768px){
    .notice-container{
        padding:20px;
    }
    .notice-title{
        font-size:1.6rem;
    }
    .download-icon{
        top:20px;
        right:20px;
    }
}
</style>
</head>

<body>
<div class="notice-container">

    <!-- DOWNLOAD ICON -->
    <div class="download-icon">
        <a href="download_notice_pdf.php?id=<?= $notice['id'] ?>" title="Download PDF">
            <i class="bi bi-download"></i>
        </a>
    </div>

    <!-- LOGO CENTER TOP -->
    <div class="notice-logo">
        <img src="images/logoheader.png" height="80px" width="100px" alt="College Logo"> <!-- replace path with your logo -->
    </div>

    <!-- TITLE -->
    <div class="notice-title">
        <?= htmlspecialchars($notice['title']) ?>
    </div>

    <!-- META -->
    <div class="notice-meta">
        <div><strong>Date:</strong> <?= date('d M, Y', strtotime($notice['created_at'])) ?></div>
        <div><strong>Department:</strong> <?= htmlspecialchars($notice['department_name']) ?></div>
        <?php if($notice['notice_type']=='internal'): ?>
            <div><strong>Semester:</strong>
                <?= $notice['semester']==0 ? 'All Semesters' : htmlspecialchars($notice['semester']) ?>
            </div>
        <?php endif; ?>
        <div><strong>Published By:</strong> <?= htmlspecialchars($notice['teacher_name']) ?></div>
    </div>

    <hr>

    <!-- MESSAGE -->
    <?php if(!empty($notice['message'])): ?>
        <div class="notice-content">
            <?= nl2br(htmlspecialchars($notice['message'])) ?>
        </div>
    <?php endif; ?>

    <!-- IMAGES -->
    <?php if(!empty($images)): ?>
        <div class="notice-gallery">
            <?php foreach($images as $img): ?>
                <img src="<?= $img ?>" onclick="window.open('<?= $img ?>','_blank')">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
