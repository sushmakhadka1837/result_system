-- Quick Fix: Populate batch_year in subjects_department_semester
-- Run this in phpMyAdmin SQL tab

-- IMPORTANT CORRECTION:
-- batch_year 1 = OLD syllabus (before 2023)
-- batch_year 2 = NEW syllabus (2023 onwards)

-- Set all NULL values to 2 (New Batch) by default
UPDATE subjects_department_semester 
SET batch_year = 2 
WHERE batch_year IS NULL;

-- If you have specific old batch subjects, update them to 1
-- Example: Update specific subject codes that belong to old syllabus
-- UPDATE subjects_department_semester sds
-- JOIN subjects_master sm ON sds.subject_id = sm.id
-- SET sds.batch_year = 1
-- WHERE sm.subject_code LIKE 'CE%' OR sm.subject_code LIKE 'EG%';

-- Verify the changes
SELECT 
    sds.id,
    sm.subject_name,
    sm.subject_code,
    sds.semester,
    sds.batch_year,
    CASE 
        WHEN sds.batch_year = 1 THEN 'Old Batch'
        WHEN sds.batch_year = 2 THEN 'New Batch'
        ELSE 'Not Assigned'
    END as batch_label
FROM subjects_department_semester sds
JOIN subjects_master sm ON sds.subject_id = sm.id
ORDER BY sds.semester, sm.subject_name;
