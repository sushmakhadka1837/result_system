<?php
session_start();
require 'db_config.php';

// Check admin login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Fetch departments for filter
$departments_result = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");

// Handle filter inputs
$dept = intval($_GET['department'] ?? 0);
$sem = intval($_GET['semester'] ?? 0);
$batch = intval($_GET['batch_year'] ?? 0);

// Fetch students with filters
$query = "SELECT s.id, s.full_name, s.email, s.batch_year, s.section, d.department_name, s.semester 
          FROM students s 
          LEFT JOIN departments d ON s.department_id=d.id 
          WHERE 1";

if($dept) $query .= " AND s.department_id=$dept";
if($sem) $query .= " AND s.semester=$sem";
if($batch) $query .= " AND s.batch_year=$batch";

$query .= " ORDER BY s.full_name ASC";
$students_result = $conn->query($query);

// Fetch all teachers
$teachers_result = $conn->query("SELECT id, full_name, email, employee_id, contact FROM teachers ORDER BY full_name ASC");

// Helper to safely output
function h($val) {
    return htmlspecialchars($val ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

<div class="container mx-auto p-6">
<h2 class="text-3xl font-semibold text-indigo-700 mb-6">Manage Users</h2>

<!-- Tabs -->
<div class="mb-6 flex space-x-4">
    <button id="tabStudents" class="px-4 py-2 bg-indigo-600 text-white rounded">Students</button>
    <button id="tabTeachers" class="px-4 py-2 bg-gray-300 text-gray-800 rounded">Teachers</button>
</div>

<!-- Students Filter Form -->
<div id="studentsTable">
<form method="get" class="mb-4 flex gap-2">
    <select name="department" class="border p-2 rounded">
        <option value="">All Departments</option>
        <?php while($d = $departments_result->fetch_assoc()): ?>
            <option value="<?= $d['id'] ?>" <?= ($dept==$d['id']?'selected':'') ?>><?= h($d['department_name']) ?></option>
        <?php endwhile; ?>
    </select>
    <select name="semester" class="border p-2 rounded">
        <option value="">All Semesters</option>
        <?php for($i=1;$i<=10;$i++): ?>
            <option value="<?= $i ?>" <?= ($sem==$i?'selected':'') ?>><?= $i ?></option>
        <?php endfor; ?>
    </select>
    <input type="number" name="batch_year" placeholder="Batch Year" value="<?= $batch ?: '' ?>" class="border p-2 rounded">
    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Filter</button>
</form>

<table class="w-full border-collapse bg-white rounded-lg overflow-hidden shadow">
<thead class="bg-indigo-100">
<tr>
    <th class="p-3 border">#</th>
    <th class="p-3 border text-left">Name</th>
    <th class="p-3 border text-left">Email</th>
    <th class="p-3 border text-center">Department</th>
    <th class="p-3 border text-center">Semester</th>
    <th class="p-3 border text-center">Batch Year</th>
    <th class="p-3 border text-center">Section</th>
    <th class="p-3 border text-center">Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($student = $students_result->fetch_assoc()): ?>
<tr class="hover:bg-gray-50">
    <td class="p-2 border text-center"><?= $i++; ?></td>
    <td class="p-2 border"><?= h($student['full_name']); ?></td>
    <td class="p-2 border"><?= h($student['email']); ?></td>
    <td class="p-2 border text-center"><?= h($student['department_name'] ?: '-'); ?></td>
    <td class="p-2 border text-center"><?= h($student['semester'] ?: '-'); ?></td>
    <td class="p-2 border text-center"><?= h($student['batch_year'] ?: '-'); ?></td>
    <td class="p-2 border text-center"><?= h($student['section'] ?: '-'); ?></td>
    <td class="p-2 border text-center">
        <a href="edit_student.php?id=<?= $student['id'] ?>" class="text-blue-600 hover:underline mr-2">Edit</a>
        <a href="delete_student.php?id=<?= $student['id'] ?>" onclick="return confirm('Delete this student?')" class="text-red-600 hover:underline">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<!-- Teachers Table -->
<div id="teachersTable" class="hidden">
<table class="w-full border-collapse bg-white rounded-lg overflow-hidden shadow">
<thead class="bg-indigo-100">
<tr>
    <th class="p-3 border">#</th>
    <th class="p-3 border text-left">Name</th>
    <th class="p-3 border text-left">Email</th>
    <th class="p-3 border text-left">Employee ID</th>
    <th class="p-3 border text-left">Contact</th>
    <th class="p-3 border text-center">Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($teacher = $teachers_result->fetch_assoc()): ?>
<tr class="hover:bg-gray-50">
    <td class="p-2 border text-center"><?= $i++; ?></td>
    <td class="p-2 border"><?= h($teacher['full_name']); ?></td>
    <td class="p-2 border"><?= h($teacher['email']); ?></td>
    <td class="p-2 border"><?= h($teacher['employee_id']); ?></td>
    <td class="p-2 border"><?= h($teacher['contact']); ?></td>
    <td class="p-2 border text-center">
        <a href="edit_teacher.php?id=<?= $teacher['id'] ?>" class="text-blue-600 hover:underline mr-2">Edit</a>
        <a href="delete_teacher.php?id=<?= $teacher['id'] ?>" onclick="return confirm('Delete this teacher?')" class="text-red-600 hover:underline">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

</div>

<!-- Footer -->
<footer class="bg-indigo-600 text-white mt-6">
  <div class="container mx-auto py-6 grid grid-cols-1 md:grid-cols-4 gap-4">
    <div>
      <h5 class="font-semibold mb-2">Quick Links</h5>
      <a href="index.php" class="block hover:underline">Home</a>
      <a href="#" class="block hover:underline">Our Programs</a>
      <a href="about.php" class="block hover:underline">About Us</a>
      <a href="notice.php" class="block hover:underline">Notice Board</a>
    </div>
    <div>
      <h5 class="font-semibold mb-2">Follow Us</h5>
      <div class="flex gap-2">
        <a href="https://www.facebook.com/PECPoU" aria-label="Facebook">
          <img src="https://img.icons8.com/ios-filled/24/ffffff/facebook-new.png" alt="Facebook"/>
        </a>
        <a href="https://www.instagram.com/pec.pkr/" aria-label="Instagram">
          <img src="https://img.icons8.com/ios-filled/24/ffffff/instagram-new.png" alt="Instagram"/>
        </a>
      </div>
    </div>
    <div>
      <h5 class="font-semibold mb-2">Contact Us</h5>
      <p>Phirke Pokhara-8, Nepal</p>
      <p>Phone: 061 581209</p>
      <p>Email: info@pec.edu.np</p>
    </div>
    <div>
      <h5 class="font-semibold mb-2">Useful Links</h5>
      <a href="https://pu.edu.np/" class="block hover:underline">Pokhara University</a>
      <a href="https://ctevt.org.np/" class="block hover:underline">CTEVT</a>
      <a href="https://nec.gov.np/" class="block hover:underline">Nepal Engineering Council</a>
      <a href="https://neanepal.org.np/" class="block hover:underline">Nepal Engineer's Association</a>
    </div>
  </div>
  <div class="text-center py-3 bg-indigo-700">
    <small>&copy; 2025 PEC Result Hub. All rights reserved.</small>
  </div>
</footer>

<script>
// Tab switching
document.getElementById('tabStudents').addEventListener('click', function(){
    document.getElementById('studentsTable').classList.remove('hidden');
    document.getElementById('teachersTable').classList.add('hidden');
    this.classList.add('bg-indigo-600','text-white');
    document.getElementById('tabTeachers').classList.remove('bg-indigo-600','text-white');
    document.getElementById('tabTeachers').classList.add('bg-gray-300','text-gray-800');
});

document.getElementById('tabTeachers').addEventListener('click', function(){
    document.getElementById('teachersTable').classList.remove('hidden');
    document.getElementById('studentsTable').classList.add('hidden');
    this.classList.add('bg-indigo-600','text-white');
    document.getElementById('tabStudents').classList.remove('bg-indigo-600','text-white');
    document.getElementById('tabStudents').classList.add('bg-gray-300','text-gray-800');
});
</script>
</body>
</html>
