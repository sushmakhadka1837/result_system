<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Fetch all departments for filter
$departments_result = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");

// Handle adding subject to filtered students
if (isset($_POST['add_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $credit_hours = intval($_POST['credit_hours']);
    $department_id = intval($_POST['department_id']);
    $semester = intval($_POST['semester']);
    $filter_batch = $_GET['batch_year'] ?? '';

    if (empty($subject_name) || empty($subject_code) || $credit_hours < 1 || $credit_hours > 4) {
        $error = "All fields are required! Credit Hours: 1-4.";
    } else {
        // Insert into master subjects table
        $stmt = $conn->prepare("INSERT INTO subjects_master (subject_name, subject_code, credit_hours) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $subject_name, $subject_code, $credit_hours);
        if ($stmt->execute()) {
            $subject_id = $stmt->insert_id;
            // Map subject to department + semester
            $stmt_map = $conn->prepare("INSERT INTO subjects_department_semester (subject_id, department_id, semester) VALUES (?, ?, ?)");
            $stmt_map->bind_param("iii", $subject_id, $department_id, $semester);
            $stmt_map->execute();
            $success = "Subject added successfully!";
        } else {
            $error = "Subject code already exists or something went wrong.";
        }
    }
}

// Handle filter/search
$filter_department = $_GET['department'] ?? '';
$filter_semester = $_GET['semester'] ?? '';
$filter_batch = $_GET['batch_year'] ?? '';

// Fetch filtered subjects
$where_clause = "1";
if ($filter_department) $where_clause .= " AND sds.department_id=".intval($filter_department);
if ($filter_semester) $where_clause .= " AND sds.semester=".intval($filter_semester);

$subjects_result = [];
if ($filter_department && $filter_semester && $filter_batch) {
    $stmt = $conn->prepare("
        SELECT sm.id, sm.subject_name, sm.subject_code, sm.credit_hours
        FROM subjects_master sm
        JOIN subjects_department_semester sds ON sm.id = sds.subject_id
        WHERE $where_clause
        ORDER BY sm.subject_name ASC
    ");
    $stmt->execute();
    $subjects_result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Student Subjects</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans p-6">

<div class="container mx-auto">

<h2 class="text-2xl mb-4 font-semibold">Manage Subjects for Students</h2>

<!-- Alerts -->
<?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded"><?= $success; ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="bg-red-100 text-red-800 px-4 py-2 mb-4 rounded"><?= $error; ?></div>
<?php endif; ?>

<!-- Filter Form -->
<div class="bg-white p-4 rounded-lg shadow mb-6">
<form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
    <select name="department" class="border p-2 rounded" required>
        <option value="">Select Department</option>
        <?php
        $departments_result->data_seek(0);
        while($row = $departments_result->fetch_assoc()):
        ?>
            <option value="<?= $row['id']; ?>" <?= ($row['id']==$filter_department)?'selected':'' ?>><?= htmlspecialchars($row['department_name']); ?></option>
        <?php endwhile; ?>
    </select>

    <select name="semester" class="border p-2 rounded" required>
        <option value="">Select Semester</option>
        <?php for($i=1;$i<=10;$i++): ?>
            <option value="<?= $i; ?>" <?= ($i==$filter_semester)?'selected':'' ?>><?= $i; ?></option>
        <?php endfor; ?>
    </select>

    <input type="number" name="batch_year" placeholder="Batch Year" value="<?= htmlspecialchars($filter_batch); ?>" class="border p-2 rounded" required>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
</form>
</div>

<?php if($subjects_result && $subjects_result->num_rows > 0): ?>
<!-- Filtered Subjects Table -->
<div class="bg-white p-6 rounded-lg shadow mb-6">
<h3 class="text-lg font-semibold mb-4">Filtered Subjects</h3>
<table class="w-full border-collapse">
<thead>
<tr class="bg-gray-200">
    <th class="p-2 border">#</th>
    <th class="p-2 border">Subject Name</th>
    <th class="p-2 border">Code</th>
    <th class="p-2 border">Credit Hours</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($row=$subjects_result->fetch_assoc()): ?>
<tr>
    <td class="p-2 border"><?= $i++; ?></td>
    <td class="p-2 border"><?= htmlspecialchars($row['subject_name']); ?></td>
    <td class="p-2 border"><?= htmlspecialchars($row['subject_code']); ?></td>
    <td class="p-2 border"><?= $row['credit_hours']; ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<!-- Add Subject Form -->
<?php if($filter_department && $filter_semester && $filter_batch): ?>
<div class="bg-white p-6 rounded-lg shadow mb-6">
<h3 class="text-lg font-semibold mb-4">Add New Subject</h3>
<form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="text" name="subject_name" placeholder="Subject Name" class="border p-2 rounded" required>
    <input type="text" name="subject_code" placeholder="Subject Code" class="border p-2 rounded" required>
    <input type="number" name="credit_hours" min="1" max="4" step="1" placeholder="Credit Hours (1-4)" class="border p-2 rounded" required>
    <input type="hidden" name="department_id" value="<?= intval($filter_department); ?>">
    <input type="hidden" name="semester" value="<?= intval($filter_semester); ?>">
    <button type="submit" name="add_subject" class="bg-indigo-600 text-white px-4 py-2 rounded col-span-2">Add Subject</button>
</form>
</div>
<?php endif; ?>

</div>
</body>
</html>
