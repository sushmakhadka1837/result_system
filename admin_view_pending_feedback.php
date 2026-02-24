<?php
require 'db_config.php';
require_once 'functions.php';

// Manually verify pending feedback
if(isset($_GET['verify'])){
    $id = intval($_GET['verify']);
    
    // Get pending feedback
    $stmt = $conn->prepare("SELECT * FROM student_feedback_pending WHERE id = ? AND is_verified = 0");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0){
        $pending = $result->fetch_assoc();
        
        // Move to verified table
        $insert = $conn->prepare("INSERT INTO student_feedback (student_name, student_email, feedback, verified_at) VALUES (?,?,?, NOW())");
        $insert->bind_param("sss", $pending['student_name'], $pending['student_email'], $pending['feedback']);
        
        if($insert->execute()){
            // Mark as verified
            $update = $conn->prepare("UPDATE student_feedback_pending SET is_verified = 1, verified_at = NOW() WHERE id = ?");
            $update->bind_param("i", $id);
            $update->execute();
            
            header("Location: admin_view_pending_feedback.php?msg=verified");
            exit;
        }
    }
}

// Delete pending feedback
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM student_feedback_pending WHERE id='$id'");
    header("Location: admin_view_pending_feedback.php?msg=deleted");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Feedback | RMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .table-row-hover:hover { background-color: #f8fafc; transition: all 0.2s; }
        .expired { opacity: 0.5; background-color: #fef2f2; }
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
                <a href="manage_feedback.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition-all text-slate-400">
                    <i class="fa-solid fa-comments w-5"></i> Verified Feedback
                </a>
                <a href="admin_view_pending_feedback.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-600 text-white shadow-lg shadow-amber-500/20">
                    <i class="fa-solid fa-clock w-5"></i> Pending Feedback
                </a>
            </nav>
        </aside>

        <main class="flex-1 md:ml-72 p-6 md:p-10">
            <?php if(isset($_GET['msg'])): ?>
                <div class="mb-6 p-4 rounded-xl <?= $_GET['msg'] == 'verified' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?>">
                    <i class="fa-solid fa-<?= $_GET['msg'] == 'verified' ? 'check-circle' : 'trash' ?>"></i>
                    <?= $_GET['msg'] == 'verified' ? 'Feedback manually verified!' : 'Feedback deleted!' ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Unverified Feedback</h1>
                    <p class="text-slate-500">Email verify nagareko feedback haru (fake emails ho ki nai check gara)</p>
                </div>
                
                <div class="flex gap-3">
                    <a href="cleanup_unverified_feedback.php" 
                       onclick="return confirm('Delete all unverified feedback older than 7 days?');"
                       class="px-4 py-2 bg-rose-600 text-white rounded-xl hover:bg-rose-700 transition-all">
                        <i class="fa-solid fa-trash"></i> Cleanup Old
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">ID</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Student Info</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Message</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Submitted</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Link Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php
                            $q = $conn->query("SELECT * FROM student_feedback_pending WHERE is_verified = 0 ORDER BY created_at DESC");
                            if($q->num_rows > 0):
                                while($row = $q->fetch_assoc()):
                                    // Check if expired (24 hours)
                                    $created_time = strtotime($row['created_at']);
                                    $current_time = time();
                                    $hours_diff = ($current_time - $created_time) / 3600;
                                    $is_expired = $hours_diff > 24;
                            ?>
                            <tr class="table-row-hover transition-colors group <?= $is_expired ? 'expired' : '' ?>">
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
                                        <?= date('M d, Y H:i', strtotime($row['created_at'])) ?>
                                    </span>
                                    <div class="text-[10px] text-slate-400 mt-1">
                                        <?= round($hours_diff, 1) ?> hours ago
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <?php if($is_expired): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-50 text-red-600 text-[11px] font-bold gap-1">
                                            <i class="fa-solid fa-times-circle"></i> Expired
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-amber-50 text-amber-600 text-[11px] font-bold gap-1">
                                            <i class="fa-solid fa-clock"></i> Active (<?= 24 - round($hours_diff) ?>h left)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <div class="flex gap-2 justify-center">
                                        <a href="?verify=<?= $row['id'] ?>" 
                                           onclick="return confirm('Manually verify this feedback?');"
                                           class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-green-50 text-green-600 hover:bg-green-600 hover:text-white transition-all shadow-sm"
                                           title="Manually Verify">
                                            <i class="fa-solid fa-check text-sm"></i>
                                        </a>
                                        <a href="?delete=<?= $row['id'] ?>" 
                                           onclick="return confirm('Delete this unverified feedback?');"
                                           class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white transition-all shadow-sm"
                                           title="Delete">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <i class="fa-solid fa-circle-check text-5xl text-green-200"></i>
                                        <p class="text-slate-400 font-medium">Sabai feedback verify bhayo! No pending items.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex justify-between items-center text-xs text-slate-400">
                    <p>Total Unverified: <span class="font-bold text-amber-600"><?= $q->num_rows ?></span></p>
                    <p class="text-rose-500"><i class="fa-solid fa-info-circle"></i> Expired links cannot be verified by email</p>
                </div>
            </div>

            <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-xl">
                <h3 class="font-bold text-blue-900 mb-2"><i class="fa-solid fa-lightbulb"></i> Info:</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• Verification links expire after 24 hours</li>
                    <li>• Expired feedback can be manually verified or deleted</li>
                    <li>• Fake emails will never verify (can't access email)</li>
                    <li>• Use "Cleanup Old" to remove unverified feedback older than 7 days</li>
                </ul>
            </div>
        </main>
    </div>

</body>
</html>
