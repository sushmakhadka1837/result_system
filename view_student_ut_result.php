<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id'])){
    header("Location: index.php");
    exit();
}

// Define gradePoint function first
if (!function_exists('gradePoint')) {
    function gradePoint($grade){
        $grade = strtoupper(trim($grade ?? ''));
        $points = ['A'=>4.0, 'A-'=>3.7, 'B+'=>3.3, 'B'=>3.0, 'B-'=>2.7, 'C+'=>2.3, 'C'=>2.0, 'C-'=>1.7, 'D+'=>1.3, 'D'=>1.0, 'F'=>0.0];
        return $points[$grade] ?? 0.0;
    }
}

$student_id = $_SESSION['student_id'];

// 1. Fetch Student and Department Details
$student_q = $conn->query("
    SELECT s.*, d.department_name 
    FROM students s
    JOIN departments d ON s.department_id = d.id
    WHERE s.id = $student_id
");
$student = $student_q->fetch_assoc();

// 2. Determine Max Semester (Architecture = 10, Others = 8)
$max_sem = (stripos($student['department_name'], 'Architecture') !== false) ? 10 : 8;

// 3. Handle Semester Filter
$selected_sem = isset($_GET['sem_id']) ? intval($_GET['sem_id']) : $student['current_semester'];

// 5. Calculate GPA and Rank Analysis
$gpa_data = [];
$all_students = $conn->query("
    SELECT s.id, s.full_name, s.symbol_no
    FROM students s
    WHERE s.department_id = {$student['department_id']} 
    AND s.batch_year = {$student['batch_year']}
");

if($all_students && $all_students->num_rows > 0) {
    while($std = $all_students->fetch_assoc()) {
        $std_id = $std['id'];
        $std_results = $conn->query("
            SELECT r.ut_grade, sm.credit_hours
            FROM results r
            INNER JOIN subjects_master sm ON r.subject_id = sm.id 
            WHERE r.student_id = $std_id 
            AND r.semester_id = $selected_sem 
            AND sm.subject_type != 'Project'
        ");
        
        $total_cr = 0;
        $total_gp = 0;
        if($std_results && $std_results->num_rows > 0) {
            while($res = $std_results->fetch_assoc()) {
                $cr = (float)$res['credit_hours'];
                $gp = gradePoint($res['ut_grade']);
                $total_cr += $cr;
                $total_gp += ($cr * $gp);
            }
        }
        
        $gpa = ($total_cr > 0) ? $total_gp / $total_cr : 0;
        $gpa_data[] = [
            'student_id' => $std_id,
            'name' => $std['full_name'],
            'symbol' => $std['symbol_no'],
            'gpa' => $gpa
        ];
    }
}

// Sort by GPA descending to find rank
usort($gpa_data, function($a, $b) {
    return $b['gpa'] <=> $a['gpa'];
});

// Find current student's rank
$student_rank = 0;
$student_gpa = 0;
foreach($gpa_data as $key => $data) {
    if($data['student_id'] == $student_id) {
        $student_rank = $key + 1;
        $student_gpa = $data['gpa'];
        break;
    }
}


if (!function_exists('gradePoint')) {
    function gradePoint($grade){
        $grade = strtoupper(trim($grade ?? ''));
        $points = ['A'=>4.0, 'A-'=>3.7, 'B+'=>3.3, 'B'=>3.0, 'B-'=>2.7, 'C+'=>2.3, 'C'=>2.0, 'C-'=>1.7, 'D+'=>1.3, 'D'=>1.0, 'F'=>0.0];
        return $points[$grade] ?? 0.0;
    }
}

// 4. Check if results are published for this semester
$publish_check = $conn->query("
    SELECT published FROM results_publish_status 
    WHERE department_id = {$student['department_id']} 
    AND semester_id = $selected_sem 
    AND result_type = 'ut'
");
$is_published = ($publish_check && $publish_check->num_rows > 0) ? $publish_check->fetch_assoc()['published'] : 0;

// 5. Fetch Results only if published
$results_sql = "
    SELECT 
        sm.subject_name, 
        sm.subject_code, 
        sm.credit_hours, 
        r.ut_obtain, 
        r.ut_grade
    FROM results r
    INNER JOIN subjects_master sm ON r.subject_id = sm.id 
    WHERE r.student_id = $student_id 
    AND r.semester_id = $selected_sem 
    AND sm.subject_type != 'Project'
    ORDER BY sm.id ASC
";
$results_q = ($is_published == 1) ? $conn->query($results_sql) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marksheet_<?= $student['symbol_no'] ?>_Sem<?= $selected_sem ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .marksheet-card { background: white; border-radius: 15px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); border: none; position: relative; }
        .header-blue { background: linear-gradient(135deg, #1a237e 0%, #283593 100%); color: white !important; padding: 30px; border-radius: 15px 15px 0 0; }
        .badge-grade { padding: 6px 12px; border-radius: 5px; font-weight: bold; }
        
        /* Class Performance Analysis Styles */
        .performance-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 8px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        
        .performance-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
            border-color: #dee2e6;
        }
        
        .performance-card.primary {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-color: #2196f3;
        }
        
        .performance-card.success {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border-color: #4caf50;
        }
        
        .performance-card.info {
            background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%);
            border-color: #00bcd4;
        }
        
        .performance-value {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 3px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .performance-card.primary .performance-value {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .performance-card.success .performance-value {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .performance-card.info .performance-value {
            background: linear-gradient(135deg, #00bcd4 0%, #00838f 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .performance-label {
            font-size: 0.65rem;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        
        .top-performers-table {
            font-size: 0.95rem;
        }
        
        .top-performers-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s ease;
        }
        
        .top-performers-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .top-performers-table .rank-badge {
            font-weight: 900;
            font-size: 1.1rem;
            min-width: 35px;
            display: inline-block;
        }
        
        .top-performers-table .student-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .top-performers-table .gpa-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        
        .progress-section {
            background: linear-gradient(90deg, #f5f5f5 0%, #fff 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
        }
        
        .progress-section h5 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

<div class="d-print-none">
    <?php include 'student_header.php'; ?>
</div>

<div class="container my-5">
    
    <div class="filter-section d-print-none mb-4">
        <div class="card p-4 border-0 shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-5">
                    <form method="GET" id="semForm">
                        <label class="form-label fw-bold text-muted small">SELECT SEMESTER</label>
                        <select name="sem_id" class="form-select border-primary" onchange="this.form.submit()">
                            <?php for($i=1; $i<=$max_sem; $i++): ?>
                                <option value="<?= $i ?>" <?= ($selected_sem == $i) ? 'selected' : '' ?>>
                                    Semester <?= $i ?> <?= ($i == $student['current_semester']) ? '(Current)' : '' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </form>
                </div>
                <div class="col-md-7 text-end pt-4">
                    <a href="download_marksheet.php?sem_id=<?= $selected_sem ?>" class="btn btn-primary shadow-sm px-4">
                        <i class="bi bi-file-earmark-pdf-fill me-2"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="marksheet-card overflow-hidden">
        <div class="header-blue d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1 text-white text-uppercase">Academic Transcript (Internal)</h2>
                <p class="mb-0 opacity-75">Pokhara Engineering College | Unit Test Results</p>
            </div>
            <div class="text-end d-none d-md-block">
                <h5 class="mb-0 fw-bold">Semester: <?= $selected_sem ?></h5>
                <small class="opacity-75">Date: <?= date('Y-m-d') ?></small>
            </div>
        </div>

        <div class="p-4 border-bottom bg-light">
            <div class="row g-3 text-center">
                <div class="col-md-3"> <small class="text-muted d-block small">NAME</small> <strong><?= htmlspecialchars($student['full_name']) ?></strong> </div>
                <div class="col-md-3"> <small class="text-muted d-block small">SYMBOL NO</small> <strong><?= htmlspecialchars($student['symbol_no']) ?></strong> </div>
                <div class="col-md-3"> <small class="text-muted d-block small">DEPARTMENT</small> <strong><?= htmlspecialchars($student['department_name']) ?></strong> </div>
                <div class="col-md-3"> <small class="text-muted d-block small">BATCH</small> <strong><?= $student['batch_year'] ?></strong> </div>
            </div>
        </div>

        <div class="p-4">
            <table class="table table-bordered align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th>S.N</th>
                        <th class="text-start">Course Title</th>
                        <th>Code</th>
                        <th>Credit</th>
                        <th>Obtained</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sn=1; $total_cr=0; $total_gp=0; $has_fail=false;
                    if($results_q && $results_q->num_rows > 0):
                        while($row = $results_q->fetch_assoc()):
                            $cr = (float)$row['credit_hours'];
                            $gr = $row['ut_grade'] ?? 'N/A';
                            $gp = gradePoint($gr);
                            $total_cr += $cr;
                            $total_gp += ($cr * $gp);
                            if(strtoupper($gr) === 'F'){ $has_fail = true; }
                    ?>
                    <tr class="text-center">
                        <td><?= $sn++ ?></td>
                        <td class="text-start fw-bold"><?= htmlspecialchars($row['subject_name']) ?></td>
                        <td><?= $row['subject_code'] ?></td>
                        <td><?= $cr ?></td>
                        <td class="fw-bold">
                            <?php if($row['ut_obtain'] === null || $row['ut_obtain'] == 0 && $gr == 'N/A'): ?>
                                <span class="text-muted">-</span>
                            <?php else: ?>
                                <?= number_format((float)$row['ut_obtain'], 2) ?>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge-grade border"><?= $gr ?></span></td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center py-5">Result Not Published.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if($is_published == 1 && $total_cr > 0): ?>
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    * This is a computer generated internal report.
                </div>
                <div class="p-3 rounded bg-dark text-white text-center shadow" style="min-width: 150px;">
                    <small class="d-block opacity-75">UT GPA</small>
                    <?php if($has_fail): ?>
                        <h6 class="mb-0 fw-bold">N/A (has failed course)</h6>
                    <?php else: ?>
                        <h4 class="mb-0 fw-bold"><?= number_format($total_gp / $total_cr, 2) ?></h4>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CLASS RANK ANALYSIS SECTION -->
    <?php if($is_published == 1): ?>
    <div class="marksheet-card mt-5 overflow-hidden">
        <div class="header-blue">
            <h3 class="fw-bold mb-0 text-white">📊 Class Performance Analysis</h3>
        </div>
        
        <div class="p-4">
            <div class="row g-2 mb-3 justify-content-center">
                <div class="col-md-3 col-sm-4">
                    <div class="performance-card primary">
                        <div class="performance-label">Your GPA</div>
                        <div class="performance-value">
                            <?php if($has_fail): ?>
                                N/A
                            <?php else: ?>
                                <?= number_format($student_gpa, 2) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-4">
                    <div class="performance-card success">
                        <div class="performance-label">Class Rank</div>
                        <div class="performance-value">
                            <?php if($has_fail): ?>
                                N/A
                            <?php else: ?>
                                <?= $student_rank ?> / <?= count($gpa_data) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-4">
                    <div class="performance-card info">
                        <div class="performance-label">Class Size</div>
                        <div class="performance-value"><?= count($gpa_data) ?></div>
                        <small style="color: #666;">Students</small>
                    </div>
                </div>
            </div>

            <!-- Rank Visualization -->
            <div class="progress-section">
                <h5>🏆 Top Performers in Your Class (Semester <?= $selected_sem ?>)</h5>
                <div class="table-responsive">
                    <table class="table table-sm top-performers-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">Rank</th>
                                <th>Student Name</th>
                                <th style="width: 100px;">GPA</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $displayed = 0;
                            foreach($gpa_data as $key => $data): 
                                if($displayed >= 5) break;
                                $rank = $key + 1;
                                $is_current = ($data['student_id'] == $student_id) ? 'bg-warning bg-opacity-25' : '';
                                $percentage = ($data['gpa'] / 4.0) * 100;
                                $displayed++;
                            ?>
                            <tr class="<?= $is_current ?>">
                                <td class="rank-badge text-center">
                                    <?php if($rank == 1): ?>
                                        🥇
                                    <?php elseif($rank == 2): ?>
                                        🥈
                                    <?php elseif($rank == 3): ?>
                                        🥉
                                    <?php else: ?>
                                        <span class="text-muted">#<?= $rank ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="student-name">
                                    <?= htmlspecialchars($data['name']) ?>
                                    <?= ($data['student_id'] == $student_id) ? '<span class="badge bg-warning text-dark ms-2" style="font-size: 0.75rem;">You</span>' : '' ?>
                                </td>
                                <td>
                                    <span class="gpa-badge"><?= number_format($data['gpa'], 2) ?></span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 28px; border-radius: 8px;">
                                        <div class="progress-bar" style="width: <?= $percentage ?>%; background: linear-gradient(90deg, <?= ($percentage >= 90 ? '#4caf50' : ($percentage >= 75 ? '#2196f3' : ($percentage >= 60 ? '#ff9800' : '#f44336'))) ?> 0%, <?= ($percentage >= 90 ? '#388e3c' : ($percentage >= 75 ? '#1976d2' : ($percentage >= 60 ? '#f57c00' : '#d32f2f'))) ?> 100%); display: flex; align-items: center; justify-content: center;">
                                            <small class="text-white fw-bold" style="text-shadow: 0 1px 2px rgba(0,0,0,0.3);"><?= number_format($percentage, 1) ?>%</small>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="d-print-none">
    <?php if($is_published == 1): ?>
        <?php include 'target.php'; ?>
        <?php include 'ai_predictive_marks.php'; ?>
    <?php endif; ?>

    <?php include 'footer.php'; ?>
</div>

</body>
</html>