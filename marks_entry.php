<?php
session_start();
require 'db_config.php';

$student_id = intval($_GET['student_id'] ?? 0);
$subject_id = intval($_GET['subject_id'] ?? 0);
$assign_id  = intval($_GET['assign_id'] ?? 0);

// Fetch existing data
$res_q = "SELECT * FROM results WHERE student_id = ? AND subject_id = ?";
$stmt_res = $conn->prepare($res_q);
$stmt_res->bind_param("ii", $student_id, $subject_id);
$stmt_res->execute();
$data = $stmt_res->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ut_obtain = $_POST['ut_obtain'];
    $final_theory = $_POST['final_theory'];
    $practical_marks = $_POST['practical_marks'];
    $attendance_days = $_POST['total_attendance_days'];

    // Update query (Sabaile use garne common columns)
    $sql = "UPDATE results SET 
            ut_obtain = ?, 
            final_theory = ?, 
            practical_marks = ?, 
            total_attendance_days = ? 
            WHERE student_id = ? AND subject_id = ?";
    
    $stmt_save = $conn->prepare($sql);
    $stmt_save->bind_param("ddiiii", $ut_obtain, $final_theory, $practical_marks, $attendance_days, $student_id, $subject_id);
    
    if($stmt_save->execute()) {
        header("Location: subject_students.php?assign_id=$assign_id&msg=success");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container { max-width: 500px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .section-label { font-weight: bold; color: #555; text-transform: uppercase; font-size: 0.85rem; display: block; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .ut-section { background-color: #f0f7ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #007bff; }
        .assessment-section { background-color: #f9fff9; padding: 15px; border-radius: 8px; border-left: 5px solid #28a745; }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .form-container { margin: 20px 10px; padding: 20px; }
            .row .col-md-6 { width: 100%; }
        }
        
        @media (max-width: 576px) {
            .form-container { margin: 10px 5px; padding: 15px; border-radius: 10px; }
            h4 { font-size: 1.2rem; }
            .section-label { font-size: 0.75rem; }
            .ut-section, .assessment-section { padding: 12px; }
        }
    </style>
</head>
<body class="bg-light">

<div class="container">
    <div class="form-container">
        <h4 class="text-center mb-4">Marks Entry</h4>
        
        <form method="POST">
            <div class="ut-section">
                <span class="section-label text-primary">Unit Test (UT)</span>
                <div class="mb-3">
                    <label class="form-label small">UT Obtained Marks</label>
                    <input type="number" step="0.01" name="ut_obtain" class="form-control" value="<?= $data['ut_obtain'] ?? 0 ?>">
                </div>
            </div>

            <div class="assessment-section">
                <span class="section-label text-success">Assessment & Final</span>
                
                <div class="mb-3">
                    <label class="form-label small">Final Theory</label>
                    <input type="number" step="0.01" name="final_theory" class="form-control" value="<?= $data['final_theory'] ?? 0 ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">Practical Marks</label>
                        <input type="number" step="0.01" name="practical_marks" class="form-control" value="<?= $data['practical_marks'] ?? 0 ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small">Attendance Days</label>
                        <input type="number" name="total_attendance_days" class="form-control" value="<?= $data['total_attendance_days'] ?? 0 ?>">
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary w-100">Save Marks</button>
                <a href="subject_students.php?assign_id=<?= $assign_id ?>" class="btn btn-link w-100 text-secondary mt-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>