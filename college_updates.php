<?php
session_start();
require 'db_config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>College Updates</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
 body {
      font-family: 'Poppins', sans-serif;
      background: #f5f6fa;
  }

.container h2 {
    color: #1a73e8;
    margin-bottom: 20px;
    font-weight: 600;
}

/* Card styling */
.notice-card {
    width: 100%;
    background: #fff;
    padding: 16px;
    border-radius: 8px;
    cursor: pointer;
    border-left: 5px solid #1a73e8;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    transition: transform 0.2s, background 0.2s, box-shadow 0.2s;
}

.notice-card:hover {
    background: #eef2fb;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

/* Title and badge */
.notice-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.notice-type-badge {
    font-size: 0.75rem;
    color: #fff;
    padding: 3px 7px;
    border-radius: 12px;
    vertical-align: middle;
    margin-left: 6px;
}

/* Message */
.notice-message {
    font-size: 0.9rem;
    color: #333;
    line-height: 1.4;
    margin-bottom: 10px;
}

/* Meta info */
.notice-meta {
    font-size: 0.8rem;
    color: #555;
}

/* Attachment */
.notice-attachment a {
    font-size: 0.85rem;
    color: #1a73e8;
    text-decoration: underline;
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container mt-4">
    <h2>📢 College Updates</h2>
</div>

<div class="container">
    <div class="row">
    <?php
    // Fetch notices of type internal, exam, general
    $query = "
        SELECT n.*, t.full_name AS teacher_name,
        CASE WHEN n.department_id = 0 THEN 'All Departments' ELSE d.department_name END AS department_name,
        CASE WHEN n.semester = 0 THEN 'All Semesters' ELSE n.semester END AS semester_name
        FROM notices n
        LEFT JOIN teachers t ON n.teacher_id = t.id
        LEFT JOIN departments d ON n.department_id = d.id
        WHERE n.notice_type IN ('internal','exam','general')
        ORDER BY n.created_at DESC
    ";
    $result = $conn->query($query);

    if($result && $result->num_rows > 0):
        while($notice = $result->fetch_assoc()):
            // Color coding based on type
            $color = match($notice['notice_type']){
                'internal' => '#dc3545', // red
                'exam' => '#fd7e14',     // orange
                'general' => '#1a73e8',  // blue
                default => '#1a73e8',
            };
    ?>
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="notice-card" style="border-left:5px solid <?= $color ?>;" onclick="location.href='notice_detail.php?id=<?= $notice['id']; ?>'">
                <h5 class="notice-title" style="color:<?= $color ?>;">
                    <?= htmlspecialchars($notice['title']); ?>
                    <span class="notice-type-badge" style="background:<?= $color ?>;"><?= ucfirst($notice['notice_type']); ?></span>
                </h5>
                <p class="notice-message"><?= nl2br(htmlspecialchars(substr($notice['message'],0,200))); ?>...</p>
                <small class="notice-meta">
                    Dept: <?= $notice['department_name']; ?> | Semester: <?= $notice['semester_name']; ?> | By: <?= $notice['teacher_name']; ?>
                </small>
                <?php if(!empty($notice['attachment'])): ?>
                    <div class="notice-attachment">
                        <a href="<?= $notice['attachment']; ?>" target="_blank">📎 View Attachment</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php
        endwhile;
    else:
        echo "<p class='text-center mt-3'>No notices found.</p>";
    endif;
    ?>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
