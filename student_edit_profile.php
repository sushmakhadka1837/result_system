<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id'])){
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch existing student data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // Optional profile photo upload
    $profile_photo = $student['profile_photo']; // default existing
    if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0){
        $target_dir = "uploads/";
        $file_name = time().'_'.basename($_FILES["profile_photo"]["name"]);
        $target_file = $target_dir . $file_name;
        if(move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)){
            $profile_photo = $target_file;
        }
    }

    // Update database (department and batch_year are not editable)
    $stmt = $conn->prepare("UPDATE students SET full_name=?, email=?, phone=?, profile_photo=? WHERE id=?");
    $stmt->bind_param("ssssi", $full_name, $email, $phone, $profile_photo, $student_id);
    if($stmt->execute()){
        header("Location: student_dashboard.php?success=1");
        exit();
    } else {
        $error = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile</title>
<style>
body { font-family: Arial; background: #f4f6f8; margin:0; padding:0;}
.container { max-width: 500px; margin:50px auto; background:white; padding:20px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
input[type=text], input[type=email], input[type=file] { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid #ccc;}
button { padding:10px 15px; background:#1a73e8; color:white; border:none; border-radius:5px; cursor:pointer;}
img { width:100px; height:100px; border-radius:50%; object-fit:cover; margin-bottom:10px;}
.error { color:red;}
</style>
</head>
<body>

<div class="container">
    <h2>Edit Profile</h2>
    <?php if(!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <form action="" method="post" enctype="multipart/form-data">
        <label>Profile Photo</label><br>
        <img src="<?php echo !empty($student['profile_photo']) ? $student['profile_photo'] : 'default.png'; ?>" alt="Profile"><br>
        <input type="file" name="profile_photo">

        <label>Full Name</label>
        <input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name'] ?? ''); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>

        <label>Phone</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">

        <label>Department</label>
        <input type="text" name="department" value="<?php echo htmlspecialchars($student['department'] ?? ''); ?>" readonly style="background-color:#e9ecef; cursor:not-allowed;">

        <label>Batch Year</label>
        <input type="text" name="batch_year" value="<?php echo htmlspecialchars($student['batch_year'] ?? ''); ?>" readonly style="background-color:#e9ecef; cursor:not-allowed;">

        <button type="submit">Update Profile</button>
    </form>
</div>

</body>
</html>
