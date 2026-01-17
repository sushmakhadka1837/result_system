<?php
session_start();
require_once 'db_config.php';

// --- ACTIONS: Publish / Unpublish Logic ---
if (isset($_POST['toggle_status'])) {
    $dept = intval($_POST['f_dept']);
    $batch = intval($_POST['f_batch']);
    $sem_id = $_POST['f_sem']; // 'all' or specific ID
    $new_status = intval($_POST['status']);

    // Update results table
    $sql = "UPDATE results r 
            JOIN students s ON r.student_id = s.id 
            SET r.published = $new_status 
            WHERE s.department_id = $dept AND s.batch_year = $batch";
    
    if ($sem_id !== 'all') {
        $sem_id_int = intval($sem_id);
        $sql .= " AND r.semester_id = $sem_id_int";
    }

    $conn->query($sql);

    // Update results_publish_status table
    if ($sem_id === 'all') {
        // Handle all semesters (1-8)
        for ($s = 1; $s <= 8; $s++) {
            $check = $conn->query("SELECT id FROM results_publish_status WHERE department_id = $dept AND semester_id = $s AND result_type = 'assessment'");
            
            if ($check->num_rows > 0) {
                // Update existing record
                if ($new_status == 1) {
                    $conn->query("UPDATE results_publish_status SET published = 1, published_at = NOW() WHERE department_id = $dept AND semester_id = $s AND result_type = 'assessment'");
                } else {
                    $conn->query("UPDATE results_publish_status SET published = 0 WHERE department_id = $dept AND semester_id = $s AND result_type = 'assessment'");
                }
            } else {
                // Insert new record if publishing
                if ($new_status == 1) {
                    $conn->query("INSERT INTO results_publish_status (department_id, semester_id, result_type, published, published_at) VALUES ($dept, $s, 'assessment', 1, NOW())");
                }
            }
        }
    } else {
        // Handle individual semester
        $sem_id_int = intval($sem_id);
        $check = $conn->query("SELECT id FROM results_publish_status WHERE department_id = $dept AND semester_id = $sem_id_int AND result_type = 'assessment'");
        
        if ($check->num_rows > 0) {
            // Update existing record
            if ($new_status == 1) {
                $conn->query("UPDATE results_publish_status SET published = 1, published_at = NOW() WHERE department_id = $dept AND semester_id = $sem_id_int AND result_type = 'assessment'");
            } else {
                $conn->query("UPDATE results_publish_status SET published = 0 WHERE department_id = $dept AND semester_id = $sem_id_int AND result_type = 'assessment'");
            }
        } else {
            // Insert new record if publishing
            if ($new_status == 1) {
                $conn->query("INSERT INTO results_publish_status (department_id, semester_id, result_type, published, published_at) VALUES ($dept, $sem_id_int, 'assessment', 1, NOW())");
            }
        }
    }

    $msg = "Action completed: " . ($new_status ? "Published" : "Unpublished");
}

