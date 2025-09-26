<?php
session_start();
require_once 'db_config.php';

$search_dept = $_GET['search_department'] ?? '';
$search_sem = $_GET['search_semester'] ?? '';

if (!$search_dept || !$search_sem) {
    die("Department and Semester are required.");
}

// Fetch subjects for this department + semester
$stmt = $conn->prepare("
    SELECT sm.subject_name, sm.subject_code, sm.credit_hours
    FROM subjects_master sm
    JOIN subjects_department_semester sds ON sm.id = sds.subject_id
    WHERE sds.department_id=? AND sds.semester=?
");
$stmt->bind_param("ii", $search_dept, $search_sem);
$stmt->execute();
$subjects_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Subjects List</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans p-6">

<div class="container mx-auto">
<h2 class="text-2xl mb-4 font-semibold">Subjects for Department <?= $search_dept ?> | Semester <?= $search_sem ?></h2>

<table class="w-full border-collapse bg-white rounded-lg shadow">
<thead>
<tr class="bg-gray-200">
    <th class="p-2 border">S.N</th>
    <th class="p-2 border">Subject Name</th>
    <th class="p-2 border">Code</th>
    <th class="p-2 border">Credit Hours</th>
</tr>
</thead>
<tbody>
<?php if ($subjects_result->num_rows > 0): ?>
    <?php $i=1; while($row = $subjects_result->fetch_assoc()): ?>
        <tr>
            <td class="p-2 border"><?= $i++; ?></td>
            <td class="p-2 border"><?= htmlspecialchars($row['subject_name']); ?></td>
            <td class="p-2 border"><?= htmlspecialchars($row['subject_code']); ?></td>
            <td class="p-2 border"><?= $row['credit_hours']; ?></td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="4" class="p-4 text-center">No subjects found for this department & semester.</td></tr>
<?php endif; ?>
</tbody>
</table>

<a href="manage_subjects.php" class="inline-block mt-4 text-blue-600 hover:underline">Back to Manage Subjects</a>
</div>

</body>
</html>
