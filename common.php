<?php
// common.php
function getCurrentSemester($batch_year, $semester_duration_months = 6) {
    $batch_start = new DateTime($batch_year.'-07-01'); // assume batch starts July
    $now = new DateTime();
    $diff = $batch_start->diff($now);
    $months_passed = $diff->y*12 + $diff->m;

    $current_semester = ceil(($months_passed+1) / $semester_duration_months);
    return $current_semester;
}
?>