// Filter Values
$f_dept = isset($_GET['dept']) ? intval($_GET['dept']) : null;
$f_batch = isset($_GET['batch']) ? intval($_GET['batch']) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin View Assessment Result</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-4 md:p-8 font-sans">
    <div class="max-w-7xl mx-auto">
        
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-800 uppercase italic leading-none">Results Dashboard</h1>
                <p class="text-slate-500 text-sm mt-2 font-medium uppercase tracking-widest">Manage & Publish Assessment Results</p>
            </div>
            <a href="admin_assessment_manager.php" class="bg-white border-2 border-slate-800 px-6 py-2 rounded-xl font-bold hover:bg-slate-800 hover:text-white transition shadow-sm">← Back to Manager</a>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400 ml-2 mb-2 block">Select Department</label>
                    <select name="dept" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition" required>
                        <option value="">-- Choose Department --</option>
                        <?php 
                        $depts = $conn->query("SELECT * FROM departments");
                        while($d = $depts->fetch_assoc()) {
                            $selected = ($f_dept == $d['id']) ? 'selected' : '';
                            echo "<option value='{$d['id']}' $selected>{$d['department_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400 ml-2 mb-2 block">Batch Year</label>
                    <input type="number" name="batch" value="<?= $f_batch ?>" placeholder="e.g. 2079" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition" required>
                </div>
                <button type="submit" class="bg-indigo-600 text-white p-4 rounded-2xl font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition">Filter Results</button>
            </form>
        </div>

        <?php if($f_dept && $f_batch): ?>
            
            <div class="bg-slate-800 p-6 rounded-3xl mb-10 flex flex-col md:flex-row justify-between items-center gap-6 shadow-2xl">
                <div>
                    <h2 class="text-white text-xl font-black uppercase">Batch <?= $f_batch ?> Master Control</h2>
                    <p class="text-slate-400 text-xs">This affects all semesters for the selected department and batch.</p>
                </div>
                <div class="flex gap-4">
                    <form method="POST" class="inline">
                        <input type="hidden" name="f_dept" value="<?= $f_dept ?>">
                        <input type="hidden" name="f_batch" value="<?= $f_batch ?>">
                        <input type="hidden" name="f_sem" value="all">
                        <input type="hidden" name="status" value="1">
                        <button type="submit" name="toggle_status" class="bg-emerald-500 text-white px-8 py-3 rounded-xl font-bold text-sm hover:bg-emerald-600 shadow-lg shadow-emerald-900/20 transition">🚀 Publish All</button>
                    </form>
                    <form method="POST" class="inline">
                        <input type="hidden" name="f_dept" value="<?= $f_dept ?>">
                        <input type="hidden" name="f_batch" value="<?= $f_batch ?>">
                        <input type="hidden" name="f_sem" value="all">
                        <input type="hidden" name="status" value="0">
                        <button type="submit" name="toggle_status" class="bg-red-500 text-white px-8 py-3 rounded-xl font-bold text-sm hover:bg-red-600 shadow-lg shadow-red-900/20 transition">🔒 Unpublish All</button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php 
                // Display 8 Semesters (or fetch from DB)
                for($sem = 1; $sem <= 8; $sem++):
                    $stats = $conn->query("SELECT COUNT(*) as total FROM results r 
                                         JOIN students s ON r.student_id = s.id 
                                         WHERE s.department_id = $f_dept AND s.batch_year = $f_batch AND r.semester_id = $sem")->fetch_assoc();
                    $has_data = ($stats['total'] > 0);
                    
                    // Check publish status from results_publish_status table
                    $pub_check = $conn->query("SELECT published FROM results_publish_status WHERE department_id = $f_dept AND semester_id = $sem AND result_type = 'assessment'");
                    $is_published = ($pub_check && $pub_check->num_rows > 0 && $pub_check->fetch_assoc()['published'] == 1);
                ?>
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between hover:border-indigo-300 transition group">
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-[10px] font-black bg-slate-100 text-slate-500 px-3 py-1 rounded-full uppercase">Semester <?= $sem ?></span>
                            <?php if($has_data): ?>
                                <span class="w-2 h-2 rounded-full <?= $is_published ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300' ?>"></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 mb-1">Sem <?= $sem ?></h3>
                        <p class="text-slate-400 text-xs font-bold"><?= $stats['total'] ?> RECORDS FOUND</p>
                    </div>

                    <div class="mt-8 space-y-3">
                        <?php if($has_data): ?>
                            <a href="view_detailed_results.php?dept=<?= $f_dept ?>&batch=<?= $f_batch ?>&sem=<?= $sem ?>" 
                               class="block w-full text-center py-3 rounded-xl border-2 border-slate-100 text-slate-600 font-bold text-xs hover:bg-slate-50 transition">
                               🔍 View List
                            </a>
                            <form method="POST">
                                <input type="hidden" name="f_dept" value="<?= $f_dept ?>">
                                <input type="hidden" name="f_batch" value="<?= $f_batch ?>">
                                <input type="hidden" name="f_sem" value="<?= $sem ?>">
                                <input type="hidden" name="status" value="<?= $is_published ? 0 : 1 ?>">
                                <button type="submit" name="toggle_status" 
                                        class="w-full py-3 rounded-xl font-black text-[10px] uppercase tracking-tighter transition shadow-sm
                                        <?= $is_published ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-emerald-600 text-white hover:bg-emerald-700' ?>">
                                    <?= $is_published ? '🛑 Unpublish Results' : '✅ Publish Results' ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="py-10 text-center border-2 border-dashed border-slate-100 rounded-2xl text-slate-300 text-[10px] font-bold uppercase">
                                No Data Uploaded
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

        <?php else: ?>
            <div class="bg-white py-32 rounded-3xl border-2 border-dashed border-slate-200 text-center">
                <div class="bg-slate-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                </div>
                <h2 class="text-slate-800 font-black uppercase text-lg italic">Select Filters</h2>
                <p class="text-slate-400 text-sm max-w-xs mx-auto">Choose a Department and Batch to manage assessment results and publications.</p>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>