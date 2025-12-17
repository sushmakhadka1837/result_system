<?php
session_start();
require_once 'db_config.php';

// ================= CREATE =================
if (isset($_POST['add_subject'])) {
    $name = trim($_POST['subject_name']);
    $code = trim($_POST['subject_code']);
    $credit = intval($_POST['credit_hours']);
    $dept = intval($_POST['department_id']);
    $sem  = intval($_POST['semester']);
    $batch = isset($_POST['batch_year']) && $_POST['batch_year'] !== '' ? intval($_POST['batch_year']) : null;

    // Insert into subjects_master
    $stmt = $conn->prepare("INSERT INTO subjects_master (subject_name, subject_code, credit_hours) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $code, $credit);
    $stmt->execute();
    $subject_id = $conn->insert_id;

    // Insert into subjects_department_semester
    if($batch === null){
        $stmt2 = $conn->prepare("INSERT INTO subjects_department_semester (subject_id, department_id, semester) VALUES (?, ?, ?)");
        $stmt2->bind_param("iii", $subject_id, $dept, $sem);
    } else {
        $stmt2 = $conn->prepare("INSERT INTO subjects_department_semester (subject_id, department_id, semester, batch_year) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("iiii", $subject_id, $dept, $sem, $batch);
    }
    $stmt2->execute();

    header("Location: manage_subjects.php?msg=added");
    exit;
}

// ================= UPDATE =================
if (isset($_POST['update_subject'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['subject_name']);
    $code = trim($_POST['subject_code']);
    $credit = intval($_POST['credit_hours']);

    $stmt = $conn->prepare("UPDATE subjects_master SET subject_name=?, subject_code=?, credit_hours=? WHERE id=?");
    $stmt->bind_param("ssii", $name, $code, $credit, $id);
    $stmt->execute();
    header("Location: manage_subjects.php?msg=updated");
    exit;
}

// ================= DELETE =================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM subjects_department_semester WHERE subject_id=$id");
    $conn->query("DELETE FROM subjects_master WHERE id=$id");
    header("Location: manage_subjects.php?msg=deleted");
    exit;
}

// ================= FILTER =================
$filter_dept  = $_GET['department'] ?? '';
$filter_sem   = $_GET['semester'] ?? '';
$filter_batch = $_GET['batch_year'] ?? '';

$subjects_sql = "
SELECT sm.id, sm.subject_name, sm.subject_code, sm.credit_hours, sds.department_id, sds.semester, sds.batch_year
FROM subjects_master sm
JOIN subjects_department_semester sds ON sm.id = sds.subject_id
WHERE 1
";

$params = [];
$types = '';
if($filter_dept) {
    $subjects_sql .= " AND sds.department_id=?";
    $types .= 'i';
    $params[] = intval($filter_dept);
}
if($filter_sem) {
    $subjects_sql .= " AND sds.semester=?";
    $types .= 'i';
    $params[] = intval($filter_sem);
}
if($filter_batch) {
    $subjects_sql .= " AND sds.batch_year=?";
    $types .= 'i';
    $params[] = intval($filter_batch);
}

$subjects_sql .= " ORDER BY sds.department_id, sds.semester, sm.subject_name ASC";

$stmt = $conn->prepare($subjects_sql);
if(!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Subjects</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<div class="container mx-auto">
<h2 class="text-2xl mb-4 font-semibold">Manage Subjects</h2>

<!-- Filter Form -->
<form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-2 items-end">
    <input type="number" name="department" placeholder="Department ID" class="border p-2 rounded" value="<?= htmlspecialchars($filter_dept); ?>" required>
    <input type="number" name="semester" placeholder="Semester" class="border p-2 rounded" value="<?= htmlspecialchars($filter_sem); ?>" required>
    <input type="number" name="batch_year" placeholder="Batch Year (2023+)" class="border p-2 rounded" value="<?= htmlspecialchars($filter_batch); ?>" required>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded col-span-1">Filter</button>
</form>

<!-- Add Subject Form -->
<form method="POST" class="mb-6 bg-white p-4 rounded shadow grid grid-cols-6 gap-2">
    <input type="text" name="subject_name" placeholder="Subject Name" required class="border p-2 rounded">
    <input type="text" name="subject_code" placeholder="Code" required class="border p-2 rounded">
    <input type="number" name="credit_hours" placeholder="Credit" required class="border p-2 rounded">
    <input type="number" name="department_id" placeholder="Dept ID" required class="border p-2 rounded">
    <input type="number" name="semester" placeholder="Semester" required class="border p-2 rounded">
    <input type="number" name="batch_year" placeholder="Batch Year (2023+)" class="border p-2 rounded">
    <button type="submit" name="add_subject" class="bg-blue-600 text-white px-4 py-2 rounded col-span-6 mt-2">Add Subject</button>
</form>

<!-- Subject List -->
<table class="w-full border-collapse bg-white rounded-lg shadow">
<thead>
<tr class="bg-gray-200">
<th class="p-2 border">S.N</th>
<th class="p-2 border">Subject Name</th>
<th class="p-2 border">Code</th>
<th class="p-2 border">Credit</th>
<th class="p-2 border">Dept</th>
<th class="p-2 border">Semester</th>
<th class="p-2 border">Batch Year</th>
<th class="p-2 border">Actions</th>
</tr>
</thead>
<tbody>
<?php if($result->num_rows>0): $i=1; while($row=$result->fetch_assoc()): ?>
<tr>
<td class="p-2 border"><?= $i++; ?></td>
<td class="p-2 border"><?= htmlspecialchars($row['subject_name']); ?></td>
<td class="p-2 border"><?= htmlspecialchars($row['subject_code']); ?></td>
<td class="p-2 border"><?= $row['credit_hours']; ?></td>
<td class="p-2 border"><?= $row['department_id']; ?></td>
<td class="p-2 border"><?= $row['semester']; ?></td>
<td class="p-2 border"><?= $row['batch_year'] ?? 'Old'; ?></td>
<td class="p-2 border">
<form method="POST" class="inline">
<input type="hidden" name="id" value="<?= $row['id']; ?>">
<input type="text" name="subject_name" value="<?= htmlspecialchars($row['subject_name']); ?>" class="border p-1 rounded w-32">
<input type="text" name="subject_code" value="<?= htmlspecialchars($row['subject_code']); ?>" class="border p-1 rounded w-24">
<input type="number" name="credit_hours" value="<?= $row['credit_hours']; ?>" class="border p-1 rounded w-16">
<button type="submit" name="update_subject" class="bg-green-500 text-white px-2 py-1 rounded">Update</button>
</form>
<a href="?delete=<?= $row['id']; ?>" onclick="return confirm('Are you sure?')" class="bg-red-600 text-white px-2 py-1 rounded ml-2">Delete</a>
</td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="8" class="p-4 text-center">No subjects found for this batch.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</body>
</html>
