<?php
session_start();
require_once 'db_config.php';

// 1. Admin Login Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$success = '';
$error = '';

// Handle Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $dept_id = intval($_GET['department'] ?? 0);
    $sem_id = intval($_GET['semester'] ?? 0);
    $syl_flag = ($_GET['syllabus'] === 'new') ? 1 : 0;
    
    if ($dept_id && $sem_id) {
        $syllabus_condition = ($syl_flag == 1) ? "sds.syllabus_flag = 1" : "(sds.syllabus_flag IS NULL)";
        $export_query = $conn->query("SELECT sm.subject_name, sm.subject_code, sm.credit_hours, sm.subject_type, sds.section
                                        FROM subjects_master sm
                                        JOIN subjects_department_semester sds ON sm.id = sds.subject_id
                                        WHERE sm.department_id=$dept_id AND sm.semester_id=$sem_id AND $syllabus_condition
                                        ORDER BY sm.subject_name ASC");
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="subjects_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Subject Name', 'Subject Code', 'Credit Hours', 'Type', 'Section']);
        
        while ($row = $export_query->fetch_assoc()) {
            fputcsv($output, [
                $row['subject_name'],
                $row['subject_code'],
                $row['credit_hours'],
                $row['subject_type'],
                $row['section'] ?: 'All'
            ]);
        }
        
        fclose($output);
        exit;
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'add') {
        $success = "Subject added successfully!";
    } elseif ($_GET['success'] === 'edit') {
        $success = "Subject updated successfully!";
    } elseif ($_GET['success'] === 'delete') {
        $success = "Subject deleted successfully!";
    } elseif ($_GET['success'] === 'copy') {
        $success = "Subjects copied for this syllabus.";
    }
}

// 2. Fetch Departments for Dropdown
$departments_result = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");

// 3. Handle Filters
$filter_department = $_GET['department'] ?? '';
$filter_semester   = $_GET['semester'] ?? '';
$filter_syllabus   = $_GET['syllabus'] ?? '';
$filter_section    = $_GET['section'] ?? '';
$search_query      = $_GET['search'] ?? '';

$syllabus_flag_filter = null;
$filter_batch = '';
if ($filter_syllabus === 'new') {
    $syllabus_flag_filter = 1;
    $filter_batch = 1; // canonical batch year for new syllabus
} elseif ($filter_syllabus === 'old') {
    $syllabus_flag_filter = 0;
    $filter_batch = 0; // canonical batch year for old syllabus
}

// Variable Initialization
$subjects_result = null; 
$semesters_result = null;

if ($filter_department) {
    // Corrected Semester Fetching Logic
    $stmt_sem = $conn->prepare("SELECT id, semester_name FROM semesters WHERE department_id=? ORDER BY semester_order ASC");
    $stmt_sem->bind_param("i", $filter_department);
    $stmt_sem->execute();
    $semesters_result = $stmt_sem->get_result();
}

