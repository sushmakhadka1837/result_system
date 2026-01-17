<?php
session_start();
require_once 'db_config.php';
require_once 'mail_config.php';

$dept_id = $_GET['dept_id'] ?? '';
$batch   = $_GET['batch'] ?? '';
$message = "";
$result_type = $_GET['type'] ?? 'ut'; // Default UT

// --- 1. PUBLISH WITH EMAIL NOTIFICATION ---
if (isset($_POST['publish_with_email'])) {
    $result_type = $_POST['result_type'] ?? 'ut';
    $type_label = ($result_type == 'ut') ? 'UT' : 'Assessment';
    
    // Get all students for this department and batch
    $students = $conn->query("SELECT id, email, full_name FROM students WHERE department_id = $dept_id AND batch_year = '$batch'");
    
    $email_count = 0;
    while($student = $students->fetch_assoc()) {
        $to_email = $student['email'];
        $to_name = $student['full_name'];
        
        // Email body based on type
        if($result_type == 'ut') {
            $subject = "Your UT Result Has Been Published - Semester ".$_GET['sem_id']."";
            $body = "Hello $to_name,<br><br>";
            $body .= "Your <b>UT (Unit Test) results for Semester ".$_GET['sem_id']." have been published</b>!<br>";
            $body .= "Please log in to the Result Management System to view your results.<br><br>";
            $body .= "Thank you!<br>";
            $body .= "Pokhara Engineering College";
        } else {
            $subject = "Your Assessment Result Has Been Published";
            $body = "Hello $to_name,<br><br>";
            $body .= "Your <b>Assessment results have been published</b>!<br>";
            $body .= "Please log in to the Result Management System to view your results.<br><br>";
            $body .= "Thank you!<br>";
            $body .= "Hamro Result";
        }
        
        // Send email
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = '';
            $mail->Password   = '';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            
            $mail->setFrom('aahanakhadka@gmail.com', 'PEC-RESULT');
            $mail->addAddress($to_email, $to_name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            
            if($mail->send()) {
                $email_count++;
            }
        } catch (Exception $e) {
            error_log('Email failed for ' . $to_email . ': ' . $e->getMessage());
        }
    }
    
    $message = "<div class='bg-emerald-500 text-white p-4 rounded-lg mb-4 shadow-lg font-semibold'>✅ Results Published! Sent notification emails to $email_count students.</div>";
}

// --- 2. PUBLISH ALL LOGIC (With Email Notification) ---
if (isset($_POST['publish_all'])) {
    // A. results table ma sabaai lai 1 banai dine
    $up_results = "UPDATE results r 
                   JOIN students s ON r.student_id = s.id 
                   SET r.published = 1 
                   WHERE s.department_id = $dept_id AND s.batch_year = '$batch'";
    $conn->query($up_results);

    // B. results_publish_status table ma entry thapne (1 dekhi 8 semester samma)
    for ($i = 1; $i <= 8; $i++) {
        $publish_status_sql = "INSERT INTO results_publish_status (department_id, semester_id, result_type, published, published_at) 
                               VALUES ('$dept_id', '$i', 'UT', 1, NOW()) 
                               ON DUPLICATE KEY UPDATE published = 1, published_at = NOW()";
        $conn->query($publish_status_sql);
    }
    
    // C. Send email notifications to all students
    $students = $conn->query("SELECT id, email, full_name FROM students WHERE department_id = $dept_id AND batch_year = '$batch'");
    
    $email_count = 0;
    while($student = $students->fetch_assoc()) {
        $to_email = $student['email'];
        $to_name = $student['full_name'];
        $subject = "Your UT Results Have Been Published - All Semesters";
        
        $body = "Hello $to_name,<br><br>";
        $body .= "Your <b>UT (Unit Test) results for all semesters have been published</b>!<br>";
        $body .= "Please log in to the Hamro-Result to view your results.<br><br>";
        $body .= "Thank you!<br>";
        $body .= "Hamro Result";
        
        // Send email
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = '';
            $mail->Password   = 'upxa vjdc wdck ccjw';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            
            $mail->setFrom('', 'Hamro-RESULT');
            $mail->addAddress($to_email, $to_name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            
            if($mail->send()) {
                $email_count++;
            }
        } catch (Exception $e) {
            error_log('Email failed for ' . $to_email . ': ' . $e->getMessage());
        }
    }
    
    $message = "<div class='bg-emerald-500 text-white p-4 rounded-lg mb-4 shadow-lg font-semibold'>✅ All Semesters Published! Sent notification emails to $email_count students.</div>";
}

