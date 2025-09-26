<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit;
}

// Fetch all departments
$departments = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");

// Add teacher
if (isset($_POST['add_teacher'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $is_hod = isset($_POST['is_hod']) ? 1 : 0;

    if ($name && $email) {
        $stmt = $conn->prepare("INSERT INTO teachers (name,email,contact,is_hod) VALUES (?,?,?,?)");
        $stmt->bind_param("sssi", $name, $email, $contact, $is_hod);
        $stmt->execute();
        $teacher_id = $conn->insert_id;

        // Assign subjects
        if (!empty($_POST['assignments'])) {
            foreach ($_POST['assignments'] as $dept_id => $assign) {
                $semester = intval($assign['semester']);
                $subject_id = intval($assign['subject']);
                if ($semester > 0 && $subject_id > 0) {
                    $stmt2 = $conn->prepare("INSERT INTO teacher_subject_assignments (teacher_id, department_id, semester, subject_id) VALUES (?,?,?,?)");
                    $stmt2->bind_param("iiii", $teacher_id, $dept_id, $semester, $subject_id);
                    $stmt2->execute();
                }
            }
        }

        $success = "Teacher added successfully!";
    } else {
        $error = "Name and Email are required!";
    }
}

// Fetch teachers with assignments
$teachers = $conn->query("
    SELECT t.id, t.name, t.email, t.contact, t.is_hod,
           d.department_name, s.subject_name, tsa.semester
    FROM teachers t
    LEFT JOIN teacher_subject_assignments tsa ON t.id = tsa.teacher_id
    LEFT JOIN departments d ON tsa.department_id = d.id
    LEFT JOIN subjects_master s ON tsa.subject_id = s.id
    ORDER BY t.name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Teachers</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

<div class="container mx-auto p-6">
  <h2 class="text-2xl font-semibold mb-4">Manage Teachers</h2>

  <?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-800 px-4 py-2 mb-4 rounded"><?= $success ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="bg-red-100 text-red-800 px-4 py-2 mb-4 rounded"><?= $error ?></div>
  <?php endif; ?>

  <!-- Add Teacher Form -->
  <form method="POST" class="grid grid-cols-1 gap-4 mb-6">
    <input type="text" name="name" placeholder="Teacher Name" class="border p-2 rounded" required>
    <input type="email" name="email" placeholder="Email" class="border p-2 rounded" required>
    <input type="text" name="contact" placeholder="Contact Number" class="border p-2 rounded">
    <label><input type="checkbox" name="is_hod"> Assign as HOD</label>

    <h3 class="font-semibold mt-4">Assign Subjects</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <?php while ($dept = $departments->fetch_assoc()): ?>
        <div class="border p-2 rounded">
          <strong><?= htmlspecialchars($dept['department_name']) ?></strong><br>
          Semester: <input type="number" name="assignments[<?= $dept['id'] ?>][semester]" min="1" max="<?= $dept['total_semesters'] ?>" class="w-16 border p-1 rounded"><br>
          Subject:
          <select name="assignments[<?= $dept['id'] ?>][subject]" class="border p-1 rounded w-full">
            <option value="">Select Subject</option>
            <?php
            $dept_id = $dept['id'];
            $subjects_dept = $conn->query("
                SELECT sm.*
                FROM subjects_master sm
                JOIN subjects_department_semester sds ON sm.id = sds.subject_id
                WHERE sds.department_id = $dept_id
                ORDER BY sm.subject_name ASC
            ");
            while ($sub = $subjects_dept->fetch_assoc()):
            ?>
              <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      <?php endwhile; ?>
    </div>

    <button type="submit" name="add_teacher" class="bg-indigo-600 text-white px-4 py-2 rounded mt-4">Add Teacher</button>
  </form>

  <!-- Teachers List -->
  <table class="w-full border-collapse bg-white rounded-lg shadow mt-6">
    <thead class="bg-gray-200">
      <tr>
        <th class="p-2 border">#</th>
        <th class="p-2 border">Name</th>
        <th class="p-2 border">Email</th>
        <th class="p-2 border">Contact</th>
        <th class="p-2 border">HOD</th>
        <th class="p-2 border">Assignments</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($teachers->num_rows > 0): ?>
        <?php while($t = $teachers->fetch_assoc()): ?>
          <tr>
            <td class="p-2 border"><?= $t['id'] ?></td>
            <td class="p-2 border"><?= htmlspecialchars($t['name']) ?></td>
            <td class="p-2 border"><?= htmlspecialchars($t['email']) ?></td>
            <td class="p-2 border"><?= htmlspecialchars($t['contact']) ?></td>
            <td class="p-2 border"><?= $t['is_hod'] ? "Yes" : "No" ?></td>
            <td class="p-2 border">
              <?= $t['department_name'] ? htmlspecialchars($t['department_name'])." - Sem ".$t['semester']." : ".htmlspecialchars($t['subject_name']) : "" ?>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="6" class="p-4 text-center">No teachers found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
