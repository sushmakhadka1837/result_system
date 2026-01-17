<?php
session_start();
require 'db_config.php';

// Admin login check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Fetch teachers ordered by Employee ID
$teachers = $conn->query("SELECT id, full_name, email, employee_id, contact FROM teachers ORDER BY employee_id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers | PEC RMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 min-h-screen">

<div class="flex">
    <aside class="w-64 bg-slate-900 text-slate-300 min-h-screen hidden lg:block fixed">
        <div class="p-6 border-b border-slate-800 text-white font-bold text-xl flex items-center gap-2">
            <i class="fa-solid fa-chalkboard-user text-indigo-500"></i> RMS Admin
        </div>
        <nav class="p-4 space-y-2 mt-4">
            <a href="admin_dashboard.php" class="block p-3 rounded-xl hover:bg-slate-800 transition">Dashboard</a>
            <a href="manage_students.php" class="block p-3 rounded-xl hover:bg-slate-800 transition">Manage Students</a>
            <a href="manage_teachers.php" class="block p-3 rounded-xl bg-indigo-600 text-white shadow-lg font-bold">Manage Teachers</a>
            <a href="manage_subjects.php" class="block p-3 rounded-xl hover:bg-slate-800 transition">Subjects</a>
        </nav>
    </aside>

    <main class="flex-1 lg:ml-64 p-8 pb-20">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Faculty Management</h2>
                    <p class="text-slate-500">Teacher haru ko details ra subject assignment yaha bata manage garnuhos.</p>
                </div>
                <a href="add_teacher.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-bold transition flex items-center gap-2 shadow-lg shadow-indigo-100">
                    <i class="fa-solid fa-user-plus"></i> Add New Teacher
                </a>
            </div>

            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 text-slate-400 text-xs font-bold uppercase tracking-wider">
                            <th class="py-5 px-6">ID</th>
                            <th class="py-5 px-6">Teacher Details</th>
                            <th class="py-5 px-6">Contact Info</th>
                            <th class="py-5 px-6 text-center">Status / Assignment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php 
                        $i = 1;
                        while ($t = $teachers->fetch_assoc()): 
                            $tid = $t['id'];
                            // Efficient check for assigned subjects
                            $assign_check = $conn->query("SELECT id FROM teacher_subjects WHERE teacher_id = $tid LIMIT 1");
                            $has_subjects = ($assign_check && $assign_check->num_rows > 0);
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-5 px-6 font-mono text-sm text-slate-400">#<?= htmlspecialchars($t['employee_id']) ?></td>
                            <td class="py-5 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold">
                                        <?= strtoupper(substr($t['full_name'], 0, 1)) ?>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800"><?= htmlspecialchars($t['full_name']) ?></span>
                                        <span class="text-xs text-slate-400"><?= htmlspecialchars($t['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-5 px-6 text-slate-600 text-sm italic">
                                <i class="fa-solid fa-phone text-xs mr-1 text-slate-300"></i> <?= htmlspecialchars($t['contact']) ?>
                            </td>
                            <td class="py-5 px-6">
                                <div class="flex justify-center">
                                    <?php if ($has_subjects): ?>
                                        <a href="assigned_subjects.php?teacher_id=<?= $tid ?>" class="flex items-center gap-2 bg-emerald-50 text-emerald-600 px-4 py-2 rounded-lg text-xs font-bold border border-emerald-100 hover:bg-emerald-600 hover:text-white transition-all">
                                            <i class="fa-solid fa-book-open"></i> Show Assigned
                                        </a>
                                    <?php else: ?>
                                        <a href="assign_subject.php?teacher_id=<?= $tid ?>" class="flex items-center gap-2 bg-indigo-50 text-indigo-600 px-4 py-2 rounded-lg text-xs font-bold border border-indigo-100 hover:bg-indigo-600 hover:text-white transition-all">
                                            <i class="fa-solid fa-plus-circle"></i> Assign Subject
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<footer class="lg:ml-64 bg-slate-900 text-slate-400 py-10">
    <div class="max-w-6xl mx-auto px-8 grid grid-cols-1 md:grid-cols-3 gap-10">
        <div>
            <h5 class="text-white font-bold mb-4">Hamro Result</h5>
            <p class="text-sm leading-relaxed">Phirke Pokhara-8, Nepal<br>Phone: 061 581209<br>Email: info@pec.edu.np</p>
        </div>
        <div>
            <h5 class="text-white font-bold mb-4">External Links</h5>
            <div class="grid grid-cols-1 gap-2 text-sm">
                <a href="https://pu.edu.np/" class="hover:text-indigo-400">Pokhara University</a>
                <a href="https://nec.gov.np/" class="hover:text-indigo-400">Nepal Engineering Council</a>
            </div>
        </div>
        <div class="text-md-right">
            <h5 class="text-white font-bold mb-4">Social Presence</h5>
            <div class="flex gap-4">
                <a href="https://facebook.com/PECPoU" class="h-10 w-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-indigo-600 transition"><i class="fa-brands fa-facebook-f text-white"></i></a>
                <a href="https://instagram.com/pec.pkr/" class="h-10 w-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-rose-600 transition"><i class="fa-brands fa-instagram text-white"></i></a>
            </div>
        </div>
    </div>
    <div class="text-center mt-10 pt-6 border-t border-slate-800 text-xs">
        &copy; 2026 Hamro Result. Phirke, Pokhara.
    </div>
</footer>

</body>
</html>