<?php
session_start();
require 'db_config.php';

$teacher_id = $_SESSION['teacher_id'] ?? 0;
if(!$teacher_id){ header("Location: login.php"); exit; }

$assign_id = intval($_GET['assign_id'] ?? 0);
if(!$assign_id) { header("Location: teacher_subjects.php"); exit; }

// 1. Subject Details Fetch garne
$details_query = "SELECT sm.id AS subject_id, sm.subject_name, sm.is_elective, 
                         ts.semester_id, ts.batch_year, d.id as dept_id, d.department_name 
                  FROM teacher_subjects ts 
                  JOIN subjects_master sm ON ts.subject_map_id = sm.id 
                  JOIN departments d ON ts.department_id = d.id
                  WHERE ts.id = ?";

$stmt_details = $conn->prepare($details_query);
$stmt_details->bind_param("i", $assign_id);
$stmt_details->execute();
$details = $stmt_details->get_result()->fetch_assoc();

if(!$details) { echo "Assignment not found."; exit; }

$subject_id = $details['subject_id'];
$is_elective = $details['is_elective'];
$target_dept = $details['dept_id'];
$target_batch = $details['batch_year']; // Yo '2022' ho

// 2. Student Fetch Logic (Semester Filter hataiyeko)
if($is_elective == 1) {
    // Elective Subject: student_electives table ma check garne (History record)
    $student_query = "SELECT s.id, s.full_name, s.email, s.symbol_no 
                      FROM students s
                      JOIN student_electives se ON s.id = se.student_id
                      WHERE se.elective_option_id = ? 
                      AND s.batch_year = ?
                      ORDER BY s.symbol_no ASC";
    $stmt_students = $conn->prepare($student_query);
    $stmt_students->bind_param("ii", $subject_id, $target_batch);
} else {
    // Regular Subject: Yo batch ka sabai student le padheka hunchhan
    $student_query = "SELECT id, full_name, email, symbol_no 
                      FROM students 
                      WHERE department_id = ? 
                      AND batch_year = ? 
                      ORDER BY s.symbol_no ASC"; 
    // Yadi mathi ko line ma error aayo bhane 'ORDER BY symbol_no ASC' matra lekhnuhos
    $student_query = "SELECT id, full_name, email, symbol_no 
                      FROM students 
                      WHERE department_id = ? 
                      AND batch_year = ? 
                      ORDER BY symbol_no ASC";
    $stmt_students = $conn->prepare($student_query);
    $stmt_students->bind_param("ii", $target_dept, $target_batch);
}

$stmt_students->execute();
$students = $stmt_students->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrolled Students - <?= htmlspecialchars($details['subject_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --navy: #0a192f; --cyan: #64ffda; }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .header-box { background: var(--navy); color: white; padding: 25px; border-radius: 0 0 15px 15px; }
        .table-card { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-top: 20px; }
    </style>
</head>
<body>

<?php include 'teacher_header.php'; ?>

<div class="header-box">
    <div class="container">
        <h2 class="fw-bold mb-0 text-cyan"><?= htmlspecialchars($details['subject_name']) ?></h2>
        <p class="mb-0 opacity-75">
            <?= $details['department_name'] ?> • Batch: <?= $target_batch ?> • 
            Status: <?= ($is_elective) ? 'Elective' : 'Regular' ?>
        </p>
    </div>
</div>

<div class="container my-4">
    <div class="alert alert-info py-2">
        <i class="fas fa-info-circle me-2"></i> 
        Showing students from <b>Batch <?= $target_batch ?></b> who were/are enrolled in this subject.
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Symbol No.</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($students->num_rows > 0): ?>
                        <?php while($st = $students->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?= $st['symbol_no'] ?></td>
                            <td><?= htmlspecialchars($st['full_name']) ?></td>
                            <td class="text-muted small"><?= $st['email'] ?></td>
                            <td class="text-center">
                                <a href="marks_entry.php?student_id=<?= $st['id'] ?>&subject_id=<?= $subject_id ?>&assign_id=<?= $assign_id ?>" class="btn btn-sm btn-dark px-3">
                                    <i class="fas fa-edit me-1"></i> Marks
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                No students found for Batch <?= $target_batch ?> in this subject.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>