<?php
session_start();
require 'db_config.php';

$teacher_id = $_SESSION['teacher_id'] ?? 0;
if(!$teacher_id){
    die("<div class='alert alert-danger'>Unauthorized</div>");
}

/* ================= DELETE NOTICE ================= */
if(isset($_GET['delete'])){
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM notices WHERE id=? AND teacher_id=?");
    $stmt->bind_param("ii", $delete_id, $teacher_id);
    $stmt->execute();
    header("Location: manage_announcements.php?msg=deleted");
    exit;
}

/* ================= UPDATE NOTICE ================= */
if(isset($_POST['update_notice'])){
    $id = intval($_POST['notice_id']);
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $type = $_POST['notice_type'];

    $stmt = $conn->prepare("UPDATE notices SET title=?, message=?, notice_type=? WHERE id=? AND teacher_id=?");
    $stmt->bind_param("sssii", $title, $message, $type, $id, $teacher_id);
    $stmt->execute();
    header("Location: manage_announcements.php?msg=updated");
    exit;
}

$stmt = $conn->prepare("
    SELECT n.*, d.department_name
    FROM notices n
    LEFT JOIN departments d ON d.id = n.department_id
    WHERE n.teacher_id=?
    ORDER BY n.created_at DESC
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Records | Academic Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --primary: #4318FF;
            --bg: #F4F7FE;
            --navy: #1B2559;
            --grey: #A3AED0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--navy);
        }

        .page-header {
            background: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .notice-card {
            border: none;
            border-radius: 20px;
            background: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .notice-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }

        .type-badge {
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 8px;
            letter-spacing: 0.5px;
        }

        .badge-exam { background: rgba(255, 91, 91, 0.1); color: #FF5B5B; }
        .badge-general { background: rgba(67, 24, 255, 0.1); color: var(--primary); }
        .badge-internal { background: rgba(5, 205, 153, 0.1); color: #05CD99; }

        .notice-title {
            font-weight: 700;
            font-size: 1.1rem;
            line-height: 1.4;
            color: var(--navy);
            margin-top: 15px;
        }

        .notice-info {
            font-size: 0.85rem;
            color: var(--grey);
            font-weight: 500;
        }

        .notice-msg {
            font-size: 0.9rem;
            color: #4A5568;
            line-height: 1.6;
            margin: 15px 0;
        }

        .image-stack {
            display: flex;
            margin-top: 10px;
        }

        .stack-img {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid white;
            margin-left: -10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .stack-img:first-child { margin-left: 0; }

        .action-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            border: none;
        }

        .btn-edit { background: #F4F7FE; color: var(--primary); }
        .btn-edit:hover { background: var(--primary); color: white; }

        .btn-delete { background: #FFF5F5; color: #FF5B5B; }
        .btn-delete:hover { background: #FF5B5B; color: white; }

        .custom-modal {
            border-radius: 24px;
            border: none;
        }

        .form-control-custom {
            background: #F4F7FE;
            border: 1px solid transparent;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
        }
        .form-control-custom:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: none;
        }
    </style>
</head>
<body>

<?php include 'teacher_header.php'; ?>

<div class="page-header shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold m-0"><i class="bi bi-stack me-2 text-primary"></i> Notice Archive</h4>
            <p class="text-muted small m-0">Review and manage your previous announcements</p>
        </div>
        <a href="teacher_dashboard.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
            <i class="bi bi-plus-lg me-1"></i> New Notice
        </a>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <?php if($result->num_rows == 0): ?>
            <div class="col-12 text-center py-5">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" style="width: 100px; opacity: 0.5;">
                <h5 class="mt-3 text-muted">No notices found.</h5>
            </div>
        <?php endif; ?>

        <?php while($row = $result->fetch_assoc()): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card notice-card h-100 p-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="type-badge badge-<?= $row['notice_type'] ?>">
                            <?= $row['notice_type'] ?>
                        </span>
                        <div class="notice-info">
                            <i class="bi bi-clock me-1"></i> <?= date('M j, Y', strtotime($row['created_at'])) ?>
                        </div>
                    </div>

                    <h5 class="notice-title"><?= htmlspecialchars($row['title']) ?></h5>
                    
                    <div class="notice-info mt-1">
                        <i class="bi bi-geo-alt-fill me-1"></i>
                        <?= $row['department_id']==0 ? 'All Departments' : $row['department_name'] ?>
                        <span class="mx-1">•</span> Sem <?= $row['semester'] ?: 'All' ?>
                    </div>

                    <div class="notice-msg text-muted">
                        <?= nl2br(htmlspecialchars(substr($row['message'], 0, 100))) ?><?= strlen($row['message']) > 100 ? '...' : '' ?>
                    </div>

                    <?php
                    if(!empty($row['notice_images'])){
                        $imgs = json_decode($row['notice_images'], true);
                        if(is_array($imgs)){
                            echo "<div class='image-stack mb-3'>";
                            foreach(array_slice($imgs, 0, 4) as $img){
                                echo "<img src='$img' class='stack-img'>";
                            }
                            if(count($imgs) > 4) echo "<span class='ms-2 small text-muted mt-2'>+".(count($imgs)-4)." more</span>";
                            echo "</div>";
                        }
                    }
                    ?>

                    <div class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top">
                        <div class="small fw-bold text-primary">
                            <i class="bi bi-check-all me-1"></i> Sent to Students
                        </div>
                        <div class="d-flex gap-2">
                            <button class="action-btn btn-edit" data-bs-toggle="modal" data-bs-target="#edit<?= $row['id'] ?>" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <a href="?delete=<?= $row['id'] ?>" class="action-btn btn-delete" onclick="return confirm('Archive this notice permanently?')" title="Delete">
                                <i class="bi bi-trash3-fill"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="edit<?= $row['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" class="modal-content custom-modal">
                        <div class="modal-header border-0 px-4 pt-4">
                            <h5 class="fw-bold">Modify Announcement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body px-4 pb-4">
                            <input type="hidden" name="notice_id" value="<?= $row['id'] ?>">
                            
                            <label class="small fw-bold text-muted mb-1">Notice Title</label>
                            <input type="text" name="title" class="form-control form-control-custom mb-3" value="<?= htmlspecialchars($row['title']) ?>" required>

                            <label class="small fw-bold text-muted mb-1">Details</label>
                            <textarea name="message" class="form-control form-control-custom mb-3" rows="4"><?= htmlspecialchars($row['message']) ?></textarea>

                            <label class="small fw-bold text-muted mb-1">Category</label>
                            <select name="notice_type" class="form-select form-control-custom">
                                <option value="internal" <?= $row['notice_type']=='internal'?'selected':'' ?>>💼 Internal Meeting</option>
                                <option value="exam" <?= $row['notice_type']=='exam'?'selected':'' ?>>📝 Exam Related</option>
                                <option value="general" <?= $row['notice_type']=='general'?'selected':'' ?>>📢 General Notice</option>
                            </select>
                        </div>
                        <div class="modal-footer border-0 px-4 pb-4 pt-0">
                            <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_notice" class="btn btn-primary rounded-pill px-4 fw-bold shadow">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>