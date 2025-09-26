<?php
session_start();
require 'db_config.php'; // database connection

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: publish_result.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$semester = isset($_GET['semester']) ? (int)$_GET['semester'] : 1;

// Fetch student details
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch marks with subject and teacher
$stmt = $conn->prepare("
    SELECT 
        sub.subject_name,
        sub.subject_code,
        t.name AS teacher_name,
        m.marks_obtained,
        m.full_marks,
        m.attendance_present,
        m.attendance_total
    FROM marks m
    JOIN subjects sub ON m.subject_id = sub.id
    LEFT JOIN teachers t ON sub.teacher_id = t.id
    WHERE m.student_id=? AND m.semester=?
");
$stmt->bind_param("ii", $student_id, $semester);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
$total_marks = 0;
$obtained_marks = 0;
$total_attendance_present = 0;
$total_attendance_total = 0;

while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
    $total_marks += $row['full_marks'];
    $obtained_marks += $row['marks_obtained'];
    $total_attendance_present += $row['attendance_present'];
    $total_attendance_total += $row['attendance_total'];
}

// Calculate percentage, GPA, CGPA
$percentage = $total_marks > 0 ? round(($obtained_marks/$total_marks)*100,2) : 0;

// Simple GPA conversion (4.0 scale)
$gpa = round(($percentage/100)*4, 2);

// For CGPA, assume we average all semesters marks
$stmt = $conn->prepare("
    SELECT SUM(marks_obtained) AS total_obt, SUM(full_marks) AS total_full
    FROM marks WHERE student_id=?
");
$stmt->bind_param("i",$student_id);
$stmt->execute();
$cgpa_result = $stmt->get_result()->fetch_assoc();
$cgpa = $cgpa_result['total_full'] > 0 ? round(($cgpa_result['total_obt']/$cgpa_result['total_full'])*4, 2) : 0;

// Attendance %
$attendance_avg = $total_attendance_total > 0 ? round(($total_attendance_present/$total_attendance_total)*100,2) : 0;

// Prepare data for JS chart
$chart_labels = array_map(function($s){ return $s['subject_name']; }, $subjects);
$chart_scores = array_map(function($s){ return round(($s['marks_obtained']/$s['full_marks'])*100,2); }, $subjects);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Result</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{font-family:Arial,sans-serif;margin:20px;background:#f9f9f9;}
.container{max-width:1000px;margin:auto;}
.card{background:#fff;padding:20px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.1);margin-bottom:20px;}
h2,h3{margin-top:0;color:#111;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #ddd;padding:8px;text-align:left;}
th{background:#f1f1f1;}
.progress{height:10px;background:#eee;border-radius:10px;overflow:hidden;}
.progress i{display:block;height:100%;background:linear-gradient(90deg,#2563eb,#7c3aed);}
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Student Details</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
        <p><strong>Symbol No:</strong> <?= htmlspecialchars($student['symbol_no']) ?></p>
        <p><strong>Batch:</strong> <?= htmlspecialchars($student['batch_year']) ?> | <strong>Semester:</strong> <?= htmlspecialchars($semester) ?></p>
        <p><strong>Section:</strong> <?= htmlspecialchars($student['section']) ?> | <strong>Faculty:</strong> <?= htmlspecialchars($student['faculty']) ?></p>
        <p><strong>Overall Percentage:</strong> <?= $percentage ?>%</p>
        <p><strong>GPA:</strong> <?= $gpa ?> | <strong>CGPA:</strong> <?= $cgpa ?></p>
        <p><strong>Attendance Avg:</strong> <?= $attendance_avg ?>%</p>
    </div>

    <div class="card">
        <h3>Subject-wise Marks & Attendance</h3>
        <table>
            <tr>
                <th>Subject</th>
                <th>Teacher</th>
                <th>Marks Obtained</th>
                <th>Full Marks</th>
                <th>Attendance</th>
                <th>Progress</th>
            </tr>
            <?php foreach($subjects as $s): 
                $att_pct = $s['attendance_total']>0 ? round(($s['attendance_present']/$s['attendance_total'])*100,2) : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($s['subject_name']) ?></td>
                <td><?= htmlspecialchars($s['teacher_name']) ?></td>
                <td><?= $s['marks_obtained'] ?></td>
                <td><?= $s['full_marks'] ?></td>
                <td><?= $s['attendance_present'].' / '.$s['attendance_total'] ?></td>
                <td>
                    <div class="progress" title="<?= $att_pct ?>%">
                        <i style="width:<?= $att_pct ?>%"></i>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h3>Marks Graph</h3>
        <canvas id="resultChart" height="100"></canvas>
    </div>
</div>

<script>
const ctx = document.getElementById('resultChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Marks (%)',
            data: <?= json_encode($chart_scores) ?>,
            backgroundColor: 'rgba(37,99,235,0.6)',
            borderColor: 'rgba(37,99,235,1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: { y: { beginAtZero:true, max:100 } }
    }
});
</script>
</body>
</html>
