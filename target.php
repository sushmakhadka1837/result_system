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

$achievement_plan = [];
$results_q->data_seek(0);
while($row = $results_q->fetch_assoc()) {
    $code = $row['sm_code'];
    $target_grade = trim((string)($saved_targets[$code] ?? ''));
    if ($target_grade === '') {
        continue;
    }

    $current_grade = trim((string)($row['ut_grade'] ?? ''));
    $current_point = gradePoint($current_grade);
    $target_point = gradePoint($target_grade);
    $gap = $target_point - $current_point;

    if ($gap <= 0) {
        continue;
    }

    if ($gap >= 1.0) {
        $action = 'Daily 60 mins + 25 numericals/week + 1 past question set';
    } elseif ($gap >= 0.6) {
        $action = 'Daily 45 mins + 15 numericals/week + weekly revision test';
    } else {
        $action = 'Daily 30 mins + 10 focused questions/week + concept recap';
    }

    $achievement_plan[] = [
        'subject_name' => $row['subject_name'],
        'current_grade' => $current_grade === '' ? 'N/A' : $current_grade,
        'target_grade' => $target_grade,
        'gap' => $gap,
        'action' => $action
    ];
}

usort($achievement_plan, function($a, $b) {
    return $b['gap'] <=> $a['gap'];
});
$achievement_plan = array_slice($achievement_plan, 0, 5);
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
        transform: translateY(-6px); 
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

    .target-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .target-subtitle {
        font-size: 11px;
        color: #64748b;
        margin-bottom: 10px;
    }

    .status-chip {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        background: #eef2ff;
        color: #4338ca;
        border: 1px solid #e0e7ff;
    }

    .target-select {
        min-width: 88px;
    }

    .plan-item {
        background: #f8fafc;
        border: 1px solid #eef2f6;
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 10px;
    }

    .plan-meta {
        font-size: 10px;
        color: #64748b;
    }

    .followup-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        margin-bottom: 8px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 7px 8px;
        background: #ffffff;
        cursor: pointer;
    }

    .followup-item input[type="checkbox"] {
        accent-color: #4f46e5;
    }

    .followup-progress {
        font-size: 11px;
        color: #475569;
    }

    .followup-bar {
        width: 100%;
        height: 6px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
        margin-top: 6px;
    }

    .followup-bar-fill {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
        transition: width 0.25s ease;
    }

    .reminder-box {
        margin-top: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px;
    }

    .reminder-controls {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .reminder-status {
        font-size: 11px;
        color: #475569;
        margin-top: 8px;
    }

    .consistency-box {
        margin-top: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px;
    }

    .consistency-score {
        font-size: 24px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }

    .consistency-meta {
        font-size: 11px;
        color: #64748b;
    }

    .recovery-plan {
        margin-top: 8px;
        font-size: 12px;
        color: #334155;
        background: #ffffff;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        padding: 8px;
    }
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

        <div class="col-xl-8 col-lg-6">
            <div class="analytics-card p-4">
                <div class="target-title">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-bullseye text-success me-2"></i> Academic Goals</h6>
                    <span class="badge rounded-pill bg-light text-dark border"><?= $has_existing_target ? 'Goal Active' : 'Not Set' ?></span>
                </div>
                <div class="target-subtitle">Set realistic target grade and track weekly consistency.</div>
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
                                <span class="text-muted" style="font-size: 11px;">Current: <span class="status-chip"><?= $res['ut_grade'] ?: 'N/A' ?></span></span>
                            </div>
                            <select name="target_grade[<?= $code ?>]" class="form-select form-select-sm target-select border-0 shadow-sm rounded-pill" style="font-size: 11px;" onchange="updateMotivation(this.value)">
                                <option value="">Set Target</option>
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
                <div class="text-muted" style="font-size:11px;">Tip: target slightly above current grade राख्दा consistency राम्रो हुन्छ।</div>
                <div id="targetResponse" class="mt-2 text-center small fw-bold" style="min-height:18px;"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1 align-items-stretch">
        <div class="col-xl-6 col-lg-12">
            <div class="analytics-card p-4">
                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-route text-primary me-2"></i>Target Achievement Plan</h6>

                <?php if($has_existing_target): ?>
                    <?php if(count($achievement_plan) > 0): ?>
                        <div class="custom-scroll" style="max-height: 190px;">
                            <?php foreach($achievement_plan as $plan): ?>
                                <div class="plan-item">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark small"><?= htmlspecialchars($plan['subject_name']) ?></span>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($plan['current_grade']) ?> → <?= htmlspecialchars($plan['target_grade']) ?></span>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($plan['action']) ?></div>
                                    <div class="plan-meta">Required jump: +<?= number_format((float)$plan['gap'], 1) ?> GPA points</div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-2 p-2 rounded" style="background: #f8fafc; border: 1px solid #eef2f6;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-bold small">This Week Follow-up</div>
                                <div class="followup-progress" id="followupProgress">Progress: 0/4 completed</div>
                            </div>
                            <label class="followup-item"><input type="checkbox" class="followup-check" value="plan"> Daily study plan ready</label>
                            <label class="followup-item"><input type="checkbox" class="followup-check" value="practice"> Completed today practice set</label>
                            <label class="followup-item"><input type="checkbox" class="followup-check" value="revision"> 20-min revision done</label>
                            <label class="followup-item"><input type="checkbox" class="followup-check" value="doubt"> Logged doubts for next class</label>
                            <div class="followup-bar"><div class="followup-bar-fill" id="followupBarFill"></div></div>
                        </div>

                        <div class="reminder-box">
                            <div class="fw-bold small mb-2"><i class="fas fa-bell text-warning me-1"></i>Smart Study Reminder</div>
                            <div class="reminder-controls">
                                <label class="small mb-0">
                                    <input type="checkbox" id="enableGoalReminder"> Enable
                                </label>
                                <select id="goalReminderInterval" class="form-select form-select-sm" style="max-width: 160px;">
                                    <option value="30">Every 30 min</option>
                                    <option value="60" selected>Every 60 min</option>
                                    <option value="120">Every 120 min</option>
                                </select>
                                <button type="button" id="saveGoalReminder" class="btn btn-sm btn-outline-primary">Save</button>
                                <button type="button" id="testGoalReminder" class="btn btn-sm btn-outline-secondary">Test</button>
                            </div>
                            <div id="goalReminderStatus" class="reminder-status">Reminder off.</div>
                        </div>

                        <div class="consistency-box">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold small">Weekly Consistency</div>
                                    <div class="consistency-meta">Based on last 7 days follow-up</div>
                                </div>
                                <div class="text-end">
                                    <div class="consistency-score" id="weeklyConsistencyScore">0%</div>
                                    <span class="badge rounded-pill bg-light text-dark border" id="weeklyConsistencyBadge">Starting</span>
                                </div>
                            </div>
                            <div class="consistency-meta mt-1" id="weeklyMissedDays">Missed days: 0/7</div>
                            <div class="recovery-plan" id="recoveryPlanText">Recovery plan will appear based on your consistency.</div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success py-2 small mb-0">Great! Current performance matches your selected targets for available subjects.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-light border py-2 small mb-0">First lock your goals above. Then this section will show subject-wise plan and weekly follow-up tracker.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-xl-6 col-lg-12">
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

    (function() {
        const checks = Array.from(document.querySelectorAll('.followup-check'));
        const progress = document.getElementById('followupProgress');
        const progressBar = document.getElementById('followupBarFill');
        const scoreEl = document.getElementById('weeklyConsistencyScore');
        const badgeEl = document.getElementById('weeklyConsistencyBadge');
        const missedEl = document.getElementById('weeklyMissedDays');
        const planEl = document.getElementById('recoveryPlanText');
        if (!checks.length || !progress) return;

        const storageKey = 'goal_followup_<?= (int)$student_id ?>_sem_<?= (int)$selected_sem ?>';
        const historyKey = 'goal_followup_history_<?= (int)$student_id ?>_sem_<?= (int)$selected_sem ?>';

        const getDateKey = (d) => {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const getHistory = () => {
            try {
                const raw = JSON.parse(localStorage.getItem(historyKey) || '{}');
                return (raw && typeof raw === 'object') ? raw : {};
            } catch (e) {
                return {};
            }
        };

        const setHistory = (history) => {
            localStorage.setItem(historyKey, JSON.stringify(history));
        };

        const updateTodayHistory = (done, total) => {
            const history = getHistory();
            const key = getDateKey(new Date());
            history[key] = total > 0 ? (done / total) : 0;

            const allowed = new Set();
            for (let i = 0; i < 30; i++) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                allowed.add(getDateKey(date));
            }

            Object.keys(history).forEach(k => {
                if (!allowed.has(k)) {
                    delete history[k];
                }
            });

            setHistory(history);
        };

        const renderConsistency = () => {
            if (!scoreEl || !badgeEl || !missedEl || !planEl) return;
            const history = getHistory();
            const ratios = [];
            let missedDays = 0;

            for (let i = 0; i < 7; i++) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                const key = getDateKey(date);
                const ratio = Number(history[key] ?? 0);
                ratios.push(ratio);
                if (ratio < 0.5) {
                    missedDays++;
                }
            }

            const avg = ratios.length ? (ratios.reduce((a, b) => a + b, 0) / ratios.length) : 0;
            const score = Math.round(avg * 100);
            scoreEl.textContent = `${score}%`;
            missedEl.textContent = `Missed days: ${missedDays}/7`;

            if (score >= 80) {
                badgeEl.textContent = 'Excellent';
                badgeEl.className = 'badge rounded-pill bg-success-subtle text-success border';
                planEl.textContent = 'Strong consistency. Keep same rhythm and increase one high-priority subject session this week.';
            } else if (score >= 60) {
                badgeEl.textContent = 'Stable';
                badgeEl.className = 'badge rounded-pill bg-primary-subtle text-primary border';
                planEl.textContent = 'Consistency is okay. Add one extra 30-min catch-up block on missed days to stay on target.';
            } else {
                badgeEl.textContent = 'Risky';
                badgeEl.className = 'badge rounded-pill bg-danger-subtle text-danger border';
                planEl.textContent = 'Recovery plan: Next 2 days complete any 2 checklist items first, then do 20-min revision before sleep.';
            }
        };

        const render = () => {
            const done = checks.filter(c => c.checked).length;
            progress.textContent = `Progress: ${done}/${checks.length} completed`;
            if (progressBar) {
                const percent = (done / checks.length) * 100;
                progressBar.style.width = `${percent}%`;
            }
            updateTodayHistory(done, checks.length);
            renderConsistency();
        };

        const restore = () => {
            try {
                const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
                checks.forEach(chk => {
                    chk.checked = saved.includes(chk.value);
                });
            } catch (e) {
                checks.forEach(chk => chk.checked = false);
            }
            render();
        };

        checks.forEach(chk => {
            chk.addEventListener('change', () => {
                const selected = checks.filter(c => c.checked).map(c => c.value);
                localStorage.setItem(storageKey, JSON.stringify(selected));
                render();
            });
        });

        document.querySelectorAll('.followup-item').forEach(label => {
            label.addEventListener('click', (event) => {
                if (event.target.tagName.toLowerCase() === 'input') return;
                const box = label.querySelector('input[type="checkbox"]');
                if (!box) return;
                box.checked = !box.checked;
                box.dispatchEvent(new Event('change'));
            });
        });

        restore();
    })();

    (function() {
        const enableEl = document.getElementById('enableGoalReminder');
        const intervalEl = document.getElementById('goalReminderInterval');
        const saveBtn = document.getElementById('saveGoalReminder');
        const testBtn = document.getElementById('testGoalReminder');
        const statusEl = document.getElementById('goalReminderStatus');
        const checks = Array.from(document.querySelectorAll('.followup-check'));

        if (!enableEl || !intervalEl || !saveBtn || !testBtn || !statusEl || !checks.length) return;

        const configKey = 'goal_reminder_cfg_<?= (int)$student_id ?>_sem_<?= (int)$selected_sem ?>';
        let timer = null;

        const getUncheckedItems = () => {
            return checks
                .filter(chk => !chk.checked)
                .map(chk => (chk.parentElement ? chk.parentElement.textContent.trim() : 'Complete your pending task'));
        };

        const buildReminderMessage = () => {
            const pending = getUncheckedItems();
            const primary = pending[0] || 'Keep consistency and continue today study plan.';
            return `Goal follow-up pending: ${primary}`;
        };

        const showReminder = () => {
            const message = buildReminderMessage();
            const now = new Date();
            statusEl.textContent = `Last reminder: ${now.toLocaleTimeString()} — ${message}`;

            if ('Notification' in window && Notification.permission === 'granted' && document.hidden) {
                new Notification('Academic Goal Reminder', {
                    body: message,
                    icon: 'https://cdn-icons-png.flaticon.com/512/1827/1827349.png'
                });
            }
        };

        const stopTimer = () => {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        };

        const startTimer = () => {
            stopTimer();
            if (!enableEl.checked) {
                statusEl.textContent = 'Reminder off.';
                return;
            }

            const minutes = parseInt(intervalEl.value, 10) || 60;
            const ms = minutes * 60 * 1000;
            timer = setInterval(showReminder, ms);
            statusEl.textContent = `Reminder active: every ${minutes} minutes.`;
        };

        const saveConfig = async () => {
            const config = {
                enabled: enableEl.checked,
                interval: intervalEl.value
            };
            localStorage.setItem(configKey, JSON.stringify(config));

            if (enableEl.checked && 'Notification' in window && Notification.permission === 'default') {
                try {
                    await Notification.requestPermission();
                } catch (e) {
                }
            }

            startTimer();
        };

        const restoreConfig = () => {
            try {
                const saved = JSON.parse(localStorage.getItem(configKey) || '{}');
                enableEl.checked = !!saved.enabled;
                if (saved.interval) {
                    intervalEl.value = String(saved.interval);
                }
            } catch (e) {
                enableEl.checked = false;
            }

            startTimer();
        };

        saveBtn.addEventListener('click', saveConfig);
        testBtn.addEventListener('click', showReminder);
        restoreConfig();
    })();
</script>