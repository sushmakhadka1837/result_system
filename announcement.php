<div class="announcement-section">
    <h2 style="color:#1a73e8; margin-bottom:15px;">📢 Post Notice / Announcement</h2>

    <?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'vendor/autoload.php'; // PHPMailer autoload

    $teacher_id = $_SESSION['teacher_id'];

    if (isset($_POST['send_notice'])) {
        $title = trim($_POST['notice_title']);
        $message = trim($_POST['notice_message']);
        $batch = !empty($_POST['notice_batch']) ? trim($_POST['notice_batch']) : null;
        $department = $_POST['notice_department'] === 'all' ? 0 : $_POST['notice_department'];
        $semester = !empty($_POST['notice_semester']) ? $_POST['notice_semester'] : 0;
        $notice_type = $_POST['notice_type'];

        // Handle file upload
        $attachment_path = null;
        if (!empty($_FILES['notice_attachment']['name'])) {
            $filename = time() . '_' . basename($_FILES['notice_attachment']['name']);
            $target_dir = 'uploads/notice_attachments/';
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES['notice_attachment']['tmp_name'], $target_file)) {
                $attachment_path = $target_file;
            }
        }

        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO notices 
            (teacher_id, title, message, batch, department_id, semester, notice_type, attachment, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isssisss", $teacher_id, $title, $message, $batch, $department, $semester, $notice_type, $attachment_path);

        if ($stmt->execute()) {
            echo "<div style='color:green; margin-bottom:10px;'>✅ Notice posted successfully!</div>";

            // Fetch student emails
            $sql_students = "SELECT email FROM students 
                             WHERE (department_id=? OR ?=0) 
                               AND (semester=? OR ?=0)";
            $stmt2 = $conn->prepare($sql_students);
            $stmt2->bind_param("iiii", $department, $department, $semester, $semester);
            $stmt2->execute();
            $emails = $stmt2->get_result();

            while ($student = $emails->fetch_assoc()) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.example.com'; // your SMTP server
                    $mail->SMTPAuth = true;
                    $mail->Username   = 'aahanakhadka6@gmail.com'; // change to your email
                    $mail->Password   = 'upxa vjdc wdck ccjw'; 
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('youremail@example.com', 'PEC Notices');
                    $mail->addAddress($student['email']);

                    $mail->isHTML(true);
                    $mail->Subject = "New Notice: $title";
                    $mail->Body    = "
                        <h3>$title</h3>
                        <p>$message</p>
                        <p><strong>Department:</strong> ".($department==0?'All Departments':$department)."</p>
                        <p><strong>Semester:</strong> ".($semester==0?'All Semesters':$semester)."</p>
                    ";
                    if ($attachment_path) {
                        $mail->addAttachment($attachment_path);
                    }

                    $mail->send();
                } catch (Exception $e) {
                    // Optional: log error
                }
            }

        } else {
            echo "<div style='color:red; margin-bottom:10px;'>❌ Failed to post notice.</div>";
        }
    }
    ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="notice_title" class="form-control mb-2" placeholder="Title" required>
        <textarea name="notice_message" class="form-control mb-2" rows="3" placeholder="Message" required></textarea>
        <input type="text" name="notice_batch" class="form-control mb-2" placeholder="Batch (Optional)">

        <select name="notice_department" class="form-control mb-2" required>
            <option value="all">All Departments</option>
            <?php
            $departments = $conn->query("SELECT * FROM departments");
            while($d = $departments->fetch_assoc()){
                echo "<option value='{$d['id']}'>{$d['department_name']}</option>";
            }
            ?>
        </select>

        <select name="notice_semester" class="form-control mb-2">
    <option value="0">All Semesters</option>
    <?php
    // Fetch semesters for selected department only
    $semesters = $conn->query("SELECT * FROM semesters WHERE department_id=2 ORDER BY semester_order ASC"); // change 2 to department dynamically if needed
    while($s = $semesters->fetch_assoc()){
        echo "<option value='{$s['id']}'>{$s['semester_name']}</option>";
    }
    ?>
</select>


        <select name="notice_type" class="form-control mb-2" required>
            <option value="internal">Internal</option>
            <option value="exam">Exam</option>
            <option value="general">General</option>
        </select>

        <input type="file" name="notice_attachment" class="form-control mb-2" accept=".pdf,.doc,.docx,.jpg,.png">

        <button type="submit" name="send_notice" class="btn btn-primary w-100">✉️ Send Notice</button>
    </form>
</div>
