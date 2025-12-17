<?php
$conn = new mysqli("localhost", "root", "", "result_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get POST data
$dept_id = $_POST['department'] ?? '';
$semester_id = $_POST['semester'] ?? '';
$section = $_POST['section'] ?? '';
$batch = $_POST['batch_year'] ?? '';

if(!$dept_id || !$semester_id) die("Department and Semester are required!");

// Fetch departments & semesters for lookup
$dept_result = $conn->query("SELECT id, department_name FROM departments");
$departments = [];
while($d = $dept_result->fetch_assoc()){
    $departments[$d['id']] = $d['department_name'];
}

$sem_result = $conn->query("SELECT id, semester_name FROM semesters");
$semesters = [];
while($s = $sem_result->fetch_assoc()){
    $semesters[$s['id']] = $s['semester_name'];
}

// Section & batch filters
$section_filter = !empty($section) ? "AND (section='$section' OR section IS NULL OR section='')" : "";
$batch_filter = !empty($batch) ? "AND batch_year='$batch'" : "";

// Main query: use department_id
$query = "SELECT * FROM students 
          WHERE department_id='$dept_id' 
          AND semester='$semester_id' 
          $section_filter 
          $batch_filter
          ORDER BY full_name ASC";

$result = $conn->query($query);
$students = [];
if($result){
    while($row = $result->fetch_assoc()){
        $students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student List</title>
    <style>
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f0f2f5; padding:20px; }
        h2 { text-align:center; margin-bottom:30px; color:#333; }
        table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 4px 8px rgba(0,0,0,0.1); }
        th, td { padding:12px 15px; text-align:center; }
        th { background: linear-gradient(90deg, #007BFF, #00aaff); color:#fff; font-weight:600; }
        tr:nth-child(even) { background:#f9f9f9; }
        tr:hover { background:#e1f0ff; }
        @media screen and (max-width:768px){ th,td{font-size:14px;padding:8px 10px;} }
    </style>
</head>
<body>

<h2>Student List</h2>

<?php if(count($students) > 0): ?>
    <table>
        <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Department</th>
            <th>Semester</th>
            <th>Section</th>
            <th>Batch Year</th>
            <th>Symbol Number</th>
        </tr>
        <?php $i=1; foreach($students as $s): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($s['full_name']) ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td><?= htmlspecialchars($s['phone']) ?></td>
            <td><?= $departments[$s['department_id']] ?? $s['department'] ?></td>
            <td><?= $semesters[$s['semester']] ?? $s['semester'] ?></td>
            <td><?= $s['section'] ?: '-' ?></td>
            <td><?= $s['batch_year'] ?></td>
            <td><?= htmlspecialchars($s['symbol_no']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p style="text-align:center;">No students found.</p>
<?php endif; ?>

</body>
</html>
