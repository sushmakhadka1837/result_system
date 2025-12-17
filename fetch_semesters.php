<?php
require 'db_config.php';
$dept = $_GET['dept_id'];
$sem = $conn->query("SELECT id, semester_name FROM semesters WHERE department_id='$dept' ORDER BY id ASC");
echo "<option value=''>-- Select Semester --</option>";
while($s = $sem->fetch_assoc()){
    echo "<option value='".$s['id']."'>".$s['semester_name']."</option>";
}
?>
