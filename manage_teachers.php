<?php
session_start();
require 'db_config.php';

// Fetch teachers ordered by Employee ID
$teachers = $conn->query("SELECT id, full_name, email, employee_id, contact FROM teachers ORDER BY employee_id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Teachers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #f0f2f5;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
footer {
    background-color: #0d6efd;
    color: #fff;
    padding: 20px 0;
    margin-top: 50px;
}
footer a {
    color: #fff !important;
    text-decoration: none;
}
footer a:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<div class="container mt-5">
  <h3 class="text-center mb-4 text-primary">Manage Teachers</h3>
  <table class="table table-bordered table-hover align-middle bg-white shadow-sm rounded">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Email</th>
        <th>Employee ID</th>
        <th>Contact</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $i = 1;
    while ($t = $teachers->fetch_assoc()) {
        $tid = $t['id'];

        // Check if teacher has assigned subjects
        $assign_check = $conn->query("SELECT id FROM teacher_subjects WHERE teacher_id = $tid");

        echo "<tr>
                <td>{$i}</td>
                <td>{$t['full_name']}</td>
                <td>{$t['email']}</td>
                <td>{$t['employee_id']}</td>
                <td>{$t['contact']}</td>
                <td>";

        if ($assign_check && $assign_check->num_rows > 0) {
            echo "<a href='assigned_subjects.php?teacher_id=$tid' class='btn btn-info btn-sm'>Show Assigned</a>";
        } else {
            echo "<a href='assign_subject.php?teacher_id=$tid' class='btn btn-primary btn-sm'>Assign Subject</a>";
        }

        echo "</td></tr>";
        $i++;
    }
    ?>
    </tbody>
  </table>
</div>

<!-- ===== Footer ===== -->
<footer>
  <div class="container d-flex flex-wrap justify-content-between">
    <div class="mb-3">
      <h5>Quick Links</h5>
      <a href="index.php" class="d-block">Home</a>
      <a href="#" class="d-block">Our Programs</a>
      <a href="about.php" class="d-block">About Us</a>
      <a href="notice.php" class="d-block">Notice Board</a>
    </div>
    <div class="mb-3">
      <h5>Follow Us</h5>
      <div class="d-flex gap-2">
        <a href="https://www.facebook.com/PECPoU" aria-label="Facebook">
          <img src="https://img.icons8.com/ios-filled/24/ffffff/facebook-new.png" alt="Facebook"/>
        </a>
        <a href="https://www.instagram.com/pec.pkr/" aria-label="Instagram">
          <img src="https://img.icons8.com/ios-filled/24/ffffff/instagram-new.png" alt="Instagram"/>
        </a>
      </div>
    </div>
    <div class="mb-3">
      <h5>Contact Us</h5>
      <p>Phirke Pokhara-8, Nepal</p>
      <p>Phone: 061 581209</p>
      <p>Email: info@pec.edu.np</p>
    </div>
    <div class="mb-3">
      <h5>Useful Links</h5>
      <a href="https://pu.edu.np/" class="d-block">Pokhara University</a>
      <a href="https://ctevt.org.np/" class="d-block">CTEVT</a>
      <a href="https://nec.gov.np/" class="d-block">Nepal Engineering Council</a>
      <a href="https://neanepal.org.np/" class="d-block">Nepal Engineer's Association</a>
      <a href="https://pu.edu.np/research/purc-seminar-series/" class="d-block">PU Research</a>
    </div>
  </div>
  <div class="text-center mt-3">
    <small>&copy; 2025 PEC Result Hub. All rights reserved.</small>
  </div>
</footer>

</body>
</html>
