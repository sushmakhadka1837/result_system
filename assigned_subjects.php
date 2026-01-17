<?php
session_start();
require 'db_config.php';

// 1. Admin Login Check
if (!isset($_SESSION['admin_logged_in'])) { header("Location: admin_login.php"); exit; }

/* =========================
   TEACHER VALIDATION
========================= */
$teacher_id = intval($_GET['teacher_id'] ?? 0);
if (!$teacher_id) die("Invalid teacher");

$teacher_res = $conn->query("SELECT full_name FROM teachers WHERE id=$teacher_id");
$teacher = $teacher_res->fetch_assoc();
if (!$teacher) die("Teacher not found");

$message = "";

/* =========================
   DELETE & ASSIGN LOGIC
========================= */
if (isset($_GET['delete_assign_id'])) {
    $del_id = intval($_GET['delete_assign_id']);
    $conn->query("DELETE FROM teacher_subjects WHERE id=$del_id AND teacher_id=$teacher_id");
    header("Location: assign_subjects.php?teacher_id=$teacher_id&msg=deleted");
    exit;
}

if (isset($_POST['assign_subjects'])) {
    $department_id = intval($_POST['department_id']);
    $semester      = intval($_POST['semester']);
    $batch_year    = intval($_POST['batch_year']);
    $section       = !empty($_POST['section']) ? $_POST['section'] : NULL;
    $subject_ids   = $_POST['subject_ids'] ?? [];
    $syllabus_type = ($batch_year <= 2022) ? 'Old' : 'New';

    foreach ($subject_ids as $sid) {
        $sid = intval($sid);
        $check = $conn->query("SELECT id FROM teacher_subjects WHERE teacher_id=$teacher_id AND subject_map_id=$sid AND batch_year=$batch_year AND semester_id=$semester");
        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO teacher_subjects (teacher_id, subject_map_id, department_id, semester_id, batch_year, section, syllabus_type, mark_lock) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("iiiiiss", $teacher_id, $sid, $department_id, $semester, $batch_year, $section, $syllabus_type);
            $stmt->execute();
        }
    }
    $message = "<div class='bg-emerald-500 text-white p-4 rounded-xl mb-6 shadow-lg shadow-emerald-100 flex items-center gap-3'><i class='fa-solid fa-circle-check'></i> Subjects Assigned Successfully! ($syllabus_type Syllabus)</div>";
}

/* =========================
   FILTER LOGIC (Batch-wise Syllabus)
========================= */
$subjects = [];
if (isset($_POST['filter'])) {
    $f_dept = intval($_POST['department_id']);
    $f_sem = intval($_POST['semester']);
    $f_batch = intval($_POST['batch_year']);

    if ($f_batch <= 2022) {
        $sql = "SELECT sm.id, sm.subject_name, sm.subject_code FROM subjects_department_semester sds JOIN subjects_master sm ON sm.id = sds.subject_id WHERE sds.department_id=$f_dept AND sds.semester=$f_sem AND (sds.batch_year IS NULL OR sds.batch_year <= 2022)";
    } else {
        $sql = "SELECT sm.id, sm.subject_name, sm.subject_code FROM subjects_department_semester sds JOIN subjects_master sm ON sm.id = sds.subject_id WHERE sds.department_id=$f_dept AND sds.semester=$f_sem AND sds.batch_year > 2022";
    }
    $subjects = $conn->query($sql);
}

/* =========================
   FETCH CURRENT ASSIGNMENTS
========================= */
$my_subjects = $conn->query("SELECT ts.*, sm.subject_name, sm.subject_code, d.department_name FROM teacher_subjects ts JOIN subjects_master sm ON sm.id = ts.subject_map_id JOIN departments d ON d.id = ts.department_id WHERE ts.teacher_id = $teacher_id ORDER BY ts.batch_year DESC");
$departments_list = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Subjects | PEC RMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen">

