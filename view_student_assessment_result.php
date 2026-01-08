<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id'])){ 
    header("Location: login.php");
    exit(); 
}

$student_id = $_SESSION['student_id'];

// 1. Student Details Fetch garne (Current Sem ra Department thaha pauna)
$student_q = $conn->prepare("
    SELECT s.id, s.full_name, s.symbol_no, s.department_id, s.semester_id as student_actual_sem, 
           d.department_name, sem.semester_name
    FROM students s
    JOIN departments d ON s.department_id=d.id
    JOIN semesters sem ON s.semester_id=sem.id
    WHERE s.id = ?
");
$student_q->bind_param("i", $student_id);
$student_q->execute();
$student = $student_q->get_result()->fetch_assoc();

if(!$student){ die("Student not found"); }

/**
 * 2. SEMESTER LOGIC:
 * URL ma 'sem_id' chha vane tyo herne, navaye Student ko Current Sem default line.
 * Dropdown ma Semester 1 dekhi 8 samma sadhai dekhaune.
 */
$sem_id = intval($_GET['sem_id'] ?? $student['student_actual_sem']);

// 3. Result Publication Status Check
$publish_check = $conn->prepare("
    SELECT published FROM results_publish_status
    WHERE department_id=? AND semester_id=? AND result_type='assessment' AND published=1
");
$publish_check->bind_param("ii", $student['department_id'], $sem_id);
$publish_check->execute();
$is_published = $publish_check->get_result()->num_rows;

/** * 4. MAIN RESULT QUERY:
 * - Specific Semester ko result matra tanne.
 * - 'Project' vanni subject lai filter garne (dekhidaina).
 * - Elective subject ho vane, student le select gareko elective ho ki haina check garne.
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

// Grade Point Mapping
function gradePoint($grade){
    $grade = strtoupper(trim($grade ?? ''));
    $points = ['A'=>4.0, 'A-'=>3.7, 'B+'=>3.3, 'B'=>3.0, 'B-'=>2.7, 'C+'=>2.3, 'C'=>2.0, 'C-'=>1.7, 'D+'=>1.3, 'D'=>1.0, 'F'=>0.0];
    return $points[$grade] ?? 0.0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Result - Semester <?= $sem_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; padding: 20px; font-family: 'Inter', sans-serif; }
        .filter-card { background: #fff; border-radius: 12px; padding: 15px; margin-bottom: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .result-card { background: #fff; border-radius: 16px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .table thead { background: #1e293b; color: white; }
        .scgpa-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px; margin-top: 20px; }
        .official-stamp { margin-top: 50px; text-align: right; font-style: italic; color: #64748b; font-size: 0.9rem; }
        @media print { 
            .no-print { display: none; } 
            body { background: white; padding: 0; }
            .result-card { box-shadow: none; border: none; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="container" style="max-width: 1000px;">
    
    <div class="filter-card no-print">
        <form method="GET" action="" class="row align-items-center">
            <div class="col-md-5">
                <label class="fw-bold text-secondary mb-1">Select Semester to View:</label>
                <select name="sem_id" class="form-select border-primary fw-bold" onchange="this.form.submit()">
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>" <?= ($sem_id == $i) ? 'selected' : '' ?>>
                            Semester <?= $i ?> <?= ($i == $student['student_actual_sem']) ? '(Current)' : '' ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-7 text-md-end pt-3">
                <span class="badge bg-light text-dark border">Batch: 2022</span>
                <span class="badge bg-primary ms-2">Viewing: Semester <?= $sem_id ?></span>
            </div>
        </form>
    </div>

    <div class="result-card">
        <?php if(!$is_published): ?>
            <div class="alert alert-warning py-5 text-center border-0 shadow-sm">
                <h4 class="fw-bold">Not Published!</h4>
                <p class="mb-0 text-muted">Assessment results for **Semester <?= $sem_id ?>** have not been published by the department yet.</p>
                <a href="?sem_id=<?= $student['student_actual_sem'] ?>" class="btn btn-sm btn-outline-warning mt-3">Back to Current Semester</a>
            </div>
        <?php else: ?>
            <div class="text-center mb-5">
                <h3 class="fw-bold mb-1 text-dark text-uppercase">Internal Assessment Report</h3>
                <p class="text-muted">Tribhuvan University | Institute of Engineering</p>
                <hr class="mx-auto" style="width: 60px; height: 4px; background: #0d6efd; border:none; border-radius:10px;">
                
                <div class="mt-4 row text-start">
                    <div class="col-6 border-end">
                        <small class="text-muted d-block text-uppercase small fw-bold">Student Name</small>
                        <span class="fw-bold h5 text-dark"><?= htmlspecialchars($student['full_name']) ?></span>
                    </div>
                    <div class="col-6 ps-4">
                        <small class="text-muted d-block text-uppercase small fw-bold">Symbol Number</small>
                        <span class="fw-bold h5 text-dark"><?= htmlspecialchars($student['symbol_no']) ?></span>
                    </div>
                </div>
                <div class="mt-3 row text-start">
                    <div class="col-6 border-end">
                        <small class="text-muted d-block text-uppercase small fw-bold">Department</small>
                        <span class="fw-semibold"><?= htmlspecialchars($student['department_name']) ?></span>
                    </div>
                    <div class="col-6 ps-4">
                        <small class="text-muted d-block text-uppercase small fw-bold">Exam Semester</small>
                        <span class="fw-semibold"><?= $sem_id ?></span>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead>
                        <tr>
                            <th style="width: 50px;">S.N</th>
                            <th class="text-start">Subject Description</th>
                            <th>Credit</th>
                            <th>Theory (30)</th>
                            <th>Practical (20)</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_cr = 0; $total_pts = 0; $i=1; $has_fail = false;
                        if($results_data->num_rows == 0):
                            echo "<tr><td colspan='6' class='py-5 text-muted italic'>No result records found for this semester.</td></tr>";
                        else:
                            while($res = $results_data->fetch_assoc()): 
                                $gp = gradePoint($res['letter_grade']);
                                $total_cr += (float)$res['sub_credit'];
                                $total_pts += ($gp * (float)$res['sub_credit']);
                                if(strtoupper($res['letter_grade'] ?? '') == 'F') $has_fail = true;
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td class="text-start">
                                <span class="fw-bold text-dark"><?= htmlspecialchars($res['subject_name']) ?></span>
                                <?php if($res['is_elective']): ?>
                                    <span class="badge bg-light text-warning border border-warning ms-1" style="font-size: 0.6rem;">ELECTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $res['sub_credit'] ?></td>
                            <td><?= $res['final_theory'] ?? '-' ?></td>
                            <td><?= $res['practical_marks'] ?? '-' ?></td>
                            <td class="fw-bold"><?= $res['letter_grade'] ?? 'N/A' ?></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="scgpa-box">
                <div class="row align-items-center">
                    <div class="col-6">
                        <div class="text-muted small">TOTAL CREDIT HOURS</div>
                        <div class="h4 fw-bold mb-0 text-dark"><?= $total_cr ?></div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="text-muted small">SEMESTER SCGPA</div>
                        <div class="h3 fw-bold mb-0 <?= $has_fail ? 'text-danger' : 'text-primary' ?>">
                            <?= ($total_cr > 0) ? ($has_fail ? 'FAIL' : number_format($total_pts/$total_cr, 2)) : '0.00' ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="official-stamp no-print">
                <p>Note: This is an online generated report for internal assessment.</p>
                <button onclick="window.print()" class="btn btn-dark mt-2">
                    <i class="fas fa-print me-2"></i> Print Marksheet
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>