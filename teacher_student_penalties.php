<?php
session_start();
require 'db_config.php';
require 'notification_helper.php';

/* ============= AUTH CHECK ============= */
if(!isset($_SESSION['teacher_id'])){
    header("Location: teacher_login.php");
    exit();
}
$teacher_id = $_SESSION['teacher_id'];

/* ============= VIEW STUDENT PENALTIES ============= */
$filter_subject = intval($_GET['subject_id'] ?? 0);
$filter_status = $_GET['status'] ?? 'active';
$filter_student = htmlspecialchars($_GET['student_search'] ?? '');

$penalties_query = "SELECT sp.*, s.full_name, s.email, s.symbol_no,
                          n.title as upload_title, sm.subject_name
                   FROM student_penalties sp
                   JOIN students s ON sp.student_id = s.id
                   LEFT JOIN notes n ON sp.upload_id = n.id
                   LEFT JOIN subjects_master sm ON n.subject_id = sm.id
                   WHERE sp.status = ? ";

if($filter_student){
    $penalties_query .= "AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.roll_number LIKE ?) ";
}

$penalties_query .= "ORDER BY sp.imposed_at DESC";

$penalties_stmt = $conn->prepare($penalties_query);

if($filter_student){
    $search_term = "%$filter_student%";
    $penalties_stmt->bind_param("sssss", $filter_status, $search_term, $search_term, $search_term, $search_term);
} else {
    $penalties_stmt->bind_param("s", $filter_status);
}

$penalties_stmt->execute();
$penalties_result = $penalties_stmt->get_result();

/* ============= REMOVE PENALTY ============= */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_penalty'){
    $penalty_id = intval($_POST['penalty_id']);
    
    $remove_stmt = $conn->prepare("UPDATE student_penalties SET status='removed' WHERE id=?");
    $remove_stmt->bind_param("i", $penalty_id);
    if($remove_stmt->execute()){
        // Get penalty details for notification
        $penalty_details = $conn->query("SELECT student_id, penalty_type FROM student_penalties WHERE id=$penalty_id")->fetch_assoc();
        notifyStudentAppealResolved($penalty_details['student_id'], $penalty_details['penalty_type'], 'removed', $conn);
        $_SESSION['success_msg'] = "Penalty removed successfully.";
    }
    
    header("Location: teacher_student_penalties.php");
    exit();
}

