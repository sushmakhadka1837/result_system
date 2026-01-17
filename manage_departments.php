<?php
session_start();
require_once 'db_config.php';

/* ---------- ADMIN CHECK ---------- */
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$success = "";
/* ---------- ADD / UPDATE ---------- */
if (isset($_POST['save_department'])) {
    $id        = intval($_POST['id']);
    $name      = trim($_POST['department_name']);
    $duration  = intval($_POST['duration_years']);
    $semesters = intval($_POST['total_semesters']);
    $hod_id    = intval($_POST['hod_id']);

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE departments SET department_name=?, duration_years=?, total_semesters=?, hod_id=? WHERE id=?");
        $stmt->bind_param("siiii", $name, $duration, $semesters, $hod_id, $id);
        $stmt->execute();
        $success = "Department updated successfully!";
    } else {
        $stmt = $conn->prepare("INSERT INTO departments (department_name, duration_years, total_semesters, hod_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siii", $name, $duration, $semesters, $hod_id);
        $stmt->execute();
        $success = "Department added successfully!";
    }
}

/* ---------- DELETE ---------- */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM departments WHERE id=$id");
    $success = "Department deleted successfully!";
}

/* ---------- FETCH DATA ---------- */
$departments = $conn->query("SELECT d.*, t.full_name AS hod_name FROM departments d LEFT JOIN teachers t ON d.hod_id = t.id ORDER BY d.id ASC");
$hod_list = $conn->query("SELECT id, full_name FROM teachers ORDER BY full_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments | RMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<div class="flex min-h-screen">
    <aside class="w-72 bg-slate-900 text-slate-300 hidden lg:flex flex-col fixed h-full z-50">
        <div class="p-6 border-b border-slate-800 flex items-center gap-3">
            <div class="h-9 w-9 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg"><i class="fa-solid fa-sitemap"></i></div>
            <span class="text-xl font-bold text-white tracking-tight">RMS Admin</span>
        </div>
        <nav class="p-4 space-y-1 mt-4">
            <a href="admin_dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition-all text-slate-400">
                <i class="fa-solid fa-house-chimney w-5"></i> Dashboard
            </a>
            <a href="manage_users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition-all text-slate-400">
                <i class="fa-solid fa-user-group w-5"></i> Manage Users
            </a>
            <a href="manage_departments.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/20">
                <i class="fa-solid fa-layer-group w-5"></i> Departments
            </a>
            <a href="manage_feedback.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition-all text-slate-400">
                <i class="fa-solid fa-comments w-5"></i> Feedback
            </a>
        </nav>
    </aside>

    <main class="flex-1 lg:ml-72 p-6 lg:p-10">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Departments Management</h1>
                <p class="text-slate-500">Academic departments, duration, ra HOD haru yaha bata manage garnu hos.</p>
            </div>
            <button onclick="history.back()" class="h-10 w-10 flex items-center justify-center rounded-full bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shadow-sm transition">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </div>

        <?php if ($success): ?>
            <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-100 text-emerald-700 px-6 py-4 rounded-2xl shadow-sm">
                <i class="fa-solid fa-circle-check"></i>
                <span class="font-semibold"><?= $success ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 p-8 mb-8">
            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-circle-plus text-indigo-500"></i>
                <span id="formTitle">Add New Department</span>
            </h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <input type="hidden" name="id" id="dept_id">
                
                <div class="lg:col-span-1">
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">Dept Name</label>
                    <input type="text" name="department_name" id="dept_name" placeholder="e.g. Computer Engineering"
                           class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" required>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">Duration (Years)</label>
                    <input type="number" name="duration_years" id="dept_duration" placeholder="Years"
                           class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" min="1" required>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">Total Semesters</label>
                    <input type="number" name="total_semesters" id="dept_semesters" placeholder="Semesters"
                           class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" min="1" max="12" required>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">Assign HOD</label>
                    <select name="hod_id" id="dept_hod" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all cursor-pointer">
                        <option value="0">Select HOD</option>
                        <?php 
                        $hod_list->data_seek(0);
                        while ($hod = $hod_list->fetch_assoc()): ?>
                            <option value="<?= $hod['id'] ?>"><?= htmlspecialchars($hod['full_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" name="save_department" id="submitBtn"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-indigo-200 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Save Dept
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-slate-400">
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-center w-20">ID</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Department Info</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-center">Structure</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Head of Dept (HOD)</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if ($departments->num_rows): ?>
                            <?php while ($row = $departments->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-5 text-center font-bold text-slate-300">#<?= $row['id'] ?></td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                                <?= strtoupper(substr($row['department_name'], 0, 1)) ?>
                                            </div>
                                            <span class="font-bold text-slate-900"><?= htmlspecialchars($row['department_name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[11px] font-black uppercase"><?= $row['duration_years'] ?> Yrs</span>
                                            <span class="px-3 py-1 bg-amber-50 text-amber-600 rounded-full text-[11px] font-black uppercase"><?= $row['total_semesters'] ?> Sems</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-sm text-slate-600 font-medium">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-user-tie text-slate-300"></i>
                                            <?= $row['hod_name'] ?? '<span class="text-slate-300 italic">Not Assigned</span>' ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex justify-center gap-2">
                                            <button onclick='editDept(<?= $row["id"] ?>, <?= json_encode($row["department_name"]) ?>, <?= $row["duration_years"] ?>, <?= $row["total_semesters"] ?>, <?= $row["hod_id"] ?? 0 ?>)'
                                                    class="h-9 w-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Edit">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>
                                            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this department?')"
                                               class="h-9 w-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Delete">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="px-6 py-20 text-center text-slate-400 italic">No departments records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function editDept(id, name, duration, sem, hod) {
    document.getElementById('dept_id').value = id;
    document.getElementById('dept_name').value = name;
    document.getElementById('dept_duration').value = duration;
    document.getElementById('dept_semesters').value = sem;
    document.getElementById('dept_hod').value = hod;

    document.getElementById('formTitle').innerText = 'Edit Department: ' + name;
    document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-check-double"></i> Update Department';
    document.getElementById('submitBtn').classList.replace('bg-indigo-600', 'bg-emerald-600');

    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

</body>
</html>