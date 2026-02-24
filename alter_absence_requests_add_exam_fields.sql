SET @has_subject_id := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'student_absence_requests'
      AND COLUMN_NAME = 'subject_id'
);

SET @sql_subject := IF(
    @has_subject_id = 0,
    'ALTER TABLE student_absence_requests ADD COLUMN subject_id INT NULL AFTER request_scope',
    'ALTER TABLE student_absence_requests MODIFY COLUMN subject_id INT NULL'
);
PREPARE stmt_subject FROM @sql_subject;
EXECUTE stmt_subject;
DEALLOCATE PREPARE stmt_subject;

SET @has_request_scope := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'student_absence_requests'
      AND COLUMN_NAME = 'request_scope'
);

SET @sql_scope := IF(
    @has_request_scope = 0,
    "ALTER TABLE student_absence_requests ADD COLUMN request_scope ENUM('class', 'exam') NOT NULL DEFAULT 'exam' AFTER student_id",
    'SELECT 1'
);
PREPARE stmt_scope FROM @sql_scope;
EXECUTE stmt_scope;
DEALLOCATE PREPARE stmt_scope;

SET @has_exam_component := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'student_absence_requests'
      AND COLUMN_NAME = 'exam_component'
);

SET @sql_exam := IF(
    @has_exam_component = 0,
    "ALTER TABLE student_absence_requests ADD COLUMN exam_component ENUM('UT', 'Assessment', 'Final', 'Practical', 'Other') NULL DEFAULT NULL AFTER request_type",
    "ALTER TABLE student_absence_requests MODIFY COLUMN exam_component ENUM('UT', 'Assessment', 'Final', 'Practical', 'Other') NULL DEFAULT NULL"
);
PREPARE stmt_exam FROM @sql_exam;
EXECUTE stmt_exam;
DEALLOCATE PREPARE stmt_exam;

SET @has_scope_index := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'student_absence_requests'
      AND INDEX_NAME = 'idx_scope_status'
);

SET @sql_scope_index := IF(
    @has_scope_index = 0,
    'ALTER TABLE student_absence_requests ADD INDEX idx_scope_status (request_scope, status)',
    'SELECT 1'
);
PREPARE stmt_scope_index FROM @sql_scope_index;
EXECUTE stmt_scope_index;
DEALLOCATE PREPARE stmt_scope_index;