// 4. Handle Add/Edit/Delete Actions
if (isset($_POST['add_subject']) || isset($_POST['edit_subject'])) {
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $credit_hours = floatval($_POST['credit_hours']);
    $department_id = intval($_POST['department_id']);
    $semester_id = intval($_POST['semester_id']);
    $section = trim($_POST['section']) ?: null;
    $is_elective = ($_POST['subject_type'] === 'Elective') ? 1 : 0;
    $subject_type = $_POST['subject_type'] ?? 'Regular';
    
    // Syllabus Logic: Use fixed batch values 1 for new, 0 for old
    $form_batch = intval($_POST['batch_year'] ?? 0);
    // Ensure batch_year is properly set from the hidden field (already 0 or 1)
    if ($form_batch !== 0 && $form_batch !== 1) {
        $form_batch = $filter_batch !== '' ? $filter_batch : 1;
    }
    // batch_year=1 gets syllabus_flag=1 (new), batch_year=0 gets syllabus_flag=NULL (old)
    $syllabus_bit = ($form_batch === 1) ? 1 : null;

    if (isset($_POST['add_subject'])) {
        // Check for duplicate subject_code in same department/semester
        $check_dup = $conn->prepare("SELECT id FROM subjects_master WHERE subject_code=? AND department_id=? AND semester_id=?");
        $check_dup->bind_param("sii", $subject_code, $department_id, $semester_id);
        $check_dup->execute();
        
        if ($check_dup->get_result()->num_rows > 0) {
            $error = "Subject with this code already exists in this department/semester!";
        } else {
            $stmt = $conn->prepare("INSERT INTO subjects_master (subject_name, subject_code, credit_hours, is_elective, subject_type, department_id, semester_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdisii", $subject_name, $subject_code, $credit_hours, $is_elective, $subject_type, $department_id, $semester_id);
            
            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                $current_syllabus = $syllabus_bit ? 'new' : 'old';
                $stmt_map = $conn->prepare("INSERT INTO subjects_department_semester (subject_id, department_id, semester, batch_year, section, syllabus_flag) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_map->bind_param("iiiiis", $new_id, $department_id, $semester_id, $form_batch, $section, $syllabus_bit);
                if ($stmt_map->execute()) {
                    // Redirect after successful insert to prevent duplicate submission
                    header("Location: manage_subjects.php?department={$department_id}&semester={$semester_id}&syllabus={$current_syllabus}&section={$filter_section}&success=add");
                    exit;
                } else {
                    $error = "Error adding subject mapping: " . $stmt_map->error;
                }
            } else {
                $error = "Error adding subject: " . $stmt->error;
            }
        }
    } else if (isset($_POST['edit_subject'])) {
        $stmt = $conn->prepare("UPDATE subjects_master SET subject_name=?, subject_code=?, credit_hours=?, is_elective=?, subject_type=? WHERE id=?");
        $stmt->bind_param("ssdisi", $subject_name, $subject_code, $credit_hours, $is_elective, $subject_type, $subject_id);
        
        if ($stmt->execute()) {
            $stmt_map = $conn->prepare("UPDATE subjects_department_semester SET batch_year=?, section=?, syllabus_flag=? WHERE subject_id=?");
            $stmt_map->bind_param("issi", $form_batch, $section, $syllabus_bit, $subject_id);
            if ($stmt_map->execute()) {
                // Redirect after successful update to prevent duplicate submission
                $current_syllabus = $syllabus_bit ? 'new' : 'old';
                header("Location: manage_subjects.php?department={$department_id}&semester={$semester_id}&syllabus={$current_syllabus}&section={$filter_section}&success=edit");
                exit;
            } else {
                $error = "Error updating subject mapping: " . $stmt_map->error;
            }
        } else {
            $error = "Error updating subject: " . $stmt->error;
        }
    }
}

