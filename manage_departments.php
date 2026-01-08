<?php
session_start();
require_once 'db_config.php';

/* ---------- ADMIN CHECK ---------- */
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

/* ---------- ADD / UPDATE ---------- */
if (isset($_POST['save_department'])) {

    $id        = intval($_POST['id']);
    $name      = trim($_POST['department_name']);
    $duration  = intval($_POST['duration_years']);
    $semesters = intval($_POST['total_semesters']);
    $hod_id    = intval($_POST['hod_id']);

    if ($id > 0) {
        $stmt = $conn->prepare("
            UPDATE departments 
            SET department_name=?, duration_years=?, total_semesters=?, hod_id=? 
            WHERE id=?
        ");
        $stmt->bind_param("siiii", $name, $duration, $semesters, $hod_id, $id);
        $stmt->execute();
        $success = "✅ Department updated successfully!";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO departments (department_name, duration_years, total_semesters, hod_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("siii", $name, $duration, $semesters, $hod_id);
        $stmt->execute();
        $success = "✅ Department added successfully!";
    }
}

/* ---------- DELETE ---------- */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM departments WHERE id=$id");
    $success = "🗑 Department deleted successfully!";
}

/* ---------- FETCH DEPARTMENTS ---------- */
$departments = $conn->query("
    SELECT d.*, t.full_name AS hod_name 
    FROM departments d 
    LEFT JOIN teachers t ON d.hod_id = t.id 
    ORDER BY d.id ASC
");

/* ---------- FETCH ALL TEACHERS FOR HOD ---------- */
$hod_list = $conn->query("
    SELECT id, full_name 
    FROM teachers 
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

<body class="bg-gray-100">

<div class="max-w-6xl mx-auto p-6">

    <h2 class="text-3xl font-semibold text-indigo-700 mb-4">
        Manage Departments
    </h2>

    <?php if (!empty($success)): ?>
        <div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded shadow">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 bg-white p-4 rounded-lg shadow mb-6">
        <input type="hidden" name="id">

        <input type="text" name="department_name"
               placeholder="Department Name"
               class="border p-2 rounded"
               required>

        <input type="number" name="duration_years"
               placeholder="Duration (Years)"
               class="border p-2 rounded"
               min="1" required>

        <input type="number" name="total_semesters"
               placeholder="Total Semesters"
               class="border p-2 rounded"
               min="1" max="12" required>

        <select name="hod_id" class="border p-2 rounded">
            <option value="0">Select HOD</option>
            <?php while ($hod = $hod_list->fetch_assoc()): ?>
                <option value="<?= $hod['id'] ?>">
                    <?= htmlspecialchars($hod['full_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit" name="save_department"
                class="bg-indigo-600 hover:bg-indigo-700 text-white rounded px-4 py-2">
            Save Department
        </button>
    </form>

    <!-- TABLE -->
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
            <?php if ($departments->num_rows): ?>
                <?php while ($row = $departments->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-2 border text-center"><?= $row['id'] ?></td>
                        <td class="p-2 border"><?= htmlspecialchars($row['department_name']) ?></td>
                        <td class="p-2 border text-center"><?= $row['duration_years'] ?></td>
                        <td class="p-2 border text-center"><?= $row['total_semesters'] ?></td>
                        <td class="p-2 border"><?= $row['hod_name'] ?? '-' ?></td>
                        <td class="p-2 border text-center">
                            <button
                              class="text-blue-600 hover:underline mr-2"
                              onclick='editDept(
                                  <?= $row["id"] ?>,
                                  <?= json_encode($row["department_name"]) ?>,
                                  <?= $row["duration_years"] ?>,
                                  <?= $row["total_semesters"] ?>,
                                  <?= $row["hod_id"] ?? 0 ?>
                              )'>
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
                <tr>
                    <td colspan="6" class="p-4 text-center text-gray-500">
                        No departments found
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function editDept(id, name, duration, sem, hod) {
    document.querySelector('input[name="id"]').value = id;
    document.querySelector('input[name="department_name"]').value = name;
    document.querySelector('input[name="duration_years"]').value = duration;
    document.querySelector('input[name="total_semesters"]').value = sem;
    document.querySelector('select[name="hod_id"]').value = hod;

    document.querySelector('button[name="save_department"]').innerText = 'Update Department';

    document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
}
</script>
<div class="flex justify-center mb-4">
    <button
        onclick="history.back()"
        class="w-10 h-10 flex items-center justify-center 
               rounded-full bg-gray-200 hover:bg-gray-300 
               text-gray-700 hover:text-gray-900 
               shadow transition"
        title="Go Back">
        ←
    </button>
</div>


</body>
</html>
