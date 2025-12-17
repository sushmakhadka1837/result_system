<?php
// view_student_notice.php
// ❗ Note: session_start() should NOT be here if already started in student_dashboard.php

$student_id = $_SESSION['student_id'];

// Fetch student details
$stmt = $conn->prepare("SELECT s.*, d.department_name FROM students s LEFT JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch latest 3 internal notices for dashboard
$stmt2 = $conn->prepare("
    SELECT n.*, t.full_name AS teacher_name 
    FROM notices n 
    JOIN teachers t ON n.teacher_id=t.id 
    WHERE n.notice_type='internal' 
      AND (n.department_id=? OR n.department_id='0') 
      AND (n.semester=? OR n.semester='all') 
    ORDER BY n.created_at DESC
    LIMIT 3
");
$stmt2->bind_param("is", $student['department_id'], $student['semester']);
$stmt2->execute();
$internal_notices = $stmt2->get_result();
?>

<div class="card-section">
    <h2>Internal Notices</h2>
    <?php if ($internal_notices->num_rows > 0): ?>
        <?php while ($row = $internal_notices->fetch_assoc()): ?>
            <div class="notice-card">
                <h4>
                    <a href="notice_detail.php?id=<?php echo $row['id']; ?>" style="text-decoration:none;">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </a>
                </h4>
                <p><?php echo nl2br(substr($row['message'], 0, 140)); ?>...</p>
                <small>
                    📅 <?php echo date("M d, Y", strtotime($row['created_at'])); ?> • 
                    👨‍🏫 <?php echo htmlspecialchars($row['teacher_name']); ?> • 
                    Dept: <?php echo ($row['department_id']==0) ? 'All Departments' : htmlspecialchars($row['department_id']); ?>
                </small>
            </div>
        <?php endwhile; ?>
        <a href="student_announcement.php" class="view-all">View All →</a>
    <?php else: ?>
        <p>No internal notices at the moment.</p>
    <?php endif; ?>
</div>
