<?php


$student_id = $_SESSION['student_id'] ?? 0;
if(!$student_id){
    die("Student not logged in.");
}

/* ================= FETCH RESULTS ================= */
$results_q = $conn->query("
    SELECT 
        r.subject_code,
        sm.subject_name,
        r.letter_grade,
        r.grade_point,
        r.attendance_marks,
        r.total_attendance_days
    FROM results r
    JOIN subjects_master sm 
        ON TRIM(r.subject_code) = TRIM(sm.subject_code)
    WHERE r.student_id = $student_id
");

$results = [];
while($r = $results_q->fetch_assoc()){
    $results[$r['subject_code']] = $r;
}

/* ================= FETCH TARGETS ================= */
$targets = [];
$tq = $conn->query("
    SELECT subject_code, target_grade 
    FROM target_grades 
    WHERE student_id=$student_id 
    AND target_type='assessment'
");
while($t = $tq->fetch_assoc()){
    $targets[$t['subject_code']] = $t['target_grade'];
}

/* ================= GPA ================= */
$total_credit = 0;
$total_points = 0;
foreach($results as $r){
    $total_credit += 3;
    $total_points += ($r['grade_point'] * 3);
}
$gpa = $total_credit ? round($total_points / $total_credit, 2) : 0;

/* ================= ATTENDANCE ARRAYS ================= */
$subjects = $attended = $total = [];
foreach($results as $r){
    $subjects[] = $r['subject_name'];
    $attended[] = (int)$r['attendance_marks'];
    $total[] = (int)$r['total_attendance_days'];
}

/* ================= GRADE COMPARE ================= */
function compareGrades($o,$t){
    $g=['A'=>10,'A-'=>9,'B+'=>8,'B'=>7,'B-'=>6,'C+'=>5,'C'=>4,'C-'=>3,'D+'=>2,'D'=>1,'F'=>0];
    if(!isset($g[$o])||!isset($g[$t])) return "<span class='text-muted'>N/A</span>";
    if($g[$o]>$g[$t]) return "<span class='text-success'>🌟 Excellent</span>";
    if($g[$o]==$g[$t]) return "<span class='text-primary'>✅ On Track</span>";
    return "<span class='text-warning'>💪 Improve</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assessment Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{ background:#f5f7fa; }
.page-wrapper{ padding:30px 0; }
.dashboard-title{ font-weight:600; }
.compact-card{
    border-radius:16px;
    font-size:14px;
    transition:.3s;
}
.compact-card:hover{
    transform:translateY(-4px);
    box-shadow:0 12px 25px rgba(0,0,0,.15);
}
</style>
</head>

<body>

<!-- ================= MAIN WRAPPER ================= -->
<div class="container-fluid page-wrapper">

    <div class="container">

        <!-- TITLE -->
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="dashboard-title">📊 Assessment Dashboard</h4>
            </div>
        </div>

        <!-- ================= GRAPHS ROW ================= -->
        <div class="row g-4 mb-4">

            <!-- GPA GRAPH -->
            <div class="col-lg-6 col-md-12">
                <div class="card compact-card p-3 h-100">
                    <h6 class="text-primary mb-2">
                        📈 Assessment Performance (GPA: <?= $gpa ?>)
                    </h6>
                    <canvas id="subjectGraph" height="170"></canvas>
                </div>
            </div>

            <!-- ATTENDANCE GRAPH -->
            <div class="col-lg-6 col-md-12">
                <div class="card compact-card p-3 h-100">
                    <h6 class="text-success mb-2">
                        📅 Attendance Overview
                    </h6>
                    <canvas id="attendanceGraph" height="170"></canvas>
                </div>
            </div>

        </div>

        <!-- ================= TABLE ROW ================= -->
        <div class="row g-4">

            <!-- LEFT TABLE -->
            <div class="col-lg-6 col-md-12">
                <div class="card compact-card p-3 h-100">
                    <h6 class="text-primary mb-2">📘 Assessment vs Target</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered text-center mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Subject</th>
                                    <th>Grade</th>
                                    <th>Target</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($results as $c=>$r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['subject_name']) ?></td>
                                    <td><?= $r['letter_grade'] ?></td>
                                    <td><?= $targets[$c] ?? 'N/A' ?></td>
                                    <td><?= compareGrades($r['letter_grade'],$targets[$c]??'') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RIGHT FORM -->
            <div class="col-lg-6 col-md-12">
                <div class="card compact-card p-3 h-100">
                    <h6 class="text-success mb-2">📗 Board Target Planning</h6>

                    <form method="post" action="save_board_targets.php">
                        <div class="table-responsive">
                        <table class="table table-sm table-bordered text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Subject</th>
                                    <th>Assessment</th>
                                    <th>Board Target</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($results as $c=>$r): ?>
                                <tr>
                                    <td><?= $r['subject_name'] ?></td>
                                    <td><?= $targets[$c] ?? '-' ?></td>
                                    <td>
                                        <select name="targets[<?= $c ?>]" class="form-select form-select-sm">
                                            <option value="">Select</option>
                                            <?php foreach(['A','A-','B+','B','B-','C+','C','C-','D+','D','F'] as $g)
                                                echo "<option>$g</option>"; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <button class="btn btn-success btn-sm w-100">Save Board Targets</button>
                    </form>
                </div>
            </div>

        </div>

    </div>
    <!-- LOW MARKS FEEDBACK SECTION -->
<?php
$low_subjects=[];
foreach($results as $res){
    if($res['grade_point']<2.7){
        $low_subjects[]=['name'=>$res['subject_name'],'code'=>$res['subject_code']];
    }
}
?>
<?php if(!empty($low_subjects)): ?>
<div class="container mt-4">
    <div class="card compact-card p-4 low-marks-card">
        <h5 class="fw-bold text-danger mb-2">⚠ Performance Alert (Assessment)</h5>
        <p class="mb-3">Tapai ko kehi subject ma marks kam aayeko cha. Mehenat garnu parne awastha cha 📉</p>

        <?php foreach($low_subjects as $sub):
            $sub_q=$conn->query("SELECT id FROM subjects_master WHERE subject_code='". $conn->real_escape_string($sub['code']) ."' LIMIT 1");
            $sub_id=$sub_q->fetch_assoc()['id'] ?? 0;
            $notes_q=$conn->query("
                SELECT title,file_path,uploader_role
                FROM notes
                WHERE department_id={$student['department_id']}
                  AND semester_id={$student['semester_id']}
                  AND subject_id=$sub_id
                ORDER BY uploader_role DESC,created_at ASC
            ");
        ?>
        <div class="border rounded-3 p-3 mb-3 bg-light">
            <h6 class="fw-bold text-primary">📘 <?= htmlspecialchars($sub['name']) ?></h6>
            <?php if($notes_q->num_rows>0): ?>
            <ul class="list-group list-group-flush">
                <?php while($n=$notes_q->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <a href="<?= $n['file_path'] ?>" target="_blank"><?= htmlspecialchars($n['title']) ?></a>
                    <span class="badge bg-secondary"><?= ucfirst($n['uploader_role']) ?></span>
                </li>
                <?php endwhile; ?>
            </ul>
            <?php else: ?>
                <small class="text-muted">Notes upload bhayeko chaina.</small>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <small class="text-muted">🤖 AI Suggestion: Revise regularly and practice to improve your grades.</small>
    </div>
</div>
<?php endif; ?>
</div>

?>


<script>
/* GPA GRAPH */
new Chart(document.getElementById('subjectGraph'),{
    type:'bar',
    data:{
        labels: <?= json_encode($subjects) ?>,
        datasets:[{
            data: <?= json_encode(array_column($results,'grade_point')) ?>,
            backgroundColor:'#1a73e8',
            borderRadius:10,
            barThickness:32
        }]
    },
    options:{
        plugins:{ legend:{display:false} },
        scales:{ y:{ beginAtZero:true, max:4 } }
    }
});

/* ATTENDANCE GRAPH */
const attended = <?= json_encode($attended) ?>;
const total = <?= json_encode($total) ?>;
const missed = total.map((t,i)=>t-attended[i]);

new Chart(document.getElementById('attendanceGraph'),{
    type:'bar',
    data:{
        labels: <?= json_encode($subjects) ?>,
        datasets:[
            { label:'Attended', data:attended, backgroundColor:'#28a745' },
            { label:'Missed', data:missed, backgroundColor:'#dc3545' }
        ]
    },
    options:{
        scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } }
    }
});
</script>

</body>
</html>
