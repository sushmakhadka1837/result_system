<?php
// PHP logic remains exactly as you provided
$student_id = $_SESSION['student_id'];

$student_q = $conn->query("
    SELECT s.id, s.full_name, s.department_id, s.current_semester, d.department_name 
    FROM students s
    JOIN departments d ON s.department_id = d.id
    WHERE s.id = $student_id
");
$student = $student_q->fetch_assoc();

$selected_sem = isset($_GET['sem_id']) ? intval($_GET['sem_id']) : $student['current_semester'];

// Filter Updated: Project I, Project II ra Project matra hataiyeko xa (REGEXP use garera)
$results_q = $conn->query("
    SELECT r.*, sm.subject_name, sm.credit_hours, sm.subject_code as sm_code
    FROM results r
    INNER JOIN subjects_master sm ON r.subject_id = sm.id
    WHERE r.student_id = $student_id 
    AND r.semester_id = $selected_sem
    AND sm.subject_name NOT REGEXP '^(Project I|Project II|Project)$'
    ORDER BY sm.id ASC
");

if (!function_exists('gradePoint')) {
    function gradePoint($grade){
        $points = ['A'=>4.0, 'A-'=>3.7, 'B+'=>3.3, 'B'=>3.0, 'B-'=>2.7, 'C+'=>2.3, 'C'=>2.0, 'C-'=>1.7, 'D+'=>1.3, 'D'=>1.0, 'F'=>0.0];
        return $points[strtoupper(trim($grade ?? ''))] ?? 0.0;
    }
}

$saved_targets = [];
$target_q = $conn->query("
    SELECT subject_code, target_grade 
    FROM target_grades 
    WHERE student_id = $student_id AND semester_id = $selected_sem
");

$has_existing_target = false;
if($target_q && $target_q->num_rows > 0) {
    $has_existing_target = true;
    while ($row = $target_q->fetch_assoc()) {
        $saved_targets[$row['subject_code']] = $row['target_grade'];
    }
}
?>

<style>
    /* 1. Main Section Hover */
    .analytics-card { 
        background: #ffffff; 
        border-radius: 20px; 
        border: 1px solid #eef2f6; 
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        height: 100%; 
    }
    .analytics-card:hover { 
        transform: translateY(-10px); 
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        border-color: #dbeafe;
    }

    /* 2. Motivation Banner Hover */
    .motivation-banner { 
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); 
        color: white; border-radius: 15px; padding: 15px; 
        transition: 0.3s;
    }
    .motivation-banner:hover { filter: hue-rotate(15deg); transform: scale(1.02); }

    /* 3. Subject Row Hover */
    .subject-row { 
        padding: 12px; border-radius: 12px; margin-bottom: 10px; 
        background: #f8fafc; border: 1px solid #f1f5f9; 
        transition: 0.2s ease-in-out;
    }
    .subject-row:hover { 
        background: #ffffff; 
        border-color: #6366f1; 
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1); 
        transform: scale(1.01);
    }

    /* 4. Focus Item & Note Hover */
    .focus-item { 
        background: #fff5f5; border-radius: 15px; padding: 15px; 
        margin-bottom: 15px; border-left: 5px solid #ef4444; 
        transition: 0.3s;
    }
    .focus-item:hover { background: #fffcfc; box-shadow: 0 5px 15px rgba(239, 68, 68, 0.08); }

    .note-badge { 
        background: #ffffff; border: 1px solid #fee2e2; border-radius: 10px; 
        padding: 10px; display: flex; align-items: center; 
        text-decoration: none !important; margin-top: 8px; 
        transition: all 0.3s ease; 
    }
    .note-badge:hover { 
        background: #ef4444; color: white !important; 
        transform: translateX(8px); border-color: transparent;
    }
    .note-badge:hover span, .note-badge:hover i { color: white !important; }

    .custom-scroll { max-height: 380px; overflow-y: auto; padding-right: 8px; scrollbar-width: thin; }
</style>

<div class="container-fluid px-4 mt-2 no-print">
    <div class="row g-4 align-items-stretch">
        
        <div class="col-xl-4 col-lg-6">
            <div class="analytics-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold text-dark m-0"><i class="fas fa-chart-line text-primary me-2"></i> Result Trends</h6>
                    <span class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill small">Semester <?= $selected_sem ?></span>
                </div>
                <div style="height: 300px;">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6">
            <div class="analytics-card p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-bullseye text-success me-2"></i> Academic Goals</h6>
                <div class="motivation-banner mb-3 small fw-bold text-center shadow-sm">
                    <span id="quote-text">"Small steps everyday lead to big results."</span>
                </div>

                <form id="targetFormAction">
                    <input type="hidden" name="semester_id" value="<?= $selected_sem ?>">
                    <div class="custom-scroll">
                        <?php 
                        $results_q->data_seek(0);
                        if($results_q->num_rows > 0):
                            while($res = $results_q->fetch_assoc()): 
                                $code = $res['sm_code'];
                                $sel_grade = $saved_targets[$code] ?? '';
                        ?>
                        <div class="d-flex align-items-center justify-content-between subject-row">
                            <div class="flex-grow-1 me-2 text-truncate">
                                <span class="d-block fw-bold text-dark small text-truncate"><?= htmlspecialchars($res['subject_name']) ?></span>
                                <span class="text-muted" style="font-size: 11px;">Status: <span class="text-primary fw-bold"><?= $res['ut_grade'] ?: 'N/A' ?></span></span>
                            </div>
                            <select name="target_grade[<?= $code ?>]" class="form-select form-select-sm w-auto border-0 shadow-sm rounded-pill" style="font-size: 11px;" onchange="updateMotivation(this.value)">
                                <option value="">Target</option>
                                <?php foreach(['A','A-','B+','B','B-','C+','C','D','F'] as $g): ?>
                                    <option value="<?= $g ?>" <?= $sel_grade == $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3 py-2 fw-bold shadow" style="border-radius: 12px; background: #6366f1; border: none;">
                        <i class="fas <?= $has_existing_target ? 'fa-sync-alt' : 'fa-lock' ?> me-2"></i> 
                        <?= $has_existing_target ? 'SYNC GOALS' : 'LOCK MY GOALS' ?>
                    </button>
                    <?php endif; ?>
                </form>
                <div id="targetResponse" class="mt-2 text-center small fw-bold"></div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-12">
            <div class="analytics-card p-4 border-danger border-opacity-10">
                <h6 class="fw-bold text-danger mb-4"><i class="fas fa-fire me-2"></i> Critical Focus Areas</h6>
                <div class="custom-scroll">
                    <?php 
                    $results_q->data_seek(0); $low_count = 0;
                    while($res = $results_q->fetch_assoc()):
                        if(gradePoint($res['ut_grade']) < 2.7): 
                            $low_count++; $sub_id = $res['subject_id'];
                    ?>
                    <div class="focus-item shadow-sm">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="fw-bold text-dark small" style="max-width: 70%; line-height: 1.2;"><?= htmlspecialchars($res['subject_name']) ?></span>
                            <span class="badge rounded-pill bg-danger shadow-sm">Grade: <?= $res['ut_grade'] ?></span>
                        </div>
                        <p class="text-muted mb-2" style="font-size: 0.7rem;">Recommended materials to improve:</p>
                        
                        <?php 
                        $notes_q = $conn->query("SELECT * FROM notes WHERE subject_id = $sub_id LIMIT 3");
                        if($notes_q && $notes_q->num_rows > 0):
                            while($note = $notes_q->fetch_assoc()): ?>
                            <a href="<?= $note['file_path'] ?>" target="_blank" class="note-badge shadow-sm">
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                <span class="text-dark small fw-bold"><?= htmlspecialchars($note['title']) ?></span>
                            </a>
                        <?php endwhile; else: ?>
                            <div class="text-center p-2 rounded border border-dashed"><small class="text-muted small">Check back later for notes.</small></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; endwhile; ?>
                    
                    <?php if($low_count == 0): ?>
                        <div class="text-center py-5">
                            <div class="mb-3 text-success"><i class="fas fa-award fa-4x"></i></div>
                            <h6 class="fw-bold">Looking Sharp!</h6>
                            <p class="small text-muted">You're maintaining a safe grade across all subjects.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Motivational quotes logic
    function updateMotivation(grade) {
        const quotes = { 'A': "Incredible! Keep that fire burning. 🔥", 'B': "Solid effort! Push for that A. 🚀", 'C': "Focus up! You can do better. 🎯", 'default': "Believe in your hustle. 💎" };
        document.getElementById('quote-text').innerText = quotes[grade] || quotes['default'];
    }

    // Chart logic
    <?php
    $results_q->data_seek(0);
    $labels = []; $data = [];
    while($row = $results_q->fetch_assoc()){
        $labels[] = (strlen($row['subject_name']) > 15) ? substr($row['subject_name'], 0, 12) . '..' : $row['subject_name']; 
        $data[] = gradePoint($row['ut_grade']);
    }
    ?>

    const ctx = document.getElementById('performanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'GPA',
                data: <?= json_encode($data) ?>,
                backgroundColor: 'rgba(99, 102, 241, 0.7)',
                hoverBackgroundColor: '#6366f1',
                borderRadius: 8,
                barThickness: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 9, weight: '600' } } },
                y: { beginAtZero: true, max: 4, ticks: { stepSize: 1, font: { size: 10 } } }
            },
            plugins: { legend: { display: false } }
        }
    });

    document.getElementById('targetFormAction').onsubmit = function(e) {
        e.preventDefault();
        const msgDiv = document.getElementById('targetResponse');
        msgDiv.innerHTML = '<span class="spinner-border spinner-border-sm text-primary me-2"></span><span class="text-muted small">Saving...</span>';
        fetch('save_target_grade.php', { method: 'POST', body: new FormData(this) })
        .then(res => res.text())
        .then(data => {
            msgDiv.innerHTML = `<span class="text-success small"><i class="fas fa-check-circle me-1"></i> Success! Targets locked.</span>`;
            setTimeout(() => { msgDiv.innerHTML = ''; }, 3000);
        });
    };
</script>