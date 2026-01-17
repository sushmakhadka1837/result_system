<?php
session_start();
require 'db_config.php';

/* ============= AUTH CHECK ============= */
if(!isset($_SESSION['student_id'])){
    header("Location: student_login.php");
    exit();
}
$student_id = $_SESSION['student_id'];

/* ============= SUBMIT APPEAL ============= */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appeal'])){
    $penalty_id = intval($_POST['penalty_id']);
    $appeal_reason = htmlspecialchars($_POST['appeal_reason']);
    
    // Verify this penalty belongs to the student
    $verify_stmt = $conn->prepare("SELECT id FROM student_penalties WHERE id=? AND student_id=? AND status='active'");
    $verify_stmt->bind_param("ii", $penalty_id, $student_id);
    $verify_stmt->execute();
    
    if($verify_stmt->get_result()->num_rows > 0){
        $update_stmt = $conn->prepare("UPDATE student_penalties SET status='appeal_pending', appeal_reason=?, appeal_date=NOW() WHERE id=?");
        $update_stmt->bind_param("si", $appeal_reason, $penalty_id);
        if($update_stmt->execute()){
            $_SESSION['success_msg'] = "Appeal submitted successfully. Your case will be reviewed by the teacher.";
            header("Location: student_penalties_view.php");
            exit();
        }
    }
}

/* ============= GET ACTIVE PENALTIES ============= */
$penalties_stmt = $conn->prepare("SELECT sp.*, n.title as upload_title, s.subject_name 
                                  FROM student_penalties sp
                                  LEFT JOIN notes n ON sp.upload_id = n.id
                                  LEFT JOIN subjects_master s ON n.subject_id = s.id
                                  WHERE sp.student_id = ? AND sp.status = 'active'
                                  ORDER BY sp.imposed_at DESC");
$penalties_stmt->bind_param("i", $student_id);
$penalties_stmt->execute();
$penalties_result = $penalties_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Penalties - Student Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --navy: #001f4d; }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .penalty-badge { display: inline-block; padding: 8px 12px; border-radius: 6px; font-size: 0.9rem; }
        .penalty-card { border: none; border-radius: 12px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .penalty-high { border-left: 6px solid #dc3545; }
        .penalty-points { background: #dc3545; color: white; padding: 12px 18px; border-radius: 8px; display: inline-block; }
        .appeal-form { background: #f0f7ff; border: 1px solid #b0d4ff; border-radius: 12px; padding: 20px; }
    </style>
</head>
<body>

<?php include 'student_header.php'; ?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-1"><i class="fa fa-exclamation-triangle text-warning me-2"></i>My Academic Records</h2>
            <p class="text-muted small">View and appeal any penalties imposed on your account</p>
        </div>
    </div>

    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i><?= $_SESSION['success_msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <!-- Penalties List -->
    <div class="row">
        <div class="col-12">
            <?php if($penalties_result->num_rows > 0): ?>
                <?php while($penalty = $penalties_result->fetch_assoc()): ?>
                    <div class="penalty-card p-4 mb-4 penalty-high">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Penalty Info -->
                                <h5 class="fw-bold mb-3">
                                    <i class="fa fa-ban text-danger me-2"></i>
                                    <?= ucfirst(str_replace('_', ' ', $penalty['penalty_type'])) ?>
                                </h5>

                                <div class="mb-3">
                                    <strong>Reason:</strong>
                                    <p class="text-muted small"><?= htmlspecialchars($penalty['reason']) ?></p>
                                </div>

                                <?php if($penalty['upload_title']): ?>
                                    <div class="mb-3">
                                        <strong>Related Upload:</strong> 
                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($penalty['upload_title']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <small class="text-muted">
                                    <i class="fa fa-calendar me-1"></i>
                                    Imposed on <?= date('M d, Y', strtotime($penalty['imposed_at'])) ?>
                                </small>
                            </div>

                            <div class="col-md-4 text-end">
                                <div class="mb-3">
                                    <span class="penalty-points">
                                        <div class="fs-5 fw-bold"><?= $penalty['penalty_points'] ?></div>
                                        <div class="small">Points</div>
                                    </span>
                                </div>

                                <!-- Appeal Button -->
                                <button class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#appealModal<?= $penalty['id'] ?>">
                                    <i class="fa fa-file-alt me-1"></i>Submit Appeal
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Appeal Modal -->
                    <div class="modal fade" id="appealModal<?= $penalty['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">
                                        <i class="fa fa-file-alt me-2"></i>Submit Appeal
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body appeal-form">
                                        <div class="alert alert-info small mb-3">
                                            <i class="fa fa-info-circle me-2"></i>
                                            <strong>Important:</strong> Be honest and specific in your appeal. False appeals will result in additional penalties.
                                        </div>

                                        <h6 class="fw-bold mb-2">Penalty Details</h6>
                                        <div class="mb-3 p-2 bg-white rounded border">
                                            <strong>Type:</strong> <?= ucfirst(str_replace('_', ' ', $penalty['penalty_type'])) ?><br>
                                            <strong>Reason:</strong> <?= htmlspecialchars($penalty['reason']) ?><br>
                                            <strong>Points:</strong> <?= $penalty['penalty_points'] ?>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Your Appeal Reason</label>
                                            <p class="small text-muted mb-2">Explain why you believe this penalty was unjust or should be reduced:</p>
                                            <textarea name="appeal_reason" class="form-control" rows="5" required 
                                                      placeholder="Provide detailed explanation of your appeal..."></textarea>
                                        </div>

                                        <p class="small text-muted">
                                            <i class="fa fa-lock me-1"></i>Your appeal will be reviewed by the concerned teacher.
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary" name="submit_appeal" value="1">
                                            <i class="fa fa-paper-plane me-1"></i>Submit Appeal
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
                    <h5 class="text-success">Clean Record!</h5>
                    <p class="text-muted">No active penalties on your account. Keep up the good academic integrity!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Guidelines Section -->
    <div class="row mt-5">
        <div class="col-md-8">
            <div class="card border-0 rounded-4 bg-light p-4">
                <h5 class="fw-bold mb-3"><i class="fa fa-book me-2"></i>Academic Integrity Guidelines</h5>
                <ul class="small mb-0">
                    <li class="mb-2"><strong>Original Work:</strong> All uploaded notes must be your own. Copying from other sources without attribution is plagiarism.</li>
                    <li class="mb-2"><strong>Self Declaration:</strong> By uploading, you declare the material is original and authentic.</li>
                    <li class="mb-2"><strong>Penalties:</strong> False declarations or plagiarism result in penalty points that may affect your grades.</li>
                    <li class="mb-2"><strong>Appeals:</strong> You have the right to appeal any penalty within 7 days of imposition.</li>
                    <li class="mb-2"><strong>Accumulation:</strong> Accumulated penalty points (20+) may result in academic warnings or suspension.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
