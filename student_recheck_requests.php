<?php
session_start();
require 'db_config.php';
require_once 'common.php';
require_once 'notification_helper.php';

if (!isset($_SESSION['student_id']) || ($_SESSION['user_type'] ?? '') !== 'student') {
    header('Location: index.php');
    exit();
}

$student_id = (int)$_SESSION['student_id'];
$success = '';
$error = '';
$window_days = 15;

$table_exists = false;
$table_check = $conn->query("SHOW TABLES LIKE 'assessment_recheck_requests'");
if ($table_check && $table_check->num_rows > 0) {
    $table_exists = true;
}

$stu_stmt = $conn->prepare("SELECT s.id, s.full_name, s.symbol_no, s.department_id, s.batch_year, d.department_name, d.total_semesters FROM students s JOIN departments d ON d.id = s.department_id WHERE s.id = ? LIMIT 1");
$stu_stmt->bind_param('i', $student_id);
$stu_stmt->execute();
$student = $stu_stmt->get_result()->fetch_assoc();

if (!$student) {
    die('Student profile not found.');
}

$current_sem_order = (int)getCurrentSemester((int)$student['batch_year']);
$total_semesters = (int)($student['total_semesters'] ?? 8);
if ($current_sem_order > $total_semesters) {
    $current_sem_order = $total_semesters;
}
if ($current_sem_order < 1) {
    $current_sem_order = 1;
}

$current_sem_id = 0;
$sem_stmt = $conn->prepare("SELECT id FROM semesters WHERE department_id = ? AND semester_order = ? LIMIT 1");
$sem_stmt->bind_param('ii', $student['department_id'], $current_sem_order);
$sem_stmt->execute();
$sem_row = $sem_stmt->get_result()->fetch_assoc();
if ($sem_row) {
    $current_sem_id = (int)$sem_row['id'];
}

$subjects = [];

