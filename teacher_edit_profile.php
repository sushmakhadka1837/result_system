<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}
$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher info
$stmt = $conn->prepare("SELECT full_name, email, employee_id, contact, profile_pic FROM teachers WHERE id=?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Save profile
if (isset($_POST['save_profile'])) {
    $full_name = $_POST['full_name'];
    $contact = $_POST['contact'];
    $stmt = $conn->prepare("UPDATE teachers SET full_name=?, contact=? WHERE id=?");
    $stmt->bind_param("ssi", $full_name, $contact, $teacher_id);
    $stmt->execute();
    header("Location: teacher_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container" style="max-width:500px;">
    <h4>Edit Profile</h4>
    <form method="POST">
      <div class="mb-2"><label><strong>Name:</strong></label>
      <input type="text" name="full_name" value="<?= htmlspecialchars($teacher['full_name']) ?>" class="form-control"></div>

      <div class="mb-2"><label><strong>Contact:</strong></label>
      <input type="text" name="contact" value="<?= htmlspecialchars($teacher['contact']) ?>" class="form-control"></div>

      <button type="submit" name="save_profile" class="btn btn-primary w-100">Save Profile</button>
      <a href="teacher_dashboard.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
    </form>
  </div>
</body>
</html>
