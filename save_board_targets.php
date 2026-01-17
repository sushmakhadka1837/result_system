<?php
// Tapaiko database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['student_id'])) {
    
    $student_id = $_SESSION['student_id'];
    $semester_id = intval($_POST['semester_id']);
    $target_type = 'board'; // Fixed as per your assessment logic
    $targets = $_POST['targets']; // Form bata aayeko array [subject_code => grade]

    if (!empty($targets)) {
        // SQL prepare garne (Security ko lagi prepared statement use gareko ramro)
        $stmt = $conn->prepare("
            INSERT INTO target_grades (student_id, semester_id, subject_code, target_type, target_grade, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                target_grade = VALUES(target_grade),
                updated_at = NOW()
        ");

        foreach ($targets as $subject_code => $grade) {
            if (!empty($grade)) {
                $code = trim($subject_code);
                $stmt->bind_param("iisss", $student_id, $semester_id, $code, $target_type, $grade);
                $stmt->execute();
            }
        }

        $stmt->close();
        
        // Success message pathaune (Assessment page ma redirect garne)
        header("Location: assessment_section.php?status=success");
        exit();
    } else {
        header("Location: assessment_section.php?status=error&msg=NoTargets");
        exit();
    }
} else {
    die("Unauthorized Access.");
}
?>