if ($current_sem_id > 0) {
        // Primary source: student's own published result rows (covers electives + non-assigned subjects)
        $subject_stmt = $conn->prepare("SELECT DISTINCT sm.id, sm.subject_name, sm.subject_code
                                                                        FROM results r
                                                                        JOIN subjects_master sm ON sm.id = r.subject_id
                                                                        WHERE r.student_id = ?
                                                                            AND r.semester_id = ?
                                                                            AND (sm.subject_name NOT LIKE '%Project%' AND sm.subject_name NOT LIKE '%Internship%')
                                                                        ORDER BY sm.subject_name ASC");
        $subject_stmt->bind_param('ii', $student_id, $current_sem_id);
        $subject_stmt->execute();
        $subject_res = $subject_stmt->get_result();
        while ($row = $subject_res->fetch_assoc()) {
                $subjects[] = $row;
        }
}

if (empty($subjects)) {
        // Fallback: teacher assignment mapping for current batch/semester
        $fallback_stmt = $conn->prepare("SELECT DISTINCT sm.id, sm.subject_name, sm.subject_code
                                                                         FROM teacher_subjects ts
                                                                         JOIN subjects_master sm ON sm.id = ts.subject_map_id
                                                                         WHERE ts.department_id = ?
                                                                             AND ts.batch_year = ?
                                                                             AND ts.semester_id = ?
                                                                             AND (sm.subject_name NOT LIKE '%Project%' AND sm.subject_name NOT LIKE '%Internship%')
                                                                         ORDER BY sm.subject_name ASC");
        $fallback_stmt->bind_param('iii', $student['department_id'], $student['batch_year'], $current_sem_order);
        $fallback_stmt->execute();
        $fallback_res = $fallback_stmt->get_result();
        while ($row = $fallback_res->fetch_assoc()) {
                $subjects[] = $row;
        }
}

$subjects_by_id = [];
foreach ($subjects as $sub_row) {
    $subjects_by_id[(int)$sub_row['id']] = $sub_row;
}

$edit_request = null;
$form_request_type = '';
$form_reason_text = '';
$form_subject_id = 0;
$form_subject_ids = [];

$is_assessment_published = false;
$is_deadline_open = false;
$published_at = null;

if ($current_sem_id > 0) {
    $pub_stmt = $conn->prepare("SELECT published_at FROM results_publish_status WHERE department_id = ? AND batch_year = ? AND semester_id = ? AND result_type = 'assessment' AND published = 1 ORDER BY id DESC LIMIT 1");
    $pub_stmt->bind_param('iii', $student['department_id'], $student['batch_year'], $current_sem_id);
    if ($pub_stmt->execute()) {
        $pub_row = $pub_stmt->get_result()->fetch_assoc();
        if ($pub_row) {
            $is_assessment_published = true;
            $published_at = $pub_row['published_at'];
            if (!empty($published_at)) {
                $deadline_ts = strtotime($published_at . ' +' . $window_days . ' days');
                $is_deadline_open = ($deadline_ts !== false && time() <= $deadline_ts);
            }
        }
    }
}

$can_submit = $table_exists && $current_sem_id > 0 && $is_assessment_published && $is_deadline_open && !empty($subjects);

if ($table_exists && isset($_GET['edit_id'])) {
    $edit_id = (int)($_GET['edit_id'] ?? 0);
    if ($edit_id > 0) {
        $edit_stmt = $conn->prepare("SELECT id, subject_id, request_type, reason_text, status FROM assessment_recheck_requests WHERE id = ? AND student_id = ? LIMIT 1");
        $edit_stmt->bind_param('ii', $edit_id, $student_id);
        $edit_stmt->execute();
        $edit_row = $edit_stmt->get_result()->fetch_assoc();
        if ($edit_row && $edit_row['status'] === 'pending') {
            $edit_request = $edit_row;
            $form_request_type = (string)$edit_row['request_type'];
            $form_reason_text = (string)$edit_row['reason_text'];
            $form_subject_id = (int)$edit_row['subject_id'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recheck_request'])) {
    if (!$table_exists) {
        $error = 'Module table missing. Please ask admin to run create_assessment_recheck_requests_table.sql';
    } else {
        $delete_id = (int)($_POST['request_id'] ?? 0);
        if ($delete_id <= 0) {
            $error = 'Invalid request selected.';
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM assessment_recheck_requests WHERE id = ? AND student_id = ? AND status = 'pending' LIMIT 1");
            $delete_stmt->bind_param('ii', $delete_id, $student_id);
            if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
                $success = 'Pending request deleted successfully.';
                if ($edit_request && (int)$edit_request['id'] === $delete_id) {
                    $edit_request = null;
                    $form_request_type = '';
                    $form_reason_text = '';
                    $form_subject_id = 0;
                }
            } else {
                $error = 'Unable to delete request. Only pending requests can be deleted.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_recheck_request'])) {
    if (!$table_exists) {
        $error = 'Module table missing. Please ask admin to run create_assessment_recheck_requests_table.sql';
    } else {
        $request_id = (int)($_POST['request_id'] ?? 0);
        $request_type = trim($_POST['request_type'] ?? '');
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $reason_text = trim($_POST['reason_text'] ?? '');

        $form_request_type = $request_type;
        $form_reason_text = $reason_text;
        $form_subject_id = $subject_id;

        $allowed_types = ['retotal', 'recheck'];
        $valid_subject_ids = array_map(static fn($s) => (int)$s['id'], $subjects);

        if ($request_id <= 0) {
            $error = 'Invalid request selected.';
        } elseif (!in_array($request_type, $allowed_types, true)) {
            $error = 'Please select valid request type.';
        } elseif (!in_array($subject_id, $valid_subject_ids, true)) {
            $error = 'Please select valid subject.';
        } elseif (strlen($reason_text) < 15) {
            $error = 'Please write a clear reason (minimum 15 characters).';
        } else {
            $check_stmt = $conn->prepare("SELECT id FROM assessment_recheck_requests WHERE id = ? AND student_id = ? AND status = 'pending' LIMIT 1");
            $check_stmt->bind_param('ii', $request_id, $student_id);
            $check_stmt->execute();

            if ($check_stmt->get_result()->num_rows === 0) {
                $error = 'Only pending requests can be edited.';
            } else {
                $dup_stmt = $conn->prepare("SELECT id FROM assessment_recheck_requests WHERE student_id = ? AND subject_id = ? AND semester_id = ? AND request_type = ? AND status IN ('pending', 'teacher_recommended') AND id <> ? LIMIT 1");
                $dup_stmt->bind_param('iiisi', $student_id, $subject_id, $current_sem_id, $request_type, $request_id);
                $dup_stmt->execute();
                if ($dup_stmt->get_result()->num_rows > 0) {
                    $error = 'You already have another active request for this subject and type.';
                } else {
                    $teacher_id = null;
                    $t_stmt = $conn->prepare("SELECT teacher_id FROM teacher_subjects WHERE subject_map_id = ? AND department_id = ? AND batch_year = ? AND semester_id = ? ORDER BY id ASC LIMIT 1");
                    $t_stmt->bind_param('iiii', $subject_id, $student['department_id'], $student['batch_year'], $current_sem_order);
                    $t_stmt->execute();
                    $t_row = $t_stmt->get_result()->fetch_assoc();
                    if ($t_row) {
                        $teacher_id = (int)$t_row['teacher_id'];
                    } else {
                        $t_stmt2 = $conn->prepare("SELECT teacher_id FROM teacher_subjects WHERE subject_map_id = ? AND department_id = ? AND semester_id = ? ORDER BY id ASC LIMIT 1");
                        $t_stmt2->bind_param('iii', $subject_id, $student['department_id'], $current_sem_order);
                        $t_stmt2->execute();
                        $t_row2 = $t_stmt2->get_result()->fetch_assoc();
                        if ($t_row2) {
                            $teacher_id = (int)$t_row2['teacher_id'];
                        }
                    }

                    $marks_snapshot = null;
                    $m_stmt = $conn->prepare("SELECT final_total FROM results WHERE student_id = ? AND subject_id = ? AND semester_id = ? ORDER BY id DESC LIMIT 1");
                    $m_stmt->bind_param('iii', $student_id, $subject_id, $current_sem_id);
                    if ($m_stmt->execute()) {
                        $m_row = $m_stmt->get_result()->fetch_assoc();
                        if ($m_row && isset($m_row['final_total'])) {
                            $marks_snapshot = (float)$m_row['final_total'];
                        }
                    }

                    $update_stmt = $conn->prepare("UPDATE assessment_recheck_requests SET subject_id = ?, assigned_teacher_id = ?, request_type = ?, reason_text = ?, student_marks_snapshot = ? WHERE id = ? AND student_id = ? AND status = 'pending'");
                    $update_stmt->bind_param('iissdii', $subject_id, $teacher_id, $request_type, $reason_text, $marks_snapshot, $request_id, $student_id);

                    if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
                        $success = 'Request updated successfully.';
                        $edit_request = null;
                        $form_request_type = '';
                        $form_reason_text = '';
                        $form_subject_id = 0;
                    } else {
                        $error = 'No changes were applied or request is no longer editable.';
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_recheck_request'])) {
    if (!$table_exists) {
        $error = 'Module table missing. Please ask admin to run create_assessment_recheck_requests_table.sql';
    } elseif (!$can_submit) {
        $error = 'Request window is closed or assessment is not published for current semester.';
    } else {
        $request_type = trim($_POST['request_type'] ?? '');
        $subject_ids = $_POST['subject_ids'] ?? [];
        if (!is_array($subject_ids)) {
            $subject_ids = [];
        }
        $subject_ids = array_values(array_unique(array_map(static fn($id) => (int)$id, $subject_ids)));
        $subject_ids = array_values(array_filter($subject_ids, static fn($id) => $id > 0));
        $reason_text = trim($_POST['reason_text'] ?? '');

        $form_request_type = $request_type;
        $form_reason_text = $reason_text;
        $form_subject_ids = $subject_ids;

        $allowed_types = ['retotal', 'recheck'];
        $valid_subject_ids = array_map(static fn($s) => (int)$s['id'], $subjects);

        if (!in_array($request_type, $allowed_types, true)) {
            $error = 'Please select valid request type.';
        } elseif (empty($subject_ids)) {
            $error = 'Please select at least one subject.';
        } elseif (strlen($reason_text) < 15) {
            $error = 'Please write a clear reason (minimum 15 characters).';
        } else {
            $invalid_selected = array_diff($subject_ids, $valid_subject_ids);
            if (!empty($invalid_selected)) {
                $error = 'One or more selected subjects are invalid.';
            } else {
                $dup_stmt = $conn->prepare("SELECT id FROM assessment_recheck_requests WHERE student_id = ? AND subject_id = ? AND semester_id = ? AND request_type = ? AND status IN ('pending', 'teacher_recommended') LIMIT 1");
                $t_stmt = $conn->prepare("SELECT teacher_id FROM teacher_subjects WHERE subject_map_id = ? AND department_id = ? AND batch_year = ? AND semester_id = ? ORDER BY id ASC LIMIT 1");
                $t_stmt2 = $conn->prepare("SELECT teacher_id FROM teacher_subjects WHERE subject_map_id = ? AND department_id = ? AND semester_id = ? ORDER BY id ASC LIMIT 1");
                $m_stmt = $conn->prepare("SELECT final_total FROM results WHERE student_id = ? AND subject_id = ? AND semester_id = ? ORDER BY id DESC LIMIT 1");
                $insert = $conn->prepare("INSERT INTO assessment_recheck_requests (student_id, department_id, batch_year, semester_id, semester_order, subject_id, assigned_teacher_id, request_type, reason_text, student_marks_snapshot, status, requested_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");

                $submitted_count = 0;
                $duplicate_subjects = [];
                $failed_subjects = [];

                foreach ($subject_ids as $subject_id) {
                    $dup_stmt->bind_param('iiis', $student_id, $subject_id, $current_sem_id, $request_type);
                    $dup_stmt->execute();
                    if ($dup_stmt->get_result()->num_rows > 0) {
                        $subject_meta = $subjects_by_id[$subject_id] ?? null;
                        $duplicate_subjects[] = $subject_meta ? (string)$subject_meta['subject_name'] : ('Subject ID ' . $subject_id);
                        continue;
                    }

                    $teacher_id = null;
                    $t_stmt->bind_param('iiii', $subject_id, $student['department_id'], $student['batch_year'], $current_sem_order);
                    $t_stmt->execute();
                    $t_row = $t_stmt->get_result()->fetch_assoc();
                    if ($t_row) {
                        $teacher_id = (int)$t_row['teacher_id'];
                    } else {
                        $t_stmt2->bind_param('iii', $subject_id, $student['department_id'], $current_sem_order);
                        $t_stmt2->execute();
                        $t_row2 = $t_stmt2->get_result()->fetch_assoc();
                        if ($t_row2) {
                            $teacher_id = (int)$t_row2['teacher_id'];
                        }
                    }

                    $marks_snapshot = null;
                    $m_stmt->bind_param('iii', $student_id, $subject_id, $current_sem_id);
                    if ($m_stmt->execute()) {
                        $m_row = $m_stmt->get_result()->fetch_assoc();
                        if ($m_row && isset($m_row['final_total'])) {
                            $marks_snapshot = (float)$m_row['final_total'];
                        }
                    }

                    $insert->bind_param('iiiiiiissd', $student_id, $student['department_id'], $student['batch_year'], $current_sem_id, $current_sem_order, $subject_id, $teacher_id, $request_type, $reason_text, $marks_snapshot);

                    if ($insert->execute()) {
                        $submitted_count++;
                        if (!empty($teacher_id)) {
                            $subject_meta = $subjects_by_id[$subject_id] ?? ['subject_name' => 'Subject', 'subject_code' => ''];
                            notifyTeacherRecheckRequest(
                                (int)$teacher_id,
                                (string)$student['full_name'],
                                (string)($student['symbol_no'] ?? ''),
                                (string)($subject_meta['subject_name'] ?? 'Subject'),
                                (string)($subject_meta['subject_code'] ?? ''),
                                (string)$request_type,
                                (string)$reason_text,
                                $conn
                            );
                        }
                    } else {
                        $subject_meta = $subjects_by_id[$subject_id] ?? null;
                        $failed_subjects[] = $subject_meta ? (string)$subject_meta['subject_name'] : ('Subject ID ' . $subject_id);
                    }
                }

                if ($submitted_count > 0) {
                    $success = $submitted_count . ' request(s) submitted successfully.';
                    if (!empty($duplicate_subjects)) {
                        $success .= ' Skipped duplicates: ' . implode(', ', array_slice($duplicate_subjects, 0, 3)) . (count($duplicate_subjects) > 3 ? '...' : '') . '.';
                    }
                    if (!empty($failed_subjects)) {
                        $success .= ' Failed: ' . implode(', ', array_slice($failed_subjects, 0, 3)) . (count($failed_subjects) > 3 ? '...' : '') . '.';
                    }
                    $form_request_type = '';
                    $form_reason_text = '';
                    $form_subject_ids = [];
                } else {
                    $error = 'No request was submitted. ';
                    if (!empty($duplicate_subjects)) {
                        $error .= 'All selected subjects already have active requests.';
                    } else {
                        $error .= 'Please try again.';
                    }
                }
            }
        }
    }
}

$my_requests = [];
if ($table_exists) {
    $list_stmt = $conn->prepare("SELECT rr.*, sm.subject_name, sm.subject_code, t.full_name AS teacher_name
                                 FROM assessment_recheck_requests rr
                                 JOIN subjects_master sm ON sm.id = rr.subject_id
                                 LEFT JOIN teachers t ON t.id = rr.assigned_teacher_id
                                 WHERE rr.student_id = ?
                                 ORDER BY rr.requested_at DESC
                                 LIMIT 20");
    $list_stmt->bind_param('i', $student_id);
    $list_stmt->execute();
    $list_res = $list_stmt->get_result();
    while ($row = $list_res->fetch_assoc()) {
        $my_requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Re-total / Recheck</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .panel { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 6px 20px rgba(0,0,0,0.04); }
        .hero { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; margin-bottom: 14px; }
        .meta-label { font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.03em; }
        .meta-value { font-weight: 700; color: #0f172a; }
    </style>
</head>
<body>
<?php include 'student_header.php'; ?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h4 class="mb-0"><i class="fas fa-rotate-left me-2 text-primary"></i>Assessment Re-total / Recheck</h4>
        <a href="student_dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <?php if (!$table_exists): ?>
        <div class="alert alert-warning">Feature is not initialized. Please run <strong>create_assessment_recheck_requests_table.sql</strong>.</div>
    <?php endif; ?>

    <div class="hero">
        <div class="row g-3">
            <div class="col-6 col-lg-3"><div class="meta-label">Student</div><div class="meta-value"><?= htmlspecialchars($student['full_name']) ?></div></div>
            <div class="col-6 col-lg-3"><div class="meta-label">Symbol No</div><div class="meta-value"><?= htmlspecialchars($student['symbol_no']) ?></div></div>
            <div class="col-6 col-lg-3"><div class="meta-label">Current Semester</div><div class="meta-value"><?= (int)$current_sem_order ?></div></div>
            <div class="col-6 col-lg-3"><div class="meta-label">Department</div><div class="meta-value"><?= htmlspecialchars($student['department_name']) ?></div></div>
        </div>
        <hr>
        <div class="small">
            <?php if (!$is_assessment_published): ?>
                <span class="text-danger"><i class="fas fa-circle-xmark me-1"></i>Assessment result not published for current semester.</span>
            <?php elseif (!$is_deadline_open): ?>
                <span class="text-danger"><i class="fas fa-circle-xmark me-1"></i>Request deadline closed (allowed within <?= $window_days ?> days of publish).</span>
            <?php else: ?>
                <span class="text-success"><i class="fas fa-circle-check me-1"></i>Request window is open.</span>
                <?php if (!empty($published_at)): ?>
                    <span class="text-muted ms-2">Published at: <?= htmlspecialchars($published_at) ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="panel p-3">
                <h6 class="fw-bold mb-3"><?= $edit_request ? 'Edit Pending Request' : 'Submit New Request' ?></h6>
                <form method="post" class="row g-3">
                    <?php if ($edit_request): ?>
                        <input type="hidden" name="request_id" value="<?= (int)$edit_request['id'] ?>">
                    <?php endif; ?>
                    <div class="col-12">
                        <label class="form-label">Request Type</label>
                        <select name="request_type" class="form-select" required <?= (!$can_submit && !$edit_request) ? 'disabled' : '' ?>>
                            <option value="">Select</option>
                            <option value="retotal" <?= $form_request_type === 'retotal' ? 'selected' : '' ?>>Re-total</option>
                            <option value="recheck" <?= $form_request_type === 'recheck' ? 'selected' : '' ?>>Recheck</option>
                        </select>
                    </div>
                    <?php if ($edit_request): ?>
                        <div class="col-12">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">Select subject</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= (int)$sub['id'] ?>" <?= $form_subject_id === (int)$sub['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sub['subject_name']) ?> (<?= htmlspecialchars($sub['subject_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-12">
                            <label class="form-label d-block">Subjects (Tick to select multiple)</label>
                            <div class="border rounded p-2" style="max-height: 240px; overflow-y: auto;">
                                <?php foreach ($subjects as $sub): ?>
                                    <?php $sub_id = (int)$sub['id']; ?>
                                    <div class="form-check mb-1">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="subject_ids[]"
                                            value="<?= $sub_id ?>"
                                            id="subject_<?= $sub_id ?>"
                                            <?= in_array($sub_id, $form_subject_ids, true) ? 'checked' : '' ?>
                                            <?= $can_submit ? '' : 'disabled' ?>
                                        >
                                        <label class="form-check-label" for="subject_<?= $sub_id ?>">
                                            <?= htmlspecialchars($sub['subject_name']) ?> (<?= htmlspecialchars($sub['subject_code']) ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text">You can tick multiple subjects.</div>
                        </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <label class="form-label">Reason</label>
                        <textarea name="reason_text" class="form-control" rows="4" minlength="15" placeholder="Why do you need re-total or recheck?" required <?= (!$can_submit && !$edit_request) ? 'disabled' : '' ?>><?= htmlspecialchars($form_reason_text) ?></textarea>
                    </div>
                    <div class="col-12">
                        <?php if ($edit_request): ?>
                            <div class="d-flex gap-2">
                                <button type="submit" name="update_recheck_request" class="btn btn-primary w-100">Update Request</button>
                                <a href="student_recheck_requests.php" class="btn btn-outline-secondary w-100">Cancel</a>
                            </div>
                        <?php else: ?>
                            <button type="submit" name="submit_recheck_request" class="btn btn-primary w-100" <?= $can_submit ? '' : 'disabled' ?>>Submit Request(s)</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="panel p-3">
                <h6 class="fw-bold mb-3">My Requests</h6>
                <?php if (!empty($my_requests)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Subject</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Teacher</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($my_requests as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($r['subject_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($r['subject_code']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($r['request_type'] === 'retotal' ? 'Re-total' : ucfirst($r['request_type'])) ?></td>
                                    <td>
                                        <?php
                                            $st = strtolower($r['status']);
                                            $cls = 'secondary';
                                            if ($st === 'pending') $cls = 'primary';
                                            elseif ($st === 'teacher_recommended' || $st === 'approved') $cls = 'success';
                                            elseif ($st === 'teacher_rejected' || $st === 'rejected') $cls = 'danger';
                                        ?>
                                        <span class="badge bg-<?= $cls ?>"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($st))) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($r['teacher_name'] ?? '-') ?></td>
                                    <td><small><?= htmlspecialchars($r['requested_at']) ?></small></td>
                                    <td>
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <div class="d-flex gap-1">
                                                <a href="student_recheck_requests.php?edit_id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <form method="post" onsubmit="return confirm('Delete this pending request?');">
                                                    <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                                    <button type="submit" name="delete_recheck_request" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">Locked</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if (!empty($r['teacher_remark']) || !empty($r['admin_remark'])): ?>
                                <tr>
                                    <td colspan="6" class="bg-light">
                                        <?php if (!empty($r['teacher_remark'])): ?>
                                            <div><strong>Teacher:</strong> <?= nl2br(htmlspecialchars($r['teacher_remark'])) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($r['admin_remark'])): ?>
                                            <div><strong>Admin:</strong> <?= nl2br(htmlspecialchars($r['admin_remark'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-muted text-center py-4">No requests yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
