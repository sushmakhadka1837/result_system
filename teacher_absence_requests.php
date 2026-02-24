<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['teacher_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = (int)$_SESSION['teacher_id'];
$filter_status = trim($_GET['status'] ?? 'pending');
$filter_subject_id = (int)($_GET['subject_id'] ?? 0);
$allowed_status = ['pending', 'approved', 'rejected', 'need_info', 'all'];
if (!in_array($filter_status, $allowed_status, true)) {
    $filter_status = 'pending';
}

$table_exists = false;
$has_exam_fields = false;
$has_scope = false;
$table_check = $conn->query("SHOW TABLES LIKE 'student_absence_requests'");
if ($table_check && $table_check->num_rows > 0) {
    $table_exists = true;
    $subject_col_check = $conn->query("SHOW COLUMNS FROM student_absence_requests LIKE 'subject_id'");
    $exam_col_check = $conn->query("SHOW COLUMNS FROM student_absence_requests LIKE 'exam_component'");
    $scope_col_check = $conn->query("SHOW COLUMNS FROM student_absence_requests LIKE 'request_scope'");
    $has_exam_fields = ($subject_col_check && $subject_col_check->num_rows > 0) && ($exam_col_check && $exam_col_check->num_rows > 0);
    $has_scope = ($scope_col_check && $scope_col_check->num_rows > 0);
}

$teacher_subjects = [];
if ($table_exists && $has_exam_fields) {
    $subject_stmt = $conn->prepare("SELECT DISTINCT sm.id, sm.subject_name, sm.subject_code
                                    FROM teacher_subjects ts
                                    JOIN subjects_master sm ON sm.id = ts.subject_map_id
                                    WHERE ts.teacher_id = ?
                                    ORDER BY sm.subject_name ASC");
    $subject_stmt->bind_param("i", $teacher_id);
    $subject_stmt->execute();
    $subject_result = $subject_stmt->get_result();
    while ($row = $subject_result->fetch_assoc()) {
        $teacher_subjects[] = $row;
    }
}

if ($filter_subject_id > 0) {
    $valid_subject_ids = array_map(static fn($row) => (int)$row['id'], $teacher_subjects);
    if (!in_array($filter_subject_id, $valid_subject_ids, true)) {
        $filter_subject_id = 0;
    }
}

$flash_message = '';
$flash_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $table_exists && isset($_POST['update_absence_status'])) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    $teacher_remark = trim($_POST['teacher_remark'] ?? '');

    if ($request_id <= 0 || !in_array($new_status, ['approved', 'rejected', 'need_info'], true)) {
        $flash_message = 'Invalid request action.';
        $flash_type = 'danger';
    } elseif ($teacher_remark === '') {
        $flash_message = 'Teacher remark is required.';
        $flash_type = 'danger';
    } else {
        $update_stmt = $conn->prepare("UPDATE student_absence_requests SET status = ?, teacher_remark = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("ssii", $new_status, $teacher_remark, $teacher_id, $request_id);
        if ($update_stmt->execute()) {
            $flash_message = 'Request updated successfully.';
            $flash_type = 'success';
        } else {
            $flash_message = 'Unable to update request. Try again.';
            $flash_type = 'danger';
        }
    }
}

$requests = [];
if ($table_exists) {
    $select_subjects = $has_exam_fields ? ", sm.subject_name, sm.subject_code" : "";
    $join_subjects = $has_exam_fields ? "LEFT JOIN subjects_master sm ON ar.subject_id = sm.id" : "";
    $join_teacher = $has_exam_fields
        ? "JOIN teacher_subjects ts ON ts.teacher_id = ? AND ((ar.request_scope = 'exam' AND ts.subject_map_id = ar.subject_id) OR (ar.request_scope = 'class' AND ts.department_id = s.department_id AND ts.batch_year = s.batch_year))"
        : "";

    $where_clauses = [];
    $params = [];
    $types = "";

    if ($has_exam_fields) {
        $params[] = $teacher_id;
        $types .= "i";
    }

    if ($filter_status !== 'all') {
        $where_clauses[] = "ar.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }

    if ($has_exam_fields && $filter_subject_id > 0) {
        $where_clauses[] = "ar.subject_id = ?";
        $params[] = $filter_subject_id;
        $types .= "i";
    }

    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = "WHERE " . implode(" AND ", $where_clauses);
    }

    $list_sql = "SELECT ar.*, s.full_name, s.symbol_no, s.batch_year" . $select_subjects . "
                 FROM student_absence_requests ar
                 JOIN students s ON ar.student_id = s.id
                 $join_teacher
                 $join_subjects
                 $where_sql
                 ORDER BY ar.created_at DESC";

    $list_stmt = $conn->prepare($list_sql);
    if (!empty($params)) {
        $list_stmt->bind_param($types, ...$params);
    }
    $list_stmt->execute();
    $requests_result = $list_stmt->get_result();
    while ($row = $requests_result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Absence Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .panel-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    </style>
</head>
<body>
<?php include 'teacher_header.php'; ?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h4 class="mb-0"><i class="fas fa-user-clock me-2 text-primary"></i>Student Absence Requests</h4>
        <a href="teacher_dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <?php if (!$table_exists): ?>
        <div class="alert alert-warning">Absence request table not found. Please run <strong>create_absence_requests_table.sql</strong>.</div>
    <?php else: ?>
        <?php if (!$has_exam_fields): ?>
            <div class="alert alert-warning">To show exact missed exam details (subject + exam), run <strong>alter_absence_requests_add_exam_fields.sql</strong>.</div>
        <?php endif; ?>
        <?php if ($flash_message !== ''): ?>
            <div class="alert alert-<?= $flash_type ?>"><?= htmlspecialchars($flash_message) ?></div>
        <?php endif; ?>

        <?php $subject_query = $filter_subject_id > 0 ? '&subject_id=' . $filter_subject_id : ''; ?>
        <div class="panel-card p-3 mb-3 d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="btn-group" role="group">
                <a href="?status=pending<?= $subject_query ?>" class="btn btn-<?= $filter_status === 'pending' ? 'primary' : 'outline-primary' ?> btn-sm">Pending</a>
                <a href="?status=need_info<?= $subject_query ?>" class="btn btn-<?= $filter_status === 'need_info' ? 'warning' : 'outline-warning' ?> btn-sm">Need Info</a>
                <a href="?status=approved<?= $subject_query ?>" class="btn btn-<?= $filter_status === 'approved' ? 'success' : 'outline-success' ?> btn-sm">Approved</a>
                <a href="?status=rejected<?= $subject_query ?>" class="btn btn-<?= $filter_status === 'rejected' ? 'danger' : 'outline-danger' ?> btn-sm">Rejected</a>
                <a href="?status=all<?= $subject_query ?>" class="btn btn-<?= $filter_status === 'all' ? 'dark' : 'outline-dark' ?> btn-sm">All</a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Subject filter</span>
                <select class="form-select form-select-sm" style="min-width: 240px;" onchange="window.location.href='?status=<?= $filter_status ?>&subject_id=' + this.value">
                    <option value="0">All Subjects</option>
                    <?php foreach ($teacher_subjects as $sub): ?>
                        <option value="<?= (int)$sub['id'] ?>" <?= $filter_subject_id === (int)$sub['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub['subject_name']) ?> (<?= htmlspecialchars($sub['subject_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="panel-card p-3">
            <?php if (!empty($requests)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Scope</th>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Exam</th>
                                <th>Type</th>
                                <th>Absence Date</th>
                                <th>Reason</th>
                                <th>Proof</th>
                                <th>Status</th>
                                <th style="min-width: 260px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars(ucfirst($row['request_scope'] ?? 'exam')) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($row['full_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['symbol_no']) ?> | Batch <?= htmlspecialchars($row['batch_year']) ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['subject_name'])): ?>
                                            <?= htmlspecialchars($row['subject_name']) ?>
                                            <div class="text-muted small"><?= htmlspecialchars($row['subject_code'] ?? '') ?></div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['exam_component'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['request_type']) ?></td>
                                    <td><?= htmlspecialchars($row['absence_date']) ?></td>
                                    <td style="max-width: 260px;"><?= nl2br(htmlspecialchars($row['reason_text'])) ?></td>
                                    <td>
                                        <?php if (!empty($row['proof_file'])): ?>
                                            <a href="<?= htmlspecialchars($row['proof_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = strtolower($row['status']);
                                        $status_class = 'secondary';
                                        if ($status === 'approved') $status_class = 'success';
                                        elseif ($status === 'rejected') $status_class = 'danger';
                                        elseif ($status === 'need_info') $status_class = 'warning';
                                        elseif ($status === 'pending') $status_class = 'primary';
                                        ?>
                                        <span class="badge bg-<?= $status_class ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                                    </td>
                                    <td>
                                        <form method="post" class="d-flex flex-column gap-2">
                                            <input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>">
                                            <select name="new_status" class="form-select form-select-sm" required>
                                                <option value="">Select action</option>
                                                <option value="approved">Approve</option>
                                                <option value="rejected">Reject</option>
                                                <option value="need_info">Need More Info</option>
                                            </select>
                                            <textarea name="teacher_remark" class="form-control form-control-sm" rows="2" placeholder="Remark (required)" required></textarea>
                                            <button type="submit" name="update_absence_status" class="btn btn-sm btn-primary">Update</button>
                                        </form>
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
