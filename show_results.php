<?php
session_start();
require 'db_config.php';

// Input & validation
$dept_id = isset($_GET['dept']) ? intval($_GET['dept']) : 0;
$sem_id  = isset($_GET['sem'])  ? intval($_GET['sem'])  : 0;
$type    = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$batch   = isset($_GET['batch']) ? trim($_GET['batch']) : '';
$section = isset($_GET['section']) ? trim($_GET['section']) : '';

$valid_types = ['ut', 'assessment'];
if (!$dept_id || !$sem_id || !in_array($type, $valid_types, true)) {
    die('Invalid parameters provided.');
}

// Check publication status
$pub_stmt = $conn->prepare("SELECT 1 FROM results_publish_status WHERE department_id=? AND semester_id=? AND result_type=? AND published=1 LIMIT 1");
$pub_stmt->bind_param('iis', $dept_id, $sem_id, $type);
$pub_stmt->execute();
if ($pub_stmt->get_result()->num_rows === 0) {
    die('Selected result is not published yet.');
}

// Dynamic filters
$where = [
    's.department_id = ?',
    'r.semester_id = ?'
];
$params = [$dept_id, $sem_id];
$types  = 'ii';

if ($batch !== '') {
    $where[] = 's.batch_year = ?';
    $params[] = intval($batch);
    $types   .= 'i';
}

if ($section !== '') {
    $where[] = 's.section = ?';
    $params[] = $section;
    $types   .= 's';
}

$where_sql = implode(' AND ', $where);

// Subject list (ordered, exclude Project)
$subject_sql = "
    SELECT DISTINCT
        COALESCE(CAST(sm.id AS CHAR), CONCAT('raw_', r.subject_code, '_', r.subject_id)) AS sub_key,
        COALESCE(sm.subject_name, r.subject_code) AS subject_name,
        sm.id AS sm_id
    FROM results r
    JOIN students s ON r.student_id = s.id
    LEFT JOIN subjects_master sm ON r.subject_id = sm.id
    WHERE {$where_sql}
      AND COALESCE(sm.subject_name, r.subject_code) NOT LIKE '%Project%'
    ORDER BY sm.id IS NULL, sm.id ASC, subject_name ASC
";
$sub_stmt = $conn->prepare($subject_sql);
$sub_stmt->bind_param($types, ...$params);
$sub_stmt->execute();
$subjects = $sub_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Student list
$student_sql = "
    SELECT DISTINCT s.id, s.symbol_no
    FROM students s
    JOIN results r ON r.student_id = s.id
    WHERE {$where_sql}
    ORDER BY s.symbol_no ASC
";
$stu_stmt = $conn->prepare($student_sql);
$stu_stmt->bind_param($types, ...$params);
$stu_stmt->execute();
$students = $stu_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Marks map per student/subject (exclude Project)
$additional_cond = ($type === 'ut')
    ? 'AND r.ut_obtain IS NOT NULL'
    : 'AND (r.final_theory IS NOT NULL OR r.practical_marks IS NOT NULL OR r.grade_point IS NOT NULL)';

$data_sql = "
    SELECT
        s.id AS student_id,
        COALESCE(CAST(sm.id AS CHAR), CONCAT('raw_', r.subject_code, '_', r.subject_id)) AS sub_key,
        COALESCE(sm.subject_name, r.subject_code) AS subject_name,
        sm.credit_hours,
        r.ut_obtain, r.ut_grade,
        r.final_theory, r.practical_marks,
        r.grade_point, r.letter_grade
    FROM results r
    JOIN students s ON r.student_id = s.id
    LEFT JOIN subjects_master sm ON r.subject_id = sm.id
    WHERE {$where_sql}
      AND COALESCE(sm.subject_name, r.subject_code) NOT LIKE '%Project%'
    {$additional_cond}
";
$data_stmt = $conn->prepare($data_sql);
$data_stmt->bind_param($types, ...$params);
$data_stmt->execute();
$data_res = $data_stmt->get_result();

$marks = [];
while ($row = $data_res->fetch_assoc()) {
    $sid = $row['student_id'];
    $sk  = $row['sub_key'];
    $marks[$sid][$sk] = $row;
}

// Names for header
$dept_name = 'Department';
$sem_name  = 'Semester';
if ($dept_res = $conn->query("SELECT department_name FROM departments WHERE id = {$dept_id} LIMIT 1")) {
    if ($drow = $dept_res->fetch_assoc()) { $dept_name = $drow['department_name']; }
}
if ($sem_res = $conn->query("SELECT semester_name FROM semesters WHERE id = {$sem_id} LIMIT 1")) {
    if ($srow = $sem_res->fetch_assoc()) { $sem_name = $srow['semester_name']; }
}

