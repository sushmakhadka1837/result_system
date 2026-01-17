<?php
session_start();
require 'db_config.php';
require_once 'functions.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | RMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .tab-active { border-bottom: 3px solid #4f46e5; color: #4f46e5; font-weight: 700; }
        .sidebar-link:hover { background: rgba(255,255,255,0.05); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<div class="flex min-h-screen">
    <aside class="w-72 bg-slate-900 text-slate-300 hidden lg:flex flex-col fixed h-full z-50">
        <div class="p-6 border-b border-slate-800 flex items-center gap-3">
            <div class="h-9 w-9 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                <i class="fa-solid fa-users-gear text-lg"></i>
            </div>
            <span class="text-xl font-bold text-white tracking-tight">Admin Portal</span>
        </div>
        <nav class="p-4 space-y-1 mt-4">
            <a href="admin_dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-400">
                <i class="fa-solid fa-chart-pie w-5"></i> Dashboard
            </a>
            <a href="manage_users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/20">
                <i class="fa-solid fa-user-group w-5"></i> Manage Users
            </a>
            <a href="manage_departments.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-400">
                <i class="fa-solid fa-sitemap w-5"></i> Departments
            </a>
            <a href="manage_feedback.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-400">
                <i class="fa-solid fa-comments w-5"></i> Feedback
            </a>
        </nav>
    </aside>

    <main class="flex-1 lg:ml-72 p-6 lg:p-10">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">User Management</h1>
                <p class="text-slate-500 mt-1">Students ra Teachers haruko database yaha bata manage garnu hos.</p>
            </div>
            <div class="flex gap-3">
                <a href="student_signup.php" class="bg-white text-slate-700 border border-slate-200 px-4 py-2.5 rounded-xl font-bold hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-user-plus text-indigo-500 text-sm"></i> Add Student
                </a>
                <a href="teacher_signup.php" class="bg-indigo-600 text-white px-4 py-2.5 rounded-xl font-bold hover:bg-indigo-700 transition-all flex items-center gap-2 shadow-lg shadow-indigo-200">
                    <i class="fa-solid fa-plus text-sm"></i> Add Teacher
                </a>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="flex border-b border-slate-100 px-8">
                <button id="btnStudents" class="px-6 py-5 text-sm font-semibold transition-all tab-active flex items-center gap-2">
                    <i class="fa-solid fa-user-graduate"></i> Students List
                </button>
                <button id="btnTeachers" class="px-6 py-5 text-sm font-semibold transition-all text-slate-400 hover:text-slate-600 flex items-center gap-2">
                    <i class="fa-solid fa-chalkboard-user"></i> Teachers List
                </button>
            </div>

            <div id="studentSection" class="p-8">
                <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8 bg-slate-50 p-5 rounded-2xl border border-slate-100">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">Department</label>
                        <select name="department" class="w-full bg-white border border-slate-200 p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition-all">
                            <option value="">All Departments</option>
                            <?php while($d = $departments_result->fetch_assoc()): ?>
                                <option value="<?= $d['id'] ?>" <?= ($dept==$d['id']?'selected':'') ?>><?= h($d['department_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">Semester</label>
                        <select name="semester" class="w-full bg-white border border-slate-200 p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition-all">
                            <option value="">All Semesters</option>
                            <?php for($i=1;$i<=10;$i++): ?>
                                <option value="<?= $i ?>" <?= ($sem==$i?'selected':'') ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1 ml-1">Batch Year</label>
                        <input type="number" name="batch_year" placeholder="e.g. 2024" value="<?= $batch ?: '' ?>" 
                               class="w-full bg-white border border-slate-200 p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition-all">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-slate-800 text-white p-2.5 rounded-xl font-bold hover:bg-slate-900 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-filter text-xs"></i> Apply Filters
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400">
                                <th class="px-4 py-4 text-xs font-bold uppercase tracking-wider text-center">ID</th>
                                <th class="px-4 py-4 text-xs font-bold uppercase tracking-wider">Student Profile</th>
                                <th class="px-4 py-4 text-xs font-bold uppercase tracking-wider">Academic Details</th>
                                <th class="px-4 py-4 text-xs font-bold uppercase tracking-wider text-center">Batch/Sec</th>
                                <th class="px-4 py-4 text-xs font-bold uppercase tracking-wider text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php $i=1; while($student = $students_result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-4 py-5 text-center text-sm font-bold text-slate-300">#<?= $i++; ?></td>
                                <td class="px-4 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold text-sm">
                                            <?= substr($student['full_name'],0,1) ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 leading-tight"><?= h($student['full_name']); ?></p>
                                            <p class="text-xs text-slate-400"><?= h($student['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-slate-700"><?= h($student['department_name'] ?: '-'); ?></span>
                                        <span class="text-[10px] text-indigo-500 font-black uppercase">Semester <?= h($student['semester']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-5 text-center">
                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[11px] font-bold">
                                        <?= h($student['batch_year']); ?> (<?= h($student['section']); ?>)
                                    </span>
                                </td>
                                <td class="px-4 py-5 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="edit_student.php?id=<?= $student['id'] ?>" class="h-8 w-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </a>
                                        <a href="delete_student.php?id=<?= $student['id'] ?>" onclick="return confirm('Delete?')" class="h-8 w-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="teacherSection" class="hidden p-8">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400">
                                <th class="px-4 py-4 text-xs font-bold uppercase tracking-wider text-center">#</th>
                                <th class="px-4 py-4 text-xs font-bold uppercase tracking-wider">Teacher Profile</th>
                                <th class="px-4 py-4 text-xs font-bold uppercase tracking-wider">Emp ID</th>
                                <th class="px-4 py-4 text-xs font-bold uppercase tracking-wider">Contact</th>
                                <th class="px-4 py-4 text-xs font-bold uppercase tracking-wider text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php $i=1; while($teacher = $teachers_result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-4 py-5 text-center text-sm font-bold text-slate-300"><?= $i++; ?></td>
                                <td class="px-4 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold text-sm">
                                            <i class="fa-solid fa-user-tie"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 leading-tight"><?= h($teacher['full_name']); ?></p>
                                            <p class="text-xs text-slate-400"><?= h($teacher['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-5 font-mono text-sm text-slate-500 font-bold">
                                    <?= h($teacher['employee_id']); ?>
                                </td>
                                <td class="px-4 py-5 text-sm text-slate-600">
                                    <i class="fa-solid fa-phone text-xs mr-1 opacity-40"></i> <?= h($teacher['contact']); ?>
                                </td>
                                <td class="px-4 py-5 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="edit_teacher.php?id=<?= $teacher['id'] ?>" class="h-8 w-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </a>
                                        <a href="delete_teacher.php?id=<?= $teacher['id'] ?>" onclick="return confirm('Delete?')" class="h-8 w-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    const btnStudents = document.getElementById('btnStudents');
    const btnTeachers = document.getElementById('btnTeachers');
    const studentSection = document.getElementById('studentSection');
    const teacherSection = document.getElementById('teacherSection');

    btnStudents.addEventListener('click', () => {
        studentSection.classList.remove('hidden');
        teacherSection.classList.add('hidden');
        btnStudents.classList.add('tab-active');
        btnStudents.classList.remove('text-slate-400');
        btnTeachers.classList.remove('tab-active');
        btnTeachers.classList.add('text-slate-400');
    });

    btnTeachers.addEventListener('click', () => {
        teacherSection.classList.remove('hidden');
        studentSection.classList.add('hidden');
        btnTeachers.classList.add('tab-active');
        btnTeachers.classList.remove('text-slate-400');
        btnStudents.classList.remove('tab-active');
        btnStudents.classList.add('text-slate-400');
    });
</script>

</body>
</html>