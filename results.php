<?php
session_start();
require 'db_config.php';

// Database connection check
if (!isset($conn)) {
    die("Database connection error: Variable \$conn not found in db_config.php");
}

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
    if ($result_type && $department && $semester && $batch) {
        // Published status check garne (batch_year included)
        $check = $conn->prepare("
            SELECT id FROM results_publish_status 
            WHERE department_id = ? 
              AND semester_id = ? 
              AND result_type = ? 
              AND batch_year = ? 
              AND published = 1 
            LIMIT 1
        ");
        $check->bind_param("iiss", $department, $semester, $result_type, $batch);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            // Data bhetiye pachi show_results.php ma redirect garne
            header("Location: show_results.php?dept=$department&sem=$semester&type=$result_type&batch=$batch&section=$section");
            exit;
        } else {
            $message = "Results for this batch/semester have not been published yet.";
        }
    } else {
        $message = "Please select all required fields (Type, Dept, Sem, Batch).";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results | PEC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .filter-container {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            padding: 30px;
            margin-top: 50px;
        }
        .form-label { font-weight: 600; color: #334155; }
        .btn-primary { background-color: #002b5c; border: none; padding: 10px 30px; }
        .btn-primary:hover { background-color: #001f4d; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .filter-container {
                padding: 20px;
                margin-top: 30px;
                border-radius: 12px;
            }
            .form-label { font-size: 0.9rem; }
            .btn-primary { padding: 10px 20px; width: 100%; }
        }

        @media (max-width: 576px) {
            .filter-container {
                padding: 15px;
                margin-top: 20px;
            }
            .form-select, .form-control { font-size: 0.9rem; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10 filter-container">
            <div class="text-center mb-4">
                <h3 class="fw-bold"><i class="fas fa-poll-h me-2 text-primary"></i>View Student Result</h3>
                <p class="text-muted">Select your criteria to view the academic performance</p>
            </div>

            <form method="POST" class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Result Type</label>
                    <select name="result_type" class="form-select" required>
                        <option value="">-- Select Type --</option>
                        <option value="ut" <?= ($result_type=='ut')?'selected':'' ?>>Unit Test (UT)</option>
                        <option value="assessment" <?= ($result_type=='assessment')?'selected':'' ?>>Terminal Assessment</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <select name="department" id="department" class="form-select" required>
                        <option value="">-- Select Dept --</option>
                        <?php while($d = $departments->fetch_assoc()): ?>
                            <option value="<?= $d['id'] ?>" <?= ($department == $d['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['department_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Semester</label>
                    <select name="semester" id="semester" class="form-select" required>
                        <option value="">-- Select Sem --</option>
                        <?php foreach ($semester_options as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($semester == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['semester_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Batch (Optional)</label>
                    <input type="text" name="batch" class="form-control" placeholder="e.g. 2079" value="<?= htmlspecialchars($batch) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Section (Optional)</label>
                    <input type="text" name="section" class="form-control" placeholder="e.g. A" value="<?= htmlspecialchars($section) ?>">
                </div>

                <div class="col-12 text-center mt-4">
                    <button class="btn btn-primary shadow-sm rounded-pill">
                        <i class="fas fa-search me-2"></i>Show Result
                    </button>
                </div>
            </form>

            <?php if ($message): ?>
                <div class="alert alert-warning text-center mt-4 border-0">
                    <i class="fas fa-info-circle me-2"></i> <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Dynamic Semester Load garnu logic
document.getElementById('department').addEventListener('change', function () {
    let deptId = this.value;
    let sem = document.getElementById('semester');
    sem.innerHTML = '<option value="">Loading...</option>';

    if (deptId) {
        fetch('get_semesters.php?dept_id=' + deptId)
            .then(res => res.json())
            .then(data => {
                sem.innerHTML = '<option value="">-- Select Sem --</option>';
                data.forEach(s => {
                    sem.innerHTML += `<option value="${s.id}">${s.semester_name}</option>`;
                });
            })
            .catch(err => {
                sem.innerHTML = '<option value="">Error loading</option>';
            });
    } else {
        sem.innerHTML = '<option value="">-- Select Sem --</option>';
    }
});
</script>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>