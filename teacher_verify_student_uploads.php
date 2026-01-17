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

/* ============= GET TEACHER'S ASSIGNED SUBJECTS ============= */
$assigned_subjects_query = "SELECT DISTINCT s.id, s.subject_name, d.department_name 
                           FROM subjects_master s
                           JOIN departments d ON s.department_id = d.id
                           WHERE s.id IN (SELECT subject_map_id FROM teacher_subjects WHERE teacher_id = ?)
                           ORDER BY s.subject_name";
$stmt = $conn->prepare($assigned_subjects_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$assigned_subjects = $stmt->get_result();

/* ============= FILTER BY SUBJECT ============= */
$subject_filter = intval($_GET['subject_id'] ?? 0);

/* ============= APPROVE UPLOAD ============= */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve'){
    $upload_id = intval($_POST['upload_id']);
    $comment = htmlspecialchars($_POST['comment'] ?? '');
    
    // Verify this upload is for a subject assigned to this teacher
    $verify_query = "SELECT n.id, n.subject_id, n.uploader_id, n.title, s.id as subject_check, sm.subject_name
                    FROM notes n
                    JOIN subjects_master s ON n.subject_id = s.id
                    JOIN subjects_master sm ON n.subject_id = sm.id
                    WHERE n.id = ? AND n.uploader_role = 'student' AND n.approval_status = 'pending'
                    AND s.id IN (SELECT subject_map_id FROM teacher_subjects WHERE teacher_id = ?)";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $upload_id, $teacher_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if($verify_result->num_rows > 0){
        $upload = $verify_result->fetch_assoc();
        
        // Update notes table
        $update_stmt = $conn->prepare("UPDATE notes SET approval_status='approved', approved_by=?, approved_at=NOW() WHERE id=?");
        $update_stmt->bind_param("ii", $teacher_id, $upload_id);
        $update_stmt->execute();
        
        // Log the action
        $log_stmt = $conn->prepare("INSERT INTO upload_approval_log (upload_id, teacher_id, action, comment) VALUES (?, ?, 'approved', ?)");
        $log_stmt->bind_param("iis", $upload_id, $teacher_id, $comment);
        $log_stmt->execute();
        
        // Send notification to student (if mail is configured)
        @notifyStudentUploadApproved($upload['uploader_id'], $upload['title'], $upload['subject_name'], $conn);
        
        $_SESSION['success_msg'] = "Upload approved successfully!";
    }
    
    header("Location: teacher_verify_student_uploads.php");
    exit();
}

/* ============= REJECT/DELETE UPLOAD ============= */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject'){
    $upload_id = intval($_POST['upload_id']);
    $rejection_reason = htmlspecialchars($_POST['rejection_reason']);
    
    // Verify this upload is for a subject assigned to this teacher
    $verify_query = "SELECT n.id, n.subject_id, n.uploader_id, n.file_path, n.title, s.id as subject_check
                    FROM notes n
                    JOIN subjects_master s ON n.subject_id = s.id
                    WHERE n.id = ? AND n.uploader_role = 'student' AND n.approval_status = 'pending'
                    AND s.id IN (SELECT subject_map_id FROM teacher_subjects WHERE teacher_id = ?)";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $upload_id, $teacher_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if($verify_result->num_rows > 0){
        $upload = $verify_result->fetch_assoc();
        
        // Delete file
        if(file_exists($upload['file_path'])){
            unlink($upload['file_path']);
        }
        
        // Delete from database
        $delete_stmt = $conn->prepare("DELETE FROM notes WHERE id=?");
        $delete_stmt->bind_param("i", $upload_id);
        $delete_stmt->execute();
        
        // Log the action
        $log_stmt = $conn->prepare("INSERT INTO upload_approval_log (upload_id, teacher_id, action, comment) VALUES (?, ?, 'rejected', ?)");
        $log_stmt->bind_param("iis", $upload_id, $teacher_id, $rejection_reason);
        $log_stmt->execute();
        
        // Send notification to student (if mail is configured)
        @notifyStudentUploadRejected($upload['uploader_id'], $upload['title'], $rejection_reason, $conn);
        
        $_SESSION['success_msg'] = "Upload rejected and deleted.";
    }
    
    header("Location: teacher_verify_student_uploads.php");
    exit();
}

