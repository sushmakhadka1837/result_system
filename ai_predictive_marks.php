<?php
// Session ra Database check...
if(!isset($_SESSION['student_id'])){ exit(); }
$student_id = $_SESSION['student_id'];
$sem_id = intval($_GET['sem_id'] ?? 8); 
$ut_total = 50; $assessment_total = 100;

// Get UT-to-Assessment correlation from PREVIOUS semester only (more accurate)
$prev_query = "SELECT sm.id as subject_id,
              AVG(r.ut_obtain) as avg_ut_marks,
              AVG(r.assessment_raw) as avg_assess_marks,
              AVG((r.assessment_raw / r.ut_obtain)) as ut_to_assess_ratio
              FROM results r 
              JOIN subjects_master sm ON r.subject_id = sm.id
              WHERE r.student_id = ? AND r.semester_id = ? - 1
              AND r.ut_obtain > 0 AND r.assessment_raw > 0
              AND sm.subject_name NOT REGEXP '^(Project I|Project II|Project)$'
              GROUP BY sm.id";
              
$prev_stmt = $conn->prepare($prev_query);
$prev_stmt->bind_param("ii", $student_id, $sem_id);
$prev_stmt->execute();
$prev_results = $prev_stmt->get_result();

// Store UT-to-Assessment correlation
$ut_assess_correlation = array();
while($prev = $prev_results->fetch_assoc()){
    $ut_assess_correlation[$prev['subject_id']] = array(
        'avg_ut' => $prev['avg_ut_marks'],
        'avg_assess' => $prev['avg_assess_marks'],
        'ratio' => $prev['ut_to_assess_ratio']
    );
}

// Current semester data with assessment
$query = "SELECT r.subject_id, sm.subject_name, sm.is_elective, r.ut_obtain, r.assessment_raw
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

<div class="container mt-5 no-print">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">🤖 AI Assessment Predictor - Semester <?= $sem_id ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Subject</th>
                            <th class="text-center">UT Marks<br><small class="text-muted fw-normal">(Unit Test /50)</small></th>
                            <th class="text-center">Predicted Assessment<br><small class="text-muted fw-normal">(AI forecast based on your UT - Work Hard! /100)</small></th>
                            <th class="text-center">Expected Grade</th>
                            <th class="text-center">Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($res = $results_data->fetch_assoc()):
                            $current_ut = $res['ut_obtain'];
                            $current_assess = $res['assessment_raw'];
                            
                            // Predict Assessment marks
                            if (isset($ut_assess_correlation[$res['subject_id']])) {
                                $corr = $ut_assess_correlation[$res['subject_id']];
                                $predicted_assess = round($current_ut * $corr['ratio']);
                                $predicted_assess = min($predicted_assess, 100);
                            } else {
                                $predicted_assess = min(round($current_ut * 2), 100);
                            }
                            
                            // Calculate grade
                            $mark = ($current_assess > 0) ? $current_assess : $predicted_assess;
                            if ($mark >= 90) { $grade = 'A+'; $badge = 'success'; }
                            elseif ($mark >= 80) { $grade = 'A'; $badge = 'success'; }
                            elseif ($mark >= 70) { $grade = 'B+'; $badge = 'info'; }
                            elseif ($mark >= 60) { $grade = 'B'; $badge = 'info'; }
                            elseif ($mark >= 50) { $grade = 'C+'; $badge = 'warning'; }
                            elseif ($mark >= 40) { $grade = 'C'; $badge = 'warning'; }
                            else { $grade = 'D'; $badge = 'danger'; }
                            
                            $is_prediction = ($current_assess == 0);
                        ?>
                        <tr>
                            <td>
                                <small class="badge badge-sm <?= $res['is_elective'] ? 'bg-warning' : 'bg-secondary' ?>">
                                    <?= $res['is_elective'] ? 'Elective' : 'Core' ?>
                                </small>
                                <span class="ms-1"><?= htmlspecialchars($res['subject_name']) ?></span>
                            </td>
                            <td class="text-center"><strong><?= $current_ut ?></strong>/50</td>
                            <td class="text-center"><strong class="text-primary"><?= $predicted_assess ?></strong>/100</td>
                            <td class="text-center">
                                <span class="badge bg-<?= $badge ?>"><?= $grade ?></span>
                            </td>
                            <td class="text-center">
                                <?php if($predicted_assess >= 80): ?>
                                    <small class="text-success">🎯 Keep it up!</small>
                                <?php elseif($predicted_assess >= 60): ?>
                                    <small class="text-info">💪 Good, push harder!</small>
                                <?php else: ?>
                                    <small class="text-warning">⚠️ Work harder!</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>