<?php
session_start();
require 'db_config.php';

// यदि login छैन भने redirect
if(!isset($_SESSION['student_id'])){
    header("Location: index.php");
    exit();
}

// DB बाट student details ल्याउने
$student_id = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT id, full_name, email, phone, department, faculty, section, batch_year, symbol_no, profile_photo 
                        FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .profile-pic {
        width: 150px;
        height: 150px;
        object-fit: cover;
        cursor: pointer;
    }
  </style>
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container mt-5">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h4>Welcome, <?php echo htmlspecialchars($student['full_name'] ?? ''); ?> 👋</h4>
    </div>
    <div class="card-body text-center">

      <!-- Profile Photo Upload -->
      <form id="uploadForm" action="edit_profile.php" method="POST" enctype="multipart/form-data">
          <label for="profileInput">
              <img src="<?php echo !empty($student['profile_photo']) ? $student['profile_photo'] : 'images/default.png'; ?>" 
                   alt="Profile" class="rounded-circle shadow profile-pic">
          </label>
          <input type="file" id="profileInput" name="profile_photo" accept="image/*" capture="user" 
                 style="display:none;" onchange="document.getElementById('uploadForm').submit();">
      </form>

      <h5 class="mt-3"><?php echo htmlspecialchars($student['full_name'] ?? ''); ?></h5>
      <p class="text-muted"><?php echo htmlspecialchars($student['email'] ?? ''); ?></p>

      <table class="table table-bordered mt-4 text-start">
        <tr><th>Student ID</th><td><?php echo htmlspecialchars($student['id'] ?? ''); ?></td></tr>
        <tr><th>Batch</th><td><?php echo htmlspecialchars($student['batch_year'] ?? ''); ?></td></tr>
        <tr><th>Program</th><td><?php echo htmlspecialchars($student['department'] ?? ''); ?></td></tr>
        <tr><th>Phone</th><td><?php echo htmlspecialchars($student['phone'] ?? ''); ?></td></tr>
        <tr><th>Faculty</th><td><?php echo htmlspecialchars($student['faculty'] ?? ''); ?></td></tr>
        <tr><th>Section</th><td><?php echo htmlspecialchars($student['section'] ?? ''); ?></td></tr>
        <tr><th>Symbol No</th><td><?php echo htmlspecialchars($student['symbol_no'] ?? ''); ?></td></tr>
      </table>

      <a href="index.php" class="btn btn-secondary">Home</a>
    </div>
  </div>
</div>
</body>
</html>