/* ============= FLAG AS PLAGIARISM ============= */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'flag_plagiarism'){
    $upload_id = intval($_POST['upload_id']);
    $plagiarism_reason = htmlspecialchars($_POST['plagiarism_reason']);
    
    // Verify this upload is for a subject assigned to this teacher
    $verify_query = "SELECT n.id, n.subject_id, n.uploader_id, n.title, s.id as subject_check, sm.subject_name
                    FROM notes n
                    JOIN subjects_master s ON n.subject_id = s.id
                    JOIN subjects_master sm ON n.subject_id = sm.id
                    WHERE n.id = ? AND n.uploader_role = 'student'
                    AND s.id IN (SELECT subject_map_id FROM teacher_subjects WHERE teacher_id = ?)";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $upload_id, $teacher_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if($verify_result->num_rows > 0){
        $upload = $verify_result->fetch_assoc();
        
        // Update notes table
        $update_stmt = $conn->prepare("UPDATE notes SET approval_status='plagiarized', has_penalty=1 WHERE id=?");
        $update_stmt->bind_param("i", $upload_id);
        $update_stmt->execute();
        
        // Record penalty
        $penalty_stmt = $conn->prepare("INSERT INTO student_penalties (student_id, upload_id, penalty_type, penalty_points, reason, imposed_by) 
                                        VALUES (?, ?, 'plagiarism', 10, ?, ?)");
        $penalty_stmt->bind_param("iisi", $upload['uploader_id'], $upload_id, $plagiarism_reason, $teacher_id);
        $penalty_stmt->execute();
        
        // Log the action
        $log_stmt = $conn->prepare("INSERT INTO upload_approval_log (upload_id, teacher_id, action, comment) VALUES (?, ?, 'flagged_plagiarism', ?)");
        $log_stmt->bind_param("iis", $upload_id, $teacher_id, $plagiarism_reason);
        $log_stmt->execute();
        
        // Send notification to student (if mail is configured)
        @notifyStudentPlagiarismFlag($upload['uploader_id'], $upload['title'], $plagiarism_reason, 10, $conn);
        
        $_SESSION['success_msg'] = "Upload flagged as plagiarism and penalty recorded.";
    }
    
    header("Location: teacher_verify_student_uploads.php");
    exit();
}

/* ============= GET PENDING UPLOADS ============= */
$pending_uploads_query = "SELECT n.id, n.title, n.file_path, n.note_type, n.created_at, 
                                n.uploader_id, s.full_name, s.email, s.symbol_no,
                                sm.subject_name, d.department_name, sd.declaration_text, sd.agreed_at
                         FROM notes n
                         JOIN students s ON n.uploader_id = s.id
                         JOIN subjects_master sm ON n.subject_id = sm.id
                         JOIN departments d ON sm.department_id = d.id
                         LEFT JOIN student_upload_declarations sd ON n.id = sd.upload_id
                         WHERE n.uploader_role = 'student' 
                         AND n.approval_status = 'pending'
                         AND sm.id IN (SELECT subject_map_id FROM teacher_subjects WHERE teacher_id = ?)
                         " . ($subject_filter ? "AND n.subject_id = $subject_filter " : "") . "
                         ORDER BY n.created_at DESC";

