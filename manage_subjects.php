<?php
session_start();
require_once 'db_config.php';

// Admin check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$success = '';
$error = '';

// Fetch departments
$departments_result = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");

// Fetch semesters according to selected department
$filter_department = $_GET['department'] ?? '';
$filter_semester   = $_GET['semester'] ?? '';
$filter_batch      = $_GET['batch_year'] ?? '';
$filter_section    = $_GET['section'] ?? '';

if ($filter_department) {
    $stmt_sem = $conn->prepare("SELECT * FROM semesters WHERE department_id=? ORDER BY semester_order ASC");
    $stmt_sem->bind_param("i", $filter_department);
    $stmt_sem->execute();
    $semesters_result = $stmt_sem->get_result();
} else {
    $semesters_result = $conn->query("SELECT * FROM semesters ORDER BY semester_order ASC");
}

// Handle Add Subject
if (isset($_POST['add_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $credit_hours = floatval($_POST['credit_hours']);
    $department_id = intval($_POST['department_id']);
    $semester_id = intval($_POST['semester_id']);
    $section = trim($_POST['section']) ?: null;
    $is_elective = isset($_POST['is_elective']) ? 1 : 0;
    $subject_type = $_POST['subject_type'] ?? 'Regular';
    $batch_year_input = $_POST['batch_year'] ?? '';
    $batch_year = ($batch_year_input !== '' && intval($batch_year_input) > 2022) ? 1 : null;

    if (empty($subject_name) || (!$is_elective && empty($subject_code)) || $credit_hours < 1) {
        $error = "All fields required!";
    } else {
        // Check duplicate for non-electives
        if (!$is_elective && $subject_code) {
            $stmt_chk = $conn->prepare("SELECT id FROM subjects_master WHERE subject_code=?");
            $stmt_chk->bind_param("s", $subject_code);
            $stmt_chk->execute();
            if ($stmt_chk->get_result()->num_rows > 0) {
                $error = "Subject code already exists!";
            }
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("INSERT INTO subjects_master (subject_name, subject_code, credit_hours, is_elective, subject_type, department_id, semester_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisii", $subject_name, $subject_code, $credit_hours, $is_elective, $subject_type, $department_id, $semester_id);
        if ($stmt->execute()) {
            $subject_id = $stmt->insert_id;

            $stmt_map = $conn->prepare("INSERT INTO subjects_department_semester (subject_id, department_id, semester, batch_year, section) VALUES (?, ?, ?, ?, ?)");
            $stmt_map->bind_param("iiiss", $subject_id, $department_id, $semester_id, $batch_year, $section);
            $stmt_map->execute();

            $success = "Subject added successfully!";
        } else {
            $error = "Failed to add subject!";
        }
    }
}

// Handle Edit Subject
if (isset($_POST['edit_subject'])) {
    $subject_id = intval($_POST['subject_id']);
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $credit_hours = floatval($_POST['credit_hours']);
    $is_elective = isset($_POST['is_elective']) ? 1 : 0;
    $subject_type = $_POST['subject_type'] ?? 'Regular';
    $section = trim($_POST['section']) ?: null;
    $batch_year_input = $_POST['batch_year'] ?? '';
    $batch_year = ($batch_year_input !== '' && intval($batch_year_input) > 2022) ? 1 : null;

    if ($subject_id && $subject_name && $credit_hours > 0) {
        $stmt = $conn->prepare("UPDATE subjects_master SET subject_name=?, subject_code=?, credit_hours=?, is_elective=?, subject_type=? WHERE id=?");
        $stmt->bind_param("ssissi", $subject_name, $subject_code, $credit_hours, $is_elective, $subject_type, $subject_id);
        $stmt->execute();

        $stmt_map = $conn->prepare("UPDATE subjects_department_semester SET batch_year=?, section=? WHERE subject_id=?");
        $stmt_map->bind_param("ssi", $batch_year, $section, $subject_id);
        $stmt_map->execute();

        $success = "Subject updated successfully!";
    } else {
        $error = "All fields required!";
    }
}

// Handle Delete Subject
if (isset($_POST['delete_subject'])) {
    $subject_id = intval($_POST['subject_id']);
    if ($subject_id) {
        $conn->query("DELETE FROM subjects_department_semester WHERE subject_id=$subject_id");
        $conn->query("DELETE FROM subjects_master WHERE id=$subject_id");
        $success = "Subject deleted successfully!";
    }
}

// Fetch subjects for filter
$subjects_result = [];
if ($filter_department && $filter_semester) {
    $where = "sm.department_id=".intval($filter_department)." AND sm.semester_id=".intval($filter_semester);
    if ($filter_batch !== '') {
        if (intval($filter_batch) > 2022) {
            $where .= " AND (sds.batch_year=1 OR sds.batch_year IS NULL)";
        } else {
            $where .= " AND (sds.batch_year=".intval($filter_batch)." OR sds.batch_year IS NULL)";
        }
    }
    if ($filter_section !== '') {
        $where .= " AND (sds.section='". $conn->real_escape_string($filter_section) ."' OR sds.section IS NULL)";
    }

    $stmt = $conn->prepare("SELECT sm.*, sds.section, sds.batch_year 
                            FROM subjects_master sm 
                            LEFT JOIN subjects_department_semester sds ON sm.id=sds.subject_id 
                            WHERE $where ORDER BY sm.subject_name ASC");
    $stmt->execute();
    $subjects_result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Subjects</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6 font-sans">
<div class="container mx-auto">
<h2 class="text-2xl mb-4 font-semibold">Manage Subjects</h2>

<?php if($success): ?><div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded"><?= $success ?></div><?php endif; ?>
<?php if($error): ?><div class="bg-red-100 text-red-800 px-4 py-2 mb-4 rounded"><?= $error ?></div><?php endif; ?>

<!-- Filter Form -->
<div class="bg-white p-4 rounded-lg shadow mb-6">
<form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
    <select name="department" class="border p-2 rounded" required onchange="this.form.submit()">
        <option value="">Select Department</option>
        <?php $departments_result->data_seek(0); while($d=$departments_result->fetch_assoc()): ?>
            <option value="<?= $d['id'] ?>" <?= ($filter_department==$d['id'])?'selected':'' ?>><?= htmlspecialchars($d['department_name']) ?></option>
        <?php endwhile; ?>
    </select>

    <select name="semester" class="border p-2 rounded" required>
        <option value="">Select Semester</option>
        <?php if($semesters_result): while($s=$semesters_result->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>" <?= ($filter_semester==$s['id'])?'selected':'' ?>><?= htmlspecialchars($s['semester_name']) ?></option>
        <?php endwhile; endif; ?>
    </select>

    <input type="number" name="batch_year" placeholder="Batch Year" value="<?= htmlspecialchars($filter_batch) ?>" class="border p-2 rounded">
    <select name="section" class="border p-2 rounded">
        <option value="">-- Section --</option>
        <?php foreach(['A','B','C'] as $sec): ?>
            <option value="<?= $sec ?>" <?= ($filter_section==$sec)?'selected':'' ?>><?= $sec ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
</form>
</div>

<?php if($filter_department && $filter_semester): ?>
<div class="bg-white p-6 rounded-lg shadow mb-6">
<h3 class="text-lg font-semibold mb-4">Subjects List</h3>
<?php if($subjects_result && $subjects_result->num_rows>0): ?>
<table class="w-full border-collapse mb-4">
<thead>
<tr class="bg-gray-200">
    <th>#</th>
    <th>Subject Name</th>
    <th>Code</th>
    <th>Credit Hours</th>
    <th>Type</th>
    <th>Section</th>
    <th>Batch Year</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($row=$subjects_result->fetch_assoc()): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($row['subject_name']) ?></td>
    <td><?= htmlspecialchars($row['subject_code'] ?? '-') ?></td>
    <td><?= $row['credit_hours'] ?></td>
    <td><?= htmlspecialchars($row['subject_type']) ?: ($row['is_elective']==1?'Elective':'Regular') ?></td>
    <td><?= $row['section']??'-' ?></td>
    <td><?= $row['batch_year']??'-' ?></td>
    <td>
        <!-- Edit Button -->
        <button onclick="editSubject(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['subject_name'])) ?>', '<?= htmlspecialchars(addslashes($row['subject_code'])) ?>', <?= $row['credit_hours'] ?>, <?= $row['is_elective'] ?>, '<?= $row['subject_type'] ?>', '<?= $row['section'] ?>', '<?= $row['batch_year'] ?>')" class="bg-yellow-500 text-white px-2 py-1 rounded">Edit</button>

        <!-- Delete Button -->
        <form method="POST" style="display:inline-block" onsubmit="return confirm('Are you sure?')">
            <input type="hidden" name="subject_id" value="<?= $row['id'] ?>">
            <button type="submit" name="delete_subject" class="bg-red-600 text-white px-2 py-1 rounded">Delete</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<p class="text-gray-600">No subjects found. You can add a new subject below.</p>
<?php endif; ?>
</div>

<!-- Add/Edit Subject Form -->
<div class="bg-white p-6 rounded-lg shadow mb-6">
<h3 class="text-lg font-semibold mb-4" id="form-title">Add New Subject</h3>
<form method="POST" class="grid grid-cols-1 gap-4" id="subject-form">
    <input type="hidden" name="subject_id" id="subject_id">
    <input type="text" name="subject_name" placeholder="Subject Name" class="border p-2 rounded" id="subject_name" required>
    <input type="text" name="subject_code" placeholder="Subject Code" class="border p-2 rounded" id="subject_code">
    <input type="number" name="credit_hours" min="1" max="4" placeholder="Credit Hours" class="border p-2 rounded" id="credit_hours" required>

    <label class="flex items-center space-x-2">
        <input type="checkbox" name="is_elective" value="1" id="is_elective"> <span>Elective Subject?</span>
    </label>

    <label>Subject Type:</label>
    <select name="subject_type" id="subject_type" class="border p-2 rounded">
        <option value="Regular">Regular</option>
        <option value="Elective">Elective</option>
        <option value="Project">Project</option>
    </select>
    
    <input type="hidden" name="batch_year" id="batch_year" value="<?= htmlspecialchars($filter_batch) ?>">
    <input type="hidden" name="section" id="section" value="<?= htmlspecialchars($filter_section) ?>">
    <input type="hidden" name="department_id" value="<?= htmlspecialchars($filter_department) ?>">
    <input type="hidden" name="semester_id" value="<?= htmlspecialchars($filter_semester) ?>">

    <button type="submit" name="add_subject" id="submit-btn" class="bg-blue-600 text-white px-4 py-2 rounded">Add Subject</button>
</form>
</div>

<script>
function editSubject(id, name, code, credit, elective, type, section, batch) {
    document.getElementById('form-title').innerText = "Edit Subject";
    document.getElementById('subject_id').value = id;
    document.getElementById('subject_name').value = name;
    document.getElementById('subject_code').value = code;
    document.getElementById('credit_hours').value = credit;
    document.getElementById('is_elective').checked = (elective==1);
    document.getElementById('subject_type').value = type;
    document.getElementById('section').value = section;
    document.getElementById('batch_year').value = batch;
    document.getElementById('submit-btn').name = "edit_subject";
    document.getElementById('submit-btn').innerText = "Update Subject";
}
</script>
<?php endif; ?>

</div>
</body>
</html>
