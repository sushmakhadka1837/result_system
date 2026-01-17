<?php
session_start();
require 'db_config.php';

// Teacher session check
if(!isset($_SESSION['teacher_id'])){
    header("Location: login.php");
    exit();
}

// URL bata parameters line
$subject_id = intval($_GET['subject_id'] ?? 0);
$ts_id      = intval($_GET['ts_id'] ?? 0); 
$batch      = intval($_GET['batch'] ?? 0);
$sem        = intval($_GET['sem'] ?? 0);
$dept       = intval($_GET['dept'] ?? 0);

if (!$subject_id || !$ts_id) {
    die("Invalid Parameters.");
}

// 1. Check if Marks are Locked by Admin
$lock_q = $conn->query("SELECT mark_lock FROM teacher_subjects WHERE id = $ts_id");
$lock_data = $lock_q->fetch_assoc();
$is_locked = ($lock_data['mark_lock'] == 1);

// 2. SAVE LOGIC
if(isset($_POST['update_marks']) && !$is_locked) {
    foreach($_POST['marks'] as $res_id => $m_val) {
        $res_id = intval($res_id);
        $m_val = ($m_val === "") ? 0 : floatval($m_val);
        $fm = floatval($_POST['fm'][$res_id]);
        $pm = floatval($_POST['pm'][$res_id]);
        
        $percent = ($m_val / $fm) * 100;
        if($m_val < $pm) {
            $grade = 'F';
        } else {
            if($percent >= 90) $grade = 'A';
            elseif($percent >= 85) $grade = 'A-';
            elseif($percent >= 80) $grade = 'B+';
            elseif($percent >= 75) $grade = 'B';
            elseif($percent >= 70) $grade = 'B-';
            elseif($percent >= 65) $grade = 'C+';
            elseif($percent >= 60) $grade = 'C';
            elseif($percent >= 55) $grade = 'C-';
            elseif($percent >= 50) $grade = 'D+';
            else $grade = 'F';
        }

        $stmt = $conn->prepare("UPDATE results SET ut_obtain = ?, ut_grade = ? WHERE id = ?");
        $stmt->bind_param("dsi", $m_val, $grade, $res_id);
        $stmt->execute();
    }
    $msg = "Marks updated successfully!";
}

// 3. FETCH SUBJECT INFO
$sub_info = $conn->query("SELECT subject_name, subject_code FROM subjects_master WHERE id = $subject_id")->fetch_assoc();

// 4. FETCH MARKS DATA
$sql = "SELECT r.id AS result_id, s.full_name, s.symbol_no, 
                r.ut_obtain, r.ut_full_marks, r.ut_pass_marks, r.ut_grade
        FROM results r
        JOIN students s ON r.student_id = s.id
        WHERE r.subject_id = $subject_id 
        AND r.semester_id = $sem 
        AND s.batch_year = '$batch'
        ORDER BY s.symbol_no ASC";
$res_list = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit UT Marks - <?= htmlspecialchars($sub_info['subject_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; color: #1a1d20; }
        .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; }
        .header-bg { background: #ffffff; border-bottom: 1px solid #eef0f2; padding: 1.5rem 2rem; }
        .table thead th { background-color: #f8f9fa; color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; border-top: none; padding: 1rem; }
        .mark-input { width: 90px; height: 40px; text-align: center; font-weight: 700; border-radius: 8px; border: 2px solid #dee2e6; transition: all 0.2s; font-size: 1rem; }
        .mark-input:focus { border-color: #0d6efd; outline: none; box-shadow: 0 0 0 4px rgba(13,110,253,0.1); }
        .readonly-input { background-color: #f1f3f5; border-color: #e9ecef; cursor: not-allowed; color: #adb5bd; }
        .grade-badge { padding: 0.5rem 1rem; border-radius: 8px; font-weight: 700; min-width: 45px; display: inline-block; }
        .grade-f { background-color: #fff0f0; color: #e03131; border: 1px solid #ffc9c9; }
        .grade-pass { background-color: #e7f5ff; color: #1971c2; border: 1px solid #a5d8ff; }
        .btn-save { padding: 0.8rem 2.5rem; border-radius: 10px; font-weight: 600; box-shadow: 0 4px 12px rgba(13,110,253,0.2); transition: transform 0.2s; }
        .btn-save:hover { transform: translateY(-2px); }
        .symbol-no { font-family: 'Monaco', 'Consolas', monospace; color: #495057; font-weight: 600; }
    </style>
</head>
<body>

<div class="container py-5">
    <?php if(isset($msg)): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i> <?= $msg ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="main-card bg-white">
        <div class="header-bg d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="publish_result.php" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item active">UT Entry</li>
                    </ol>
                </nav>
                <h4 class="mb-1 fw-bold"><?= htmlspecialchars($sub_info['subject_name']) ?></h4>
                <div class="d-flex gap-3 text-muted small fw-medium">
                    <span><i class="bi bi-code-square me-1"></i> <?= $sub_info['subject_code'] ?></span>
                    <span><i class="bi bi-people me-1"></i> Batch: <?= $batch ?></span>
                    <span><i class="bi bi-journal-bookmark me-1"></i> Sem: <?= $sem ?></span>
                </div>
            </div>
            <div class="mt-3 mt-md-0 d-flex align-items-center gap-2">
                <?php if($is_locked): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-bold">
                        <i class="bi bi-lock-fill me-1"></i> LOCKED
                    </span>
                <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold">
                        <i class="bi bi-unlock-fill me-1"></i> EDITING OPEN
                    </span>
                <?php endif; ?>
                <a href="publish_result.php" class="btn btn-outline-dark btn-sm rounded-3 px-3">Back</a>
            </div>
        </div>
        
        <div class="card-body p-0">
            <form method="POST">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Symbol No</th>
                                <th>Student Name</th>
                                <th class="text-center">Full / Pass</th>
                                <th class="text-center">Marks Obtained</th>
                                <th class="text-center pe-4">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($res_list->num_rows > 0): ?>
                                <?php while($row = $res_list->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="symbol-no px-2 py-1 bg-light rounded"><?= $row['symbol_no'] ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= $row['full_name'] ?></div>
                                    </td>
                                    <td class="text-center text-muted small fw-bold">
                                        <?= $row['ut_full_marks'] ?> / <?= $row['ut_pass_marks'] ?>
                                        <input type="hidden" name="fm[<?= $row['result_id'] ?>]" value="<?= $row['ut_full_marks'] ?>">
                                        <input type="hidden" name="pm[<?= $row['result_id'] ?>]" value="<?= $row['ut_pass_marks'] ?>">
                                    </td>
                                    <td class="text-center">
                                        <input type="number" step="0.1" 
                                               name="marks[<?= $row['result_id'] ?>]" 
                                               value="<?= $row['ut_obtain'] ?>" 
                                               class="mark-input <?= $is_locked ? 'readonly-input' : '' ?>"
                                               <?= $is_locked ? 'readonly' : '' ?>>
                                    </td>
                                    <td class="text-center pe-4">
                                        <span class="grade-badge <?= ($row['ut_grade'] == 'F') ? 'grade-f' : 'grade-pass' ?>">
                                            <?= $row['ut_grade'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <i class="bi bi-folder-x fs-1 text-muted opacity-25"></i>
                                        <p class="text-muted mt-2">No records found. Admin must upload marks first.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if(!$is_locked && $res_list->num_rows > 0): ?>
                <div class="bg-light p-4 text-end">
                    <button type="submit" name="update_marks" class="btn btn-primary btn-save">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i> Save Changes
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>