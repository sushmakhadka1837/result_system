<?php
session_start();
require 'db_config.php';
require_once 'common.php'; 
require_once 'notification_helper.php';

if (!isset($_SESSION['student_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch student details
$stmt = $conn->prepare("SELECT s.*, d.department_name FROM students s LEFT JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$stmt->bind_param("i",$student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Auto-select current semester logic
$filter_batch = $student['batch_year'];
$filter_department = $student['department_id'];
$current_semester_order = getCurrentSemester($filter_batch);

$stmt_sem = $conn->prepare("SELECT id FROM semesters WHERE department_id=? AND semester_order=?");
$stmt_sem->bind_param("ii", $filter_department, $current_semester_order);
$stmt_sem->execute();
$result_sem = $stmt_sem->get_result();
$filter_semester = ($row_sem = $result_sem->fetch_assoc()) ? $row_sem['id'] : 0;

$absence_success = '';
$absence_error = '';
$absence_requests = [];
$open_requests_panel = true; // Always show on dedicated page
$absence_table_exists = false;
$absence_has_exam_fields = false;
$absence_has_scope = false;
$student_subjects = [];
$current_subject_semester = (int)$current_semester_order;

if ($current_subject_semester <= 0) {
    $current_subject_semester = (int)($student['semester_id'] ?? 0);
}

if ($current_subject_semester > 8) {
    $sem_order_stmt = $conn->prepare("SELECT semester_order FROM semesters WHERE id = ? LIMIT 1");
    $sem_order_stmt->bind_param("i", $current_subject_semester);
    $sem_order_stmt->execute();
    $sem_order_res = $sem_order_stmt->get_result()->fetch_assoc();
    $mapped_order = (int)($sem_order_res['semester_order'] ?? 0);
    if ($mapped_order > 0) {
        $current_subject_semester = $mapped_order;
    }
}

$absence_table_check = $conn->query("SHOW TABLES LIKE 'student_absence_requests'");
if ($absence_table_check && $absence_table_check->num_rows > 0) {
    $absence_table_exists = true;

    $subject_col_check = $conn->query("SHOW COLUMNS FROM student_absence_requests LIKE 'subject_id'");
    $exam_col_check = $conn->query("SHOW COLUMNS FROM student_absence_requests LIKE 'exam_component'");
    $scope_col_check = $conn->query("SHOW COLUMNS FROM student_absence_requests LIKE 'request_scope'");
    $absence_has_exam_fields = ($subject_col_check && $subject_col_check->num_rows > 0) && ($exam_col_check && $exam_col_check->num_rows > 0);
    $absence_has_scope = ($scope_col_check && $scope_col_check->num_rows > 0);

    $subject_stmt = $conn->prepare("SELECT DISTINCT sm.id, sm.subject_name, sm.subject_code
                                    FROM teacher_subjects ts
                                    JOIN subjects_master sm ON sm.id = ts.subject_map_id
                                    WHERE ts.department_id = ?
                                        AND ts.semester_id = ?
                                        AND ts.batch_year = ?
                                        AND (sm.subject_type IS NULL OR sm.subject_type IN ('Regular', 'Elective'))
                                        AND sm.subject_name NOT LIKE '%Internship%'
                                        AND sm.subject_name NOT LIKE '%Project%'
                                        AND LOWER(sm.subject_name) NOT IN ('internship', 'project', 'elective')
                                        AND LOWER(sm.subject_code) NOT IN ('internship', 'project', 'elective')
                                    ORDER BY sm.subject_name ASC");
    $subject_stmt->bind_param("iii", $filter_department, $current_subject_semester, $filter_batch);
    $subject_stmt->execute();
    $subject_result = $subject_stmt->get_result();
    while ($sub = $subject_result->fetch_assoc()) {
        $student_subjects[] = $sub;
    }

    if (empty($student_subjects)) {
        $fallback_stmt = $conn->prepare("SELECT DISTINCT sm.id, sm.subject_name, sm.subject_code
                                         FROM subjects_master sm
                                         WHERE sm.department_id = ?
                                             AND sm.semester_id = ?
                                             AND (sm.subject_type IS NULL OR sm.subject_type IN ('Regular', 'Elective'))
                                             AND sm.subject_name NOT LIKE '%Internship%'
                                             AND sm.subject_name NOT LIKE '%Project%'
                                             AND LOWER(sm.subject_name) NOT IN ('internship', 'project', 'elective')
                                             AND LOWER(sm.subject_code) NOT IN ('internship', 'project', 'elective')
                                         ORDER BY sm.subject_name ASC");
        $fallback_stmt->bind_param("ii", $filter_department, $current_subject_semester);
        $fallback_stmt->execute();
        $fallback_result = $fallback_stmt->get_result();
        while ($sub = $fallback_result->fetch_assoc()) {
            $student_subjects[] = $sub;
        }
    }
}

// Delete request handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_absence_request'])) {
    if (!$absence_table_exists) {
        $absence_error = 'Absence request feature is not ready yet. Please contact admin.';
    } else {
        $request_id = (int)($_POST['request_id'] ?? 0);
        if ($request_id <= 0) {
            $absence_error = 'Invalid request selected.';
        } else {
            $check_stmt = $conn->prepare("SELECT proof_file FROM student_absence_requests WHERE id = ? AND student_id = ? AND status = 'pending' LIMIT 1");
            $check_stmt->bind_param("ii", $request_id, $student_id);
            $check_stmt->execute();
            $check_row = $check_stmt->get_result()->fetch_assoc();

            if (!$check_row) {
                $absence_error = 'Only pending requests can be deleted.';
            } else {
                $delete_stmt = $conn->prepare("DELETE FROM student_absence_requests WHERE id = ? AND student_id = ? AND status = 'pending'");
                $delete_stmt->bind_param("ii", $request_id, $student_id);
                if ($delete_stmt->execute()) {
                    $proof_path = $check_row['proof_file'] ?? '';
                    if ($proof_path && file_exists($proof_path)) {
                        @unlink($proof_path);
                    }
                    $absence_success = 'Request deleted successfully.';
                } else {
                    $absence_error = 'Unable to delete request. Please try again.';
                }
            }
        }
    }
}

// Submit request handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_absence_request'])) {
    if (!$absence_table_exists) {
        $absence_error = 'Absence request feature is not ready yet. Please contact admin.';
    } elseif (!$absence_has_exam_fields || !$absence_has_scope) {
        $absence_error = 'Please ask admin to run alter_absence_requests_add_exam_fields.sql first.';
    } else {
        $request_scope = trim($_POST['request_scope'] ?? '');
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $request_type = trim($_POST['request_type'] ?? '');
        $exam_component = trim($_POST['exam_component'] ?? '');
        $absence_date = trim($_POST['absence_date'] ?? '');
        $reason_text = trim($_POST['reason_text'] ?? '');

        $allowed_types = ['Medical', 'Family Emergency', 'Official', 'Other'];
        $allowed_exam_components = ['UT', 'Assessment', 'Final', 'Practical', 'Other'];
        $allowed_scopes = ['class', 'exam'];
        $valid_subject_ids = array_map(static fn($row) => (int)$row['id'], $student_subjects);

        if (!in_array($request_scope, $allowed_scopes, true)) {
            $absence_error = 'Please select a valid request scope.';
        } elseif ($request_scope === 'exam' && ($subject_id <= 0 || !in_array($subject_id, $valid_subject_ids, true))) {
            $absence_error = 'Please select a valid subject.';
        } elseif ($request_scope === 'exam' && !in_array($exam_component, $allowed_exam_components, true)) {
            $absence_error = 'Please select a valid exam type.';
        } elseif (!in_array($request_type, $allowed_types, true)) {
            $absence_error = 'Please select a valid request type.';
        } elseif (empty($absence_date)) {
            $absence_error = 'Please select absence date.';
        } elseif (strlen($reason_text) < 15) {
            $absence_error = 'Please provide a detailed reason (minimum 15 characters).';
        } elseif (!isset($_FILES['proof_file']) || ($_FILES['proof_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $absence_error = 'Proof document is required.';
        } else {
            $proof_file = $_FILES['proof_file'];
            $ext = strtolower(pathinfo($proof_file['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];

            if (!in_array($ext, $allowed_ext, true)) {
                $absence_error = 'Invalid file type. Allowed: PDF, JPG, JPEG, PNG.';
            } elseif (($proof_file['size'] ?? 0) > (5 * 1024 * 1024)) {
                $absence_error = 'File size must be less than 5MB.';
            } else {
                $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'absence_proofs' . DIRECTORY_SEPARATOR;
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_name = 'absence_' . $student_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $relative_path = 'uploads/absence_proofs/' . $new_name;
                $full_path = $upload_dir . $new_name;

                if (!move_uploaded_file($proof_file['tmp_name'], $full_path)) {
                    $absence_error = 'Unable to upload proof file. Try again.';
                } else {
                    $subject_id_param = $request_scope === 'exam' ? $subject_id : null;
                    $exam_component_param = $request_scope === 'exam' ? $exam_component : null;

                    $insert_absence = $conn->prepare("INSERT INTO student_absence_requests (student_id, request_scope, subject_id, request_type, exam_component, absence_date, reason_text, proof_file, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                    $insert_absence->bind_param("isisssss", $student_id, $request_scope, $subject_id_param, $request_type, $exam_component_param, $absence_date, $reason_text, $relative_path);

                    if ($insert_absence->execute()) {
                        $subject_info = ['subject_name' => 'Class Leave', 'subject_code' => ''];
                        if ($request_scope === 'exam' && $subject_id_param) {
                            $subject_info_stmt = $conn->prepare("SELECT subject_name, subject_code FROM subjects_master WHERE id = ? LIMIT 1");
                            $subject_info_stmt->bind_param("i", $subject_id_param);
                            $subject_info_stmt->execute();
                            $subject_info = $subject_info_stmt->get_result()->fetch_assoc();
                        }

                        $teacher_ids = [];
                        if ($request_scope === 'exam' && $subject_id_param) {
                            $teacher_stmt = $conn->prepare("SELECT DISTINCT ts.teacher_id
                                                            FROM teacher_subjects ts
                                                            WHERE ts.subject_map_id = ?
                                                              AND ts.department_id = ?
                                                              AND ts.semester_id = ?
                                                              AND ts.batch_year = ?");
                            $teacher_stmt->bind_param("iiii", $subject_id_param, $filter_department, $current_subject_semester, $filter_batch);
                            $teacher_stmt->execute();
                            $teacher_result = $teacher_stmt->get_result();
                            while ($teacher_row = $teacher_result->fetch_assoc()) {
                                $teacher_ids[(int)$teacher_row['teacher_id']] = true;
                            }
                        } else {
                            $teacher_stmt = $conn->prepare("SELECT DISTINCT ts.teacher_id
                                                            FROM teacher_subjects ts
                                                            WHERE ts.department_id = ?
                                                              AND ts.semester_id = ?
                                                              AND ts.batch_year = ?");
                            $teacher_stmt->bind_param("iii", $filter_department, $current_subject_semester, $filter_batch);
                            $teacher_stmt->execute();
                            $teacher_result = $teacher_stmt->get_result();
                            while ($teacher_row = $teacher_result->fetch_assoc()) {
                                $teacher_ids[(int)$teacher_row['teacher_id']] = true;
                            }
                        }

                        if (empty($teacher_ids) && $request_scope === 'exam' && $subject_id_param) {
                            $fallback_teacher_stmt = $conn->prepare("SELECT DISTINCT ts.teacher_id
                                                                     FROM teacher_subjects ts
                                                                     WHERE ts.subject_map_id = ?");
                            $fallback_teacher_stmt->bind_param("i", $subject_id_param);
                            $fallback_teacher_stmt->execute();
                            $fallback_teacher_result = $fallback_teacher_stmt->get_result();
                            while ($teacher_row = $fallback_teacher_result->fetch_assoc()) {
                                $teacher_ids[(int)$teacher_row['teacher_id']] = true;
                            }
                        }

                        foreach (array_keys($teacher_ids) as $target_teacher_id) {
                            @notifyTeacherAbsenceRequest(
                                (int)$target_teacher_id,
                                $student['full_name'],
                                $subject_info['subject_name'] ?? 'Unknown',
                                $subject_info['subject_code'] ?? '',
                                $exam_component_param ?: 'Class',
                                $absence_date,
                                $conn
                            );
                        }

                        $absence_success = 'Request submitted successfully. Please wait for teacher review.';
                    } else {
                        $absence_error = 'Could not save request. Please try again.';
                    }
                }
            }
        }
    }
}

// Fetch request history
if ($absence_table_exists) {
    if ($absence_has_exam_fields) {
        $absence_stmt = $conn->prepare("SELECT ar.id, ar.request_scope, ar.request_type, ar.exam_component, ar.absence_date, ar.reason_text, ar.proof_file, ar.status, ar.teacher_remark, ar.created_at,
                                               sm.subject_name, sm.subject_code
                                        FROM student_absence_requests ar
                                        LEFT JOIN subjects_master sm ON ar.subject_id = sm.id
                                        WHERE ar.student_id = ?
                                        ORDER BY ar.created_at DESC
                                        LIMIT 20");
    } else {
        $absence_stmt = $conn->prepare("SELECT id, request_type, absence_date, reason_text, proof_file, status, teacher_remark, created_at FROM student_absence_requests WHERE student_id = ? ORDER BY created_at DESC LIMIT 20");
    }
    $absence_stmt->bind_param("i", $student_id);
    $absence_stmt->execute();
    $absence_result = $absence_stmt->get_result();
    while ($row = $absence_result->fetch_assoc()) {
        $absence_requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave / Absence Requests | PEC Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy: #001f4d;
            --gold: #f4c430;
            --bg-gray: #f3f7ff;
        }
        body { 
            background: radial-gradient(circle at top right, rgba(29, 78, 216, 0.08), transparent 38%),
                        radial-gradient(circle at left center, rgba(14, 116, 144, 0.06), transparent 34%),
                        var(--bg-gray);
            font-family: 'Inter', 'Poppins', sans-serif; 
            color: #1e293b; 
        }
        .section-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.07);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 32px;
            border-radius: 18px;
            margin-bottom: 24px;
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.25);
        }
        .card-title-custom {
            color: var(--navy);
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 0;
        }
        .form-label { font-weight: 600; color: #334155; }
        .btn-danger { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border: none; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(220, 38, 38, 0.3); }
    </style>
</head>
<body>
<?php include 'student_header.php'; ?>

<div class="container my-5">
    <div class="hero-section">
        <div class="d-flex align-items-center gap-3 mb-2">
            <i class="fas fa-notes-medical fa-2x"></i>
            <h2 class="mb-0 fw-bold">Leave / Absence Requests</h2>
        </div>
        <p class="mb-0 opacity-90">Submit medical or emergency leave requests for missed classes or exams.</p>
    </div>

    <div class="section-card mb-4">
        <h4 class="card-title-custom mb-4">
            <i class="fas fa-file-medical text-danger me-2"></i>Submit New Request
        </h4>

        <?php if (!$absence_table_exists): ?>
            <div class="alert alert-warning mb-3">Absence module is not initialized yet. Please ask admin to run <strong>create_absence_requests_table.sql</strong>.</div>
        <?php elseif (!$absence_has_exam_fields || !$absence_has_scope): ?>
            <div class="alert alert-warning mb-3">Please ask admin to run <strong>alter_absence_requests_add_exam_fields.sql</strong> to enable scope/subject tracking.</div>
        <?php endif; ?>

        <?php if (!empty($absence_success)): ?>
            <div class="alert alert-success mb-3"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($absence_success) ?></div>
        <?php endif; ?>

        <?php if (!empty($absence_error)): ?>
            <div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($absence_error) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Request Scope</label>
                <select name="request_scope" id="request_scope" class="form-select" required>
                    <option value="">Select scope</option>
                    <option value="class">Class Leave</option>
                    <option value="exam" selected>Exam Absence</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Subject</label>
                <select name="subject_id" id="subject_id" class="form-select" required>
                    <option value="">Select subject</option>
                    <?php foreach ($student_subjects as $sub): ?>
                        <option value="<?= (int)$sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?> (<?= htmlspecialchars($sub['subject_code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Exam Type</label>
                <select name="exam_component" id="exam_component" class="form-select" required>
                    <option value="">Select exam</option>
                    <option value="UT">UT</option>
                    <option value="Assessment">Assessment</option>
                    <option value="Practical">Practical</option>
                    <option value="Final">Final</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Request Type</label>
                <select name="request_type" class="form-select" required>
                    <option value="">Select type</option>
                    <option value="Medical">Medical</option>
                    <option value="Family Emergency">Family Emergency</option>
                    <option value="Official">Official</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Absence Date</label>
                <input type="date" name="absence_date" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Proof (PDF/JPG/PNG, max 5MB)</label>
                <input type="file" name="proof_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <div class="col-12">
                <label class="form-label">Reason</label>
                <textarea name="reason_text" class="form-control" rows="3" placeholder="Explain your situation clearly (minimum 15 characters)..." required></textarea>
            </div>
            <div class="col-12">
                <button type="submit" name="submit_absence_request" class="btn btn-danger px-4" <?= (!$absence_table_exists || !$absence_has_exam_fields || !$absence_has_scope) ? 'disabled' : '' ?>>
                    <i class="fas fa-paper-plane me-2"></i>Submit Request
                </button>
            </div>
        </form>
    </div>

    <div class="section-card">
        <h4 class="card-title-custom mb-4">
            <i class="fas fa-history text-primary me-2"></i>My Request History
        </h4>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Scope</th>
                        <th>Subject</th>
                        <th>Exam</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Proof</th>
                        <th>Teacher Remark</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($absence_requests)): ?>
                        <?php foreach ($absence_requests as $req): ?>
                            <tr>
                                <td><?= htmlspecialchars(ucfirst($req['request_scope'] ?? 'exam')) ?></td>
                                <td>
                                    <?php if (!empty($req['subject_name'])): ?>
                                        <?= htmlspecialchars($req['subject_name']) ?>
                                        <div class="text-muted small"><?= htmlspecialchars($req['subject_code'] ?? '') ?></div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($req['exam_component'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($req['absence_date']) ?></td>
                                <td><?= htmlspecialchars($req['request_type']) ?></td>
                                <td>
                                    <?php
                                    $status = strtolower($req['status']);
                                    $status_class = 'secondary';
                                    if ($status === 'approved') $status_class = 'success';
                                    elseif ($status === 'rejected') $status_class = 'danger';
                                    elseif ($status === 'need_info') $status_class = 'warning';
                                    elseif ($status === 'pending') $status_class = 'primary';
                                    ?>
                                    <span class="badge bg-<?= $status_class ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($req['proof_file'])): ?>
                                        <a href="<?= htmlspecialchars($req['proof_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($req['teacher_remark']) ? htmlspecialchars($req['teacher_remark']) : '<span class="text-muted">-</span>' ?></td>
                                <td>
                                    <?php if (($req['status'] ?? '') === 'pending'): ?>
                                        <form method="post" onsubmit="return confirm('Delete this request?');">
                                            <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                            <button type="submit" name="delete_absence_request" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No requests yet. Submit your first request above.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
