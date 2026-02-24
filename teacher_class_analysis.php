<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['teacher_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher info
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id=?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Get teacher's assigned subjects with UT marks
$ut_subjects_query = "SELECT DISTINCT 
                   sm.id AS subject_id,
                   sm.subject_name,
                   sm.subject_code,
                   ts.semester_id,
                   ts.batch_year,
                   d.department_name
                   FROM teacher_subjects ts
                   JOIN subjects_master sm ON ts.subject_map_id = sm.id
                   JOIN departments d ON ts.department_id = d.id
                   WHERE ts.teacher_id = ? AND EXISTS (
                       SELECT 1 FROM results WHERE subject_id = sm.id AND ut_obtain > 0
                   )
                   ORDER BY ts.batch_year DESC, ts.semester_id ASC";
$ut_stmt = $conn->prepare($ut_subjects_query);
$ut_stmt->bind_param("i", $teacher_id);
$ut_stmt->execute();
$ut_subjects = $ut_stmt->get_result();

// Get teacher's assigned subjects with Assessment marks
$assessment_subjects_query = "SELECT DISTINCT 
                   sm.id AS subject_id,
                   sm.subject_name,
                   sm.subject_code,
                   ts.semester_id,
                   ts.batch_year,
                   d.department_name
                   FROM teacher_subjects ts
                   JOIN subjects_master sm ON ts.subject_map_id = sm.id
                   JOIN departments d ON ts.department_id = d.id
                   WHERE ts.teacher_id = ? AND EXISTS (
                       SELECT 1 FROM results WHERE subject_id = sm.id AND assessment_raw > 0
                   )
                   ORDER BY ts.batch_year DESC, ts.semester_id ASC";
$assessment_stmt = $conn->prepare($assessment_subjects_query);
$assessment_stmt->bind_param("i", $teacher_id);
$assessment_stmt->execute();
$assessment_subjects = $assessment_stmt->get_result();

// Function to get subject analytics
function getSubjectAnalytics($conn, $subject_id) {
    $data = [];
    
    // Total students enrolled
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT student_id) as total FROM results WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $data['total_students'] = $stmt->get_result()->fetch_assoc()['total'];
    
    // Pass/Fail statistics
    $stmt = $conn->prepare("SELECT 
                            COUNT(CASE WHEN letter_grade NOT IN ('F', 'NG') THEN 1 END) as passed,
                            COUNT(CASE WHEN letter_grade IN ('F', 'NG') THEN 1 END) as failed,
                            COUNT(*) as total_evaluated
                            FROM results WHERE subject_id = ? AND published = 1");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $data['pass_fail'] = $stmt->get_result()->fetch_assoc();
    
    // Average marks
    $stmt = $conn->prepare("SELECT 
                           AVG(total_obtained) as avg_total,
                           MAX(total_obtained) as highest,
                           MIN(total_obtained) as lowest,
                           AVG(practical_marks) as avg_practical,
                           MAX(practical_marks) as max_practical,
                           AVG(final_theory) as avg_theory,
                           MAX(final_theory) as max_theory,
                           AVG(total_attendance_days) as avg_attendance_days
                           FROM results WHERE subject_id = ? AND published = 1");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $data['averages'] = $stmt->get_result()->fetch_assoc();
    
    return $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Performance Analysis | Teacher Portal</title>
    
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
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
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
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .subject-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: var(--primary-color);
        }
        
        .subject-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .subject-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }
        
        .subject-code {
            background: #f1f5f9;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
        }
        
        .mini-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .mini-stat {
            text-align: center;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 10px;
        }
        
        .mini-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .mini-stat-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        
        .view-details-btn {
            margin-top: 1rem;
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, var(--primary-color), #6366f1);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .view-details-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }
        
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 24px 24px;
        }
        
        .btn-primary-custom {
            background: var(--primary-color);
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            background: #4338ca;
            transform: translateY(-2px);
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
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="mb-2"><i class="fas fa-chart-line me-2"></i> Class Performance Analysis</h1>
                    <p class="mb-0 opacity-90">Comprehensive insights into student performance and class statistics</p>
                </div>
                <a href="teacher_dashboard.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap">
            <h4 class="mb-3 mb-md-0"><i class="fas fa-book-open me-2 text-primary"></i> Your Assigned Subjects</h4>
            
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-outline-primary selector-btn selected" id="utBtn" onclick="switchView('ut')">
                    <i class="fas fa-file-alt me-2"></i> UT
                </button>
                <button class="btn btn-outline-secondary selector-btn" id="assessmentBtn" onclick="switchView('assessment')">
                    <i class="fas fa-check-square me-2"></i> Assessment
                </button>
            </div>
        </div>

        <style>
            .selector-btn {
                border-radius: 8px;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .selector-btn.selected {
                background-color: #4f46e5;
                color: white;
                border-color: #4f46e5;
            }
            
            .selector-btn:not(.selected):hover {
                border-color: #4f46e5;
                color: #4f46e5;
            }
            
            .subjects-container {
                display: none;
            }
            
            .subjects-container.active {
                display: block;
            }
        </style>
        
        <!-- UT Subjects Container -->
        <div id="utContainer" class="subjects-container active">
        <?php 
        $ut_subjects->data_seek(0);
        $has_ut_subjects = false;
        ?>
        <div class="row g-4">
        <?php
        while($subject = $ut_subjects->fetch_assoc()): 
            $has_ut_subjects = true;
            $analytics = getSubjectAnalytics($conn, $subject['subject_id']);
            $total_evaluated = $analytics['pass_fail']['total_evaluated'];
            $pass_percent = $total_evaluated > 0 ? round(($analytics['pass_fail']['passed'] / $total_evaluated) * 100, 1) : 0;
            $class_avg = round($analytics['averages']['avg_total'] ?? 0, 1);
        ?>
        
        <div class="col-md-6 col-xl-4">
        <!-- Subject Card -->
        <div class="subject-card" onclick="window.location.href='teacher_subject_details.php?subject_id=<?= $subject['subject_id'] ?>&component=ut'">
            <div class="subject-header">
                <div>
                    <h3 class="subject-title"><?= htmlspecialchars($subject['subject_name']) ?></h3>
                    <small class="text-muted">
                        <?= htmlspecialchars($subject['department_name']) ?> | 
                        Batch: <?= $subject['batch_year'] ?> | 
                        Semester: <?= $subject['semester_id'] ?>
                    </small>
                </div>
                <span class="subject-code"><?= htmlspecialchars($subject['subject_code']) ?></span>
            </div>
            
            <div class="mini-stats">
                <div class="mini-stat">
                    <div class="mini-stat-value text-primary">
                        <i class="fas fa-users"></i> <?= $analytics['total_students'] ?>
                    </div>
                    <div class="mini-stat-label">Students</div>
                </div>
                
                <div class="mini-stat">
                    <div class="mini-stat-value text-success">
                        <i class="fas fa-check-circle"></i> <?= $pass_percent ?>%
                    </div>
                    <div class="mini-stat-label">Pass Rate</div>
                </div>
                
                <div class="mini-stat">
                    <div class="mini-stat-value" style="color: #9f1239;">
                        <i class="fas fa-trophy"></i> <?= round($analytics['averages']['highest'] ?? 0, 1) ?>%
                    </div>
                    <div class="mini-stat-label">UT Highest</div>
                </div>
            </div>
            
            
            <button class="view-details-btn" onclick="window.location.href='teacher_subject_details.php?subject_id=<?= $subject['subject_id'] ?>&component=ut'">
                <i class="fas fa-chart-pie me-2"></i> View Detailed Analysis
            </button>
        </div>
        </div>
        
        <?php endwhile; ?>
        </div>
        
        <?php if (!$has_ut_subjects): ?>
            <!-- Empty State -->
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-5x text-muted mb-4 opacity-25"></i>
                <h4 class="text-muted">No UT Subjects Assigned</h4>
                <p class="text-muted">You don't have any subjects with UT marks assigned yet. Please contact the administrator.</p>
            </div>
        <?php endif; ?>
        </div>

        <!-- Assessment Subjects Container -->
        <div id="assessmentContainer" class="subjects-container">
        <?php 
        $assessment_subjects->data_seek(0);
        $has_assessment_subjects = false;
        ?>
        <div class="row g-4">
        <?php
        while($subject = $assessment_subjects->fetch_assoc()): 
            $has_assessment_subjects = true;
            $analytics = getSubjectAnalytics($conn, $subject['subject_id']);
            $total_evaluated = $analytics['pass_fail']['total_evaluated'];
            $pass_percent = $total_evaluated > 0 ? round(($analytics['pass_fail']['passed'] / $total_evaluated) * 100, 1) : 0;
            $class_avg = round($analytics['averages']['avg_total'] ?? 0, 1);
        ?>
        
        <div class="col-md-6 col-xl-4">
        <!-- Subject Card -->
        <div class="subject-card" onclick="window.location.href='teacher_subject_details.php?subject_id=<?= $subject['subject_id'] ?>&component=assessment'">
            <div class="subject-header">
                <div>
                    <h3 class="subject-title"><?= htmlspecialchars($subject['subject_name']) ?></h3>
                    <small class="text-muted">
                        <?= htmlspecialchars($subject['department_name']) ?> | 
                        Batch: <?= $subject['batch_year'] ?> | 
                        Semester: <?= $subject['semester_id'] ?>
                    </small>
                </div>
                <span class="subject-code"><?= htmlspecialchars($subject['subject_code']) ?></span>
            </div>
            
            <div class="mini-stats">
                <div class="mini-stat">
                    <div class="mini-stat-value text-primary">
                        <i class="fas fa-users"></i> <?= $analytics['total_students'] ?>
                    </div>
                    <div class="mini-stat-label">Students</div>
                </div>
                
                <div class="mini-stat">
                    <div class="mini-stat-value text-success">
                        <i class="fas fa-check-circle"></i> <?= $pass_percent ?>%
                    </div>
                    <div class="mini-stat-label">Pass Rate</div>
                </div>
                
                <div class="mini-stat">
                    <div class="mini-stat-value" style="color: #7c3aed;">
                        <i class="fas fa-calendar-days"></i> <?= round($analytics['averages']['avg_attendance_days'] ?? 0, 1) ?>
                    </div>
                    <div class="mini-stat-label">Avg Attendance Days</div>
                </div>
            </div>
            
            
            <button class="view-details-btn" onclick="window.location.href='teacher_subject_details.php?subject_id=<?= $subject['subject_id'] ?>&component=assessment'">
                <i class="fas fa-chart-pie me-2"></i> View Detailed Analysis
            </button>
        </div>
        </div>
        
        <?php endwhile; ?>
        </div>
        
        <?php if (!$has_assessment_subjects): ?>
            <!-- Empty State -->
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-5x text-muted mb-4 opacity-25"></i>
                <h4 class="text-muted">No Assessment Subjects Assigned</h4>
                <p class="text-muted">You don't have any subjects with Assessment marks assigned yet. Please contact the administrator.</p>
            </div>
        <?php endif; ?>
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function switchView(view) {
            const utBtn = document.getElementById('utBtn');
            const assessmentBtn = document.getElementById('assessmentBtn');
            const utContainer = document.getElementById('utContainer');
            const assessmentContainer = document.getElementById('assessmentContainer');
            
            if (view === 'ut') {
                utBtn.classList.add('selected');
                assessmentBtn.classList.remove('selected');
                utContainer.classList.add('active');
                assessmentContainer.classList.remove('active');
            } else if (view === 'assessment') {
                assessmentBtn.classList.add('selected');
                utBtn.classList.remove('selected');
                assessmentContainer.classList.add('active');
                utContainer.classList.remove('active');
            }
        }
    </script>
</body>
</html>