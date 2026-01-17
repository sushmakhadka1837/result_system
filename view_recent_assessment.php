<?php
require_once 'db_config.php';

// --- DELETE LOGIC ---
if(isset($_GET['delete_id'])) {
    $rid = intval($_GET['delete_id']);
    // Resetting assessment fields instead of deleting row to keep UT data
    $conn->query("UPDATE results SET 
        assessment_raw = NULL, assessment_ai_marks = 0, 
        tutorial_marks = NULL, attendance_marks = 0, 
        total_attendance_days = 0, practical_marks = NULL, 
        final_theory = NULL, total_obtained = NULL, 
        letter_grade = NULL, grade_point = 0 
        WHERE id = $rid");
    header("Location: view_recent_assessment.php?msg=Deleted");
}

$results = $conn->query("SELECT r.*, s.full_name, s.symbol_no, sub.subject_name 
                        FROM results r
                        JOIN students s ON r.student_id = s.id
                        JOIN subjects_master sub ON r.subject_id = sub.id
                        WHERE r.assessment_raw IS NOT NULL 
                        ORDER BY r.id DESC LIMIT 50");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Assessments</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-black text-emerald-400 uppercase">Manage Recent Uploads</h1>
            <a href="admin_assessment_manager.php" class="bg-slate-700 px-6 py-2 rounded-full hover:bg-slate-600 transition">← Back</a>
        </div>

        <div class="bg-slate-800 rounded-2xl overflow-hidden border border-slate-700 shadow-2xl">
            <table class="w-full text-left">
                <thead class="bg-slate-700 text-slate-300 text-xs uppercase">
                    <tr>
                        <th class="p-4">Student</th>
                        <th class="p-4">Subject</th>
                        <th class="p-4 text-center">Ass(15)</th>
                        <th class="p-4 text-center text-emerald-400">Total(50)</th>
                        <th class="p-4 text-center">Grade</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <?php while($row = $results->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-700/50">
                        <td class="p-4">
                            <span class="font-bold"><?= $row['full_name'] ?></span>
                            <p class="text-xs text-slate-500"><?= $row['symbol_no'] ?></p>
                        </td>
                        <td class="p-4 text-slate-400"><?= $row['subject_name'] ?></td>
                        <td class="p-4 text-center font-mono"><?= number_format($row['assessment_ai_marks'], 2) ?></td>
                        <td class="p-4 text-center font-bold text-emerald-400"><?= number_format($row['total_obtained'], 2) ?></td>
                        <td class="p-4 text-center">
                            <span class="bg-slate-900 px-2 py-1 rounded border border-slate-600"><?= $row['letter_grade'] ?></span>
                        </td>
                        <td class="p-4 text-right space-x-2">
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)" class="text-blue-400 hover:text-blue-300 text-sm font-bold">EDIT</button>
                            <a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this assessment?')" class="text-red-500 hover:text-red-400 text-sm font-bold">DELETE</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4">
        <form action="update_single_assessment.php" method="POST" class="bg-slate-800 p-8 rounded-2xl w-full max-w-md border border-slate-600 shadow-2xl">
            <h2 class="text-xl font-bold mb-4 text-emerald-400">Update Marks</h2>
            <input type="hidden" name="id" id="modal_id">
            
            <div class="grid grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-xs text-slate-400 mb-1">Ass Raw (100)</label>
                    <input type="number" step="0.01" name="ass_raw" id="modal_ass" class="w-full bg-slate-900 border border-slate-700 p-2 rounded">
                </div>
                <div class="mb-4">
                    <label class="block text-xs text-slate-400 mb-1">Tutorial (5)</label>
                    <input type="number" step="0.01" name="tut" id="modal_tut" class="w-full bg-slate-900 border border-slate-700 p-2 rounded">
                </div>
                <div class="mb-4">
                    <label class="block text-xs text-slate-400 mb-1">Practical (20)</label>
                    <input type="number" step="0.01" name="prac" id="modal_prac" class="w-full bg-slate-900 border border-slate-700 p-2 rounded">
                </div>
                <div class="mb-4">
                    <label class="block text-xs text-slate-400 mb-1">Att. Days</label>
                    <input type="number" name="att_days" id="modal_att" class="w-full bg-slate-900 border border-slate-700 p-2 rounded">
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-3">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-4 py-2 text-slate-400 hover:text-white">Cancel</button>
                <button type="submit" class="bg-emerald-600 px-6 py-2 rounded-lg font-bold hover:bg-emerald-500">Save Changes</button>
            </div>
        </form>
    </div>

    <script>
    function openEditModal(data) {
        document.getElementById('modal_id').value = data.id;
        document.getElementById('modal_ass').value = data.assessment_raw;
        document.getElementById('modal_tut').value = data.tutorial_marks;
        document.getElementById('modal_prac').value = data.practical_marks;
        document.getElementById('modal_att').value = data.attendance_marks;
        document.getElementById('editModal').classList.remove('hidden');
    }
    </script>
</body>
</html>