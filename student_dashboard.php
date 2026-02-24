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
$open_requests_panel = false;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_absence_request'])) {
    $open_requests_panel = true;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_absence_request'])) {
    $open_requests_panel = true;
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

if ($absence_table_exists) {
    if ($absence_has_exam_fields) {
        $absence_stmt = $conn->prepare("SELECT ar.id, ar.request_type, ar.exam_component, ar.absence_date, ar.reason_text, ar.proof_file, ar.status, ar.teacher_remark, ar.created_at,
                                               sm.subject_name, sm.subject_code
                                        FROM student_absence_requests ar
                                        LEFT JOIN subjects_master sm ON ar.subject_id = sm.id
                                        WHERE ar.student_id = ?
                                        ORDER BY ar.created_at DESC
                                        LIMIT 10");
    } else {
        $absence_stmt = $conn->prepare("SELECT id, request_type, absence_date, reason_text, proof_file, status, teacher_remark, created_at FROM student_absence_requests WHERE student_id = ? ORDER BY created_at DESC LIMIT 10");
    }
    $absence_stmt->bind_param("i", $student_id);
    $absence_stmt->execute();
    $absence_result = $absence_stmt->get_result();
    while ($row = $absence_result->fetch_assoc()) {
        $absence_requests[] = $row;
    }
}

if (isset($_GET['show_requests']) && $_GET['show_requests'] === '1') {
    $open_requests_panel = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PEC Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy: #001f4d;
            --navy-light: #003366;
            --gold: #f4c430;
            --bg-gray: #f3f7ff;
            --border-color: #e2e8f0;
        }

        body { 
            background:
                radial-gradient(circle at top right, rgba(29, 78, 216, 0.08), transparent 38%),
                radial-gradient(circle at left center, rgba(14, 116, 144, 0.06), transparent 34%),
                var(--bg-gray);
            font-family: 'Inter', 'Poppins', sans-serif; 
            color: #1e293b; 
        }
        
        .dashboard-container { padding: 30px 0 60px; }

        /* Profile Card - White & Clean */
        .profile-sidebar {
            background: #ffffff;
            border-radius: 26px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid var(--border-color);
            position: sticky;
            top: 100px;
        }
        .profile-header-accent {
            background: linear-gradient(115deg, #0f172a 0%, #1d4ed8 55%, #0ea5e9 100%);
            height: 110px;
            position: relative;
        }
        .profile-header-accent::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.12), transparent 65%);
        }
        .profile-img-container {
            margin-top: -55px;
            text-align: center;
        }
        .profile-img-container img {
            width: 115px;
            height: 115px;
            border-radius: 50%;
            border: 6px solid white;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .profile-body { padding: 20px 25px 30px; text-align: center; }
        .profile-body h4 { font-weight: 700; color: var(--navy); margin-bottom: 4px; }
        .dept-tag {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 500;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 6px 12px;
            display: inline-flex;
            align-items: center;
        }

        .info-list { text-align: left; margin-top: 25px; }
        .sem-highlight {
            background: #fff9e6;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #475569;
        }
        .info-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
        .info-item i { color: var(--navy-light); width: 18px; text-align: center; }

        /* Welcome Card - Dynamic Gradient */
        .welcome-card {
            background: linear-gradient(135deg, var(--navy) 0%, #004080 100%);
            border-radius: 24px;
            padding: 40px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .welcome-card h2 { font-size: 1.8rem; margin-bottom: 10px; }

        /* Content Sections */
        .section-card {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            padding: 25px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            transition: transform 0.24s ease, box-shadow 0.24s ease;
        }
        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        }
        .card-title-custom {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .edit-btn {
            background: linear-gradient(135deg, var(--navy) 0%, #2563eb 100%);
            color: #fff;
            border-radius: 12px;
            width: 100%;
            margin-top: 20px;
            font-weight: 600;
            border: none;
            padding: 12px;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
        }
        .edit-btn:hover { transform: translateY(-1px); color: white; }

        .quiz-cta-card {
            background: linear-gradient(125deg, #0f172a 0%, #1d4ed8 52%, #0ea5e9 100%);
            color: #fff;
            border: none;
            box-shadow: 0 20px 38px rgba(37, 99, 235, 0.3);
        }
        .quiz-cta-card .text-dark,
        .quiz-cta-card .text-muted {
            color: rgba(255, 255, 255, 0.95) !important;
        }
        .quiz-cta-card small {
            color: rgba(255,255,255,0.86) !important;
        }
        .quiz-start-btn {
            border-radius: 12px;
            font-weight: 700;
            border: 0;
            padding: 10px 18px;
            background: #fff;
            color: #0f172a;
            box-shadow: 0 10px 20px rgba(15,23,42,0.22);
        }
        .quiz-start-btn:hover {
            color: #0f172a;
            transform: translateY(-1px);
        }

        .section-card .form-control,
        .section-card .form-select {
            border-radius: 10px;
            border-color: #dbe3ee;
            padding-top: 0.58rem;
            padding-bottom: 0.58rem;
            box-shadow: none;
        }
        .section-card .form-control:focus,
        .section-card .form-select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.14);
        }
        .section-card .btn {
            border-radius: 10px;
            font-weight: 600;
        }
        .section-card .table {
            border-color: #e7edf5;
        }
        .section-card .table thead th {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #475569;
        }
        .section-card .table td,
        .section-card .table th {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .chatbot-launcher {
            position: fixed;
            right: 22px;
            bottom: 24px;
            background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%);
            color: #fff;
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            font-weight: 700;
            box-shadow: 0 14px 30px rgba(37, 99, 235, 0.25);
            z-index: 1050;
        }
        .chatbot-panel {
            position: fixed;
            right: 22px;
            bottom: 86px;
            width: 320px;
            max-height: 70vh;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
            border: 1px solid #e2e8f0;
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 1050;
        }
        .chatbot-header {
            background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%);
            color: #fff;
            padding: 12px 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chatbot-body {
            padding: 12px 14px;
            background: #f8fafc;
            flex: 1;
            overflow-y: auto;
        }
        .chatbot-message {
            padding: 10px 12px;
            border-radius: 12px;
            margin-bottom: 10px;
            font-size: 0.88rem;
            line-height: 1.4;
            max-width: 90%;
        }
        .chatbot-user {
            background: #2563eb;
            color: #fff;
            margin-left: auto;
        }
        .chatbot-ai {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #0f172a;
        }
        .chatbot-footer {
            padding: 10px 12px;
            display: flex;
            gap: 8px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .chatbot-footer input {
            flex: 1;
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 0.9rem;
        }
        .chatbot-footer button {
            background: #0f172a;
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 8px 12px;
            font-weight: 600;
        }
        .chatbot-status {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 6px;
        }

        /* Mobile Responsive */
        @media (max-width: 992px) {
            .profile-sidebar {
                position: relative;
                top: 0;
                margin-bottom: 20px;
            }
            .dashboard-container { padding: 15px 0 30px; }
            .welcome-card { padding: 25px; }
            .welcome-card h2 { font-size: 1.4rem; }
            .section-card { padding: 20px; }
            .quiz-start-btn { width: 100%; }
        }

        @media (max-width: 768px) {
            .profile-header-accent { height: 70px; }
            .profile-img-container { margin-top: -40px; }
            .profile-img-container img { width: 85px; height: 85px; border-width: 4px; }
            .profile-body { padding: 15px 20px 25px; }
            .profile-body h4 { font-size: 1.1rem; }
            .dept-tag { font-size: 0.8rem; }
            .info-item { font-size: 0.85rem; padding: 10px 0; }
            .sem-highlight { padding: 10px; gap: 10px; }
            .welcome-card { padding: 20px; margin-bottom: 20px; }
            .welcome-card h2 { font-size: 1.2rem; }
            .section-card { padding: 15px; }
            .card-title-custom { font-size: 1rem; margin-bottom: 15px; }
            .quiz-cta-card { padding: 16px !important; }
        }

        @media (max-width: 576px) {
            .dashboard-container { padding: 10px 0 20px; }
            .profile-img-container img { width: 75px; height: 75px; }
            .profile-body h4 { font-size: 1rem; }
            .info-item { flex-direction: column; align-items: flex-start; gap: 5px; font-size: 0.8rem; }
            .info-item i { margin-bottom: 5px; }
            .welcome-card { padding: 15px; }
            .welcome-card h2 { font-size: 1rem; }
        }
    </style>
</head>
<body>

<?php include 'student_header.php'; ?>

<div class="container dashboard-container">
    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="profile-sidebar">
                <div class="profile-header-accent"></div>
                <div class="profile-img-container">
                    <form action="upload_profile.php" method="post" enctype="multipart/form-data" id="profileForm">
                        <label for="profile_photo" style="cursor: pointer;">
                            <img src="<?php echo !empty($student['profile_photo']) ? $student['profile_photo'] : 'images/default.png'; ?>" alt="Student Photo">
                        </label>
                        <input type="file" name="profile_photo" id="profile_photo" style="display:none;" onchange="document.getElementById('profileForm').submit()">
                    </form>
                </div>
                <div class="profile-body">
                    <h4><?php echo htmlspecialchars($student['full_name']); ?></h4>
                    <span class="dept-tag"><i class="fas fa-university me-1"></i> <?php echo htmlspecialchars($student['department_name']); ?></span>
                    
                    <div class="info-list">
                        <div class="sem-highlight">
                            <div class="icon-circle bg-white shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 10px;">
                                <i class="fas fa-graduation-cap text-warning"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.7rem; text-transform: uppercase;">Active Status</small>
                                <span class="fw-bold text-dark"><?php echo ($current_semester_order > 0) ? $current_semester_order . "th Semester" : "N/A"; ?></span>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($student['phone']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Batch Year: <?php echo htmlspecialchars($student['batch_year']); ?></span>
                        </div>
                    </div>
                    
                    <button class="edit-btn" onclick="window.location.href='student_edit_profile.php'">
                        <i class="fas fa-fingerprint me-2"></i> Update Profile
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            
            <!-- Academic Actions Section - MCQ, Recheck, Absence -->
            <div class="section-card mb-4">
                <h4 class="card-title-custom mb-4">
                    <i class="fas fa-tasks text-primary me-2"></i>Academic Actions
                </h4>
                
                <div class="row g-3">
                    <!-- MCQ Quiz Card -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="card-body text-white d-flex flex-column">
                                <div class="mb-3">
                                    <i class="fas fa-brain fa-2x mb-2"></i>
                                    <h5 class="fw-bold">AI MCQ Quiz</h5>
                                </div>
                                <p class="small mb-auto">Challenge yourself with 50 smart MCQs and get instant performance insights.</p>
                                <a href="ai_mcq_quiz.php" class="btn btn-light btn-sm mt-3 w-100">
                                    <i class="fas fa-play me-1"></i>Start Quiz
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Recheck Request Card -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 shadow-sm border-0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <div class="card-body text-white d-flex flex-column">
                                <div class="mb-3">
                                    <i class="fas fa-rotate-left fa-2x mb-2"></i>
                                    <h5 class="fw-bold">Assessment Recheck</h5>
                                </div>
                                <p class="small mb-auto">Request re-total or recheck within 15 days of result publication.</p>
                                <a href="student_recheck_requests.php" class="btn btn-light btn-sm mt-3 w-100">
                                    <i class="fas fa-paper-plane me-1"></i>Submit Request
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Absence Request Card -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 shadow-sm border-0" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <div class="card-body text-white d-flex flex-column">
                                <div class="mb-3">
                                    <i class="fas fa-notes-medical fa-2x mb-2"></i>
                                    <h5 class="fw-bold">Leave Absence</h5>
                                </div>
                                <p class="small mb-auto">Submit medical or emergency leave requests for class or exams.</p>
                                <a href="student_absence_requests.php" class="btn btn-light btn-sm mt-3 w-100">
                                    <i class="fas fa-file-medical me-1"></i>Submit Request
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <h4 class="card-title-custom">
                    <i class="fas fa-bullhorn text-warning"></i> Recent Announcements
                </h4>
                <hr class="text-muted opacity-25">
                <?php include 'view_student_notice.php'; ?>
            </div>
        </div>

    </div>
</div>

<button class="chatbot-launcher" id="chatbotLauncher"><i class="fas fa-robot me-2"></i>AI Assistant</button>
<div class="chatbot-panel" id="chatbotPanel">
    <div class="chatbot-header">
        <span><i class="fas fa-sparkles me-2"></i>AI Assistant</span>
        <button type="button" id="chatbotClose" class="btn btn-sm btn-light">Close</button>
    </div>
    <div class="chatbot-body" id="chatbotBody">
        <div class="chatbot-message chatbot-ai">Hi! Ask me anything about results, assessments, or study tips.</div>
    </div>
    <div class="chatbot-footer">
        <input type="text" id="chatbotInput" placeholder="Type your question...">
        <button type="button" id="chatbotSend">Send</button>
    </div>
</div>

<script>
    (function() {
        const launcher = document.getElementById('chatbotLauncher');
        const panel = document.getElementById('chatbotPanel');
        const closeBtn = document.getElementById('chatbotClose');
        const input = document.getElementById('chatbotInput');
        const sendBtn = document.getElementById('chatbotSend');
        const body = document.getElementById('chatbotBody');

        if (!launcher || !panel || !closeBtn || !input || !sendBtn || !body) return;

        const togglePanel = (open) => {
            panel.style.display = open ? 'flex' : 'none';
        };

        const appendMessage = (text, type) => {
            const bubble = document.createElement('div');
            bubble.className = `chatbot-message ${type}`;
            bubble.textContent = text;
            body.appendChild(bubble);
            body.scrollTop = body.scrollHeight;
        };

        const sendMessage = async () => {
            const message = input.value.trim();
            if (!message) return;

            appendMessage(message, 'chatbot-user');
            input.value = '';

            appendMessage('Thinking...', 'chatbot-ai');
            const loader = body.lastElementChild;

            try {
                const response = await fetch('chatbot_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });
                const data = await response.json();
                loader.textContent = (data.reply || 'Sorry, I could not respond right now.').replace(/<br\s*\/?>/g, '\n');
            } catch (err) {
                loader.textContent = 'Sorry, something went wrong. Please try again.';
            }
        };

        launcher.addEventListener('click', () => togglePanel(true));
        closeBtn.addEventListener('click', () => togglePanel(false));
        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendMessage();
            }
        });
    })();
</script>

<script>
    (function() {
        const scopeSelect = document.getElementById('request_scope');
        const subjectSelect = document.getElementById('subject_id');
        const examSelect = document.getElementById('exam_component');

        if (!scopeSelect || !subjectSelect || !examSelect) return;

        const toggleFields = () => {
            const isExam = scopeSelect.value === 'exam' || scopeSelect.value === '';
            subjectSelect.disabled = !isExam;
            examSelect.disabled = !isExam;
            subjectSelect.required = isExam;
            examSelect.required = isExam;
        };

        scopeSelect.addEventListener('change', toggleFields);
        toggleFields();
    })();
</script>

<?php include 'footer.php'; ?>

</body>
</html>