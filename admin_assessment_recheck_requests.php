<?php
session_start();
require 'db_config.php';
require_once 'notification_helper.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$filter_status = trim($_GET['status'] ?? 'teacher_recommended');
$allowed_status = ['pending', 'teacher_recommended', 'teacher_rejected', 'approved', 'rejected', 'all'];
if (!in_array($filter_status, $allowed_status, true)) {
    $filter_status = 'teacher_recommended';
}

$table_exists = false;
$table_check = $conn->query("SHOW TABLES LIKE 'assessment_recheck_requests'");
if ($table_check && $table_check->num_rows > 0) {
    $table_exists = true;
}

$flash = '';
$flash_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_decision']) && $table_exists) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    $admin_remark = trim($_POST['admin_remark'] ?? '');

    if ($request_id <= 0 || !in_array($new_status, ['approved', 'rejected'], true)) {
        $flash = 'Invalid admin action.';
        $flash_type = 'danger';
    } elseif ($admin_remark === '') {
        $flash = 'Admin remark is required.';
        $flash_type = 'danger';
    } else {
        $up_stmt = $conn->prepare("UPDATE assessment_recheck_requests SET status = ?, admin_remark = ?, admin_reviewed_at = NOW() WHERE id = ? AND status IN ('teacher_recommended', 'teacher_rejected')");
        $up_stmt->bind_param('ssi', $new_status, $admin_remark, $request_id);
        if ($up_stmt->execute() && $up_stmt->affected_rows > 0) {
            $notify_stmt = $conn->prepare("SELECT rr.student_id, rr.request_type, rr.status, rr.admin_remark, s.full_name AS student_name, sm.subject_name, sm.subject_code
                                           FROM assessment_recheck_requests rr
                                           JOIN students s ON s.id = rr.student_id
                                           JOIN subjects_master sm ON sm.id = rr.subject_id
                                           WHERE rr.id = ? LIMIT 1");
            $notify_stmt->bind_param('i', $request_id);
            $notify_stmt->execute();
            $notify_row = $notify_stmt->get_result()->fetch_assoc();
            if ($notify_row) {
                notifyStudentRecheckStatusUpdate(
                    (int)$notify_row['student_id'],
                    (string)($notify_row['student_name'] ?? ''),
                    (string)($notify_row['subject_name'] ?? 'Subject'),
                    (string)($notify_row['subject_code'] ?? ''),
                    (string)($notify_row['request_type'] ?? ''),
                    (string)($notify_row['status'] ?? $new_status),
                    (string)($notify_row['admin_remark'] ?? $admin_remark),
                    'admin',
                    $conn
                );
            }
            $flash = 'Final decision saved.';
            $flash_type = 'success';
        } else {
            $flash = 'Request cannot be updated (already finalized or not reviewed by teacher).';
            $flash_type = 'warning';
        }
    }
}

