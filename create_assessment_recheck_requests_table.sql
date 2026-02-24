-- Create table for Assessment Re-total / Recheck requests
CREATE TABLE IF NOT EXISTS assessment_recheck_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    department_id INT NOT NULL,
    batch_year INT NOT NULL,
    semester_id INT NOT NULL,
    semester_order INT NOT NULL,
    subject_id INT NOT NULL,
    assigned_teacher_id INT DEFAULT NULL,
    request_type ENUM('retotal', 'recheck') NOT NULL,
    reason_text TEXT NOT NULL,
    status ENUM('pending', 'teacher_recommended', 'teacher_rejected', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    student_marks_snapshot DECIMAL(6,2) DEFAULT NULL,
    teacher_remark TEXT DEFAULT NULL,
    admin_remark TEXT DEFAULT NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    teacher_reviewed_at DATETIME DEFAULT NULL,
    admin_reviewed_at DATETIME DEFAULT NULL,
    KEY idx_student (student_id),
    KEY idx_teacher_status (assigned_teacher_id, status),
    KEY idx_admin_status (status),
    KEY idx_scope (department_id, batch_year, semester_id, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
