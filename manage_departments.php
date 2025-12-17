<?php
session_start();
require_once 'db_config.php';

// Check admin login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle Add/Edit
if (isset($_POST['save_department'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['department_name']);
    $duration = intval($_POST['duration_years']);
    $semesters = intval($_POST['total_semesters']);
    $hod_id = intval($_POST['hod_id']);

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE departments SET department_name=?, duration_years=?, total_semesters=?, hod_id=? WHERE id=?");
        $stmt->bind_param("siiii", $name, $duration, $semesters, $hod_id, $id);
        $stmt->execute();
        $success = "✅ Department updated successfully!";
    } else {
        $stmt = $conn->prepare("INSERT INTO departments (department_name, duration_years, total_semesters, hod_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siii", $name, $duration, $semesters, $hod_id);
        $stmt->execute();
        $success = "✅ Department added successfully!";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM departments WHERE id=$id");
    $success = "🗑 Department deleted successfully!";
}

// Fetch all departments
$departments = $conn->query("
    SELECT d.*, t.full_name AS hod_name 
    FROM departments d 
    LEFT JOIN teachers t ON d.hod_id = t.id 
    ORDER BY d.id ASC
");

// Fetch available teachers for HOD selection (exclude already assigned)
$hod_list = $conn->query("
    SELECT id, full_name 
    FROM teachers 
    WHERE id NOT IN (
        SELECT hod_id FROM departments WHERE hod_id IS NOT NULL AND hod_id != 0
    )
    ORDER BY full_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Departments</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

<div class="max-w-6xl mx-auto p-6">

    <h2 class="text-3xl font-semibold text-indigo-700 mb-4">Manage Departments</h2>

    <?php if(!empty($success)): ?>
        <div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded shadow"><?= $success; ?></div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 bg-white p-4 rounded-lg shadow">
        <input type="hidden" name="id" value="">
        <input type="text" name="department_name" placeholder="Department Name" class="border p-2 rounded" required>
        <input type="number" name="duration_years" placeholder="Duration (Years)" class="border p-2 rounded" min="1" required>
        <input type="number" name="total_semesters" placeholder="Total Semesters" class="border p-2 rounded" min="1" max="12" required>

        <select name="hod_id" class="border p-2 rounded">
            <option value="0">Select HOD (Teacher)</option>
            <?php while($hod = $hod_list->fetch_assoc()): ?>
                <option value="<?= $hod['id'] ?>"><?= htmlspecialchars($hod['full_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit" name="save_department" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
            Save
        </button>
    </form>

    <!-- Department Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full border-collapse">
            <thead class="bg-indigo-100">
                <tr>
                    <th class="p-3 border">#</th>
                    <th class="p-3 border text-left">Department</th>
                    <th class="p-3 border">Years</th>
                    <th class="p-3 border">Semesters</th>
                    <th class="p-3 border text-left">HOD</th>
                    <th class="p-3 border">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($departments->num_rows > 0): ?>
                <?php while ($row = $departments->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-2 border text-center"><?= $row['id'] ?></td>
                        <td class="p-2 border"><?= htmlspecialchars($row['department_name']) ?></td>
                        <td class="p-2 border text-center"><?= $row['duration_years'] ?></td>
                        <td class="p-2 border text-center"><?= $row['total_semesters'] ?></td>
                        <td class="p-2 border"><?= htmlspecialchars($row['hod_name'] ?? '-') ?></td>
                        <td class="p-2 border text-center">
                            <button 
                                class="text-blue-600 hover:underline mr-2"
                                onclick="editDept(<?= $row['id'] ?>, '<?= htmlspecialchars($row['department_name']) ?>', <?= $row['duration_years'] ?>, <?= $row['total_semesters'] ?>, <?= $row['hod_id'] ?? 0 ?>)">
                                Edit
                            </button>
                            <a href="?delete=<?= $row['id'] ?>" 
                               onclick="return confirm('Delete this department?')" 
                               class="text-red-600 hover:underline">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="p-4 text-center text-gray-500">No departments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tailwind Footer -->
<footer class="bg-indigo-600 text-white mt-8 p-6">
  <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-6">
    <div>
      <h5 class="font-semibold mb-2">Quick Links</h5>
      <ul>
        <li><a href="index.php" class="hover:underline">Home</a></li>
        <li><a href="#" class="hover:underline">Our Programs</a></li>
        <li><a href="about.php" class="hover:underline">About Us</a></li>
        <li><a href="notice.php" class="hover:underline">Notice Board</a></li>
      </ul>
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
      <ul>
        <li><a href="https://pu.edu.np/" class="hover:underline">Pokhara University</a></li>
        <li><a href="https://ctevt.org.np/" class="hover:underline">CTEVT</a></li>
        <li><a href="https://nec.gov.np/" class="hover:underline">Nepal Engineering Council</a></li>
        <li><a href="https://neanepal.org.np/" class="hover:underline">Nepal Engineer's Association</a></li>
        <li><a href="https://pu.edu.np/research/purc-seminar-series/" class="hover:underline">PU Research</a></li>
      </ul>
    </div>
  </div>
  <div class="text-center mt-6 text-sm">
    &copy; 2025 PEC Result Hub. All rights reserved.
  </div>
</footer>

<script>
function editDept(id, name, duration, sem, hod) {
    document.querySelector('input[name="id"]').value = id;
    document.querySelector('input[name="department_name"]').value = name;
    document.querySelector('input[name="duration_years"]').value = duration;
    document.querySelector('input[name="total_semesters"]').value = sem;
    document.querySelector('select[name="hod_id"]').value = hod;
    document.querySelector('button[name="save_department"]').innerText = 'Update';
}
</script>
</body>
</html>
