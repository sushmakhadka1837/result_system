-- Add batch_year to results_publish_status and enforce uniqueness per dept/batch/semester/type
ALTER TABLE results_publish_status
    ADD COLUMN batch_year VARCHAR(10) NOT NULL DEFAULT '' AFTER department_id;

-- If you already have data, backfill the correct batch_year values before applying unique constraints.
-- Example backfill (adjust as needed):
-- UPDATE results_publish_status rps
-- JOIN results r ON rps.department_id = r.department_id AND rps.semester_id = r.semester_id
-- JOIN students s ON r.student_id = s.id
-- SET rps.batch_year = s.batch_year
-- WHERE rps.batch_year = '';

-- Drop any existing unique index that does not include batch_year
ALTER TABLE results_publish_status
    DROP INDEX dept_sem_type_unique,
    ADD UNIQUE KEY dept_batch_sem_type_unique (department_id, batch_year, semester_id, result_type);