$requests = [];
if ($table_exists) {
    $where = '1=1';
    $types = '';
    $params = [];

    if ($filter_status !== 'all') {
        $where .= ' AND rr.status = ?';
        $types .= 's';
        $params[] = $filter_status;
    }

    $sql = "SELECT rr.*, s.full_name AS student_name, s.symbol_no, d.department_name,
                   sm.subject_name, sm.subject_code, t.full_name AS teacher_name
            FROM assessment_recheck_requests rr
            JOIN students s ON s.id = rr.student_id
            JOIN departments d ON d.id = rr.department_id
            JOIN subjects_master sm ON sm.id = rr.subject_id
            LEFT JOIN teachers t ON t.id = rr.assigned_teacher_id
            WHERE {$where}
            ORDER BY rr.requested_at DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Recheck Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800">
<div class="flex min-h-screen">
    <aside class="w-72 bg-slate-900 text-slate-300 hidden md:flex flex-col fixed h-full">
        <div class="p-6 border-b border-slate-800 flex items-center gap-3">
            <div class="h-8 w-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white"><i class="fa-solid fa-graduation-cap"></i></div>
            <span class="text-xl font-bold text-white uppercase tracking-tighter">RMS Admin</span>
        </div>
        <nav class="p-4 space-y-1">
            <a href="admin_dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition-all text-slate-400"><i class="fa-solid fa-house w-5"></i> Dashboard</a>
            <a href="admin_publish_results.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 transition-all text-slate-400"><i class="fa-solid fa-paper-plane w-5"></i> Publish Results</a>
            <a href="admin_assessment_recheck_requests.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/20"><i class="fa-solid fa-rotate-left w-5"></i> Recheck Requests</a>
        </nav>
    </aside>

    <main class="flex-1 md:ml-72 p-6 md:p-10">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <h1 class="text-2xl font-bold">Assessment Re-total / Recheck Requests</h1>
            <a href="admin_dashboard.php" class="px-4 py-2 bg-white border rounded-xl text-sm hover:bg-slate-100"><i class="fa-solid fa-arrow-left mr-1"></i>Back</a>
        </div>

        <?php if (!$table_exists): ?>
            <div class="mb-4 p-4 rounded-xl bg-amber-50 text-amber-700 border border-amber-200">Table missing. Run <strong>create_assessment_recheck_requests_table.sql</strong>.</div>
        <?php else: ?>
            <?php if ($flash !== ''): ?>
                <div class="mb-4 p-4 rounded-xl <?= $flash_type === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($flash_type === 'danger' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') ?>"><?= htmlspecialchars($flash) ?></div>
            <?php endif; ?>

            <div class="mb-4 bg-white border border-slate-200 rounded-2xl p-3">
                <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider mb-2">Filter by status</div>
                <div class="flex flex-wrap gap-2">
                <?php
                $tabs = [
                    'teacher_recommended' => 'Teacher Recommended',
                    'teacher_rejected' => 'Teacher Rejected',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'pending' => 'Pending',
                    'all' => 'All'
                ];
                foreach ($tabs as $key => $label):
                    $active = ($filter_status === $key);
                ?>
                    <a href="?status=<?= urlencode($key) ?>" class="px-3 py-2 rounded-lg text-sm font-semibold <?= $active ? 'bg-slate-900 text-white' : 'bg-white border text-slate-600 hover:bg-slate-100' ?>"><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <?php if (!empty($requests)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 text-left">Student</th>
                                    <th class="px-4 py-3 text-left">Dept/Sem</th>
                                    <th class="px-4 py-3 text-left">Subject</th>
                                    <th class="px-4 py-3 text-left">Type</th>
                                    <th class="px-4 py-3 text-left">Teacher Review</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">Admin Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                            <?php foreach ($requests as $row): ?>
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold"><?= htmlspecialchars($row['student_name']) ?></div>
                                        <div class="text-xs text-slate-500"><?= htmlspecialchars($row['symbol_no']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        <div><?= htmlspecialchars($row['department_name']) ?></div>
                                        <div>Batch <?= (int)$row['batch_year'] ?> | Sem <?= (int)$row['semester_order'] ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div class="font-semibold"><?= htmlspecialchars($row['subject_name']) ?></div>
                                        <div class="text-slate-500"><?= htmlspecialchars($row['subject_code']) ?></div>
                                    </td>
                                    <td class="px-4 py-3"><?= htmlspecialchars(ucfirst($row['request_type'])) ?></td>
                                    <td class="px-4 py-3 text-xs">
                                        <div><strong><?= htmlspecialchars($row['teacher_name'] ?? '-') ?></strong></div>
                                        <div class="text-slate-600 mt-1"><?= !empty($row['teacher_remark']) ? nl2br(htmlspecialchars($row['teacher_remark'])) : '-' ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php
                                        $st = strtolower($row['status']);
                                        $badge = 'bg-slate-200 text-slate-700';
                                        if (in_array($st, ['teacher_recommended', 'approved'], true)) $badge = 'bg-emerald-100 text-emerald-700';
                                        if (in_array($st, ['teacher_rejected', 'rejected'], true)) $badge = 'bg-rose-100 text-rose-700';
                                        if ($st === 'pending') $badge = 'bg-blue-100 text-blue-700';
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-bold <?= $badge ?>"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($st))) ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if (in_array($row['status'], ['teacher_recommended', 'teacher_rejected'], true)): ?>
                                            <form method="post" class="space-y-2">
                                                <input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>">
                                                <select name="new_status" class="w-full border rounded-lg px-2 py-1 text-sm" required>
                                                    <option value="">Select</option>
                                                    <option value="approved">Approve</option>
                                                    <option value="rejected">Reject</option>
                                                </select>
                                                <textarea name="admin_remark" rows="2" class="w-full border rounded-lg px-2 py-1 text-sm" placeholder="Admin remark (required)" required></textarea>
                                                <button type="submit" name="final_decision" class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700">Save</button>
                                            </form>
                                        <?php else: ?>
                                            <div class="text-xs text-slate-600"><?= !empty($row['admin_remark']) ? nl2br(htmlspecialchars($row['admin_remark'])) : 'Finalized / awaiting teacher review' ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center text-slate-500">No requests found.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
