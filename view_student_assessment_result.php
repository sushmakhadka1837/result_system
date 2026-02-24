<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id'])){ 
    header("Location: login.php");
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

// 1. Student Details Fetch
$student_q = $conn->prepare("
    SELECT s.id, s.full_name, s.symbol_no, s.department_id, s.batch_year, s.semester_id as student_actual_sem, 
           d.department_name
    FROM students s
    JOIN departments d ON s.department_id=d.id
    WHERE s.id = ?
");
$student_q->bind_param("i", $student_id);
$student_q->execute();
$student = $student_q->get_result()->fetch_assoc();

if(!$student){ die("Student not found"); }

// 2. Semester Selection
$sem_id = intval($_GET['sem_id'] ?? $student['student_actual_sem']);

// 3. Result Publication Status Check
$publish_check = $conn->prepare("
    SELECT published FROM results_publish_status
    WHERE department_id=? AND batch_year=? AND semester_id=? AND result_type='assessment' AND published=1
");
$publish_check->bind_param("isi", $student['department_id'], $student['batch_year'], $sem_id);
$publish_check->execute();
$is_published = $publish_check->get_result()->num_rows;

/** * 4. MAIN RESULT QUERY (Project Exclusion Fix)
 * - Specific Semester ko result tanne.
 * - 'Project' vanni subject lai exclude garne (Report ra Calculation dubai bata).
 */
$results_query = "
    SELECT r.*, sm.subject_name, sm.is_elective, sm.credit_hours as sub_credit
    FROM results r
    JOIN subjects_master sm ON r.subject_id = sm.id
    LEFT JOIN student_electives se ON (
        sm.id = se.elective_option_id 
        AND se.student_id = r.student_id 
        AND se.semester_id = r.semester_id
    )
    WHERE r.student_id = ? 
    AND r.semester_id = ?
    AND sm.subject_name NOT LIKE '%Project%' 
    AND (sm.is_elective = 0 OR se.elective_option_id IS NOT NULL)
    ORDER BY sm.id ASC
";
$stmt = $conn->prepare($results_query);
$stmt->bind_param("ii", $student_id, $sem_id);
$stmt->execute();
$results_data = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Marksheet - Semester <?= $sem_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; padding: 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .result-card { background: #fff; border-radius: 0; padding: 50px; border: 1px solid #dee2e6; position: relative; }
        .table thead { background: #f1f5f9; color: #334155; border-bottom: 2px solid #cbd5e1; }
        .table-bordered td, .table-bordered th { border-color: #cbd5e1; }
        .scgpa-box { border-top: 2px solid #334155; padding-top: 20px; margin-top: 30px; }
        
        /* Class Performance Analysis Styles */
        .header-blue { background: linear-gradient(135deg, #1a237e 0%, #283593 100%); color: white !important; padding: 30px; border-radius: 15px 15px 0 0; }
        .performance-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .performance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            border-color: #dee2e6;
        }
        
        .performance-card.primary { background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-color: #2196f3; }
        .performance-card.success { background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-color: #4caf50; }
        .performance-card.info { background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%); border-color: #00bcd4; }
        
        .performance-value {
            font-size: 2rem;
            font-weight: 900;
            margin: 8px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .performance-card.primary .performance-value { background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .performance-card.success .performance-value { background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .performance-card.info .performance-value { background: linear-gradient(135deg, #00bcd4 0%, #00838f 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        
        .performance-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .top-performers-table tbody tr { border-bottom: 1px solid #f0f0f0; transition: all 0.2s ease; }
        .top-performers-table tbody tr:hover { background-color: #f8f9fa; }
        .top-performers-table .rank-badge { font-weight: 900; font-size: 1.1rem; min-width: 35px; display: inline-block; }
        .top-performers-table .student-name { font-weight: 600; color: #2c3e50; }
        .top-performers-table .gpa-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 5px 12px; border-radius: 20px; font-weight: bold; display: inline-block; }
        .progress-section { background: linear-gradient(90deg, #f5f5f5 0%, #fff 100%); padding: 20px; border-radius: 12px; margin-top: 30px; }
        .progress-section h5 { color: #2c3e50; font-weight: 700; margin-bottom: 20px; font-size: 1.1rem; }
        
        @media print { 
            .no-print { display: none; } 
            body { background: white; padding: 0; }
            .result-card { border: none; padding: 10px; }
        }

        /* Mobile Responsive */
        @media (max-width: 992px) {
            body { padding: 10px; }
            .result-card { padding: 30px 20px; }
            .header-blue { padding: 20px; }
            .performance-card { padding: 12px; }
            .performance-value { font-size: 1.5rem; }
        }

        @media (max-width: 768px) {
            body { padding: 5px; }
            .result-card { padding: 20px 15px; }
            .header-blue { padding: 15px; font-size: 0.9rem; }
            .performance-card { padding: 10px; margin-bottom: 10px; }
            .performance-value { font-size: 1.3rem; }
            .performance-label { font-size: 0.65rem; }
            .scgpa-box { padding-top: 15px; margin-top: 20px; }
            .table { font-size: 0.85rem; }
            .table thead th { font-size: 0.75rem; padding: 8px; }
            .table tbody td { padding: 8px; }
        }

        @media (max-width: 576px) {
            body { padding: 2px; }
            .result-card { padding: 15px 10px; border-radius: 8px; }
            .result-card h2 { font-size: 1.1rem; letter-spacing: 1px; }
            .result-card h4 { font-size: 0.9rem; padding: 8px 12px; }
            .result-card h5 { font-size: 0.9rem; }
            .header-blue { padding: 12px; font-size: 0.8rem; border-radius: 10px 10px 0 0; }
            .performance-card { padding: 8px; }
            .performance-value { font-size: 1.1rem; }
            .performance-label { font-size: 0.6rem; }
            .table { font-size: 0.7rem; }
            .table thead th { font-size: 0.65rem; padding: 5px; }
            .table tbody td { padding: 5px; }
            .btn { font-size: 0.8rem; padding: 6px 12px; }
        }
    </style>
</head>
<body>
<?php include 'student_header.php'; ?>
<div class="container shadow-sm p-0 mb-5 no-print" style="max-width: 900px;">
    <div class="bg-white p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-auto"><label class="fw-bold text-secondary">Switch Semester:</label></div>
            <div class="col-auto">
                <select name="sem_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>" <?= ($sem_id == $i) ? 'selected' : '' ?>>Semester <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col text-end">
                <button onclick="window.print()" class="btn btn-sm btn-dark px-4">Print Report</button>
            </div>
        </form>
    </div>
</div>

<div class="container result-card shadow-sm" style="max-width: 900px;">
    <?php if(!$is_published): ?>
        <div class="alert alert-light text-center py-5 border shadow-sm">
            <h4 class="text-secondary fw-bold">Result Not Published</h4>
            <p class="text-muted">The internal assessment results for Semester <?= $sem_id ?> are not available yet.</p>
        </div>
    <?php else: ?>
        <div class="text-center mb-5">
            <h2 class="fw-black m-0 text-uppercase" style="letter-spacing: 2px;">Pokhara Engineering College</h2>
            <h5 class="text-secondary uppercase mb-4"><?= htmlspecialchars($student['department_name']) ?></h5>
            <h4 class="bg-dark text-white d-inline-block px-4 py-1 rounded-1">INTERNAL ASSESSMENT REPORT</h4>
        </div>

        <div class="row mb-4">
            <div class="col-7">
                <table class="table table-sm table-borderless">
                    <tr><td width="150" class="text-muted uppercase small">Student Name:</td><td class="fw-bold h6 text-uppercase"><?= htmlspecialchars($student['full_name']) ?></td></tr>
                    <tr><td class="text-muted uppercase small">Symbol Number:</td><td class="fw-bold h6"><?= htmlspecialchars($student['symbol_no']) ?></td></tr>
                </table>
            </div>
            <div class="col-5 text-end">
                <table class="table table-sm table-borderless">
                    <tr><td class="text-muted uppercase small text-end">Exam Semester:</td><td class="fw-bold h6 text-end"><?= $sem_id ?></td></tr>
                    <tr><td class="text-muted uppercase small text-end">Academic Year:</td><td class="fw-bold h6 text-end">2081/2026</td></tr>
                </table>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
                <thead>
                    <tr>
                        <th rowspan="2">S.N</th>
                        <th rowspan="2" class="text-start">Subject Description</th>
                        <th rowspan="2">Cr. Hr</th>
                        <th colspan="2">Marks Obtained</th>
                        <th rowspan="2">Grade</th>
                        <th rowspan="2">GP</th>
                    </tr>
                    <tr class="small fw-bold bg-light">
                        <th>Theory</th>
                        <th>Practical</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_cr = 0; $total_pts = 0; $i=1; $has_fail = false;
                    while($res = $results_data->fetch_assoc()): 
                        // Exact DB GP and Credit for 3.12 consistency
                        $gp = (float)($res['grade_point'] ?? 0.0);
                        $cr = (float)($res['sub_credit'] ?? 3.0);
                        
                        $total_cr += $cr;
                        $total_pts += ($gp * $cr);
                        
                        if(strtoupper($res['letter_grade'] ?? '') == 'F') $has_fail = true;
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="text-start fw-semibold"><?= htmlspecialchars($res['subject_name']) ?></td>
                        <td><?= $cr ?></td>
                        <td><?= $res['final_theory'] ?? '-' ?></td>
                        <td><?= $res['practical_marks'] ?? '-' ?></td>
                        <td class="fw-bold"><?= $res['letter_grade'] ?? 'N/A' ?></td>
                        <td class="fw-bold"><?= number_format($gp, 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="scgpa-box mt-4">
            <div class="row align-items-center">
                <div class="col-8">
                    <p class="small text-muted mb-0">
                        * Note: Project assessment marks are not included in this report.<br>
                        
                    </p>
                </div>
                
                <div class="col-4 text-end">
                    <div class="border p-3 bg-light rounded shadow-sm">
                        <small class="d-block text-muted fw-bold uppercase" style="font-size: 0.7rem;">Semester SGPA</small>
                        <span class="h2 fw-black <?= $has_fail ? 'text-danger' : 'text-primary' ?>">
                            <?= ($total_cr > 0) ? ($has_fail ? 'FAIL' : number_format($total_pts/$total_cr, 2)) : '0.00' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 pt-5 d-flex justify-content-between text-center">
            <div class="border-top px-4 pt-2" style="border-color: #334155 !important; min-width: 150px;">Prepared By</div>
            <div class="border-top px-4 pt-2" style="border-color: #334155 !important; min-width: 150px;">HOD / Coordinator</div>
            <div class="border-top px-4 pt-2" style="border-color: #334155 !important; min-width: 150px;">Controller</div>
        </div>
    <?php endif; ?>
</div>

<?php 
    if($is_published) {
        // Preserve cohort identifiers before include (include overwrites $student)
        $cohort_dept_id = (int)($student['department_id'] ?? 0);
        $cohort_batch_year = (int)($student['batch_year'] ?? 0);

        // Pass semester to assessment_section and render the dashboard BEFORE analysis
        $_GET['sem_id_override'] = $sem_id;
        include 'assessment_section.php';
    }
?>
<!-- CLASS RANK ANALYSIS SECTION FOR ASSESSMENT -->
<?php if($is_published): ?>
<div class="no-print" style="background:#eef2ff; padding: 30px 0; margin-top: 0; margin-bottom: 0;">
    <div class="container">
        <div class="bg-white rounded-3 shadow-sm overflow-hidden">
            <div class="header-blue">
            <h3 class="fw-bold mb-0 text-white">📊 Assessment Class Performance Analysis</h3>
            </div>
            
            <div class="p-4">
            <?php 
            // Calculate GPA and Rank Analysis for Assessment
            $gpa_data = [];
            $all_students = $conn->query("
                SELECT s.id, s.full_name, s.symbol_no
                FROM students s
                 WHERE s.department_id = {$cohort_dept_id} 
                 AND s.batch_year = {$cohort_batch_year}
            ");

            if($all_students && $all_students->num_rows > 0) {
                while($std = $all_students->fetch_assoc()) {
                    $std_id = $std['id'];
                    $std_results = $conn->query("
                        SELECT r.letter_grade, sm.credit_hours
                        FROM results r
                        INNER JOIN subjects_master sm ON r.subject_id = sm.id 
                        WHERE r.student_id = $std_id 
                        AND r.semester_id = $sem_id 
                        AND sm.subject_type != 'Project'
                    ");
                    
                    $total_cr = 0;
                    $total_gp = 0;
                    if($std_results && $std_results->num_rows > 0) {
                        while($res = $std_results->fetch_assoc()) {
                            $cr = (float)$res['credit_hours'];
                            $gp = gradePoint($res['letter_grade']);
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

            // Sort by GPA descending
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
            ?>
            
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="performance-card primary">
                        <div class="performance-label">Your GPA</div>
                        <div class="performance-value"><?= number_format($student_gpa, 2) ?></div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="performance-card success">
                        <div class="performance-label">Class Rank</div>
                        <div class="performance-value"><?= $student_rank ?> / <?= count($gpa_data) ?></div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="performance-card info">
                        <div class="performance-label">Class Size</div>
                        <div class="performance-value"><?= count($gpa_data) ?></div>
                        <small style="color: #666;">Students</small>
                    </div>
                </div>
            </div>

            <!-- Top Performers Table -->
            <div class="progress-section">
                <h5>🏆 Top Performers in Your Class (Semester <?= $sem_id ?>)</h5>
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
</div>
<?php endif; ?>
<?php include 'footer.php'; ?>
</body>
</html>