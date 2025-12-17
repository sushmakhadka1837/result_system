// Before session destroy
$stmt = $conn->prepare("UPDATE teachers SET is_logged_in = 0 WHERE id = ?");
$stmt->bind_param("i", $_SESSION['teacher_id']);
$stmt->execute();

session_destroy();
header("Location: teacher_login.php");
exit();
