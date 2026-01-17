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

// Check for success message from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'add') {
        $success = "Subject added successfully!";
    } elseif ($_GET['success'] === 'edit') {
        $success = "Subject updated successfully!";
    } elseif ($_GET['success'] === 'delete') {
        $success = "Subject deleted successfully!";
    }
}

// 2. Fetch Departments for Dropdown
$departments_result = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");

// 3. Handle Filters
$filter_department = $_GET['department'] ?? '';
$filter_semester   = $_GET['semester'] ?? '';
$filter_batch      = $_GET['batch_year'] ?? '';
$filter_section    = $_GET['section'] ?? '';

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
    
    // Syllabus Logic: Batch Year input bata detect garne
    $form_batch = intval($_POST['batch_year'] ?? 0);
    // Ensure batch_year is properly set (use current year if empty)
    if ($form_batch === 0) {
        $form_batch = date('Y'); // Default to current year if not set
    }
    // New batch if > 2022, otherwise old batch
    $syllabus_bit = ($form_batch > 2022) ? 1 : 0;

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
                $stmt_map = $conn->prepare("INSERT INTO subjects_department_semester (subject_id, department_id, semester, batch_year, section, syllabus_flag) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_map->bind_param("iiiisi", $new_id, $department_id, $semester_id, $form_batch, $section, $syllabus_bit);
                if ($stmt_map->execute()) {
                    // Redirect after successful insert to prevent duplicate submission
                    header("Location: manage_subjects.php?department={$department_id}&semester={$semester_id}&batch_year={$form_batch}&success=add");
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
            $stmt_map->bind_param("isii", $form_batch, $section, $syllabus_bit, $subject_id);
            if ($stmt_map->execute()) {
                // Redirect after successful update to prevent duplicate submission
                header("Location: manage_subjects.php?department={$department_id}&semester={$semester_id}&batch_year={$form_batch}&success=edit");
                exit;
            } else {
                $error = "Error updating subject mapping: " . $stmt_map->error;
            }
        } else {
            $error = "Error updating subject: " . $stmt->error;
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
        header("Location: manage_subjects.php?department={$filter_department}&semester={$filter_semester}&batch_year={$filter_batch}&success=delete");
        exit;
    } else {
        $error = "Error deleting subject.";
    }
}

// 5. FETCH SUBJECTS (Strict Syllabus Wise Filtering)
if ($filter_department && $filter_semester) {
    $current_batch_year = intval($filter_batch);
    
    // Logic: 2022 vanda mathi batch vaye sds.syllabus_flag=1 khojne, natra sds.syllabus_flag=0 khojne
    $syllabus_condition = ($current_batch_year > 2022) ? "sds.syllabus_flag = 1" : "(sds.syllabus_flag = 0 OR sds.syllabus_flag IS NULL)";
    
    $where = "sm.department_id=" . intval($filter_department) . " AND sm.semester_id=" . intval($filter_semester) . " AND sds.batch_year=" . $current_batch_year . " AND " . $syllabus_condition;
    
    if ($filter_section) {
        $where .= " AND (sds.section='" . $conn->real_escape_string($filter_section) . "' OR sds.section IS NULL)";
    }

    $subjects_result = $conn->query("SELECT sm.*, sds.section, sds.syllabus_flag, sds.batch_year
                                    FROM subjects_master sm 
                                    JOIN subjects_department_semester sds ON sm.id=sds.subject_id 
                                    WHERE $where ORDER BY sm.subject_name ASC");
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

                <input type="number" name="batch_year" placeholder="Batch Year (e.g. 2024)" value="<?= htmlspecialchars($filter_batch) ?>" class="border p-4 rounded-2xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500" required>
                
                <select name="section" class="border p-4 rounded-2xl bg-slate-50 outline-none">
                    <option value="">All Sections</option>
                    <?php foreach(['A','B','C'] as $sec): ?>
                        <option value="<?= $sec ?>" <?= ($filter_section==$sec)?'selected':'' ?>><?= $sec ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="bg-indigo-600 text-white font-bold rounded-2xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">Apply Filter</button>
            </form>
        </div>

        <?php if($filter_department && $filter_semester && $filter_batch): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 bg-slate-50 border-b flex justify-between items-center">
                        <span class="font-bold text-slate-700 italic">Showing Subjects for Batch <?= $filter_batch ?></span>
                        <span class="text-[10px] px-3 py-1 bg-white border rounded-full font-black text-slate-400 uppercase"><?= (intval($filter_batch) > 2022) ? 'New Syllabus' : 'Old Syllabus' ?></span>
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
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 leading-tight"><?= htmlspecialchars($row['subject_name']) ?></div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-1"><?= htmlspecialchars($row['subject_code'] ?: '-') ?></div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-lg text-xs font-bold"><?= $row['credit_hours'] ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-[9px] font-black uppercase px-2 py-1 rounded-md <?= $row['subject_type']=='Elective' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' ?>">
                                        <?= htmlspecialchars($row['subject_type']) ?>
                                    </span>
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
                <p class="text-indigo-400 text-sm italic">Please provide Department, Semester, and Batch to see subjects.</p>
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