<div class="flex">
    <aside class="w-64 bg-slate-900 text-slate-300 min-h-screen hidden lg:block fixed">
        <div class="p-6 border-b border-slate-800 text-white font-bold text-xl flex items-center gap-2">
            <i class="fa-solid fa-graduation-cap text-indigo-500"></i> RMS Admin
        </div>
        <nav class="p-4 space-y-2 mt-4">
            <a href="admin_dashboard.php" class="block p-3 rounded-xl hover:bg-slate-800 transition">Dashboard</a>
            <a href="manage_students.php" class="block p-3 rounded-xl hover:bg-slate-800 transition">Manage Students</a>
            <a href="manage_teachers.php" class="block p-3 rounded-xl bg-indigo-600 text-white shadow-lg font-bold">Manage Teachers</a>
            <a href="manage_subjects.php" class="block p-3 rounded-xl hover:bg-slate-800 transition">Subjects</a>
            <a href="logout.php" class="block p-3 rounded-xl hover:bg-rose-900/20 hover:text-rose-400 transition mt-10">Logout</a>
        </nav>
    </aside>

    <main class="flex-1 lg:ml-64 p-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-10">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Assign Subjects</h2>
                    <p class="text-slate-500">Faculty: <span class="font-bold text-indigo-600"><?= htmlspecialchars($teacher['full_name']) ?></span></p>
                </div>
                <a href="manage_teachers.php" class="h-12 w-12 flex items-center justify-center rounded-full bg-white shadow-sm border border-slate-200 hover:bg-slate-50 transition">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
            </div>

            <?= $message ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
                        <h3 class="font-bold text-slate-800 mb-6 flex items-center gap-2">
                            <i class="fa-solid fa-magnifying-glass text-indigo-500 text-sm"></i> Find Subjects
                        </h3>
                        <form method="POST" class="space-y-4">
                            <select name="department_id" id="dept" class="w-full p-4 bg-slate-50 border-none rounded-2xl outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-500 transition" required>
                                <option value="">Select Department</option>
                                <?php while($d = $departments_list->fetch_assoc()): ?>
                                    <option value="<?= $d['id'] ?>" data-sem="<?= $d['total_semesters'] ?>" <?= (isset($_POST['department_id']) && $_POST['department_id']==$d['id'])?'selected':'' ?>><?= $d['department_name'] ?></option>
                                <?php endwhile; ?>
                            </select>

                            <select name="semester" id="sem" class="w-full p-4 bg-slate-50 border-none rounded-2xl outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-500 transition" required>
                                <option value="">Semester</option>
                            </select>

                            <input type="number" name="batch_year" placeholder="Batch (e.g. 2024)" value="<?= $_POST['batch_year'] ?? '' ?>" class="w-full p-4 bg-slate-50 border-none rounded-2xl outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-500 transition" required>

                            <select name="section" class="w-full p-4 bg-slate-50 border-none rounded-2xl outline-none ring-1 ring-slate-200">
                                <option value="">Section (Optional)</option>
                                <option value="A">A</option><option value="B">B</option><option value="C">C</option>
                            </select>

                            <button name="filter" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-bold hover:bg-black transition shadow-lg shadow-slate-200">Filter Subjects</button>
                        </form>
                    </div>

                    <?php if (isset($_POST['filter'])): ?>
                    <div class="bg-indigo-600 p-8 rounded-[2rem] shadow-xl shadow-indigo-100">
                        <h3 class="font-bold text-white mb-6 flex items-center gap-2">
                            <i class="fa-solid fa-check-double"></i> Select Subjects
                        </h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="department_id" value="<?= $_POST['department_id'] ?>">
                            <input type="hidden" name="semester" value="<?= $_POST['semester'] ?>">
                            <input type="hidden" name="batch_year" value="<?= $_POST['batch_year'] ?>">
                            <input type="hidden" name="section" value="<?= $_POST['section'] ?>">

                            <?php if ($subjects && $subjects->num_rows > 0): ?>
                                <div class="space-y-3 mb-6 max-h-72 overflow-y-auto pr-2 custom-scrollbar">
                                    <?php while($s = $subjects->fetch_assoc()): 
                                        // Check if subject is already assigned
                                        $check_assigned = $conn->query("SELECT teacher_id FROM teacher_subjects WHERE subject_map_id={$s['id']} AND department_id={$_POST['department_id']} AND semester_id={$_POST['semester']} AND batch_year={$_POST['batch_year']}");
                                        $assigned_teacher_id = null;
                                        if($check_assigned && $check_assigned->num_rows > 0) {
                                            $assigned_row = $check_assigned->fetch_assoc();
                                            $assigned_teacher_id = $assigned_row['teacher_id'];
                                        }
                                    ?>
                                    <div class="flex items-center p-4 bg-indigo-500/30 border border-indigo-400/30 rounded-2xl">
                                        <?php if($assigned_teacher_id !== null): ?>
                                            <?php if($assigned_teacher_id == $teacher_id): ?>
                                                <span class="px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-lg">Your Subject</span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-lg">Assigned to Another Teacher</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <input type="checkbox" name="subject_ids[]" value="<?= $s['id'] ?>" class="w-5 h-5 rounded accent-white">
                                        <?php endif; ?>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-white leading-none"><?= $s['subject_name'] ?></div>
                                            <div class="text-[10px] text-indigo-200 mt-1 uppercase"><?= $s['subject_code'] ?></div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <button name="assign_subjects" class="w-full bg-white text-indigo-600 py-4 rounded-2xl font-black hover:bg-slate-50 transition shadow-lg">Assign Selected</button>
                            <?php else: ?>
                                <p class="text-indigo-100 text-sm italic">No subjects matching this syllabus/batch.</p>
                            <?php endif; ?>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="lg:col-span-8 bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 bg-slate-50/50 border-b flex justify-between items-center">
                        <h3 class="font-bold text-slate-800 tracking-tight italic">Assigned Subjects History</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400">
                                <tr>
                                    <th class="p-6">Subject / Department</th>
                                    <th class="p-6 text-center">Batch Info</th>
                                    <th class="p-6 text-center">Syllabus</th>
                                    <th class="p-6 text-center">Marks</th>
                                    <th class="p-6"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php while($r = $my_subjects->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="p-6">
                                        <div class="font-bold text-slate-800 leading-tight"><?= $r['subject_name'] ?></div>
                                        <div class="text-[11px] text-slate-400 mt-1 font-medium"><?= $r['department_name'] ?> (<?= $r['subject_code'] ?>)</div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <div class="inline-block px-3 py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-600">
                                            <?= $r['batch_year'] ?> / Sec <?= $r['section'] ?: 'All' ?>
                                        </div>
                                        <div class="text-[10px] text-indigo-500 font-bold mt-1 uppercase tracking-wider">Sem <?= $r['semester_id'] ?></div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black <?= ($r['syllabus_type'] == 'New') ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' ?>">
                                            <?= strtoupper($r['syllabus_type']) ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-center">
                                        <?php if($r['mark_lock']): ?>
                                            <i class="fa-solid fa-lock text-rose-400" title="Locked"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-lock-open text-emerald-400" title="Open"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-6 text-right">
                                        <a href="?teacher_id=<?= $teacher_id ?>&delete_assign_id=<?= $r['id'] ?>" onclick="return confirm('Remove assignment?')" class="text-slate-300 hover:text-rose-500 transition opacity-0 group-hover:opacity-100">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Dynamic Semester Selection
document.getElementById('dept').addEventListener('change', function() {
    const semCount = this.options[this.selectedIndex].getAttribute('data-sem');
    const semSelect = document.getElementById('sem');
    semSelect.innerHTML = '<option value="">Semester</option>';
    if(semCount) {
        for(let i=1; i<=semCount; i++) {
            semSelect.innerHTML += `<option value="${i}">${i}</option>`;
        }
    }
});

// For persistence after filter
<?php if(isset($_POST['semester'])): ?>
    window.onload = function() {
        document.getElementById('dept').dispatchEvent(new Event('change'));
        document.getElementById('sem').value = "<?= $_POST['semester'] ?>";
    };
<?php endif; ?>
</script>

</body>
</html>