// --- 3. TOGGLE INDIVIDUAL STATUS LOGIC (With Email Notification) ---
if (isset($_POST['toggle_status'])) {
    $s_id = intval($_POST['sem_id']);
    $new_status = intval($_POST['new_status']);
    
    // A. Update results table only if publishing (new_status = 1)
    if($new_status == 1) {
        $up_results = "UPDATE results r 
                       JOIN students s ON r.student_id = s.id 
                       SET r.published = $new_status 
                       WHERE s.department_id = $dept_id AND s.batch_year = '$batch' AND r.semester_id = $s_id";
        $conn->query($up_results);
        
        // B. Update results_publish_status table
        $publish_status_sql = "INSERT INTO results_publish_status (department_id, semester_id, result_type, published, published_at) 
                               VALUES ('$dept_id', '$s_id', 'UT', $new_status, NOW()) 
                               ON DUPLICATE KEY UPDATE published = $new_status, published_at = NOW()";
        
        if($conn->query($publish_status_sql)) {
            // C. Send email notifications to all students
            $students = $conn->query("SELECT id, email, full_name FROM students WHERE department_id = $dept_id AND batch_year = '$batch'");
            
            $email_count = 0;
            while($student = $students->fetch_assoc()) {
                $to_email = $student['email'];
                $to_name = $student['full_name'];
                $subject = "Your UT Results Have Been Published - Semester ".$s_id."";
                
                $body = "Hello $to_name,<br><br>";
                $body .= "Your <b>UT (Unit Test) results for Semester ".$s_id." have been published</b>!<br>";
                $body .= "Please log in to the Hamro Result to view your results.<br><br>";
                $body .= "Thank you!<br>";
                $body .= "Hamro Result";
                
                // Send email
                try {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'aahanakhadka@gmail.com';
                    $mail->Password   = 'upxa vjdc wdck ccjw';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;
                    
                    $mail->setFrom('aahanakhadka@gmail.com', 'Hamro Result');
                    $mail->addAddress($to_email, $to_name);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $body;
                    
                    if($mail->send()) {
                        $email_count++;
                    }
                } catch (Exception $e) {
                    error_log('Email failed for ' . $to_email . ': ' . $e->getMessage());
                }
            }
            
            $message = "<div class='bg-emerald-500 text-white p-4 rounded-lg mb-4 shadow-lg font-semibold'>✅ Semester $s_id Published! Sent notification emails to $email_count students.</div>";
        }
    } else {
        // If unpublishing, just update without emails
        $up_results = "UPDATE results r 
                       JOIN students s ON r.student_id = s.id 
                       SET r.published = $new_status 
                       WHERE s.department_id = $dept_id AND s.batch_year = '$batch' AND r.semester_id = $s_id";
        $conn->query($up_results);

        $publish_status_sql = "INSERT INTO results_publish_status (department_id, semester_id, result_type, published, published_at) 
                               VALUES ('$dept_id', '$s_id', 'UT', $new_status, NOW()) 
                               ON DUPLICATE KEY UPDATE published = $new_status, published_at = NOW()";
        $conn->query($publish_status_sql);
        
        $message = "<div class='bg-yellow-500 text-white p-4 rounded-lg mb-4 shadow-lg font-semibold'>⚠️ Semester $s_id Unpublished.</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Results Manager | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-6 md:p-10">
    <div class="max-w-5xl mx-auto">
        
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Manage Semester Results</h1>
            
            <?php if($dept_id && $batch): ?>
            <div class="flex gap-3">
                <form method="POST">
                    <input type="hidden" name="result_type" value="ut">
                    <button name="publish_with_email" class="bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-emerald-700 shadow-md transition-all flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                        Publish UT & Email
                    </button>
                </form>
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to publish results for ALL 8 semesters?');">
                    <button name="publish_all" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-indigo-700 shadow-md transition-all flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Publish All Semesters
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Department</label>
                    <select name="dept_id" class="w-full border p-3 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="">-- Select Dept --</option>
                        <?php 
                        $d_res = $conn->query("SELECT id, department_name FROM departments");
                        while($d = $d_res->fetch_assoc()) {
                            $sel = ($dept_id == $d['id']) ? 'selected' : '';
                            echo "<option value='{$d['id']}' $sel>{$d['department_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Batch Year</label>
                    <input type="number" name="batch" value="<?= $batch ?>" placeholder="e.g. 2024" class="w-full border p-3 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <button type="submit" class="bg-slate-800 text-white p-3.5 rounded-xl font-bold hover:bg-black shadow-lg transition-all">
                    Search Semesters
                </button>
            </form>
        </div>

        <?= $message ?>

        <?php if($dept_id && $batch): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[11px] uppercase font-black text-slate-400">
                    <tr>
                        <th class="p-5">Semester</th>
                        <th class="p-5 text-center">Status</th>
                        <th class="p-5 text-center">Action</th>
                        <th class="p-5 text-right">View Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php 
                    for($i = 1; $i <= 8; $i++): 
                        $check = $conn->query("SELECT published FROM results r 
                                               JOIN students s ON r.student_id = s.id 
                                               WHERE s.department_id = $dept_id AND s.batch_year = '$batch' AND r.semester_id = $i LIMIT 1");
                        $row = $check->fetch_assoc();
                        $is_published = ($row && $row['published'] == 1);
                    ?>
                    <tr class="hover:bg-slate-50 transition-all">
                        <td class="p-5 font-bold text-slate-700">Semester <?= $i ?></td>
                        <td class="p-5 text-center">
                            <span class="<?= $is_published ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400' ?> px-3 py-1 rounded-full text-[10px] font-black uppercase ring-1 <?= $is_published ? 'ring-emerald-200' : 'ring-slate-200' ?>">
                                <?= $is_published ? 'Published' : 'Draft' ?>
                            </span>
                        </td>
                        <td class="p-5 text-center">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="sem_id" value="<?= $i ?>">
                                <input type="hidden" name="new_status" value="<?= $is_published ? 0 : 1 ?>">
                                <button name="toggle_status" class="text-xs font-bold <?= $is_published ? 'text-red-500' : 'text-indigo-600' ?> hover:underline">
                                    <?= $is_published ? 'Unpublish' : 'Publish Now' ?>
                                </button>
                            </form>
                        </td>
                        <td class="p-5 text-right">
                            <a href="admin_view_ut_marks_details.php?dept_id=<?= $dept_id ?>&batch=<?= $batch ?>&sem=<?= $i ?>" class="bg-slate-100 text-slate-700 px-4 py-2 rounded-lg text-xs font-bold hover:bg-slate-200 transition-all">
                                View Matrix →
                            </a>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>