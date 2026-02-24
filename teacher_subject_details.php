<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['teacher_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$component = isset($_GET['component']) ? htmlspecialchars($_GET['component'], ENT_QUOTES) : 'ut';
$component = ($component === 'assessment') ? 'assessment' : 'ut';
$score_field = ($component === 'assessment') ? 'total_obtained' : 'ut_obtain';
$score_label = ($component === 'assessment') ? 'Assessment Total' : 'UT';
$grade_field = ($component === 'assessment') ? 'letter_grade' : 'ut_grade';

if (!$subject_id) {
    header("Location: teacher_class_analysis.php");
    exit();
}

// Verify teacher has access to this subject
$verify_query = "SELECT sm.*, ts.batch_year, ts.semester_id, d.department_name
                 FROM teacher_subjects ts
                 JOIN subjects_master sm ON ts.subject_map_id = sm.id
                 JOIN departments d ON ts.department_id = d.id
                 WHERE sm.id = ? AND ts.teacher_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $subject_id, $teacher_id);
$stmt->execute();
$subject_info = $stmt->get_result()->fetch_assoc();

if (!$subject_info) {
    header("Location: teacher_class_analysis.php");
    exit();
}

$target_semester = (int)($subject_info['semester_id'] ?? 0);
$target_batch = (int)($subject_info['batch_year'] ?? 0);

// Total students enrolled
$total_students_query = "SELECT COUNT(DISTINCT student_id) as total
                         FROM results
                         WHERE subject_id = ?
                         AND semester_id = ?
                         AND student_id IN (SELECT id FROM students WHERE batch_year = ?)";
$stmt = $conn->prepare($total_students_query);
$stmt->bind_param("iii", $subject_id, $target_semester, $target_batch);
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['total'];

// Component score statistics
$score_stats_query = "SELECT 
                    AVG($score_field) as avg_score,
                    MAX($score_field) as max_score,
                    MIN($score_field) as min_score
                    FROM results
                    WHERE subject_id = ?
                    AND semester_id = ?
                    AND student_id IN (SELECT id FROM students WHERE batch_year = ?)
                    AND $score_field > 0";
$stmt = $conn->prepare($score_stats_query);
$stmt->bind_param("iii", $subject_id, $target_semester, $target_batch);
$stmt->execute();
$score_stats = $stmt->get_result()->fetch_assoc();

$average_score = $score_stats['avg_score'] ?? 0;
$max_score = $score_stats['max_score'] ?? 0;
$min_score = $score_stats['min_score'] ?? 0;

// Above/Below average counts for component score
$pass_fail_query = "SELECT 
                    COUNT(CASE WHEN $score_field >= ? THEN 1 END) as above_avg,
                    COUNT(CASE WHEN $score_field < ? THEN 1 END) as below_avg
                    FROM results
                    WHERE subject_id = ?
                    AND semester_id = ?
                    AND student_id IN (SELECT id FROM students WHERE batch_year = ?)
                    AND $score_field > 0";
$stmt = $conn->prepare($pass_fail_query);
$stmt->bind_param("ddiii", $average_score, $average_score, $subject_id, $target_semester, $target_batch);
$stmt->execute();
$pass_fail = $stmt->get_result()->fetch_assoc();

// Grade-based pass/fail counts
$pass_fail_grade_query = "SELECT 
                    COUNT(CASE WHEN $grade_field NOT IN ('F', 'NG') THEN 1 END) as passed,
                    COUNT(CASE WHEN $grade_field IN ('F', 'NG') THEN 1 END) as failed
                    FROM results
                    WHERE subject_id = ?
                    AND semester_id = ?
                    AND student_id IN (SELECT id FROM students WHERE batch_year = ?)
                    AND $score_field > 0";
$stmt = $conn->prepare($pass_fail_grade_query);
$stmt->bind_param("iii", $subject_id, $target_semester, $target_batch);
$stmt->execute();
$pass_fail_grade = $stmt->get_result()->fetch_assoc();

