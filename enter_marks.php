<?php
session_start();
require 'db_config.php';

// IF NOT LOGGED IN AS TEACHER
if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$subject_id = $_GET['subject_id'] ?? 0;

// ---- FETCH STUDENTS FOR SUBJECT ----
$students = $conn->query("
    SELECT st.id, st.full_name, st.symbol_no 
    FROM students st
    JOIN student_subjects ss ON ss.student_id = st.id
    WHERE ss.subject_id = $subject_id
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Enter Marks</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-3">

<h4 class="mb-3">Subject Marks Entry</h4>

<form method="POST" action="save_marks.php">

<input type="hidden" name="subject_id" value="<?= $subject_id ?>">

<!-- ✅ SUBJECT SETTINGS -->
<div class="card p-3 mb-3">
<h5>Subject Assessment Setup</h5>

<div class="row mb-2">
  <div class="col-md-3">
    <label>Total Full Marks (External 100)</label>
    <input type="number" class="form-control" name="full_marks" id="full_marks" value="100" required>
  </div>

  <div class="col-md-3">
    <label>Scaled Marks (Internal System)</label>
    <input type="number" class="form-control" name="scaled_marks" id="scaled_marks" value="50" required>
  </div>

  <div class="col-md-3">
    <label>Pass Marks</label>
    <input type="number" class="form-control" name="pass_marks" id="pass_marks" value="20" required>
  </div>

  <div class="col-md-3">
    <label>Total Attendance Days</label>
    <input type="number" class="form-control" name="total_attendance_days" id="total_attendance_days" value="90" required>
  </div>
</div>
</div>

<!-- ✅ MARKS TABLE -->
<table class="table table-bordered">
<thead class="table-dark">
<tr>
<th>Student</th>
<th>Assignment</th>
<th>Practical</th>
<th>Attendance Marks</th>
<th>Other</th>

<th>External (100)</th>
<th>External Scaled</th>

<th>Internal Total</th>
<th>Final Total</th>

<th>Attendance Days</th>
</tr>
</thead>

<tbody id="marksBody">

<?php while ($row = $students->fetch_assoc()) { ?>
<tr data-id="<?= $row['id'] ?>">

<td><?= $row['full_name'] ?> <br><small>(<?= $row['symbol_no'] ?>)</small></td>

<td><input type="number" name="assignment[<?= $row['id'] ?>]" class="form-control assignment"></td>
<td><input type="number" name="practical[<?= $row['id'] ?>]" class="form-control practical"></td>
<td><input type="number" name="attendance[<?= $row['id'] ?>]" class="form-control attendance"></td>
<td><input type="number" name="other[<?= $row['id'] ?>]" class="form-control other"></td>

<td><input type="number" name="external[<?= $row['id'] ?>]" class="form-control external"></td>
<td><input type="number" class="form-control external_scaled" readonly></td>

<td><input type="number" class="form-control internal_total" readonly></td>
<td><input type="number" name="final_total[<?= $row['id'] ?>]" class="form-control final_total" readonly></td>

<td><input type="number" name="attendance_days[<?= $row['id'] ?>]" class="form-control"></td>

</tr>
<?php } ?>

</tbody>
</table>

<button class="btn btn-primary mt-3">✅ Save Marks</button>
</form>

<!-- ✅ Auto Calculation Script -->
<script>
document.querySelectorAll("input").forEach(i => i.addEventListener("input", calc));

function calc() {
let scaledMax = parseFloat(document.getElementById("scaled_marks").value) || 50;

document.querySelectorAll("#marksBody tr").forEach(row => {

let get = c => parseFloat(row.querySelector(c)?.value) || 0;

let a = get(".assignment");
let p = get(".practical");
let am = get(".attendance");
let o = get(".other");
let ext = get(".external");

let internal = a + p + am + o;
let ext_scaled = (ext / 100) * scaledMax;
let final = internal + ext_scaled;

row.querySelector(".internal_total").value = internal.toFixed(2);
row.querySelector(".external_scaled").value = ext_scaled.toFixed(2);
row.querySelector(".final_total").value = final.toFixed(2);
});
}
</script>

</body>
</html>
