<?php
// 1. Session and Database Initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Login Check
$student_id = $_SESSION['student_id'] ?? 0;
if (!$student_id) {
    // Redirect to login if session is not found
    header("Location: login.php");
    exit();
}

// 2. Fetch Student Info (Current Semester or Override)
$student_q = $conn->query("SELECT semester_id, department_id FROM students WHERE id = $student_id");
$student = $student_q->fetch_assoc();
if (!$student) { die("Student record not found."); }
// Use overridden semester if provided, otherwise use current semester
$current_sem = isset($_GET['sem_id_override']) ? intval($_GET['sem_id_override']) : $student['semester_id'];

// 3. FETCH RESULTS (Filtered by Semester & Excluding Projects)
// Properly handle both regular and elective subjects
$results_q = $conn->query("
    SELECT r.subject_code, sm.subject_name, sm.is_elective, r.letter_grade, r.grade_point, r.attendance_marks, r.total_attendance_days
    FROM results r
    JOIN subjects_master sm ON r.subject_id = sm.id
    WHERE r.student_id = $student_id AND r.semester_id = $current_sem
    AND sm.subject_name NOT REGEXP '^(Project I|Project II|Project)$'
    ORDER BY sm.id ASC
");

$results = [];
$total_credit = 0; $total_points = 0;
$subjects_names = []; $attended = []; $total_days = []; $grade_points = [];

while($r = $results_q->fetch_assoc()){
    $code = trim($r['subject_code']);
    $results[$code] = $r;
    
    // GPA calculation logic
    $total_credit += 3; 
    $total_points += ($r['grade_point'] * 3);
    
    // Arrays for Charts
    $subjects_names[] = $r['subject_name'];
    $grade_points[] = $r['grade_point'];
    $attended[] = (int)$r['attendance_marks'];
    $total_days[] = (int)$r['total_attendance_days'];
}
$gpa = $total_credit ? round($total_points / $total_credit, 2) : 0;

// 4. FETCH TARGETS (UT assessment targets)
$targets = [];
$tq = $conn->query("SELECT subject_code, target_grade FROM target_grades WHERE student_id=$student_id AND semester_id = $current_sem AND target_type='assessment'");
while($t = $tq->fetch_assoc()){ $targets[trim($t['subject_code'])] = $t['target_grade']; }

// 5. FETCH BOARD TARGETS
$board_targets = [];
$btq = $conn->query("SELECT subject_code, target_grade FROM target_grades WHERE student_id=$student_id AND semester_id = $current_sem AND target_type='board'");
while($bt = $btq->fetch_assoc()){ $board_targets[trim($bt['subject_code'])] = $bt['target_grade']; }

// Grade Compare Helper Function
function compareGrades($actual, $target){
    $g = ['A'=>10,'A-'=>9,'B+'=>8,'B'=>7,'B-'=>6,'C+'=>5,'C'=>4,'C-'=>3,'D+'=>2,'D'=>1,'F'=>0];
    if(!isset($g[$actual]) || !isset($g[$target])) return "<span class='text-muted small'>N/A</span>";
    if($g[$actual] > $g[$target]) return "<span class='badge bg-success'>🌟 Excellent</span>";
    if($g[$actual] == $g[$target]) return "<span class='badge bg-primary'>✅ On Track</span>";
    return "<span class='badge bg-warning text-dark'>💪 Improve</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Assessment Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body{ background:#f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .compact-card { 
            border: none; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
            background: #fff;
            transition: all 0.3s ease;
        }
        .compact-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
            border-color: #4338ca;
        }
        .bg-soft-primary { background-color: #eef2ff; color: #4338ca; }
        
        /* Table Row Hover */
        .table-hover tbody tr {
            transition: all 0.2s ease;
        }
        .table-hover tbody tr:hover {
            background-color: #f0f4ff !important;
            transform: translateX(4px);
        }
        
        /* Badge Hover */
        .badge {
            transition: all 0.2s ease;
        }
        .badge:hover {
            transform: scale(1.05);
            filter: brightness(0.9);
        }
        
        /* Target Card Hover */
        .target-item {
            transition: all 0.2s ease;
            padding: 12px;
            border-radius: 8px;
        }
        .target-item:hover {
            background-color: #f0f4ff;
            transform: translateX(4px);
        }
    </style>
</head>
<body>

<div class="container py-4">
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <strong>Success!</strong> Targets updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-dark mb-0"><i class="fas fa-chart-pie text-primary me-2"></i> Assessment Dashboard</h4>
        <div class="badge bg-primary px-3 py-2 rounded-pill">Semester <?= $current_sem ?></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card compact-card p-4 h-100">
                <h6 class="fw-bold text-muted mb-3">Academic Performance (GPA: <?= $gpa ?>)</h6>
                <canvas id="subjectGraph" height="180"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card compact-card p-4 h-100">
                <h6 class="fw-bold text-muted mb-3">Attendance Distribution</h6>
                <canvas id="attendanceGraph" height="180"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card compact-card p-4 h-100">
                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-history me-2"></i>Result vs UT Target</h6>
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light small text-uppercase">
                            <tr><th>Subject</th><th class="text-center">Actual</th><th class="text-center">Target</th><th class="text-center">Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($results as $code => $r): ?>
                            <tr>
                                <td class="small fw-bold text-dark"><?= $r['subject_name'] ?></td>
                                <td class="text-center"><span class="badge bg-soft-primary"><?= $r['letter_grade'] ?></span></td>
                                <td class="text-center text-muted small"><?= $targets[$code] ?? 'N/A' ?></td>
                                <td class="text-center"><?= compareGrades($r['letter_grade'], $targets[$code] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <?php $has_targets = (count($board_targets) > 0); ?>
            <div class="card compact-card p-4 h-100 border-top border-5 <?= $has_targets ? 'border-primary' : 'border-success' ?>">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold <?= $has_targets ? 'text-primary' : 'text-success' ?> mb-0">
                        <i class="fas <?= $has_targets ? 'fa-lock' : 'fa-bullseye' ?> me-2"></i>
                        <?= $has_targets ? 'Targets Locked' : 'Board Exam Goals' ?>
                    </h6>
                    <?php if($has_targets): ?>
                        <button type="button" id="editBtn" class="btn btn-sm btn-outline-primary border-0"><i class="fas fa-edit"></i> Edit</button>
                    <?php endif; ?>
                </div>

                <form action="save_board_targets.php" method="POST" id="targetForm">
                    <input type="hidden" name="semester_id" value="<?= $current_sem ?>">
                    <table class="table table-sm align-middle mb-3">
                        <?php foreach($results as $code => $r): ?>
                        <tr>
                            <td class="small py-2"><?= htmlspecialchars($r['subject_name']) ?></td>
                            <td width="100">
                                <select name="targets[<?= $code ?>]" class="form-select form-select-sm target-select" <?= $has_targets ? 'disabled' : '' ?> required>
                                    <option value="">Grade</option>
                                    <?php foreach(['A','A-','B+','B','B-','C+','C','C-','D+','D','F'] as $g): ?>
                                        <option value="<?= $g ?>" <?= ($board_targets[$code] ?? '') == $g ? 'selected' : '' ?>><?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>

                    <div id="buttonContainer">
                        <?php if($has_targets): ?>
                            <div class="text-center p-2 rounded-3 bg-light text-muted small fw-bold">
                                <i class="fas fa-check-circle text-primary me-1"></i> Targets are locked.
                            </div>
                        <?php else: ?>
                            <button type="submit" class="btn btn-success btn-sm w-100 py-2 fw-bold">LOCK BOARD TARGETS</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php 
    $low_subjects = array_filter($results, function($r) { return $r['grade_point'] < 2.7; });
    if(!empty($low_subjects)): ?>
    <div class="card compact-card border-0 shadow-sm mt-4 overflow-hidden">
        <div class="card-header bg-danger text-white py-3">
            <h6 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Attention Needed: Low Performance</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <?php foreach($low_subjects as $code => $sub): 
                    $sub_q = $conn->query("SELECT id FROM subjects_master WHERE subject_code='$code' LIMIT 1");
                    $s_id = ($sub_q->fetch_assoc())['id'] ?? 0;
                    $notes = $conn->query("SELECT title, file_path FROM notes WHERE subject_id = $s_id LIMIT 2");
                ?>
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-dark"><?= $sub['subject_name'] ?></span>
                            <span class="badge bg-danger"><?= $sub['letter_grade'] ?></span>
                        </div>
                        <div class="small text-muted mb-2 border-top pt-2">Resources for Improvement:</div>
                        <?php if($notes->num_rows > 0): ?>
                            <?php while($n = $notes->fetch_assoc()): ?>
                                <a href="<?= $n['file_path'] ?>" class="d-block small text-decoration-none text-primary mb-1"><i class="fas fa-file-pdf text-danger me-1"></i> <?= $n['title'] ?></a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="small text-muted italic">No specific notes available.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert bg-white shadow-sm rounded-4 mt-4 d-flex align-items-center p-4">
            <div class="h1 mb-0 me-3 text-warning">🏆</div>
            <div>
                <h6 class="fw-bold mb-0">Excellent Work!</h6>
                <p class="small text-muted mb-0">All your grades are above B-. Keep maintaining this standard for board exams.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Graph Data
const labels = <?= json_encode($subjects_names) ?>;
const gpaData = <?= json_encode($grade_points) ?>;
const attData = <?= json_encode($attended) ?>;
const totalData = <?= json_encode($total_days) ?>;

// Performance Chart
new Chart(document.getElementById('subjectGraph'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{ label: 'Grade Point', data: gpaData, backgroundColor: 'rgba(59, 130, 246, 0.8)', borderRadius: 5 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { max: 4, beginAtZero: true } } }
});

// Attendance Chart
new Chart(document.getElementById('attendanceGraph'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            { label: 'Attended', data: attData, backgroundColor: '#10b981' },
            { label: 'Missed', data: totalData.map((t,i)=>t-attData[i]), backgroundColor: '#f43f5e' }
        ]
    },
    options: { scales: { x: { stacked: true }, y: { stacked: true } } }
});

// Edit Button Interaction
document.getElementById('editBtn')?.addEventListener('click', function() {
    const selects = document.querySelectorAll('.target-select');
    const container = document.getElementById('buttonContainer');
    const isEditing = this.innerHTML.includes('Cancel');

    if (!isEditing) {
        selects.forEach(s => s.disabled = false);
        this.innerHTML = '<i class="fas fa-times"></i> Cancel';
        this.classList.replace('btn-outline-primary', 'btn-outline-danger');
        container.innerHTML = `<button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-bold">UPDATE TARGETS</button>`;
    } else {
        location.reload();
    }
});
</script>
</body>
</html>