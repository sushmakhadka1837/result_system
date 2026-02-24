<?php
require 'db_config.php';

// Handle batch assignment
if(isset($_POST['assign_batch'])) {
    $id = intval($_POST['entry_id']);
    $batch = $_POST['batch_value'];
    
    if($batch == 'NULL') {
        $conn->query("UPDATE subjects_department_semester SET batch_year = NULL WHERE id = $id");
    } else {
        $batch_int = intval($batch);
        $conn->query("UPDATE subjects_department_semester SET batch_year = $batch_int WHERE id = $id");
    }
    
    header("Location: batch_assignment_tool.php?msg=updated");
    exit;
}

// Handle bulk assignment by semester
if(isset($_POST['bulk_assign'])) {
    $semester = intval($_POST['semester']);
    $batch = $_POST['batch_choice'];
    
    if($batch == 'NULL') {
        $conn->query("UPDATE subjects_department_semester SET batch_year = NULL WHERE semester = $semester");
    } else {
        $batch_int = intval($batch);
        $conn->query("UPDATE subjects_department_semester SET batch_year = $batch_int WHERE semester = $semester");
    }
    
    header("Location: batch_assignment_tool.php?msg=bulk_updated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Assignment Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50">
    <div class="max-w-7xl mx-auto p-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900">📚 Subject Batch Assignment Tool</h1>
            <p class="text-slate-600">Old batch ra new batch ko subjects assign gara</p>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <p class="font-bold">✅ <?= $_GET['msg'] == 'bulk_updated' ? 'Bulk update successful!' : 'Batch assigned successfully!' ?></p>
            </div>
        <?php endif; ?>

        <!-- Bulk Assignment -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-slate-200">
            <h2 class="text-xl font-bold text-slate-800 mb-4">⚡ Bulk Assignment by Semester</h2>
            <form method="POST" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Semester</label>
                    <select name="semester" class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
                        <option value="">Select Semester</option>
                        <?php for($i=1; $i<=8; $i++): ?>
                            <option value="<?= $i ?>">Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Assign To</label>
                    <select name="batch_choice" class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
                        <option value="1">Old Batch (1 - Before 2023)</option>
                        <option value="2">New Batch (2 - 2023 onwards)</option>
                        <option value="NULL">Both Batches (NULL)</option>
                    </select>
                </div>
                <button type="submit" name="bulk_assign" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition">
                    <i class="fas fa-bolt"></i> Assign All
                </button>
            </form>
        </div>

        <!-- Individual Assignment -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-slate-800 text-white px-6 py-4">
                <h2 class="text-xl font-bold">🎯 Individual Subject Assignment</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase">Semester</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase">Current Batch</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase">Assign To</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php
                        $query = "SELECT sds.id, sds.semester, sds.batch_year, sm.subject_name, sm.subject_code 
                                  FROM subjects_department_semester sds
                                  JOIN subjects_master sm ON sds.subject_id = sm.id
                                  ORDER BY sds.semester ASC, sm.subject_name ASC";
                        $result = $conn->query($query);
                        
                        while($row = $result->fetch_assoc()):
                            $batch_display = $row['batch_year'] === null ? 'Both (NULL)' : ($row['batch_year'] == 1 ? 'Old (1)' : 'New (2)');
                            $badge_color = $row['batch_year'] === null ? 'bg-purple-100 text-purple-800' : ($row['batch_year'] == 1 ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800');
                        ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm text-slate-900"><?= $row['id'] ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-900"><?= htmlspecialchars($row['subject_name']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($row['subject_code']) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800">
                                        Sem <?= $row['semester'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $badge_color ?>">
                                        <?= $batch_display ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <form method="POST" class="flex gap-2 justify-center">
                                        <input type="hidden" name="entry_id" value="<?= $row['id'] ?>">
                                        <select name="batch_value" class="px-3 py-1 border border-slate-300 rounded text-sm focus:ring-2 focus:ring-indigo-500" required>
                                            <option value="1" <?= $row['batch_year'] == 1 ? 'selected' : '' ?>>Old (1)</option>
                                            <option value="2" <?= $row['batch_year'] == 2 ? 'selected' : '' ?>>New (2)</option>
                                            <option value="NULL" <?= $row['batch_year'] === null ? 'selected' : '' ?>>Both (NULL)</option>
                                        </select>
                                        <button type="submit" name="assign_batch" class="px-3 py-1 bg-indigo-600 text-white rounded text-sm font-bold hover:bg-indigo-700 transition">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <h3 class="font-bold text-blue-900 mb-2"><i class="fas fa-info-circle"></i> Batch Assignment Guide:</h3>
            <ul class="text-sm text-blue-800 space-y-1">
                <li><strong>Old Batch (1):</strong> Before 2023 syllabus - Purano subjects</li>
                <li><strong>New Batch (2):</strong> 2023 onwards syllabus - Naya subjects</li>
                <li><strong>Both (NULL):</strong> Common subjects for both batches (e.g., Basic Math, Physics)</li>
            </ul>
        </div>

        <div class="mt-4 flex justify-end gap-3">
            <a href="admin_assessment_manager.php" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">
                <i class="fas fa-arrow-left"></i> Back to Assessment Manager
            </a>
        </div>
    </div>
</body>
</html>
