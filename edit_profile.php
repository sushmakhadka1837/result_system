<?php
session_start();
require 'db_config.php';

// Only logged-in students allowed
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student'){
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id']; // IMPORTANT
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile</title>
</head>
<body>
<h2>Edit Your Profile</h2>
<form action="student_edit_profile_submit.php" method="post" enctype="multipart/form-data">
    <label>Full Name:</label>
    <input type="text" name="full_name" required><br><br>

    <label>Phone:</label>
    <input type="text" name="phone"><br><br>

    <label>Profile Photo:</label>
    <input type="file" name="profile_photo"><br><br>

    <button type="submit">Update Profile</button>
</form>

<a href="student_dashboard.php">Back to Dashboard</a>
</body>
</html>