/* ============= APPEAL RESOLUTION ============= */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resolve_appeal'){
    $penalty_id = intval($_POST['penalty_id']);
    $appeal_decision = $_POST['appeal_decision']; // 'approve_appeal' or 'reject_appeal'
    
    if($appeal_decision === 'approve_appeal'){
        $new_status = 'removed';
    } else {
        $new_status = 'appeal_resolved';
    }
    
    $update_stmt = $conn->prepare("UPDATE student_penalties SET status=? WHERE id=?");
    $update_stmt->bind_param("si", $new_status, $penalty_id);
    if($update_stmt->execute()){
        // Get penalty details for notification
        $penalty_details = $conn->query("SELECT student_id, penalty_type FROM student_penalties WHERE id=$penalty_id")->fetch_assoc();
        notifyStudentAppealResolved($penalty_details['student_id'], $penalty_details['penalty_type'], $appeal_decision, $conn);
        $_SESSION['success_msg'] = "Appeal " . ($appeal_decision === 'approve_appeal' ? 'approved' : 'rejected') . " successfully.";
    }
    
    header("Location: teacher_student_penalties.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Penalties - Academic Integrity</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --navy: #001f4d; }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar-top { background: var(--navy); }
        .penalty-card { border: none; border-radius: 12px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .penalty-level-high { border-left: 6px solid #dc3545; }
        .penalty-level-medium { border-left: 6px solid #ffc107; }
        .penalty-level-low { border-left: 6px solid #17a2b8; }
        .badge-penalty-points { background: #dc3545; color: white; font-size: 0.95rem; }
        .student-badge { background: #e7f3ff; color: var(--navy); padding: 8px 12px; border-radius: 6px; }
        .tab-header { background: var(--navy); color: white; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
    </style>
</head>
<body>

<?php include 'teacher_header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-1"><i class="fa fa-exclamation-triangle text-warning me-2"></i>Student Penalties</h2>
            <p class="text-muted small">Manage academic integrity penalties and appeals</p>
        </div>
    </div>

    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i><?= $_SESSION['success_msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="student_search" class="form-control form-control-sm" 
                       placeholder="Search student name, email, roll..." value="<?= htmlspecialchars($filter_student) ?>">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-search me-1"></i>Search</button>
            </form>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group" role="group">
                <a href="?status=active" class="btn btn-sm <?= $filter_status == 'active' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fa fa-ban me-1"></i>Active (<?= $conn->query("SELECT COUNT(*) as cnt FROM student_penalties WHERE status='active'")->fetch_assoc()['cnt'] ?>)
                </a>
                <a href="?status=appeal_pending" class="btn btn-sm <?= $filter_status == 'appeal_pending' ? 'btn-warning' : 'btn-outline-warning' ?>">
                    <i class="fa fa-clock me-1"></i>Appeals Pending
                </a>
                <a href="?status=removed" class="btn btn-sm <?= $filter_status == 'removed' ? 'btn-success' : 'btn-outline-success' ?>">
                    <i class="fa fa-check-circle me-1"></i>Removed
                </a>
            </div>
        </div>
    </div>

    <!-- Penalties List -->
    <div class="row">
        <div class="col-12">
            <?php if($penalties_result->num_rows > 0): ?>
                <?php while($penalty = $penalties_result->fetch_assoc()): 
                    $penalty_class = $penalty['penalty_points'] >= 10 ? 'penalty-level-high' : ($penalty['penalty_points'] >= 5 ? 'penalty-level-medium' : 'penalty-level-low');
                ?>
                    <div class="penalty-card p-4 mb-3 <?= $penalty_class ?>">
                        <div class="row align-items-start">
                            <div class="col-md-8">
                                <!-- Student Info -->
                                <div class="mb-3">
                                    <h5 class="fw-bold mb-2">
                                        <i class="fa fa-student me-1"></i>
                                        <?= htmlspecialchars($penalty['full_name']) ?>
                                    </h5>
                                    <div class="small">
                                        <div class="mb-2">
                                            <span class="student-badge"><?= htmlspecialchars($penalty['symbol_no']) ?></span>
                                            <a href="mailto:<?= htmlspecialchars($penalty['email']) ?>" class="ms-2 text-decoration-none">
                                                <i class="fa fa-envelope me-1"></i><?= htmlspecialchars($penalty['email']) ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Penalty Details -->
                                <div class="mb-3">
                                    <div class="mb-2">
                                        <strong>Penalty Type:</strong>
                                        <span class="badge bg-danger"><?= ucfirst(str_replace('_', ' ', $penalty['penalty_type'])) ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Reason:</strong>
                                        <p class="small text-muted mb-0 mt-1"><?= htmlspecialchars($penalty['reason']) ?></p>
                                    </div>
                                    <?php if($penalty['upload_title']): ?>
                                        <div class="mb-2">
                                            <strong>Related Upload:</strong> <?= htmlspecialchars($penalty['upload_title']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="small text-muted">
                                        <strong>Imposed:</strong> <?= date('M d, Y', strtotime($penalty['imposed_at'])) ?>
                                    </div>
                                </div>

                                <!-- Appeal Info (if pending) -->
                                <?php if($penalty['status'] === 'appeal_pending'): ?>
                                    <div class="alert alert-info small mb-0">
                                        <strong>Appeal Reason:</strong> <?= htmlspecialchars($penalty['appeal_reason']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4">
                                <!-- Penalty Points -->
                                <div class="text-center mb-3">
                                    <span class="badge-penalty-points p-3 rounded-3 d-inline-block">
                                        <div class="fs-5 fw-bold"><?= $penalty['penalty_points'] ?></div>
                                        <div class="small">Points</div>
                                    </span>
                                </div>

                                <!-- Status -->
                                <div class="mb-3 text-center">
                                    <span class="badge bg-<?= $penalty['status'] === 'active' ? 'danger' : ($penalty['status'] === 'appeal_pending' ? 'warning' : 'success') ?> py-2 px-3">
                                        <?= ucfirst(str_replace('_', ' ', $penalty['status'])) ?>
                                    </span>
                                </div>

                                <!-- Actions -->
                                <div class="d-grid gap-2">
                                    <?php if($penalty['status'] === 'active'): ?>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#removePenaltyModal<?= $penalty['id'] ?>">
                                            <i class="fa fa-trash me-1"></i>Remove Penalty
                                        </button>
                                    <?php elseif($penalty['status'] === 'appeal_pending'): ?>
                                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#resolveAppealModal<?= $penalty['id'] ?>">
                                            <i class="fa fa-gavel me-1"></i>Resolve Appeal
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Remove Penalty Modal -->
                    <div class="modal fade" id="removePenaltyModal<?= $penalty['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Remove Penalty</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <p>Remove penalty of <strong><?= $penalty['penalty_points'] ?> points</strong> from 
                                           <strong><?= htmlspecialchars($penalty['full_name']) ?></strong>?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger" name="action" value="remove_penalty">
                                            <i class="fa fa-trash me-1"></i>Remove
                                        </button>
                                    </div>
                                    <input type="hidden" name="penalty_id" value="<?= $penalty['id'] ?>">
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Resolve Appeal Modal -->
                    <div class="modal fade" id="resolveAppealModal<?= $penalty['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title">Resolve Appeal</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <p class="mb-3"><strong>Student Appeal:</strong></p>
                                        <p class="text-muted"><?= htmlspecialchars($penalty['appeal_reason']) ?></p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="appeal_decision" value="approve_appeal" id="approve<?= $penalty['id'] ?>" required>
                                            <label class="form-check-label" for="approve<?= $penalty['id'] ?>">
                                                <strong>Approve Appeal</strong> - Remove the penalty
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="appeal_decision" value="reject_appeal" id="reject<?= $penalty['id'] ?>">
                                            <label class="form-check-label" for="reject<?= $penalty['id'] ?>">
                                                <strong>Reject Appeal</strong> - Keep the penalty
                                            </label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning" name="action" value="resolve_appeal">
                                            <i class="fa fa-gavel me-1"></i>Resolve
                                        </button>
                                    </div>
                                    <input type="hidden" name="penalty_id" value="<?= $penalty['id'] ?>">
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded-4 border">
                    <i class="fa fa-check-circle fs-1 text-success mb-3 d-block"></i>
                    <h5>No <?= $filter_status === 'active' ? 'Active' : ucfirst(str_replace('_', ' ', $filter_status)) ?> Penalties</h5>
                    <p class="text-muted small">Great! All students are following academic integrity rules.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