// Copy subjects from previous batch into current filter batch (same syllabus type)
if (isset($_POST['copy_batch'])) {
    $target_batch = intval($_POST['target_batch'] ?? 0);
    $department_id = intval($_POST['department_id'] ?? 0);
    $semester_id = intval($_POST['semester_id'] ?? 0);

    if ($target_batch === 0 || $department_id === 0 || $semester_id === 0) {
        $error = "Invalid copy request.";
    } else {
        // target_batch is 0 (old) or 1 (new); set syllabus_bit accordingly
        $syllabus_bit = ($target_batch === 1) ? 1 : null;

        // Find the latest batch (any year) with the same syllabus flag for this dept/semester
        if ($syllabus_bit === 1) {
            $find_prev = $conn->prepare("SELECT DISTINCT batch_year FROM subjects_department_semester WHERE department_id=? AND semester=? AND syllabus_flag=1 ORDER BY batch_year DESC LIMIT 1");
            $find_prev->bind_param("ii", $department_id, $semester_id);
        } else {
            $find_prev = $conn->prepare("SELECT DISTINCT batch_year FROM subjects_department_semester WHERE department_id=? AND semester=? AND (syllabus_flag IS NULL) ORDER BY batch_year DESC LIMIT 1");
            $find_prev->bind_param("ii", $department_id, $semester_id);
        }
        $find_prev->execute();
        $res_prev = $find_prev->get_result();
        $prev = $res_prev->fetch_assoc();

        if (!$prev) {
            $error = "No batch with the same syllabus found to copy from.";
        } else {
            $source_batch = intval($prev['batch_year']);

            // Insert missing mappings for the target batch (avoid duplicates by subject + section)
            if ($syllabus_bit === 1) {
                $stmt_copy = $conn->prepare("INSERT INTO subjects_department_semester (subject_id, department_id, semester, batch_year, section, syllabus_flag)
                                             SELECT sds.subject_id, sds.department_id, sds.semester, ?, sds.section, ?
                                             FROM subjects_department_semester sds
                                             WHERE sds.department_id=? AND sds.semester=? AND sds.batch_year=? AND sds.syllabus_flag=1
                                               AND NOT EXISTS (
                                                   SELECT 1 FROM subjects_department_semester t
                                                   WHERE t.subject_id = sds.subject_id
                                                     AND t.department_id = sds.department_id
                                                     AND t.semester = sds.semester
                                                     AND t.batch_year = ?
                                                     AND ((t.section IS NULL AND sds.section IS NULL) OR t.section = sds.section)
                                               )");
                $stmt_copy->bind_param("iiiiii", $target_batch, $syllabus_bit, $department_id, $semester_id, $source_batch, $target_batch);
            } else {
                $stmt_copy = $conn->prepare("INSERT INTO subjects_department_semester (subject_id, department_id, semester, batch_year, section, syllabus_flag)
                                             SELECT sds.subject_id, sds.department_id, sds.semester, ?, sds.section, ?
                                             FROM subjects_department_semester sds
                                             WHERE sds.department_id=? AND sds.semester=? AND sds.batch_year=? AND (sds.syllabus_flag IS NULL OR sds.syllabus_flag=0)
                                               AND NOT EXISTS (
                                                   SELECT 1 FROM subjects_department_semester t
                                                   WHERE t.subject_id = sds.subject_id
                                                     AND t.department_id = sds.department_id
                                                     AND t.semester = sds.semester
                                                     AND t.batch_year = ?
                                                     AND ((t.section IS NULL AND sds.section IS NULL) OR t.section = sds.section)
                                               )");
                $stmt_copy->bind_param("iiiiii", $target_batch, $syllabus_bit, $department_id, $semester_id, $source_batch, $target_batch);
            }

            if ($stmt_copy->execute()) {
                $syllabus_param = $syllabus_bit ? 'new' : 'old';
                header("Location: manage_subjects.php?department={$department_id}&semester={$semester_id}&syllabus={$syllabus_param}&section={$filter_section}&success=copy");
                exit;
            } else {
                $error = "Failed to copy subjects.";
            }
        }
    }
}

if (isset($_POST['delete_subject'])) {
    $subject_id = intval($_POST['subject_id']);
    
    // Delete from subjects_department_semester first (foreign key constraint)
    $del_map = $conn->query("DELETE FROM subjects_department_semester WHERE subject_id=$subject_id");
    
    // Delete from subjects_master
    $del_subject = $conn->query("DELETE FROM subjects_master WHERE id=$subject_id");
    
    if ($del_map && $del_subject) {
        // Redirect after successful delete to prevent duplicate submission
        header("Location: manage_subjects.php?department={$filter_department}&semester={$filter_semester}&syllabus={$filter_syllabus}&section={$filter_section}&success=delete");
        exit;
    } else {
        $error = "Error deleting subject.";
    }
}


if ($filter_department && $filter_semester && $syllabus_flag_filter !== null) {
    $department_id = intval($filter_department);
    $semester_id = intval($filter_semester);
    
    // For new batches, accept 1; for old, accept NULL or 0
    $syllabus_condition = ($syllabus_flag_filter == 1) ? "sds.syllabus_flag = 1" : "(sds.syllabus_flag IS NULL)";
    
    $where = "sm.department_id=$department_id AND sm.semester_id=$semester_id AND " . $syllabus_condition;

    if ($filter_section) {
        $where .= " AND (sds.section='" . $conn->real_escape_string($filter_section) . "' OR sds.section IS NULL)";
    }
    
    // Search filter
    if ($search_query) {
        $search_escaped = $conn->real_escape_string($search_query);
        $where .= " AND (sm.subject_name LIKE '%$search_escaped%' OR sm.subject_code LIKE '%$search_escaped%')";
    }

    // Pick latest batch entry per subject/section for this syllabus flag to avoid duplicates across years
    // Handle NULL batch_year by coalescing to 0 for comparison purposes
    $subjects_result = $conn->query("SELECT sm.*, sds.section, sds.syllabus_flag, sds.batch_year
                                    FROM subjects_master sm
                                    JOIN subjects_department_semester sds ON sm.id = sds.subject_id
                                    WHERE $where
                                      AND COALESCE(sds.batch_year, 0) = (
                                          SELECT COALESCE(MAX(sds2.batch_year), 0) FROM subjects_department_semester sds2
                                          WHERE sds2.subject_id = sds.subject_id
                                            AND sds2.department_id = sds.department_id
                                            AND sds2.semester = sds.semester
                                            AND ($syllabus_condition)
                                            AND ((sds2.section IS NULL AND sds.section IS NULL) OR sds2.section = sds.section)
                                      )
                                    ORDER BY sm.subject_name ASC");
    
    // Calculate statistics
    $stats = [];
    if ($subjects_result) {
        $stats_query = $conn->query("SELECT 
                                        COUNT(*) as total_subjects,
                                        SUM(CASE WHEN sm.subject_type='Regular' THEN 1 ELSE 0 END) as regular_count,
                                        SUM(CASE WHEN sm.subject_type='Elective' THEN 1 ELSE 0 END) as elective_count,
                                        SUM(CASE WHEN sm.subject_type='Project' THEN 1 ELSE 0 END) as project_count,
                                        SUM(sm.credit_hours) as total_credits
                                    FROM subjects_master sm
                                    JOIN subjects_department_semester sds ON sm.id = sds.subject_id
                                    WHERE $where");
        $stats = $stats_query->fetch_assoc();
        
        // Count assigned vs unassigned
        $assigned_query = $conn->query("SELECT COUNT(DISTINCT sm.id) as assigned_count
                                        FROM subjects_master sm
                                        JOIN subjects_department_semester sds ON sm.id = sds.subject_id
                                        JOIN teacher_subjects ts ON ts.subject_map_id = sm.id
                                        WHERE $where");
        $assigned_data = $assigned_query->fetch_assoc();
        $stats['assigned'] = $assigned_data['assigned_count'] ?? 0;
        $stats['unassigned'] = ($stats['total_subjects'] ?? 0) - $stats['assigned'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects | PEC Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 min-h-screen">

<div class="flex">
    <aside class="w-64 bg-slate-900 text-slate-300 min-h-screen hidden md:block fixed">
        <div class="p-6 border-b border-slate-800 text-white font-bold text-xl flex items-center gap-2">
            <i class="fa-solid fa-graduation-cap text-indigo-500"></i> PEC Hub
        </div>
        <nav class="p-4 space-y-2">
            <a href="admin_dashboard.php" class="block p-3 rounded-lg hover:bg-slate-800 transition">Dashboard</a>
            <a href="manage_teachers.php" class="block p-3 rounded-lg hover:bg-slate-800 transition">Manage Teachers</a>
            <a href="manage_subjects.php" class="block p-3 rounded-lg bg-indigo-600 text-white shadow-lg font-bold">Manage Subjects</a>
            <a href="manage_departments.php" class="block p-3 rounded-lg hover:bg-slate-800 transition">Departments</a>
        </nav>
    </aside>

    <main class="flex-1 md:ml-64 p-8">
        <h2 class="text-3xl font-bold text-slate-800 mb-8 tracking-tight">Subject Management</h2>

        <?php if($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-xl mb-6 flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-6 py-4 rounded-xl mb-6 flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($stats) && $filter_department && $filter_semester): ?>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition">
                <div class="text-2xl font-black text-indigo-600"><?= $stats['total_subjects'] ?? 0 ?></div>
                <div class="text-xs text-slate-400 font-bold uppercase mt-1">Total Subjects</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition">
                <div class="text-2xl font-black text-emerald-600"><?= $stats['total_credits'] ?? 0 ?></div>
                <div class="text-xs text-slate-400 font-bold uppercase mt-1">Credit Hours</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition">
                <div class="text-2xl font-black text-blue-600"><?= $stats['regular_count'] ?? 0 ?></div>
                <div class="text-xs text-slate-400 font-bold uppercase mt-1">Regular</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition">
                <div class="text-2xl font-black text-amber-600"><?= $stats['elective_count'] ?? 0 ?></div>
                <div class="text-xs text-slate-400 font-bold uppercase mt-1">Elective</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition">
                <div class="text-2xl font-black text-rose-600"><?= $stats['unassigned'] ?? 0 ?></div>
                <div class="text-xs text-slate-400 font-bold uppercase mt-1">Unassigned</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 mb-8">
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">Search & Filter</h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <select name="department" class="border p-4 rounded-2xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500" required onchange="this.form.submit()">
                    <option value="">Select Dept</option>
                    <?php $departments_result->data_seek(0); while($d=$departments_result->fetch_assoc()): ?>
                        <option value="<?= $d['id'] ?>" <?= ($filter_department==$d['id'])?'selected':'' ?>><?= htmlspecialchars($d['department_name']) ?></option>
                    <?php endwhile; ?>
                </select>

                <select name="semester" class="border p-4 rounded-2xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500" required>
                    <option value="">Select Sem</option>
                    <?php if($semesters_result): while($s=$semesters_result->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>" <?= ($filter_semester==$s['id'])?'selected':'' ?>><?= htmlspecialchars($s['semester_name']) ?></option>
                    <?php endwhile; endif; ?>
                </select>
                
                <select name="syllabus" class="border p-4 rounded-2xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500" required>
                    <option value="">Select Syllabus</option>
                    <option value="new" <?= ($filter_syllabus==='new')?'selected':'' ?>>New Batch (2023+)</option>
                    <option value="old" <?= ($filter_syllabus==='old')?'selected':'' ?>>Old Batch (<=2022)</option>
                </select>
                
                <select name="section" class="border p-4 rounded-2xl bg-slate-50 outline-none">
                    <option value="">All Sections</option>
                    <?php foreach(['A','B','C'] as $sec): ?>
                        <option value="<?= $sec ?>" <?= ($filter_section==$sec)?'selected':'' ?>><?= $sec ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="bg-indigo-600 text-white font-bold rounded-2xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">Apply Filter</button>
            </form>
        </div>

        <?php if($filter_department && $filter_semester && $syllabus_flag_filter !== null): ?>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mb-8">
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                <form method="GET" class="flex-1 max-w-md">
                    <input type="hidden" name="department" value="<?= $filter_department ?>">
                    <input type="hidden" name="semester" value="<?= $filter_semester ?>">
                    <input type="hidden" name="syllabus" value="<?= $filter_syllabus ?>">
                    <input type="hidden" name="section" value="<?= $filter_section ?>">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search by name or code..." class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-slate-200 focus:border-indigo-500 outline-none">
                    </div>
                </form>
                <div class="flex gap-3">
                    <a href="?department=<?= $filter_department ?>&semester=<?= $filter_semester ?>&syllabus=<?= $filter_syllabus ?>&section=<?= $filter_section ?>&export=csv" class="flex items-center gap-2 px-5 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition shadow-sm">
                        <i class="fa-solid fa-file-csv"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if($filter_department && $filter_semester && $syllabus_flag_filter !== null): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 bg-slate-50 border-b flex justify-between items-center">
                        <div>
                            <span class="font-bold text-slate-700 italic">Showing Subjects for <?= ($syllabus_flag_filter ? 'New' : 'Old') ?> Syllabus</span>
                            <?php if($search_query): ?>
                                <div class="text-xs text-slate-500 mt-1">Search: "<?= htmlspecialchars($search_query) ?>"</div>
                            <?php endif; ?>
                        </div>
                        <span class="text-[10px] px-3 py-1 bg-white border rounded-full font-black text-slate-400 uppercase"><?= ($syllabus_flag_filter ? 'New Syllabus' : 'Old Syllabus') ?></span>
                    </div>
                    <div class="p-4 flex justify-end border-b border-slate-100">
                        <form method="POST" class="flex items-center gap-3">
                            <input type="hidden" name="department_id" value="<?= $filter_department ?>">
                            <input type="hidden" name="semester_id" value="<?= $filter_semester ?>">
                            <input type="hidden" name="target_batch" value="<?= $filter_batch ?>">
                            <button type="submit" name="copy_batch" class="text-xs font-bold px-4 py-2 rounded-lg bg-slate-800 text-white hover:bg-black transition">
                                Copy subjects for this syllabus
                            </button>
                        </form>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-slate-400 uppercase text-[10px] font-bold">
                            <tr>
                                <th class="px-6 py-4">Subject Info</th>
                                <th class="px-6 py-4 text-center">Cr. Hr</th>
                                <th class="px-6 py-4 text-center">Type</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if($subjects_result && $subjects_result->num_rows > 0): ?>
                            <?php while($row=$subjects_result->fetch_assoc()): ?>
                            <?php
                            // Check if assigned to any teacher
                            $check_assigned = $conn->query("SELECT COUNT(*) as teacher_count FROM teacher_subjects WHERE subject_map_id={$row['id']}");
                            $assign_data = $check_assigned->fetch_assoc();
                            $is_assigned = ($assign_data['teacher_count'] > 0);
                            ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <?php if($row['subject_type']=='Project'): ?>
                                            <span class="text-lg">📊</span>
                                        <?php elseif($row['subject_type']=='Elective'): ?>
                                            <span class="text-lg">⚡</span>
                                        <?php else: ?>
                                            <span class="text-lg">📘</span>
                                        <?php endif; ?>
                                        <div>
                                            <div class="font-bold text-slate-800 leading-tight"><?= htmlspecialchars($row['subject_name']) ?></div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-1"><?= htmlspecialchars($row['subject_code'] ?: '-') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-lg text-xs font-bold"><?= $row['credit_hours'] ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-[9px] font-black uppercase px-2 py-1 rounded-md <?= $row['subject_type']=='Elective' ? 'bg-amber-100 text-amber-700' : ($row['subject_type']=='Project' ? 'bg-purple-100 text-purple-700' : 'bg-indigo-100 text-indigo-700') ?>">
                                            <?= htmlspecialchars($row['subject_type']) ?>
                                        </span>
                                        <?php if($is_assigned): ?>
                                            <span class="text-[8px] text-emerald-600 font-bold">✓ Assigned</span>
                                        <?php else: ?>
                                            <span class="text-[8px] text-rose-400 font-bold">⚠ Not Assigned</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-3">
                                        <button onclick="editSubject(<?= $row['id'] ?>, '<?= addslashes($row['subject_name']) ?>', '<?= addslashes($row['subject_code']) ?>', <?= $row['credit_hours'] ?>, '<?= $row['subject_type'] ?>')" class="text-indigo-400 hover:text-indigo-600 transition"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <form method="POST" onsubmit="return confirm('Delete this subject?')">
                                            <input type="hidden" name="subject_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_subject" class="text-slate-300 hover:text-rose-500 transition"><i class="fa-solid fa-trash-can"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="p-20 text-center text-slate-400 italic">No subjects found for this syllabus version.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-8 rounded-[2rem] shadow-xl border border-slate-100 sticky top-8 ring-8 ring-slate-50">
                    <h3 class="text-xl font-black mb-6 text-slate-800 flex items-center gap-2" id="form-title">
                        <i class="fa-solid fa-plus-circle text-indigo-500"></i> Add New
                    </h3>
                    <form method="POST" class="space-y-4" id="subject-form">
                        <input type="hidden" name="subject_id" id="subject_id">
                        
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Subject Name</label>
                            <input type="text" name="subject_name" class="w-full border-none p-4 rounded-2xl bg-slate-50 outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-500" id="subject_name" required>
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Code</label>
                            <input type="text" name="subject_code" class="w-full border-none p-4 rounded-2xl bg-slate-50 outline-none ring-1 ring-slate-200" id="subject_code">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Cr. Hours</label>
                                <input type="number" name="credit_hours" min="1" max="5" step="0.5" class="w-full border-none p-4 rounded-2xl bg-slate-50 outline-none ring-1 ring-slate-200" id="credit_hours" required>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Type</label>
                                <select name="subject_type" id="subject_type" class="w-full border-none p-4 rounded-2xl bg-slate-50 outline-none ring-1 ring-slate-200">
                                    <option value="Regular">Regular</option>
                                    <option value="Elective">Elective</option>
                                    <option value="Project">Project</option>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" name="batch_year" value="<?= $filter_batch ?>">
                        <input type="hidden" name="section" value="<?= $filter_section ?>">
                        <input type="hidden" name="department_id" value="<?= $filter_department ?>">
                        <input type="hidden" name="semester_id" value="<?= $filter_semester ?>">

                        <button type="submit" name="add_subject" id="submit-btn" class="w-full bg-indigo-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all mt-4">Save Subject</button>
                        <button type="button" onclick="location.reload()" class="w-full text-slate-400 text-xs font-bold mt-2">Cancel Edit</button>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div class="bg-indigo-50/50 rounded-[3rem] p-24 text-center border-4 border-dashed border-indigo-100">
                <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm">
                    <i class="fa-solid fa-layer-group text-indigo-300 text-3xl"></i>
                </div>
                <p class="text-indigo-900 font-bold text-xl mb-2 tracking-tight">Select Filter Criteria</p>
                <p class="text-indigo-400 text-sm italic">Please provide Department, Semester, and Syllabus to see subjects.</p>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
function editSubject(id, name, code, credit, type) {
    document.getElementById('form-title').innerHTML = "<i class='fa-solid fa-pen-to-square text-emerald-500'></i> Update Subject";
    document.getElementById('subject_id').value = id;
    document.getElementById('subject_name').value = name;
    document.getElementById('subject_code').value = code;
    document.getElementById('credit_hours').value = credit;
    document.getElementById('subject_type').value = type;
    
    let btn = document.getElementById('submit-btn');
    btn.name = "edit_subject";
    btn.innerText = "Update Subject Info";
    btn.classList.replace('bg-indigo-600', 'bg-emerald-600');
    window.scrollTo({top: 0, behavior: 'smooth'});
}
</script>

</body>
</html>