<?php
require_once 'db_config.php';
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Admin authentication
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $department = $_POST['department'] ?? 'all';

    if($title && $content){
        // Insert announcement in DB
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, department_id, posted_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssis", $title, $content, $department, $admin_id);
        $stmt->execute();
        $stmt->close();

        // Fetch users for email notification
        if($department == 'all'){
            $users = $conn->query("SELECT email FROM students UNION SELECT email FROM teachers");
        } else {
            $users = $conn->query("SELECT email FROM students WHERE department_id='$department' 
                                   UNION 
                                   SELECT email FROM teachers WHERE department_id='$department'");
        }

        // Send Gmail notifications
        while($user = $users->fetch_assoc()){
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'yourgmail@gmail.com'; // Gmail
                $mail->Password = 'yourgmailpassword';   
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('yourgmail@gmail.com', 'College Admin');
                $mail->addAddress($user['email']); 
                $mail->isHTML(true);
                $mail->Subject = "New Announcement: ".$title;
                $mail->Body    = "<h3>$title</h3><p>$content</p>";

                $mail->send();
            } catch (Exception $e) {
                // Optional: log $e->getMessage()
            }
        }

        $success_msg = "Announcement posted and notifications sent!";
    } else {
        $error_msg = "Please fill all fields.";
    }
}
?>

<!-- ===========================
     POST ANNOUNCEMENT SECTION
=========================== -->
<div class="p-6 bg-white rounded-lg shadow max-w-lg mt-6">
    <h2 class="text-xl font-bold mb-4">📢 Post Announcement</h2>

    <?php if(!empty($success_msg)) echo "<p class='text-green-600 mb-3'>$success_msg</p>"; ?>
    <?php if(!empty($error_msg)) echo "<p class='text-red-600 mb-3'>$error_msg</p>"; ?>

    <form method="POST" class="space-y-4">

        <div>
            <label class="font-semibold">Department:</label>
            <select name="department" class="w-full border rounded p-2">
                <option value="all">All Departments</option>
                <?php
                $departments = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
                while($d = $departments->fetch_assoc()):
                ?>
                    <option value="<?= $d['id']; ?>"><?= $d['department_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label class="font-semibold">Title:</label>
            <input type="text" name="title" class="w-full border rounded p-2" placeholder="Enter title" required>
        </div>

        <div>
            <label class="font-semibold">Content:</label>
            <textarea name="content" class="w-full border rounded p-2" rows="5" placeholder="Enter announcement content" required></textarea>
        </div>

        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
            Post Announcement & Notify
        </button>
    </form>
</div>
