<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Fetch all HOD candidates (from staff table)
$hod_result = $conn->query("SELECT id, name FROM staff ORDER BY name ASC");

// Predefined departments
$predefined_departments = [
    ['name'=>'Civil','duration'=>4,'semesters'=>8],
    ['name'=>'Computer','duration'=>4,'semesters'=>8],
    ['name'=>'BEIT','duration'=>4,'semesters'=>8],
    ['name'=>'Architecture','duration'=>5,'semesters'=>10]
];

// Insert predefined departments if table empty
$check = $conn->query("SELECT COUNT(*) as cnt FROM departments")->fetch_assoc();
if($check['cnt']==0){
    foreach($predefined_departments as $dept){
        $stmt = $conn->prepare("INSERT INTO departments (department_name,duration_years,total_semesters) VALUES (?,?,?)");
        $stmt->bind_param("sii",$dept['name'],$dept['duration'],$dept['semesters']);
        $stmt->execute();
    }
}

// Handle Add/Edit Department
if(isset($_POST['save_department'])){
    $id = intval($_POST['id']);
    $name = trim($_POST['department_name']);
    $duration = intval($_POST['duration_years']);
    $semesters = intval($_POST['total_semesters']);
    $hod_id = intval($_POST['hod_id']);

    // Validation
    if($duration < 1) $duration = 1;
    if($semesters < 1) $semesters = 1;
    if($semesters > 10) $semesters = 10;

    if($id > 0){
        // Update existing
        $stmt = $conn->prepare("UPDATE departments SET department_name=?, duration_years=?, total_semesters=?, hod_id=? WHERE id=?");
        $stmt->bind_param("siiii",$name,$duration,$semesters,$hod_id,$id);
        $stmt->execute();
        $success = "Department updated successfully!";
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO departments (department_name,duration_years,total_semesters,hod_id) VALUES (?,?,?,?)");
        $stmt->bind_param("iiii",$name,$duration,$semesters,$hod_id);
        $stmt->execute();
        $success = "Department added successfully!";
    }
}

// Delete Department
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM departments WHERE id=$id");
    $success = "Department deleted successfully!";
}

// Fetch all departments with HOD name
$result = $conn->query("SELECT d.*, s.name as hod_name FROM departments d LEFT JOIN staff s ON d.hod_id=s.id ORDER BY d.id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Departments</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
<div class="container mx-auto p-6">

<h2 class="text-2xl mb-4 font-semibold">Manage Departments</h2>

<?php if(!empty($success)): ?>
<div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded"><?= $success; ?></div>
<?php endif; ?>

<?php if(!empty($error)): ?>
<div class="bg-red-100 text-red-800 px-4 py-2 mb-4 rounded"><?= $error; ?></div>
<?php endif; ?>

<!-- Add/Edit Form -->
<form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    <input type="hidden" name="id" value="">
    <input type="text" name="department_name" placeholder="Department Name" class="border p-2 rounded" required>
    <input type="number" name="duration_years" placeholder="Duration (Years)" class="border p-2 rounded" min="1" step="1" required>
    <input type="number" name="total_semesters" placeholder="Total Semesters" class="border p-2 rounded" min="1" max="10" step="1" required>
    <select name="hod_id" class="border p-2 rounded">
        <option value="0">Select HOD</option>
        <?php while($hod = $hod_result->fetch_assoc()): ?>
            <option value="<?= $hod['id'] ?>"><?= htmlspecialchars($hod['name']) ?></option>
        <?php endwhile; ?>
    </select>
    <button type="submit" name="save_department" class="bg-indigo-600 text-white px-4 py-2 rounded">Save</button>
</form>

<!-- Departments Table -->
<table class="w-full border-collapse bg-white rounded-lg overflow-hidden shadow">
<thead class="bg-gray-200">
<tr>
<th class="p-2 border">#</th>
<th class="p-2 border">Department</th>
<th class="p-2 border">Duration (Years)</th>
<th class="p-2 border">Total Semesters</th>
<th class="p-2 border">HOD</th>
<th class="p-2 border">Action</th>
</tr>
</thead>
<tbody>
<?php if($result->num_rows>0): ?>
<?php while($row=$result->fetch_assoc()): ?>
<tr>
<td class="p-2 border"><?= $row['id']; ?></td>
<td class="p-2 border"><?= htmlspecialchars($row['department_name']); ?></td>
<td class="p-2 border"><?= $row['duration_years']; ?></td>
<td class="p-2 border"><?= $row['total_semesters']; ?></td>
<td class="p-2 border"><?= htmlspecialchars($row['hod_name'] ?? '-'); ?></td>
<td class="p-2 border">
    <a href="#" onclick="editDept(<?= $row['id']; ?>,'<?= $row['department_name']; ?>',<?= $row['duration_years']; ?>,<?= $row['total_semesters']; ?>,<?= $row['hod_id'] ?? 0 ?>)" class="text-blue-600 mr-2">Edit</a>
    <a href="?delete=<?= $row['id']; ?>" onclick="return confirm('Delete this department?')" class="text-red-600">Delete</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6" class="p-4 text-center">No departments found.</td></tr>
<?php endif; ?>
</tbody>
</table>

</div>

<script>
function editDept(id,name,duration,sem,hod){
    document.querySelector('input[name="id"]').value=id;
    document.querySelector('input[name="department_name"]').value=name;
    document.querySelector('input[name="duration_years"]').value=duration;
    document.querySelector('input[name="total_semesters"]').value=sem;
    document.querySelector('select[name="hod_id"]').value=hod;
}
</script>
</body>
</html>
