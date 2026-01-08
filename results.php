<?php
session_start();
require 'db_config.php';

$departments = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");

$message = "";

// FORM VALUES
$result_type = $_POST['result_type'] ?? '';
$department  = $_POST['department'] ?? '';
$semester    = $_POST['semester'] ?? '';
$batch       = $_POST['batch'] ?? '';
$section     = $_POST['section'] ?? '';

// LOAD SEMESTERS IF DEPARTMENT SELECTED
$semester_options = [];
if (!empty($department)) {
    $stmt = $conn->prepare("SELECT id, semester_name FROM semesters WHERE department_id = ? ORDER BY semester_order ASC");
    $stmt->bind_param("i", $department);
    $stmt->execute();
    $semester_options = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($result_type && $department && $semester) {

        // CHECK IF RESULT IS PUBLISHED
        $check = $conn->prepare("
            SELECT id FROM results_publish_status
            WHERE department_id = ?
              AND semester_id = ?
              AND result_type = ?
              AND published = 1
            LIMIT 1
        ");
        $check->bind_param("iis", $department, $semester, $result_type);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            header("Location: show_results.php?dept=$department&sem=$semester&type=$result_type");
            exit;
        } else {
            $message = "Result not published yet.";
        }
    } else {
        $message = "Please select Result Type, Department and Semester.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>View Result</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<?php include 'header.php'; ?>

<div class="container my-5">
    <h3 class="text-center mb-4">View Student Result</h3>

    <form method="POST" class="row g-3">

        <!-- RESULT TYPE -->
        <div class="col-md-3">
            <label class="form-label">Result Type</label>
            <select name="result_type" class="form-select" required>
                <option value="">Select</option>
                <option value="ut" <?= ($result_type=='ut')?'selected':'' ?>>UT</option>
                <option value="assessment" <?= ($result_type=='assessment')?'selected':'' ?>>Assessment</option>
            </select>
        </div>

        <!-- DEPARTMENT -->
        <div class="col-md-3">
            <label class="form-label">Department</label>
            <select name="department" id="department" class="form-select" required>
                <option value="">Select</option>
                <?php while($d = $departments->fetch_assoc()): ?>
                    <option value="<?= $d['id'] ?>" <?= ($department == $d['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['department_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- SEMESTER -->
        <div class="col-md-2">
            <label class="form-label">Semester</label>
            <select name="semester" id="semester" class="form-select" required>
                <option value="">Select</option>
                <?php foreach ($semester_options as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($semester == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['semester_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- BATCH -->
        <div class="col-md-2">
            <label class="form-label">Batch (Optional)</label>
            <input type="text" name="batch" class="form-control" value="<?= htmlspecialchars($batch) ?>">
        </div>

        <!-- SECTION -->
        <div class="col-md-2">
            <label class="form-label">Section (Optional)</label>
            <input type="text" name="section" class="form-control" value="<?= htmlspecialchars($section) ?>">
        </div>

        <div class="col-12 text-center mt-3">
            <button class="btn btn-primary px-4">Show Result</button>
        </div>
    </form>

    <?php if ($message): ?>
        <p class="text-danger text-center mt-3"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
</div>

<script>
document.getElementById('department').addEventListener('change', function () {
    let deptId = this.value;
    let sem = document.getElementById('semester');
    sem.innerHTML = '<option value="">Select</option>';

    if (deptId) {
        fetch('get_semesters.php?dept_id=' + deptId)
            .then(res => res.json())
            .then(data => {
                data.forEach(s => {
                    sem.innerHTML += `<option value="${s.id}">${s.semester_name}</option>`;
                });
            });
    }
});
</script>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>