$pending_stmt = $conn->prepare($pending_uploads_query);
$pending_stmt->bind_param("i", $teacher_id);
$pending_stmt->execute();
$pending_uploads = $pending_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Student Uploads - Teacher Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --navy: #001f4d; }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar-top { background: var(--navy); }
        .upload-card { border: none; border-radius: 15px; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 6px solid #ffc107; }
        .upload-card.approved { border-left-color: #28a745; }
        .upload-card.plagiarism { border-left-color: #dc3545; }
        .student-info { background: #f0f4f8; padding: 12px; border-radius: 8px; font-size: 0.9rem; }
        .declaration-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; border-radius: 6px; margin: 10px 0; }
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .modal-header { background: var(--navy); color: white; }
        .badge-pending { background: #ffc107; color: #000; }
    </style>
</head>
<body>

<?php include 'teacher_header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-1"><i class="fa fa-check-circle text-warning me-2"></i>Verify Student Uploads</h2>
            <p class="text-muted small">Review and approve student's uploaded study materials</p>
        </div>
    </div>

    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i><?= $_SESSION['success_msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <!-- Filter by Subject -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" class="d-flex gap-2">
                <select name="subject_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    <?php
                    $assigned_subjects->data_seek(0);
                    while($subj = $assigned_subjects->fetch_assoc()):
                    ?>
                        <option value="<?= $subj['id'] ?>" <?= $subject_filter == $subj['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subj['subject_name']) ?> (<?= htmlspecialchars($subj['department_name']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>
        <div class="col-md-6 text-end">
            <span class="badge badge-pending fs-6 py-2 px-3">
                <i class="fa fa-hourglass-half me-1"></i>
                <?= $pending_uploads->num_rows ?> Pending Uploads
            </span>
        </div>
    </div>

    <!-- Pending Uploads List -->
    <div class="row">
        <div class="col-12">
            <?php if($pending_uploads->num_rows > 0): ?>
                <?php while($upload = $pending_uploads->fetch_assoc()): ?>
                    <div class="upload-card p-4 mb-3">
                        <!-- Student Info -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <h5 class="fw-bold mb-2">
                                    <i class="fa fa-file-pdf text-danger me-2"></i>
                                    <?= htmlspecialchars($upload['title']) ?>
                                </h5>
                                <div class="student-info">
                                    <div class="mb-2">
                                        <strong>Student:</strong> <?= htmlspecialchars($upload['full_name']) ?>
                                        <span class="badge bg-light text-dark ms-2"><?= htmlspecialchars($upload['symbol_no']) ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($upload['email']) ?>"><?= htmlspecialchars($upload['email']) ?></a>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Subject:</strong> <?= htmlspecialchars($upload['subject_name']) ?>
                                    </div>
                                    <div class="mb-0">
                                        <strong>Uploaded:</strong> <?= date('M d, Y \a\t H:i', strtotime($upload['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge badge-pending py-2 px-3">
                                    <i class="fa fa-hourglass-half me-1"></i>Pending
                                </span>
                            </div>
                        </div>

                        <!-- Declaration Section -->
                        <?php if($upload['declaration_text']): ?>
                            <div class="declaration-box">
                                <strong class="d-block mb-2">
                                    <i class="fa fa-handshake me-1 text-warning"></i>Student Declaration:
                                </strong>
                                <p class="mb-1 small">"<?= htmlspecialchars($upload['declaration_text']) ?>"</p>
                                <small class="text-muted">
                                    Declared on: <?= date('M d, Y \a\t H:i', strtotime($upload['agreed_at'])) ?>
                                </small>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="action-buttons pt-2">
                            <!-- View File Button -->
                            <a href="<?= htmlspecialchars($upload['file_path']) ?>" target="_blank" class="btn btn-sm btn-info">
                                <i class="fa fa-eye me-1"></i>View File
                            </a>

                            <!-- Download Button -->
                            <a href="<?= htmlspecialchars($upload['file_path']) ?>" download class="btn btn-sm btn-secondary">
                                <i class="fa fa-download me-1"></i>Download
                            </a>

                            <!-- Approve Button -->
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?= $upload['id'] ?>">
                                <i class="fa fa-check-circle me-1"></i>Approve
                            </button>

                            <!-- Plagiarism Flag Button -->
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#plagiarismModal<?= $upload['id'] ?>">
                                <i class="fa fa-ban me-1"></i>Flag Plagiarism
                            </button>

                            <!-- Reject Button -->
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $upload['id'] ?>">
                                <i class="fa fa-trash me-1"></i>Reject
                            </button>
                        </div>
                    </div>

                    <!-- Approve Modal -->
                    <div class="modal fade" id="approveModal<?= $upload['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Approve Upload</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <p class="mb-3">Approve this upload for <strong><?= htmlspecialchars($upload['full_name']) ?></strong>?</p>
                                        <div class="mb-3">
                                            <label class="form-label">Optional Comment</label>
                                            <textarea name="comment" class="form-control" rows="3" placeholder="e.g., Good quality notes, well organized..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success" name="action" value="approve">
                                            <i class="fa fa-check me-1"></i>Approve
                                        </button>
                                    </div>
                                    <input type="hidden" name="upload_id" value="<?= $upload['id'] ?>">
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Plagiarism Modal -->
                    <div class="modal fade" id="plagiarismModal<?= $upload['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-danger">
                                    <h5 class="modal-title text-white">Flag as Plagiarism</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <div class="alert alert-warning">
                                            <i class="fa fa-exclamation-triangle me-2"></i>
                                            This will record a penalty (10 points) for the student and mark the upload as plagiarized.
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Reason for Plagiarism Flag</label>
                                            <textarea name="plagiarism_reason" class="form-control" rows="4" required placeholder="Explain why this is flagged as plagiarism..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger" name="action" value="flag_plagiarism">
                                            <i class="fa fa-ban me-1"></i>Flag as Plagiarism
                                        </button>
                                    </div>
                                    <input type="hidden" name="upload_id" value="<?= $upload['id'] ?>">
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div class="modal fade" id="rejectModal<?= $upload['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Reject Upload</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <p class="mb-3">Reject this upload for <strong><?= htmlspecialchars($upload['full_name']) ?></strong>?</p>
                                        <div class="mb-3">
                                            <label class="form-label">Rejection Reason</label>
                                            <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="e.g., Incomplete content, poor quality, not relevant..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-outline-danger" name="action" value="reject">
                                            <i class="fa fa-trash me-1"></i>Reject & Delete
                                        </button>
                                    </div>
                                    <input type="hidden" name="upload_id" value="<?= $upload['id'] ?>">
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded-4 border">
                    <i class="fa fa-inbox fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No Pending Uploads</h5>
                    <p class="text-muted small">All student uploads have been reviewed!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