$total_cols = 2 + count($subjects);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result - <?= htmlspecialchars(strtoupper($type)) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .transcript-box { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin: 40px auto; max-width: 1200px; }
        .table thead th { font-size: 0.9rem; letter-spacing: 0.3px; }
        .subject-head { white-space: nowrap; }
        .cell-mini { font-size: 0.85rem; line-height: 1.2; }
        
        /* Mobile Responsive - Horizontal Scroll for Tables */
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        
        @media (max-width: 992px) {
            .transcript-box { padding: 20px; margin: 20px auto; }
            .table { font-size: 0.85rem; }
            .table thead th { font-size: 0.8rem; padding: 8px; }
            .table tbody td { padding: 8px; }
        }
        
        @media (max-width: 768px) {
            .transcript-box { padding: 15px; margin: 15px 10px; border-radius: 10px; }
            .transcript-box h3 { font-size: 1.3rem; }
            .transcript-box h5 { font-size: 1rem; }
            .table { font-size: 0.75rem; }
            .table thead th { font-size: 0.7rem; padding: 6px; }
            .table tbody td { padding: 6px; }
            .cell-mini { font-size: 0.7rem; }
        }
        
        @media (max-width: 576px) {
            .transcript-box { padding: 10px; margin: 10px 5px; }
            .transcript-box h3 { font-size: 1.1rem; }
            .transcript-box h5 { font-size: 0.9rem; }
            .transcript-box p { font-size: 0.75rem; }
            .table { font-size: 0.7rem; }
            .table thead th { font-size: 0.65rem; padding: 5px; }
            .table tbody td { padding: 5px; }
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="transcript-box">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-primary mb-1">POKHARA ENGINEERING COLLEGE</h3>
        <h5 class="text-muted mb-1"><?= htmlspecialchars($dept_name) ?> | <?= htmlspecialchars($sem_name) ?></h5>
        <p class="text-secondary mb-0 text-uppercase small fw-bold">
            Result Type: <?= strtoupper($type) ?><?= $batch !== '' ? ' | Batch '.$batch : '' ?><?= $section !== '' ? ' | Section '.htmlspecialchars($section) : '' ?>
        </p>
    </div>

    <div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="bg-light text-center">
            <tr>
                <th>Symbol No.</th>
                <?php foreach ($subjects as $sub): ?>
                    <th class="subject-head text-center"><?= htmlspecialchars($sub['subject_name']) ?></th>
                <?php endforeach; ?>
                <th class="text-center">SGPA</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($students) > 0 && count($subjects) > 0): ?>
                <?php foreach ($students as $stu): 
                    $fail_any = false;
                    $total_cr = 0.0;
                    $total_pts = 0.0;
                ?>
                    <tr>
                        <td class="fw-bold text-center"><?= htmlspecialchars($stu['symbol_no']) ?></td>
                        <?php foreach ($subjects as $sub): 
                            $cell = '-';
                            $m = $marks[$stu['id']][$sub['sub_key']] ?? null;
                            if ($m) {
                                $cr = (float)($m['credit_hours'] ?? 3.0);
                                if ($type === 'ut') {
                                    $ut = isset($m['ut_obtain']) ? number_format((float)$m['ut_obtain'], 2) : '-';
                                    $is_fail = (strtoupper(trim($m['ut_grade'] ?? '')) === 'F');
                                    if (!$is_fail && isset($m['grade_point'])) {
                                        $total_cr += $cr;
                                        $total_pts += ((float)$m['grade_point']) * $cr;
                                    } else {
                                        $fail_any = $fail_any || $is_fail;
                                    }
                                    $cell = "<div class='cell-mini fw-bold text-primary'>{$ut}</div>";
                                } else {
                                    $th = isset($m['final_theory']) ? number_format((float)$m['final_theory'], 2) : '-';
                                    $pr = isset($m['practical_marks']) ? number_format((float)$m['practical_marks'], 2) : '-';
                                    $gp_float = (float)($m['grade_point'] ?? 0);
                                    $is_fail = (strtoupper(trim($m['letter_grade'] ?? '')) === 'F') || ($gp_float <= 0);
                                    if ($is_fail) {
                                        $fail_any = true;
                                    } else {
                                        $total_cr += $cr;
                                        $total_pts += $gp_float * $cr;
                                    }
                                    $cell = "<div class='cell-mini fw-bold'>Th: {$th}</div><div class='cell-mini'>Pr: {$pr}</div>";
                                }
                            }
                        ?>
                            <td class="text-center"><?= $cell ?></td>
                        <?php endforeach; ?>
                        <td class="text-center fw-bold">
                            <?php
                                if (!$fail_any && $total_cr > 0) {
                                    echo number_format($total_pts / $total_cr, 2);
                                } else {
                                    echo '-';
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $total_cols ?>" class="text-center py-5">
                        <div class="alert alert-warning d-inline-block mb-0">
                            <i class="fas fa-search me-2"></i> No records found for this selection.
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>