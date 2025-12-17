<?php
require 'db_config.php';
$teacher_id = $_GET['teacher_id'] ?? 0;
$teacher = $conn->query("SELECT full_name FROM teachers WHERE id=$teacher_id")->fetch_assoc();

$departments = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");
$sections = ['A', 'B', 'C'];
$message = "";

// Handle adding new subject
if(isset($_POST['add_subject'])){
    $new_subject = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $credit_hours = intval($_POST['credit_hours']);
    $is_elective = isset($_POST['is_elective']) ? 1 : 0;

    if($new_subject && $credit_hours){
        $stmt = $conn->prepare("INSERT INTO subjects_master (subject_name, subject_code, credit_hours, is_elective) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $new_subject, $subject_code, $credit_hours, $is_elective);
        if($stmt->execute()){
            $message = "<div class='alert alert-success'>New subject added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to add subject.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Please enter subject name and credit hours.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assign Subject</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-5">
  <h4 class="text-center mb-4">Assign Subjects to <?= htmlspecialchars($teacher['full_name']) ?></h4>

  <?= $message ?>

  <!-- Form: Department / Semester / Section / Batch -->
  <form method="post" class="card p-4 shadow-sm mb-4">
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label">Department</label>
        <select class="form-select" name="department_id" id="department" required>
          <option value="">Select Department</option>
          <?php while($d = $departments->fetch_assoc()): ?>
            <option value="<?= $d['id'] ?>" data-total-sem="<?= $d['total_semesters'] ?>"><?= $d['department_name'] ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Semester</label>
        <select class="form-select" name="semester" id="semester" required>
          <option value="">Select Department First</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Section (Optional)</label>
        <select class="form-select" name="section">
          <option value="">-- None --</option>
          <?php foreach($sections as $sec): ?>
            <option value="<?= $sec ?>"><?= $sec ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Batch Year</label>
        <input type="number" name="batch_year" class="form-control" placeholder="e.g. 2023" required>
      </div>
    </div>

    <button type="submit" name="show" class="btn btn-success w-100">Show Subjects</button>
  </form>

  <!-- Add New Subject -->
  <div class="card p-4 shadow-sm mb-4">
    <h5>Add New Subject</h5>
    <form method="post" class="row g-3">
      <div class="col-md-4">
        <input type="text" class="form-control" name="subject_name" placeholder="Subject Name" required>
      </div>
      <div class="col-md-2">
        <input type="text" class="form-control" name="subject_code" placeholder="Subject Code">
      </div>
      <div class="col-md-2">
        <input type="number" class="form-control" name="credit_hours" placeholder="Credit Hours" required>
      </div>
      <div class="col-md-2">
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="is_elective" id="is_elective">
          <label class="form-check-label" for="is_elective">Elective</label>
        </div>
      </div>
      <div class="col-md-2">
        <button type="submit" name="add_subject" class="btn btn-primary w-100">Add Subject</button>
      </div>
    </form>
  </div>

<?php
if(isset($_POST['show'])){
    $dept = intval($_POST['department_id']);
    $sem = intval($_POST['semester']);
    $batch = intval($_POST['batch_year']);
    $section = !empty($_POST['section']) ? $_POST['section'] : null;
    $syllabus = ($batch <= 2022) ? 'Old' : 'New';

    // Fetch subjects for department+semester
    $subs = $conn->query("
        SELECT sds.subject_id, sm.subject_name, sm.subject_code, sm.credit_hours, sm.is_elective
        FROM subjects_department_semester sds
        JOIN subjects_master sm ON sm.id = sds.subject_id
        WHERE sds.department_id=$dept AND sds.semester=$sem
    ");

    if($subs && $subs->num_rows>0){
        echo "<form method='post' class='card p-3 shadow-sm'>
              <input type='hidden' name='department_id' value='$dept'>
              <input type='hidden' name='semester' value='$sem'>
              <input type='hidden' name='batch_year' value='$batch'>
              <input type='hidden' name='section' value='".($section ?? '')."'>
              <h5 class='mb-3'>Available Subjects for Assignment</h5>
              <table class='table table-bordered table-striped'>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Subject Name</th>
                    <th>Code</th>
                    <th>Credit Hours</th>
                    <th>Type</th>
                    <th>Assign</th>
                  </tr>
                </thead>
                <tbody>";
        $i=1;
        while($s = $subs->fetch_assoc()){
            $subject_id = $s['subject_id'];
            // Check if subject already assigned
            $check_sql = "
                SELECT * FROM teacher_subjects
                WHERE subject_map_id=$subject_id
                AND department_id=$dept
                AND semester_id=$sem
                AND batch_year=$batch
            ";
            if($section === null){
                $check_sql .= " AND section IS NULL";
            } else {
                $check_sql .= " AND section='$section'";
            }
            $check = $conn->query($check_sql);
            $assigned = ($check && $check->num_rows>0);

            $code = !empty($s['subject_code']) ? $s['subject_code'] : '-';
            $type = $s['is_elective'] ? 'Elective' : 'Regular';

            echo "<tr>
                    <td>$i</td>
                    <td>{$s['subject_name']}</td>
                    <td>{$code}</td>
                    <td>{$s['credit_hours']}</td>
                    <td>{$type}</td>
                    <td class='text-center'>";
            if($assigned){
                echo "<span class='badge bg-success'>Assigned</span>";
            } else {
                echo "<input type='checkbox' name='subject_map_id[]' value='$subject_id'>";
            }
            echo "</td></tr>";
            $i++;
        }
        echo "</tbody></table>
              <button type='submit' name='assign' class='btn btn-primary mt-3 w-100'>Assign Selected Subjects</button>
              </form>";
    } else {
        echo "<div class='alert alert-warning'>No subjects found for this department/semester.</div>";
    }
}

// Handle Assign Selected Subjects
if(isset($_POST['assign'])){
    $dept = intval($_POST['department_id']);
    $sem = intval($_POST['semester']);
    $batch = intval($_POST['batch_year']);
    $section = !empty($_POST['section']) ? $_POST['section'] : null;
    $subject_ids = $_POST['subject_map_id'] ?? [];
    $syllabus = ($batch <= 2022) ? 'Old' : 'New';

    if(!empty($subject_ids)){
        foreach($subject_ids as $sid){
            $check_sql = "
                SELECT * FROM teacher_subjects
                WHERE subject_map_id=$sid
                AND department_id=$dept
                AND semester_id=$sem
                AND batch_year=$batch
            ";
            if($section === null){
                $check_sql .= " AND section IS NULL";
            } else {
                $check_sql .= " AND section='$section'";
            }
            $check = $conn->query($check_sql);
            if($check->num_rows==0){
                $conn->query("
                    INSERT INTO teacher_subjects
                    (teacher_id, subject_map_id, batch_year, department_id, semester_id, section, syllabus_type)
                    VALUES ($teacher_id, $sid, $batch, $dept, $sem, ".($section===null?'NULL':"'$section'").", '$syllabus')
                ");
            }
        }
        echo "<div class='alert alert-success mt-3'>Subjects assigned successfully!</div>";
    } else {
        echo "<div class='alert alert-danger mt-3'>Please select at least one subject.</div>";
    }
}
?>

</div>

<script>
$(document).ready(function(){
    $('#department').change(function(){
        var totalSem = $('option:selected', this).data('total-sem');
        var semester = $('#semester');
        semester.empty();
        semester.append('<option value="">Select Semester</option>');
        for(var i=1;i<=totalSem;i++){
            semester.append('<option value="'+i+'">'+i+' Semester</option>');
        }
    });
});
</script>
</body>
</html>
