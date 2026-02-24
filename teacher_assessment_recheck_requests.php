<?php
session_start();
require 'db_config.php';
require_once 'notification_helper.php';

if (!isset($_SESSION['teacher_id']) || ($_SESSION['user_type'] ?? '') !== 'teacher') {
    header('Location: login.php');
    exit();
}

$teacher_id = (int)$_SESSION['teacher_id'];
$filter_status = trim($_GET['status'] ?? 'pending');
$allowed_status = ['pending', 'teacher_recommended', 'teacher_rejected', 'approved', 'rejected', 'all'];
if (!in_array($filter_status, $allowed_status, true)) {
    $filter_status = 'pending';
}

$table_exists = false;
$table_check = $conn->query("SHOW TABLES LIKE 'assessment_recheck_requests'");
if ($table_check && $table_check->num_rows > 0) {
    $table_exists = true;
}

$flash = '';
$flash_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_request']) && $table_exists) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    $teacher_remark = trim($_POST['teacher_remark'] ?? '');

    if ($request_id <= 0 || !in_array($new_status, ['teacher_recommended', 'teacher_rejected'], true)) {
        $flash = 'Invalid action selected.';
        $flash_type = 'danger';
    } elseif ($teacher_remark === '') {
        $flash = 'Remark is required.';
        $flash_type = 'danger';
    } else {
        $up_stmt = $conn->prepare("UPDATE assessment_recheck_requests SET status = ?, teacher_remark = ?, teacher_reviewed_at = NOW() WHERE id = ? AND assigned_teacher_id = ? AND status = 'pending'");
        $up_stmt->bind_param('ssii', $new_status, $teacher_remark, $request_id, $teacher_id);
        if ($up_stmt->execute() && $up_stmt->affected_rows > 0) {
            $notify_stmt = $conn->prepare("SELECT rr.student_id, rr.request_type, rr.status, rr.teacher_remark, s.full_name AS student_name, sm.subject_name, sm.subject_code
                                           FROM assessment_recheck_requests rr
                                           JOIN students s ON s.id = rr.student_id
                                           JOIN subjects_master sm ON sm.id = rr.subject_id
                                           WHERE rr.id = ? AND rr.assigned_teacher_id = ? LIMIT 1");
            $notify_stmt->bind_param('ii', $request_id, $teacher_id);
            $notify_stmt->execute();
            $notify_row = $notify_stmt->get_result()->fetch_assoc();
            if ($notify_row) {
                notifyStudentRecheckStatusUpdate(
                    (int)$notify_row['student_id'],
                    (string)($notify_row['student_name'] ?? ''),
                    (string)($notify_row['subject_name'] ?? 'Subject'),
                    (string)($notify_row['subject_code'] ?? ''),
                    (string)($notify_row['request_type'] ?? ''),
                    (string)($notify_row['status'] ?? $new_status),
                    (string)($notify_row['teacher_remark'] ?? $teacher_remark),
                    'teacher',
                    $conn
                );
            }
            $flash = 'Request reviewed successfully.';
            $flash_type = 'success';
        } else {
            $flash = 'Unable to update request. It may already be reviewed.';
            $flash_type = 'warning';
        }
    }
}

$requests = [];
if ($table_exists) {
    $where = "rr.assigned_teacher_id = ?";
    $types = 'i';
    $params = [$teacher_id];

    if ($filter_status !== 'all') {
        $where .= " AND rr.status = ?";
        $types .= 's';
        $params[] = $filter_status;
    }

    $list_sql = "SELECT rr.*, s.full_name, s.symbol_no, sm.subject_name, sm.subject_code
                 FROM assessment_recheck_requests rr
                 JOIN students s ON s.id = rr.student_id
                 JOIN subjects_master sm ON sm.id = rr.subject_id
                 WHERE {$where}
                 ORDER BY rr.requested_at DESC";

    $list_stmt = $conn->prepare($list_sql);
    $list_stmt->bind_param($types, ...$params);
    $list_stmt->execute();
    $res = $list_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Recheck Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .panel-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 6px 20px rgba(0,0,0,0.04); }
    </style>
</head>
<body>
<?php include 'teacher_header.php'; ?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h4 class="mb-0"><i class="fas fa-clipboard-check me-2 text-primary"></i>Assessment Re-total / Recheck Requests</h4>
        <a href="teacher_dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <?php if (!$table_exists): ?>
        <div class="alert alert-warning">Feature is not initialized. Ask admin to run <strong>create_assessment_recheck_requests_table.sql</strong>.</div>
    <?php else: ?>
        <?php if ($flash !== ''): ?>
            <div class="alert alert-<?= $flash_type ?>"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div class="panel-card p-3 mb-3">
            <div class="small text-muted mb-2">Filter by status</div>
            <div class="btn-group" role="group">
                <a href="?status=pending" class="btn btn-<?= $filter_status === 'pending' ? 'primary' : 'outline-primary' ?> btn-sm">Pending</a>
                <a href="?status=teacher_recommended" class="btn btn-<?= $filter_status === 'teacher_recommended' ? 'success' : 'outline-success' ?> btn-sm">Recommended</a>
                <a href="?status=teacher_rejected" class="btn btn-<?= $filter_status === 'teacher_rejected' ? 'danger' : 'outline-danger' ?> btn-sm">Rejected</a>
                <a href="?status=approved" class="btn btn-<?= $filter_status === 'approved' ? 'secondary' : 'outline-secondary' ?> btn-sm">Admin Approved</a>
                <a href="?status=all" class="btn btn-<?= $filter_status === 'all' ? 'dark' : 'outline-dark' ?> btn-sm">All</a>
            </div>
        </div>

        <div class="panel-card p-3">
            <?php if (!empty($requests)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Snapshot</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th style="min-width:260px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $row): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($row['full_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($row['symbol_no']) ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['subject_name']) ?>
                                    <div class="small text-muted"><?= htmlspecialchars($row['subject_code']) ?></div>
                                </td>
                                <td><?= htmlspecialchars(ucfirst($row['request_type'])) ?></td>
                                <td><?= ($row['student_marks_snapshot'] !== null) ? htmlspecialchars($row['student_marks_snapshot']) : '-' ?></td>
                                <td style="max-width:250px;"><?= nl2br(htmlspecialchars($row['reason_text'])) ?></td>
                                <td>
                                    <?php
                                        $st = strtolower($row['status']);
                                        $cls = 'secondary';
                                        if ($st === 'pending') $cls = 'primary';
                                        elseif ($st === 'teacher_recommended' || $st === 'approved') $cls = 'success';
                                        elseif ($st === 'teacher_rejected' || $st === 'rejected') $cls = 'danger';
                                    ?>
                                    <span class="badge bg-<?= $cls ?>"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($st))) ?></span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <form method="post" class="d-flex flex-column gap-2">
                                            <input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>">
                                            <select name="new_status" class="form-select form-select-sm" required>
                                                <option value="">Select action</option>
                                                <option value="teacher_recommended">Recommend</option>
                                                <option value="teacher_rejected">Reject</option>
                                            </select>
                                            <textarea name="teacher_remark" class="form-control form-control-sm" rows="2" placeholder="Teacher remark (required)" required></textarea>
                                            <button type="submit" name="review_request" class="btn btn-sm btn-primary">Submit Review</button>
                                        </form>
                                    <?php else: ?>
                                        <small class="text-muted"><?= !empty($row['teacher_remark']) ? nl2br(htmlspecialchars($row['teacher_remark'])) : 'Reviewed' ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">No requests found for this filter.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
