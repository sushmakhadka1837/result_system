<?php
session_start();
require_once 'db_config.php';
require_once 'mail_config.php';

$exam_type = $_GET['exam_type'] ?? 'ut'; // Default UT
$error_message = '';

// Handle form submission
if(isset($_POST['btn_publish'])){
    $dept_id = intval($_POST['department']);
    $batch = intval($_POST['batch']);
    
    // Validate department and batch exist
    $check = $conn->query("SELECT COUNT(*) as count FROM students WHERE department_id=$dept_id AND batch_year=$batch");
    $result = $check->fetch_assoc();
    
    if($result['count'] > 0){
        // Get all student emails for this department and batch
        $students = $conn->query("SELECT email, full_name FROM students WHERE department_id=$dept_id AND batch_year=$batch");
        
        // Send email notification to all students
        $email_count = 0;
        while($student = $students->fetch_assoc()){
            $to_email = $student['email'];
            $to_name = $student['full_name'];
            $subject = "Results Published";
            
            // Email body
            $body = "Hello $to_name,<br><br>";
            $body .= "Your <b>results have been published</b>!<br>";
            $body .= "Please log in to the Result Management System to view your results.<br><br>";
            $body .= "Thank you!<br>";
            $body .= "Pokhara Engineering College";
            
            // Send email using PHPMailer
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'aahanakhadka6@gmail.com';
                $mail->Password   = 'upxa vjdc wdck ccjw';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;
                
                $mail->setFrom('aahanakhadka6@gmail.com', 'Hamro Result');
                $mail->addAddress($to_email, $to_name);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                
                if($mail->send()){
                    $email_count++;
                }
            } catch (Exception $e) {
                error_log('Email failed for ' . $to_email . ': ' . $e->getMessage());
            }
        }
        
        // Redirect to results page
        header("Location: admin_view_results.php?dept_id=$dept_id&batch=$batch&emails_sent=$email_count");
        exit;
    } else {
        $error_message = "Invalid department or batch! No students found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Publish Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-10">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-black mb-8">PUBLISH CONTROL PANEL</h1>

        <?php if($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-600 text-red-700 p-4 mb-6 rounded-lg font-semibold">
                ❌ <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-2xl shadow-sm mb-8 flex items-center justify-between border-l-8 border-indigo-600">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Select Exam Type to Manage</label>
                <select onchange="location.href='?exam_type=' + this.value" class="p-3 border rounded-xl font-bold text-indigo-700 outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="ut" <?= $exam_type == 'ut' ? 'selected' : '' ?>>Unit Test (UT)</option>
                    <option value="assessment" <?= $exam_type == 'assessment' ? 'selected' : '' ?>>Internal Assessment</option>
                </select>
            </div>
            
            <a href="admin_<?= $exam_type ?>_manager.php" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all">
                Go to <?= strtoupper($exam_type) ?> Manager →
            </a>
        </div>

        <?php if($exam_type == 'ut'): ?>
            <div class="bg-white p-8 rounded-2xl shadow-sm border-t-4 border-slate-800">
                <h2 class="text-lg font-bold mb-4">Publish Unit Test Results</h2>
                <form method="POST" class="grid grid-cols-3 gap-4 italic">
                    <input type="hidden" name="type" value="ut">
                    <select name="department" class="border p-3 rounded-lg font-bold" required>
                        <option value="">Select Department</option>
                        <?php $depts = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");
                        while($d = $depts->fetch_assoc()): ?>
                            <option value="<?= $d['id'] ?>"><?= $d['department_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <input type="number" name="batch" placeholder="Batch (e.g. 2022)" class="border p-3 rounded-lg" required>
                    <button type="submit" name="btn_publish" class="bg-slate-800 text-white rounded-lg font-bold hover:bg-slate-900">Apply Status</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if($exam_type == 'assessment'): ?>
            <div class="bg-white p-8 rounded-2xl shadow-sm border-t-4 border-orange-500">
                <h2 class="text-lg font-bold mb-4 text-orange-600">Publish Internal Assessment</h2>
                <form method="POST" class="grid grid-cols-3 gap-4 italic">
                    <input type="hidden" name="type" value="assessment">
                    <select name="department" class="border p-3 rounded-lg font-bold" required>
                        <option value="">Select Department</option>
                        <?php $depts = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");
                        while($d = $depts->fetch_assoc()): ?>
                            <option value="<?= $d['id'] ?>"><?= $d['department_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <input type="number" name="batch" placeholder="Batch" class="border p-3 rounded-lg" required>
                    <button type="submit" name="btn_publish" class="bg-orange-600 text-white rounded-lg font-bold hover:bg-orange-700">Apply Status</button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>