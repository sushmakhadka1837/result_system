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
:root{
    --navy:#001f4d;
    --navy-soft:#0f2d66;
    --gold:#f4c430;
    --bg:#f5f6fa;
    --card:#ffffff;
    --text:#1f2937;
    --muted:#6b7280;
}

body{
    font-family: 'Poppins', sans-serif;
    background:radial-gradient(circle at 15% 20%, rgba(244,196,48,0.1), transparent 28%),
              radial-gradient(circle at 80% 10%, rgba(0,31,77,0.08), transparent 35%),
              var(--bg);
    padding:24px;
    color:var(--text);
}

.notice-container{
    max-width:960px;
    margin:auto;
    background:var(--card);
    padding:34px 42px;
    border-radius:18px;
    box-shadow:0 18px 45px rgba(0,31,77,0.12);
    position:relative;
    border:1px solid rgba(0,31,77,0.06);
    overflow:hidden;
}

.notice-container::before{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(135deg, rgba(0,31,77,0.04) 0%, rgba(244,196,48,0.05) 100%);
    opacity:0.7;
    pointer-events:none;
}

.notice-logo{
    text-align:center;
    margin-bottom:28px;
    position:relative;
    z-index:1;
}

.notice-logo img{ height:90px; }

.download-icon{
    position:absolute;
    top:26px;
    right:30px;
    color:var(--navy);
    font-size:1.6rem;
    transition:0.2s;
    z-index:2;
}
.download-icon:hover{ color:var(--gold); }

.notice-title{
    text-align:center;
    font-weight:800;
    font-size:2rem;
    color:var(--navy);
    margin:0 0 14px;
    position:relative;
    z-index:1;
}

.notice-title::after{
    content:"";
    display:block;
    width:70px;
    height:4px;
    border-radius:999px;
    background:linear-gradient(90deg, var(--navy), var(--gold));
    margin:10px auto 0;
}

.notice-meta{
    text-align:center;
    color:var(--muted);
    font-size:0.95rem;
    margin-bottom:18px;
    position:relative;
    z-index:1;
}

.notice-meta div{
    margin:4px 0;
}

.notice-content{
    font-size:1.05rem;
    line-height:1.75;
    margin-bottom:26px;
    position:relative;
    z-index:1;
}

.notice-gallery{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:16px;
    margin-bottom:10px;
    position:relative;
    z-index:1;
}

.notice-gallery img{
    width:100%;
    height:auto;
    max-height:430px;
    object-fit:contain;
    border-radius:12px;
    cursor:pointer;
    transition:transform 0.2s ease, box-shadow 0.2s ease;
    background:#f8fafc;
    border:1px solid rgba(0,31,77,0.08);
}

.notice-gallery img:hover{
    transform:scale(1.02);
    box-shadow:0 10px 24px rgba(0,31,77,0.15);
}

@media(max-width:768px){
    body{ padding:16px; }
    .notice-container{ padding:22px; }
    .notice-title{ font-size:1.65rem; }
    .download-icon{ top:18px; right:18px; }
}
</style>
</head>

<body>

<?php include 'header.php'; ?>

<main class="py-4">
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
</main>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