// Score range distribution based on max score
if ($max_score > 0) {
    $excellent_min = $max_score * 0.9;
    $very_good_min = $max_score * 0.8;
    $good_min = $max_score * 0.7;
    $satisfactory_min = $max_score * 0.6;

    $marks_query = "SELECT 
                    COUNT(CASE WHEN $score_field >= ? THEN 1 END) as excellent,
                    COUNT(CASE WHEN $score_field >= ? AND $score_field < ? THEN 1 END) as very_good,
                    COUNT(CASE WHEN $score_field >= ? AND $score_field < ? THEN 1 END) as good,
                    COUNT(CASE WHEN $score_field >= ? AND $score_field < ? THEN 1 END) as satisfactory,
                    COUNT(CASE WHEN $score_field < ? THEN 1 END) as below_avg
                    FROM results
                    WHERE subject_id = ?
                    AND semester_id = ?
                    AND student_id IN (SELECT id FROM students WHERE batch_year = ?)
                    AND published = 1";
    $stmt = $conn->prepare($marks_query);
    $stmt->bind_param(
        "ddddddddiii",
        $excellent_min,
        $very_good_min, $excellent_min,
        $good_min, $very_good_min,
        $satisfactory_min, $good_min,
        $satisfactory_min,
        $subject_id,
        $target_semester,
        $target_batch
    );
    $stmt->execute();
    $marks_dist = $stmt->get_result()->fetch_assoc();
} else {
    $marks_dist = [
        'excellent' => 0,
        'very_good' => 0,
        'good' => 0,
        'satisfactory' => 0,
        'below_avg' => 0
    ];
}

// Component averages
$component_query = "SELECT 
                    AVG(ut_obtain) as ut_avg,
                    AVG(total_obtained) as assessment_avg,
                    MAX(ut_obtain) as ut_max,
                    MAX(total_obtained) as assessment_max
                    FROM results
                    WHERE subject_id = ?
                    AND semester_id = ?
                    AND student_id IN (SELECT id FROM students WHERE batch_year = ?)
                    AND published = 1";
$stmt = $conn->prepare($component_query);
$stmt->bind_param("iii", $subject_id, $target_semester, $target_batch);
$stmt->execute();
$components = $stmt->get_result()->fetch_assoc();

// UT Details
$ut_query = "SELECT 
                AVG(ut_obtain) as ut_avg,
                MAX(ut_obtain) as ut_max,
                MIN(ut_obtain) as ut_min
                FROM results
                WHERE subject_id = ?
                AND semester_id = ?
                AND student_id IN (SELECT id FROM students WHERE batch_year = ?)
                AND published = 1";
$stmt = $conn->prepare($ut_query);
$stmt->bind_param("iii", $subject_id, $target_semester, $target_batch);
$stmt->execute();
$ut_details = $stmt->get_result()->fetch_assoc();

// Assessment Details (Practical, Theory, Attendance)
$assessment_query = "SELECT 
                AVG(practical_marks) as practical_avg,
                MAX(practical_marks) as practical_max,
                AVG(final_theory) as theory_avg,
                MAX(final_theory) as theory_max,
                AVG(total_attendance_days) as attendance_avg,
                MAX(total_attendance_days) as attendance_max,
                MIN(total_attendance_days) as attendance_min
                FROM results
                WHERE subject_id = ?
                AND semester_id = ?
                AND student_id IN (SELECT id FROM students WHERE batch_year = ?)
                AND published = 1";
$stmt = $conn->prepare($assessment_query);
$stmt->bind_param("iii", $subject_id, $target_semester, $target_batch);
$stmt->execute();
$assessment_details = $stmt->get_result()->fetch_assoc();

// Top 10 performers based on component score
$top_query = "SELECT s.full_name, s.symbol_no, r.$score_field as component_score, r.$grade_field as component_grade, 
                     r.practical_marks, r.final_theory
              FROM results r
              JOIN students s ON r.student_id = s.id
        WHERE r.subject_id = ?
        AND r.semester_id = ?
        AND s.batch_year = ?
        AND r.$score_field > 0
              ORDER BY s.symbol_no ASC
              LIMIT 10";
$stmt = $conn->prepare($top_query);
$stmt->bind_param("iii", $subject_id, $target_semester, $target_batch);
$stmt->execute();
$top_performers = $stmt->get_result();

