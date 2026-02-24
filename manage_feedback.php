<?php
require 'db_config.php';
require_once 'functions.php'; // Dashboard ko functions load garna

// Delete feedback logic
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM student_feedback WHERE id='$id'");
    header("Location: manage_feedback.php?msg=deleted");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedback | RMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .table-row-hover:hover { background-color: #f8fafc; transition: all 0.2s; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <div class="flex min-h-screen">
        <aside class="w-72 bg-slate-900 text-slate-300 hidden md:flex flex-col fixed h-full">
            <div class="p-6 border-b border-slate-800 flex items-center gap-3">
                <div class="h-8 w-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white"><i class="fa-solid fa-graduation-cap"></i></div>
                <span class="text-xl font-bold text-white uppercase tracking-tighter">RMS Admin</span>
            </div>
            <nav class="p-4 space-y-1">
                <a href="admin_dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition-all text-slate-400">
                    <i class="fa-solid fa-house-chimney w-5"></i> Dashboard
                </a>
                <a href="manage_feedback.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/20">
                    <i class="fa-solid fa-comments w-5"></i> Verified Feedback
                </a>
                <a href="admin_view_pending_feedback.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition-all text-slate-400">
                    <i class="fa-solid fa-clock w-5"></i> Pending Feedback
                </a>
                </nav>
        </aside>

        <main class="flex-1 md:ml-72 p-6 md:p-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Verified Feedback</h1>
                    <p class="text-slate-500">Email verify bhayeko genuine feedback haru yaha herna sakincha.</p>
                </div>
                
                <div class="relative group w-full md:w-96">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="searchInput" 
                        placeholder="Search by name, email, or content..." 
                        class="w-full bg-white border border-slate-200 py-3 pl-10 pr-4 rounded-2xl shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                </div>
            </div>

            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="feedbackTable">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">ID</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Student Info</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Message</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Date</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php
                            $q = $conn->query("SELECT * FROM student_feedback ORDER BY created_at DESC");
                            if($q->num_rows > 0):
                                while($row = $q->fetch_assoc()):
                            ?>
                            <tr class="table-row-hover transition-colors group">
                                <td class="px-6 py-5 text-center text-sm font-semibold text-slate-400">#<?= $row['id'] ?></td>
                                <td class="px-6 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-slate-900 font-bold"><?= htmlspecialchars($row['student_name']) ?></span>
                                        <span class="text-slate-400 text-xs flex items-center gap-1">
                                            <i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($row['student_email']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="max-w-xs md:max-w-md">
                                        <p class="text-slate-600 text-sm italic leading-relaxed">
                                            "<?= htmlspecialchars($row['feedback']) ?>"
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-500 text-[11px] font-bold">
                                        <?= date('M d, Y', strtotime($row['created_at'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <?php if($row['verified_at']): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-50 text-green-600 text-[11px] font-bold gap-1">
                                            <i class="fa-solid fa-circle-check"></i> Verified
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-amber-50 text-amber-600 text-[11px] font-bold gap-1">
                                            <i class="fa-solid fa-clock"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <a href="?delete=<?= $row['id'] ?>" 
                                       onclick="return confirm('K tapai yo feedback delete garna chahanuhuncha?');"
                                       class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white transition-all shadow-sm"
                                       title="Delete Feedback">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <i class="fa-solid fa-inbox text-5xl text-slate-200"></i>
                                        <p class="text-slate-400 font-medium">Kunai feedback bhetiyena.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex justify-between items-center text-xs text-slate-400">
                    <p>Total Feedbacks Found: <span class="font-bold text-slate-600"><?= $q->num_rows ?></span></p>
                    <p>RMS Management System &bull; v1.0</p>
                </div>
            </div>
        </main>
    </div>

    <script>
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#feedbackTable tbody tr');
        
        rows.forEach(row => {
            // "No results" row lai skip garne logic
            if(row.cells.length < 5) return;

            const text = row.innerText.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = '';
                row.classList.add('animate-fade-in');
            } else {
                row.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>