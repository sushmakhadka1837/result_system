<?php
session_start();
require 'db_config.php'; // Tapaiko DB connection file name anusaar change garnuhos

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
    
    // Semester ID thik sanga liyeko check garne
    $semester_id = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : 0;
    $target_type = $_POST['target_type'] ?? 'assessment';
    $target_grades = $_POST['target_grade'] ?? [];

    if ($semester_id == 0) {
        echo "Error: Semester ID missing!";
        exit;
    }

    foreach ($target_grades as $sub_code => $grade) {
        if (!empty($grade)) {
            // sanitize data
            $sub_code = $conn->real_escape_string($sub_code);
            $grade = $conn->real_escape_string($grade);

            // Check if record exists for this student, semester, and subject
            $check = $conn->query("SELECT id FROM target_grades 
                                  WHERE student_id = $student_id 
                                  AND semester_id = $semester_id 
                                  AND subject_code = '$sub_code'");

            if ($check && $check->num_rows > 0) {
                // Update existing record
                $conn->query("UPDATE target_grades 
                             SET target_grade = '$grade', updated_at = NOW() 
                             WHERE student_id = $student_id 
                             AND semester_id = $semester_id 
                             AND subject_code = '$sub_code'");
            } else {
                // Insert new record
                $conn->query("INSERT INTO target_grades (student_id, semester_id, subject_code, target_type, target_grade, created_at, updated_at) 
                             VALUES ($student_id, $semester_id, '$sub_code', '$target_type', '$grade', NOW(), NOW())");
            }
        }
    }
    echo "Target Saved Successfully for Sem $semester_id!";
} else {
    echo "Invalid Request!";
}
?>