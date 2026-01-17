<?php
/**
 * Notification Helper for Upload Verification System
 * Sends email notifications to students and teachers
 */

/**
 * Notify student of upload approval
 */
function notifyStudentUploadApproved($student_id, $upload_title, $subject_name, $conn){
    try {
        $student = $conn->query("SELECT email, full_name FROM students WHERE id=$student_id")->fetch_assoc();
        
        if(!$student) return false;
        
        $subject = "Your Upload Approved ✅";
        $message = "
        <h2>Upload Approved</h2>
        <p>Hi {$student['full_name']},</p>
        <p>Your upload \"<strong>$upload_title</strong>\" for <strong>$subject_name</strong> has been approved and is now available to students.</p>
        <p>Thank you for maintaining academic integrity!</p>
        ";
        
        return sendEmail($student['email'], $subject, $message);
    } catch(Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify student of upload rejection
 */
function notifyStudentUploadRejected($student_id, $upload_title, $rejection_reason, $conn){
    try {
        $student = $conn->query("SELECT email, full_name FROM students WHERE id=$student_id")->fetch_assoc();
        
        if(!$student) return false;
        
        $subject = "Upload Rejected - Action Required";
        $message = "
        <h2>Upload Rejected</h2>
        <p>Hi {$student['full_name']},</p>
        <p>Your upload \"<strong>$upload_title</strong>\" was rejected.</p>
        <p><strong>Reason:</strong> $rejection_reason</p>
        <p>Please review the feedback and upload again with corrections.</p>
        ";
        
        return sendEmail($student['email'], $subject, $message);
    } catch(Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify student of plagiarism flag and penalty
 */
function notifyStudentPlagiarismFlag($student_id, $upload_title, $reason, $penalty_points, $conn){
    try {
        $student = $conn->query("SELECT email, full_name FROM students WHERE id=$student_id")->fetch_assoc();
        
        if(!$student) return false;
        
        $subject = "Academic Integrity Concern ⚠️";
        $message = "
        <h2>Plagiarism Flag</h2>
        <p>Hi {$student['full_name']},</p>
        <p>Your upload \"<strong>$upload_title</strong>\" has been flagged for plagiarism.</p>
        <p><strong>Reason:</strong> $reason</p>
        <p><strong>Penalty Points:</strong> $penalty_points</p>
        <p>You can <a href='[LOGIN_LINK]/student_penalties_view.php'>submit an appeal</a> within 7 days if you believe this is incorrect.</p>
        <p>Remember: Academic integrity is essential. Ensure all work is original.</p>
        ";
        
        return sendEmail($student['email'], $subject, $message);
    } catch(Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify teacher of pending uploads for review
 */
function notifyTeacherPendingUploads($teacher_id, $pending_count, $subject_name, $conn){
    try {
        $teacher = $conn->query("SELECT email, full_name FROM teachers WHERE id=$teacher_id")->fetch_assoc();
        
        if(!$teacher) return false;
        
        $subject = "Pending Student Uploads to Review";
        $message = "
        <h2>Action Required</h2>
        <p>Hi {$teacher['full_name']},</p>
        <p>You have <strong>$pending_count</strong> student uploads pending verification for <strong>$subject_name</strong>.</p>
        <p>Please <a href='[LOGIN_LINK]/teacher_verify_student_uploads.php'>review and approve</a> these uploads.</p>
        <p>Remember to check each student's self-declaration.</p>
        ";
        
        return sendEmail($teacher['email'], $subject, $message);
    } catch(Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify student of appeal decision
 */
function notifyStudentAppealResolved($student_id, $penalty_type, $decision, $conn){
    try {
        $student = $conn->query("SELECT email, full_name FROM students WHERE id=$student_id")->fetch_assoc();
        
        if(!$student) return false;
        
        $decision_text = $decision === 'approved' ? 'Approved ✅' : 'Rejected ❌';
        $decision_message = $decision === 'approved' 
            ? "Your penalty has been removed."
            : "Your penalty remains in effect.";
        
        $subject = "Your Appeal Has Been Reviewed";
        $message = "
        <h2>Appeal Decision: $decision_text</h2>
        <p>Hi {$student['full_name']},</p>
        <p>Your appeal regarding the $penalty_type penalty has been reviewed.</p>
        <p><strong>Decision:</strong> $decision_message</p>
        <p>If you wish to file another appeal, contact your teacher or department head.</p>
        ";
        
        return sendEmail($student['email'], $subject, $message);
    } catch(Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email (integration with existing mail system)
 * Uses the project's mail configuration
 */
function sendEmail($to, $subject, $message){
    // Include mail config if not already loaded
    if(!function_exists('sendMail')){
        require_once 'mail_config.php';
    }
    
    // Use existing mail system
    if(function_exists('sendMail')){
        return @sendMail($to, $subject, $message);
    }
    
    // Fallback to PHP mail (suppress errors if mail server not configured)
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: noreply@result-system.local\r\n";
    
    return @mail($to, $subject, $message, $headers);
}

/**
 * Get pending notifications for a user (dashboard widget)
 */
function getPendingNotifications($user_id, $user_type, $conn){
    $notifications = [];
    
    if($user_type === 'student'){
        // Pending approvals for student's uploads
        $pending = $conn->query("SELECT COUNT(*) as cnt FROM notes WHERE uploader_id=$user_id AND uploader_role='student' AND approval_status='pending'");
        $count = $pending->fetch_assoc()['cnt'];
        if($count > 0){
            $notifications[] = [
                'type' => 'pending_uploads',
                'message' => "$count uploads pending teacher approval",
                'icon' => 'hourglass-half',
                'color' => 'warning'
            ];
        }
        
        // Active penalties
        $penalties = $conn->query("SELECT COUNT(*) as cnt FROM student_penalties WHERE student_id=$user_id AND status='active'");
        $count = $penalties->fetch_assoc()['cnt'];
        if($count > 0){
            $notifications[] = [
                'type' => 'active_penalties',
                'message' => "$count active penalties on your record",
                'icon' => 'ban',
                'color' => 'danger'
            ];
        }
    } 
    elseif($user_type === 'teacher'){
        // Pending uploads to review
        $pending = $conn->query("SELECT COUNT(*) as cnt FROM notes n 
                               JOIN subjects_master s ON n.subject_id = s.id 
                               WHERE n.approval_status='pending' 
                               AND n.uploader_role='student'
                               AND s.id IN (SELECT subject_map_id FROM teacher_subjects WHERE teacher_id=$user_id)");
        $count = $pending->fetch_assoc()['cnt'];
        if($count > 0){
            $notifications[] = [
                'type' => 'pending_reviews',
                'message' => "$count student uploads need your review",
                'icon' => 'check-circle',
                'color' => 'info'
            ];
        }
        
        // Appeals to resolve
        $appeals = $conn->query("SELECT COUNT(*) as cnt FROM student_penalties WHERE status='appeal_pending'");
        $count = $appeals->fetch_assoc()['cnt'];
        if($count > 0){
            $notifications[] = [
                'type' => 'pending_appeals',
                'message' => "$count penalty appeals awaiting resolution",
                'icon' => 'gavel',
                'color' => 'warning'
            ];
        }
    }
    
    return $notifications;
}
?>