// Focus Needed students
if ($component === 'ut') {
    $bottom_query = "SELECT s.full_name, s.symbol_no, r.ut_obtain as component_score, r.ut_grade as component_grade, 
                            r.practical_marks, r.final_theory
                     FROM results r
                     JOIN students s ON r.student_id = s.id
                     WHERE r.subject_id = ? 
                     AND r.semester_id = ?
                     AND s.batch_year = ?
                     AND r.ut_grade IN ('C', 'C-', 'D+', 'D', 'F', 'NG')
                     ORDER BY s.symbol_no ASC";
    $stmt = $conn->prepare($bottom_query);
    $stmt->bind_param("iii", $subject_id, $target_semester, $target_batch);
    $stmt->execute();
    $bottom_performers = $stmt->get_result();
} else {
    $bottom_query = "SELECT s.full_name, s.symbol_no, r.total_obtained as component_score, r.letter_grade as component_grade, 
                            r.practical_marks, r.final_theory
                     FROM results r
                     JOIN students s ON r.student_id = s.id
                     WHERE r.subject_id = ?
                     AND r.semester_id = ?
                     AND s.batch_year = ?
                     AND r.letter_grade IN ('B-', 'C+', 'C', 'C-', 'D+', 'D', 'F', 'NG')
                     ORDER BY s.symbol_no ASC";
    $stmt = $conn->prepare($bottom_query);
    $stmt->bind_param("iii", $subject_id, $target_semester, $target_batch);
    $stmt->execute();
    $bottom_performers = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($subject_info['subject_name']) ?> - Analysis</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --bg-soft: #f8fafc;
            --text-dark: #1e293b;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-soft);
            color: var(--text-dark);
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 24px 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .chart-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #4f46e5;
        }
        
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        } vanera 
        
        .table-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #4f46e5;
        }
        
        .component-selector {
            cursor: pointer;
        }
        
        .component-selector.selected {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1), 0 10px 25px rgba(0,0,0,0.1) !important;
        }
        
        .badge-grade {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .rank-badge {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
        }
        
        .rank-1 { background: linear-gradient(135deg, #ffd700, #ffed4e); color: #000; }
        .rank-2 { background: linear-gradient(135deg, #c0c0c0, #e8e8e8); color: #000; }
        .rank-3 { background: linear-gradient(135deg, #cd7f32, #e6a85c); color: #fff; }
        .rank-other { background: #e2e8f0; color: #64748b; }
        
        @media (max-width: 768px) {
            .stat-value { font-size: 1.5rem; }
            .chart-container { padding: 1rem; }
        }
    </style>
</head>
<body>
<?php include 'teacher_header.php'; ?>
    <div class="page-header">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-chart-pie me-2"></i> 
                        <?= htmlspecialchars($subject_info['subject_name']) ?>
                    </h1>
                    <p class="mb-0 opacity-90">
                        <span class="badge bg-light text-dark me-2"><?= htmlspecialchars($subject_info['subject_code']) ?></span>
                        <?= htmlspecialchars($subject_info['department_name']) ?> | 
                        Batch: <?= $subject_info['batch_year'] ?> | 
                        Semester: <?= $subject_info['semester_id'] ?>
                    </p>
                </div>
                <a href="teacher_class_analysis.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <!-- Component Header with Navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <div style="background: linear-gradient(135deg, <?= $component === 'ut' ? '#667eea 0%, #764ba2' : '#10b981 0%, #059669' ?> 100%); color: white; padding: 1rem; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; box-shadow: 0 4px 15px rgba(<?= $component === 'ut' ? '102, 126, 234' : '16, 185, 129' ?>, 0.3);">
                    <div>
                        <i class="fas fa-<?= $component === 'ut' ? 'file-alt' : 'check-square' ?>"></i>
                        <strong style="font-size: 1.1rem; margin-left: 0.5rem;">
                            <?= $component === 'ut' ? 'UT (Unit Test) Analysis' : 'Assessment Analysis' ?>
                        </strong>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="?subject_id=<?= $subject_id ?>&component=ut" class="btn btn-<?= $component === 'ut' ? 'light' : 'outline-light' ?> btn-sm" style="padding: 0.5rem 1rem;">
                            <i class="fas fa-file-alt me-2"></i> UT
                        </a>
                        <a href="?subject_id=<?= $subject_id ?>&component=assessment" class="btn btn-<?= $component === 'assessment' ? 'light' : 'outline-light' ?> btn-sm" style="padding: 0.5rem 1rem;">
                            <i class="fas fa-check-square me-2"></i> Assessment
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(79, 70, 229, 0.1); color: var(--primary-color);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?= $total_students ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success-color);">
                        <i class="fas fa-<?= $component === 'ut' ? 'check-circle' : 'calendar-alt' ?>"></i>
                    </div>
                    <div class="stat-value">
                        <?php if ($component === 'ut'): ?>
                            <?= $pass_fail_grade['passed'] ?? 0 ?>
                        <?php else: ?>
                            <?= (int) round($assessment_details['attendance_max'] ?? 0) ?>
                        <?php endif; ?>
                    </div>
                    <div class="stat-label">
                        <?php if ($component === 'ut'): ?>
                            Passed
                        <?php else: ?>
                            Total Attendance Days
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-3" style="<?= $component === 'assessment' ? 'display: none;' : '' ?>">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--danger-color);">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-value">
                        <?= $pass_fail_grade['failed'] ?? 0 ?>
                    </div>
                    <div class="stat-label">Failed</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-3" id="fourthStatCard" style="display: none;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning-color);">
                        <i class="fas fa-calculator" id="fourthStatIcon"></i>
                    </div>
                    <div class="stat-value" id="fourthStatValue"><?= round($average_score, 1) ?></div>
                    <div class="stat-label" id="fourthStatLabel"><?= $score_label ?> Average</div>
                </div>
            </div>
        
        <div class="col-md-3 col-sm-6 mb-3" id="fifthStatCard" style="<?= $component === 'assessment' ? 'display: none;' : '' ?>">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(251, 191, 36, 0.1); color: #fbbf24;">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="stat-value" id="fifthStatValue"><?= round($max_score, 1) ?></div>
                <div class="stat-label" id="fifthStatLabel">Highest <?= $score_label ?> Score</div>
            </div>
        </div>
    </div>

        <!-- UT and Assessment Section (Hidden) -->
        <div class="row mb-4" style="display: none;">
            <div class="col-md-6 mb-3">
                <div class="stat-card component-selector selected" id="utCard" onclick="selectComponent('ut')">
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="flex: 1;">
                            <div class="stat-label">UT (Unit Test) Marks</div>
                            <div class="stat-value" style="font-size: 1.5rem;"><?= round($ut_details['ut_avg'] ?? 0, 1) ?></div>
                            <div class="stat-label" style="font-size: 0.75rem; color: #94a3b8;">Average</div>
                        </div>
                        <div style="text-align: center; border-left: 1px solid #e2e8f0; padding-left: 1rem;">
                            <div class="stat-label">Highest</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #3b82f6;"><?= round($ut_details['ut_max'] ?? 0, 1) ?></div>
                            <div class="stat-label" style="font-size: 0.75rem; color: #94a3b8;">Lowest: <?= round($ut_details['ut_min'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="stat-card component-selector" id="assessmentCard" onclick="selectComponent('assessment')">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="fas fa-check-square"></i>
                    </div>
                    <div>
                        <div class="stat-label"><strong>Assessment Components</strong></div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.5rem;">
                            <div>
                                <div class="stat-label" style="font-size: 0.75rem;">Practical</div>
                                <div style="font-size: 1.25rem; font-weight: 700; color: #10b981;"><?= round($assessment_details['practical_avg'] ?? 0, 1) ?></div>
                                <div class="stat-label" style="font-size: 0.7rem;">Max: <?= round($assessment_details['practical_max'] ?? 0, 1) ?></div>
                            </div>
                            <div>
                                <div class="stat-label" style="font-size: 0.75rem;">Theory</div>
                                <div style="font-size: 1.25rem; font-weight: 700; color: #10b981;"><?= round($assessment_details['theory_avg'] ?? 0, 1) ?></div>
                                <div class="stat-label" style="font-size: 0.7rem;">Max: <?= round($assessment_details['theory_max'] ?? 0, 1) ?></div>
                            </div>
                            <div>
                                <div class="stat-label" style="font-size: 0.75rem;">Attendance</div>
                                <div style="font-size: 1.25rem; font-weight: 700; color: #10b981;"><?= round($assessment_details['attendance_avg'] ?? 0, 1) ?></div>
                                <div class="stat-label" style="font-size: 0.7rem;">Max: <?= round($assessment_details['attendance_max'] ?? 0, 1) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- UT Data Section -->
        <div id="utDataSection" style="display: <?= $component === 'ut' ? 'block' : 'none' ?>;">
            <!-- Grade Distribution Section -->
            <div style="background: #f0f4ff; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <h5><i class="fas fa-chart-bar me-2" style="color: #3b82f6;"></i><?= $score_label ?> Score Distribution</h5>
            </div>
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="chart-container">
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="gradeChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Summary Section -->
                <div class="col-lg-6 mb-3">
                    <div style="background: #f0f4ff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <h5><i class="fas fa-pie-chart me-2" style="color: #3b82f6;"></i>Performance Summary</h5>
                    </div>
                    <div class="chart-container">
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Component Average (Removed or Hidden) -->
            <div style="display: none;">
                <div class="col-lg-4 mb-3">
                    <div class="chart-container">
                        <h5 class="mb-3"><i class="fas fa-sliders-h me-2"></i>Component Average</h5>
                        <div style="position: relative; height: 100px; width: 100%;">
                            <canvas id="componentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tables Section: Performers Side by Side -->
            <div class="row">
                <!-- Top Performers Table -->
                <div class="col-lg-6 mb-3">
                    <div class="table-container">
                        <h5 class="mb-3"><i class="fas fa-star me-2 text-warning"></i>Top 10 Performers</h5>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Rank</th>
                                        <th>Name</th>
                                        <th>Symbol No.</th>
                                        <th><?= $score_label ?> Score</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($top_performers && $top_performers->num_rows > 0):
                                        $top_performers->data_seek(0);
                                        $rank = 1;
                                        while($row = $top_performers->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><span class="rank-badge rank-<?= min($rank, 3) ?>">🏆</span></td>
                                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                                            <td><?= htmlspecialchars($row['symbol_no']) ?></td>
                                            <td><?= round($row['component_score'] ?? 0, 1) ?></td>
                                            <td><span class="badge-grade bg-success text-white"><?= $row['component_grade'] ?></span></td>
                                        </tr>
                                        <?php $rank++; endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">No student data available.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bottom Performers Table -->
                <div class="col-lg-6 mb-3">
                    <div class="table-container">
                        <h5 class="mb-3">
                            <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                            Focus Needed Students
                            <?php if ($component === 'ut'): ?>
                                <small style="display: block; font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Grade C+ or below</small>
                            <?php else: ?>
                                <small style="display: block; font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Grade B- or below</small>
                            <?php endif; ?>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Rank</th>
                                        <th>Name</th>
                                        <th>Symbol No.</th>
                                        <th><?= $score_label ?> Score</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($bottom_performers && $bottom_performers->num_rows > 0):
                                        $bottom_performers->data_seek(0);
                                        $rank = 1;
                                        while($row = $bottom_performers->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><span class="rank-badge rank-other"><?= $rank ?></span></td>
                                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                                            <td><?= htmlspecialchars($row['symbol_no']) ?></td>
                                            <td><?= round($row['component_score'] ?? 0, 1) ?></td>
                                            <td><span class="badge-grade bg-danger text-white"><?= $row['component_grade'] ?></span></td>
                                        </tr>
                                        <?php $rank++; endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">No student data available.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- End UT Data Section -->

        <!-- Assessment Data Section -->
        <div id="assessmentDataSection" style="display: <?= $component === 'assessment' ? 'block' : 'none' ?>;">
            <!-- Grade Distribution Section -->
            <div style="background: #f0f5e9; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <h5><i class="fas fa-chart-bar me-2" style="color: #10b981;"></i><?= $score_label ?> Score Distribution</h5>
            </div>
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="chart-container">
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="gradeChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Summary Section -->
                <div class="col-lg-6 mb-3">
                    <div style="background: #f0f5e9; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <h5><i class="fas fa-pie-chart me-2" style="color: #10b981;"></i>Performance Summary</h5>
                    </div>
                    <div class="chart-container">
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Component Average (Removed or Hidden) -->
            <div style="display: none;">
                <div class="col-lg-4 mb-3">
                    <div class="chart-container">
                        <h5 class="mb-3"><i class="fas fa-sliders-h me-2"></i>Component Average</h5>
                        <div style="position: relative; height: 100px; width: 100%;">
                            <canvas id="componentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tables Section: Performers Side by Side -->
            <div class="row">
                <!-- Top Performers Table -->
                <div class="col-lg-6 mb-3">
                    <div class="table-container">
                        <h5 class="mb-3"><i class="fas fa-star me-2 text-warning"></i>Top 10 Performers</h5>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Rank</th>
                                        <th>Name</th>
                                        <th>Symbol No.</th>
                                        <th>Practical</th>
                                        <th>Theory</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($top_performers && $top_performers->num_rows > 0):
                                        $top_performers->data_seek(0);
                                        $rank = 1;
                                        while($row = $top_performers->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><span class="rank-badge rank-<?= min($rank, 3) ?>">🏆</span></td>
                                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                                            <td><?= htmlspecialchars($row['symbol_no']) ?></td>
                                            <td><?= round($row['practical_marks'] ?? 0, 2) ?></td>
                                            <td><?= round($row['final_theory'] ?? 0, 2) ?></td>
                                            <td><span class="badge-grade bg-success text-white"><?= $row['component_grade'] ?></span></td>
                                        </tr>
                                        <?php $rank++; endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">No student data available.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bottom Performers Table -->
                <div class="col-lg-6 mb-3">
                    <div class="table-container">
                        <h5 class="mb-3">
                            <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                            Focus Needed Students
                            <small style="display: block; font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Lowest scoring</small>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Rank</th>
                                        <th>Name</th>
                                        <th>Symbol No.</th>
                                        <th>Practical</th>
                                        <th>Theory</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($bottom_performers && $bottom_performers->num_rows > 0):
                                        $bottom_performers->data_seek(0);
                                        $rank = 1;
                                        while($row = $bottom_performers->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><span class="rank-badge rank-other"><?= $rank ?></span></td>
                                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                                            <td><?= htmlspecialchars($row['symbol_no']) ?></td>
                                            <td><?= round($row['practical_marks'] ?? 0, 2) ?></td>
                                            <td><?= round($row['final_theory'] ?? 0, 2) ?></td>
                                            <td><span class="badge-grade bg-danger text-white"><?= $row['component_grade'] ?></span></td>
                                        </tr>
                                        <?php $rank++; endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">No student data available.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- End Assessment Data Section -->
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Score Distribution Chart
        <?php
        $grade_labels = ['Excellent', 'Very Good', 'Good', 'Satisfactory', 'Needs Improvement'];
        $grade_data = [
            $marks_dist['excellent'] ?? 0,
            $marks_dist['very_good'] ?? 0,
            $marks_dist['good'] ?? 0,
            $marks_dist['satisfactory'] ?? 0,
            $marks_dist['below_avg'] ?? 0
        ];
        $grade_colors = ['#10b981', '#34d399', '#f59e0b', '#fbbf24', '#ef4444'];
        ?>
        
        const activeSection = document.getElementById('<?= $component === 'ut' ? 'utDataSection' : 'assessmentDataSection' ?>');
        const gradeCanvas = activeSection ? activeSection.querySelector('canvas[id="gradeChart"]') : null;
        if (gradeCanvas) {
            const gradeCtx = gradeCanvas.getContext('2d');
            new Chart(gradeCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($grade_labels) ?>,
                    datasets: [{
                        label: 'Number of Students',
                        data: <?= json_encode($grade_data) ?>,
                        backgroundColor: <?= json_encode($grade_colors) ?>,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // Component Average Chart
        const componentCanvas = activeSection ? activeSection.querySelector('canvas[id="componentChart"]') : null;
        if (componentCanvas) {
            const componentCtx = componentCanvas.getContext('2d');
            new Chart(componentCtx, {
                type: 'bar',
                data: {
                    labels: ['UT', 'Assessment'],
                    datasets: [{
                        label: 'Average Marks',
                        data: [
                            <?= round($components['ut_avg'] ?? 0, 1) ?>,
                            <?= round($components['assessment_avg'] ?? 0, 1) ?>
                        ],
                        backgroundColor: ['#667eea', '#764ba2'],
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });
        }

        // Performance Summary (Doughnut Chart)
        <?php 
        if ($component === 'ut') {
            $passed = $pass_fail_grade['passed'] ?? 0;
            $failed = $pass_fail_grade['failed'] ?? 0;
        } else {
            $passed = $pass_fail_grade['passed'] ?? 0;
            $failed = $pass_fail_grade['failed'] ?? 0;
        }
        $total = $passed + $failed;
        ?>
        
        const performanceCanvas = activeSection ? activeSection.querySelector('canvas[id="performanceChart"]') : null;
        if (performanceCanvas) {
            const performanceCtx = performanceCanvas.getContext('2d');
            new Chart(performanceCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Passed', 'Failed'],
                    datasets: [{
                        data: [<?= $passed ?>, <?= $failed ?>],
                        backgroundColor: ['#10b981', '#ef4444'],
                        borderColor: 'white',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    </script>
</body>
</html>
