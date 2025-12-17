<?php
session_start();
require 'db_config.php';

if(!isset($_GET['dept_id']) || !isset($_GET['sem_id'])){
    header("Location: admin_publish_results.php");
    exit;
}

$dept_id = intval($_GET['dept_id']);
$sem_id = intval($_GET['sem_id']);

// Unpublish status table
$conn->query("UPDATE results_publish_status 
              SET published = 0 
              WHERE department_id=$dept_id AND semester_id=$sem_id");

// Unpublish actual results (hide from students)
$conn->query("
    UPDATE results r
    JOIN students s ON r.student_id = s.id
    SET r.published = 0
    WHERE s.department_id = $dept_id AND s.semester = $sem_id
");

// Teacher can edit marks again — if you have mark_lock column
$conn->query("
    UPDATE teacher_subjects ts
    JOIN subjects_department_semester sds ON ts.subject_map_id = sds.id
    SET ts.mark_lock = 0
    WHERE sds.department_id = $dept_id AND sds.semester = $sem_id
");

echo "<script>
alert('✅ Results Unpublished Successfully!');
window.location='publish_department.php?dept_id=$dept_id';
</script>";
exit;
?>
