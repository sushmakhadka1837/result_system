<?php
// Session ra Database check...
if(!isset($_SESSION['student_id'])){ exit(); }
$student_id = $_SESSION['student_id'];
$sem_id = intval($_GET['sem_id'] ?? 8); 
$ut_total = 50; $assessment_total = 100;

$query = "SELECT r.subject_id, sm.subject_name, sm.is_elective, r.ut_obtain
          FROM results r JOIN subjects_master sm ON r.subject_id = sm.id
          LEFT JOIN student_electives se ON (sm.id = se.elective_option_id AND se.student_id = r.student_id AND se.semester_id = r.semester_id)
          WHERE r.student_id = ? AND r.semester_id = ?
          AND (sm.is_elective = 0 OR se.elective_option_id IS NOT NULL)
          AND sm.subject_name NOT REGEXP '^(Project I|Project II|Project)$'
          ORDER BY sm.is_elective ASC, sm.id ASC";

$results_q = $conn->prepare($query);
$results_q->bind_param("ii", $student_id, $sem_id);
$results_q->execute();
$results_data = $results_q->get_result();
?>

<div class="ai-wide-footer-section mt-5 no-print">
    <div class="container">
        <div class="ai-main-card">
            <div class="ai-card-header-mini">
                <div class="pulse-red me-2"></div>
                <span class="fw-bold" style="font-size: 0.8rem; color: #1e293b;">AI ASSESSOR: SEMESTER <?= $sem_id ?> PREDICTIONS</span>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless mb-0 align-middle">
                    <thead>
                        <tr class="text-muted" style="font-size: 0.65rem; letter-spacing: 1px;">
                            <th class="ps-4">COURSE TITLE</th>
                            <th class="text-center">UNIT TEST</th>
                            <th class="text-center">PROJECTED FINAL</th>
                            <th class="pe-4">AI ANALYSIS</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($res = $results_data->fetch_assoc()):
                        $ut = floatval($res['ut_obtain']);
                        $pred = round(($ut / $ut_total) * $assessment_total);
                        $c = ($pred >= 80) ? "#10b981" : (($pred >= 60) ? "#3b82f6" : "#ef4444");
                    ?>
                        <tr class="interactive-row">
                            <td class="ps-4">
                                <div class="sub-label-mini <?= $res['is_elective'] ? 'bg-warning-subtle text-warning' : 'bg-primary-subtle text-primary' ?>">
                                    <?= $res['is_elective'] ? 'Elective' : 'Core' ?>
                                </div>
                                <div class="subject-title-bold"><?= htmlspecialchars($res['subject_name']) ?></div>
                            </td>
                            <td class="text-center">
                                <span class="ut-badge-compact"><?= $ut ?></span>
                            </td>
                            <td class="text-center">
                                <div class="prediction-value" style="color: <?= $c ?>;"><?= $pred ?>%</div>
                                <div class="progress-bar-mini"><div class="fill" style="width:<?= $pred ?>%; background:<?= $c ?>;"></div></div>
                            </td>
                            <td class="pe-4">
                                <div class="analysis-bubble" style="border-left: 3px solid <?= $c ?>;">
                                    <?= ($pred >= 80) ? "Excellent momentum!" : (($pred >= 60) ? "Good work, aim for 80+" : "Needs focused revision") ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Wide Section Layout */
.ai-wide-footer-section { width: 100%; background: #fcfdfe; padding: 40px 0; border-top: 1px solid #edf2f7; }

.ai-main-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }

/* 🚀 INTERACTIVE HOVER LOGIC */
.interactive-row { 
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); 
    cursor: pointer;
    border-bottom: 1px solid #f8fafc;
}

/* Row Hover huda halka pop-out hune ra shake hune */
.interactive-row:hover {
    background-color: #f8fbff;
    transform: translateX(8px); /* Halka debre bata dahine dhakeline */
    box-shadow: inset 4px 0 0 #3b82f6; /* Side blue border */
}

/* Row hover huda text lai color dine */
.interactive-row:hover .subject-title-bold { color: #3b82f6; }

.subject-title-bold { font-weight: 700; color: #334155; font-size: 0.85rem; transition: 0.2s; }
.sub-label-mini { font-size: 0.55rem; font-weight: 800; text-transform: uppercase; padding: 1px 6px; border-radius: 3px; display: inline-block; margin-bottom: 2px; }

.ut-badge-compact { background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; color: #475569; }
.prediction-value { font-size: 1rem; font-weight: 900; line-height: 1; margin-bottom: 4px; }

.progress-bar-mini { width: 60px; height: 3px; background: #e2e8f0; border-radius: 10px; margin: 0 auto; overflow: hidden; }
.fill { height: 100%; transition: width 1s ease-in-out; }

.analysis-bubble { 
    background: #f8fafc; padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; color: #64748b; 
    transition: all 0.3s ease;
}

/* Hover garda bubble shake hune logic */
.interactive-row:hover .analysis-bubble {
    background: #fff;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
    transform: scale(1.05);
}

.ai-card-header-mini { padding: 12px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; }

/* Pulse animation for AI icon */
.pulse-red {
  width: 8px; height: 8px; background: #3b82f6; border-radius: 50%;
  box-shadow: 0 0 0 rgba(59, 130, 246, 0.4);
  animation: pulse-blue 2s infinite;
}

@keyframes pulse-blue {
  0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
  70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
  100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
}
</style>