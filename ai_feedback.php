$message = $_POST['message'];
$response = "Thank you for sharing. You're doing great!"; // Replace with AI API response
$conn->query("INSERT INTO feedback (student_id, message, response) VALUES ($student_id, '$message', '$response')");
