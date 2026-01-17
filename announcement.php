<?php


// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$teacher_id = $_SESSION['teacher_id'] ?? 0;
if(!$teacher_id){
    echo "<div class='text-danger'>Unauthorized</div>";
    return;
}

if(isset($_POST['send_notice'])){
    $title = trim($_POST['notice_title']);
    $message = trim($_POST['notice_message'] ?? '');
    $batch = !empty($_POST['notice_batch']) ? trim($_POST['notice_batch']) : null;
    $department = $_POST['notice_department'] === 'all' ? 0 : intval($_POST['notice_department']);
    $semester = !empty($_POST['notice_semester']) ? intval($_POST['notice_semester']) : 0;
    $notice_type = $_POST['notice_type'];

    $image_paths = [];
    if(isset($_FILES['notice_images']) && !empty($_FILES['notice_images']['name'][0])){
        $img_dir = "uploads/notice_images/";
        if(!is_dir($img_dir)) mkdir($img_dir,0777,true);

        // Limit to max 5 images
        $max_images = 5;
        $count = 0;
        foreach($_FILES['notice_images']['tmp_name'] as $k => $tmp){
            if($count >= $max_images) break;
            if(empty($tmp)) continue;
            $ext = strtolower(pathinfo($_FILES['notice_images']['name'][$k], PATHINFO_EXTENSION));
            if(!in_array($ext,['jpg','jpeg','png','webp'])) continue;

            $newName = round(microtime(true) * 10000).'_'.$k.'.'.$ext;
            $path = $img_dir.$newName;
            if(move_uploaded_file($tmp,$path)){
                $image_paths[] = $path;
                $count++;
            }
        }
    }

    if(empty($title)){
        echo "<div class='text-danger'>❌ Title required.</div>";
        return;
    }

    $images_json = !empty($image_paths) ? json_encode($image_paths) : null;

    $stmt = $conn->prepare("
        INSERT INTO notices
        (teacher_id,title,message,batch,department_id,semester,notice_type,notice_images,created_at)
        VALUES (?,?,?,?,?,?,?,?,NOW())
    ");
    $stmt->bind_param(
        "isssiiss",
        $teacher_id,
        $title,
        $message,
        $batch,
        $department,
        $semester,
        $notice_type,
        $images_json
    );
if($stmt->execute()){
    echo "<div class='text-success mb-2'>✅ Notice posted successfully!</div>";

    // ================== SEND EMAIL TO STUDENTS ==================
    $students_query = ($department==0) 
        ? "SELECT email, full_name FROM students"
        : "SELECT email, full_name FROM students WHERE department_id=$department AND (semester=$semester OR $semester=0)";
    $students = $conn->query($students_query);

    $emails_sent = false; // track email sending

    // Get teacher name
    $teacherName = $conn->query("SELECT full_name FROM teachers WHERE id=$teacher_id")->fetch_assoc()['full_name'];

    if($students && $students->num_rows>0){
        while($student = $students->fetch_assoc()){
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'aahanakhadka6@gmail.com'; 
                $mail->Password   = 'upxa vjdc wdck ccjw';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('your_gmail@gmail.com', 'College Notice');
                $mail->addAddress($student['email'], $student['full_name']);

                $mail->isHTML(true);
                $mail->Subject = $title; // Only notice title in subject
                $mail->Body    = "
                    <h3>{$title}</h3>
                    <p>{$message}</p>
                    <p><strong>Sent by: {$teacherName}</strong></p>
                ";

                $mail->send();
                $emails_sent = true; // at least one email sent successfully
            } catch (Exception $e) {
                error_log("Mailer Error ({$student['email']}): {$mail->ErrorInfo}");
            }
        }
    }

    // ================== UPDATE notify_sent ==================
    if($emails_sent){
        $notice_id = $stmt->insert_id; // just inserted notice
        $conn->query("UPDATE notices SET notify_sent=1 WHERE id=$notice_id");
    }
}

}
?>

<div class="announcement-section">
    <h2 style="color:#1a73e8; margin-bottom:15px;">📢 Post Notice / Announcement</h2>

    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="notice_title" class="form-control mb-2" placeholder="Title" required>
        <textarea name="notice_message" class="form-control mb-2" rows="3" placeholder="Message (optional)"></textarea>
        <input type="text" name="notice_batch" class="form-control mb-2" placeholder="Batch (Optional)">
       <select name="notice_department" id="notice_department" class="form-control mb-2">
    <option value="0">All Departments</option>
    <?php
    $departments = $conn->query("SELECT * FROM departments");
    while($d = $departments->fetch_assoc()){
        echo "<option value='{$d['id']}'>{$d['department_name']}</option>";
    }
    ?>
</select>

<select name="notice_semester" id="notice_semester" class="form-control mb-2">
    <option value="0">All Semesters</option>
</select>

<script>
document.getElementById('notice_department').addEventListener('change', function(){
    let deptId = this.value;

    fetch('get_semesters.php?department_id=' + deptId)
    .then(response => response.json())
    .then(data => {
        let semSelect = document.getElementById('notice_semester');
        semSelect.innerHTML = '<option value="0">All Semesters</option>'; // default
        data.forEach(s => {
            let opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.semester_name;
            semSelect.appendChild(opt);
        });
    });
});

// Image preview and validation
document.getElementById('notice_images').addEventListener('change', function(e){
    let files = e.target.files;
    let preview = document.getElementById('image_preview');
    preview.innerHTML = '';
    
    if(files.length > 5){
        alert('⚠️ Maximum 5 images allowed!');
        this.value = '';
        return;
    }
    
    for(let i = 0; i < files.length; i++){
        if(files[i].type.match('image.*')){
            let reader = new FileReader();
            reader.onload = function(ev){
                let img = document.createElement('img');
                img.src = ev.target.result;
                img.style.width = '80px';
                img.style.height = '80px';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '8px';
                img.style.border = '2px solid #1a73e8';
                preview.appendChild(img);
            };
            reader.readAsDataURL(files[i]);
        }
    }
});
</script>




        <select name="notice_type" class="form-control mb-2">
            <option value="internal">Internal</option>
            <option value="exam">Exam</option>
            <option value="general">General</option>
        </select>
        <label class="fw-bold">📸 Upload Photos (Max 5)</label>
        <input type="file" name="notice_images[]" id="notice_images" multiple accept="image/*" class="form-control mb-2">
        <div id="image_preview" style="display:flex; gap:10px; flex-wrap:wrap; margin:10px 0;"></div>
        <button type="submit" name="send_notice" class="btn btn-primary w-100">📢 Publish Notice</button>
    </form>
    <a href="manage_announcements.php" class="btn btn-outline-primary mt-2">View Announcements</a>
</div>
