<?php
session_start();
require 'db_config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>College Updates | PEC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary-blue: #1a73e8;
        --bg-gray: #f8f9fa;
    }

    body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background: var(--bg-gray);
    }

    /* Page Header */
    .update-header {
        background: white;
        padding: 40px 0;
        border-bottom: 1px solid #e0e0e0;
        margin-bottom: 30px;
    }

    /* Notice Card Styling */
    .notice-card {
        background: #fff;
        border: none;
        border-radius: 16px;
        padding: 24px;
        height: 100%;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        display: flex;
        flex-direction: column;
    }

    .notice-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.1);
    }

    .notice-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 100%;
        background: var(--accent-color, var(--primary-blue));
    }

    .notice-type-badge {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 50px;
        display: inline-block;
        margin-bottom: 15px;
    }

    .notice-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
        line-height: 1.3;
    }

    .notice-message {
        font-size: 0.95rem;
        color: #64748b;
        line-height: 1.6;
        margin-bottom: 20px;
        flex-grow: 1;
    }

    /* Meta Information */
    .notice-footer {
        border-top: 1px solid #f1f5f9;
        padding-top: 15px;
        margin-top: auto;
    }

    .meta-item {
        font-size: 0.8rem;
        color: #94a3b8;
        margin-right: 15px;
        display: inline-flex;
        align-items: center;
    }

    .meta-item i {
        margin-right: 5px;
    }

    .attachment-btn {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        color: var(--primary-blue);
        padding: 8px 15px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 15px;
        transition: 0.2s;
    }

    .attachment-btn:hover {
        background: var(--primary-blue);
        color: white;
    }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<section class="update-header">
    <div class="container text-center">
        <h1 class="display-5 fw-bold mb-2">📢 College Updates</h1>
        <p class="text-muted">Stay informed with the latest exam schedules, notices, and internal news.</p>
    </div>
</section>

<div class="container pb-5">
    <div class="row">
    <?php
    $query = "
        SELECT n.*, t.full_name AS teacher_name,
        CASE WHEN n.department_id = 0 THEN 'All Dept' ELSE d.department_name END AS department_name,
        CASE WHEN n.semester = 0 THEN 'All Sem' ELSE CONCAT(n.semester, ' Sem') END AS semester_name
        FROM notices n
        LEFT JOIN teachers t ON n.teacher_id = t.id
        LEFT JOIN departments d ON n.department_id = d.id
        WHERE n.notice_type IN ('internal','exam','general')
        ORDER BY n.created_at DESC
    ";
    $result = $conn->query($query);

    if($result && $result->num_rows > 0):
        while($notice = $result->fetch_assoc()):
            // Modern Color Coding
            $type_color = match($notice['notice_type']){
                'internal' => ['bg' => '#fee2e2', 'text' => '#dc2626', 'border' => '#dc2626'], // Red
                'exam' => ['bg' => '#ffedd5', 'text' => '#ea580c', 'border' => '#ea580c'],     // Orange
                'general' => ['bg' => '#dbeafe', 'text' => '#2563eb', 'border' => '#2563eb'],  // Blue
                default => ['bg' => '#f1f5f9', 'text' => '#475569', 'border' => '#475569'],
            };
    ?>
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="notice-card" 
                 style="--accent-color: <?= $type_color['border'] ?>;" 
                 onclick="location.href='notice_detail.php?id=<?= $notice['id']; ?>'">
                
                <div class="d-flex justify-content-between align-items-start">
                    <span class="notice-type-badge" style="background: <?= $type_color['bg'] ?>; color: <?= $type_color['text'] ?>;">
                        <i class="fas fa-info-circle me-1"></i> <?= $notice['notice_type'] ?>
                    </span>
                    <small class="text-muted small"><i class="far fa-calendar-alt me-1"></i> <?= date('M d, Y', strtotime($notice['created_at'])) ?></small>
                </div>

                <h3 class="notice-title"><?= htmlspecialchars($notice['title']); ?></h3>
                
                <p class="notice-message">
                    <?= nl2br(htmlspecialchars(substr($notice['message'], 0, 180))); ?>...
                </p>

                <div class="notice-footer">
                    <div class="d-flex flex-wrap">
                        <span class="meta-item"><i class="fas fa-university"></i> <?= $notice['department_name']; ?></span>
                        <span class="meta-item"><i class="fas fa-layer-group"></i> <?= $notice['semester_name']; ?></span>
                        <span class="meta-item"><i class="fas fa-user-circle"></i> <?= $notice['teacher_name']; ?></span>
                    </div>

                    <?php if(!empty($notice['attachment'])): ?>
                        <a href="<?= $notice['attachment']; ?>" target="_blank" class="attachment-btn" onclick="event.stopPropagation();">
                            <i class="fas fa-paperclip me-2"></i> View Document
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php
        endwhile;
    else:
        echo "<div class='text-center py-5'><i class='fas fa-folder-open fa-3x text-muted mb-3'></i><p>No updates at the moment.</p></div>";
    endif;
    ?>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>