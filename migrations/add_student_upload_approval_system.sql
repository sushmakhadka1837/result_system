-- Student Upload Approval System Migration
-- Add approval workflow for student PDF uploads

-- Step 1: Modify notes table to add approval fields
-- Check if columns exist before adding
SET @dbname = DATABASE();
SET @tablename = 'notes';
SET @columnname = 'approval_status';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename) AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN approval_status ENUM('pending','approved','rejected','plagiarized') DEFAULT 'pending' AFTER uploader_role")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'approved_by';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename) AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN approved_by INT NULL AFTER approval_status")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'approved_at';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename) AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'rejection_reason';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename) AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN rejection_reason TEXT NULL AFTER approved_at")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'has_penalty';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename) AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN has_penalty TINYINT(1) DEFAULT 0 AFTER rejection_reason")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Step 2: Create table for student declarations
CREATE TABLE IF NOT EXISTS `student_upload_declarations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `upload_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `declaration_text` TEXT NOT NULL,
  `agreed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_upload` (`upload_id`),
  KEY `upload_id_idx` (`upload_id`),
  KEY `student_id_idx` (`student_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Step 3: Create table for penalties
CREATE TABLE IF NOT EXISTS `student_penalties` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `upload_id` INT NULL,
  `penalty_type` ENUM('plagiarism','false_declaration','academic_dishonesty') DEFAULT 'false_declaration',
  `penalty_points` INT DEFAULT 5,
  `reason` TEXT NOT NULL,
  `imposed_by` INT NOT NULL,
  `imposed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('active','removed','appeal_pending','appeal_resolved') DEFAULT 'active',
  `appeal_reason` TEXT NULL,
  `appeal_date` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `student_id_idx` (`student_id`),
  KEY `status_idx` (`status`),
  KEY `upload_id_idx` (`upload_id`),
  KEY `imposed_by_idx` (`imposed_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Step 4: Create table for upload approval workflows
CREATE TABLE IF NOT EXISTS `upload_approval_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `upload_id` INT NOT NULL,
  `teacher_id` INT NOT NULL,
  `action` ENUM('approved','rejected','flagged_plagiarism','pending') DEFAULT 'pending',
  `action_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `comment` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `upload_id_idx` (`upload_id`),
  KEY `teacher_id_idx` (`teacher_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Step 5: Add index for faster queries
ALTER TABLE `notes` 
ADD INDEX `idx_approval_status` (`approval_status`),
ADD INDEX `idx_subject_approval` (`subject_id`, `approval_status`),
ADD INDEX `idx_uploader` (`uploader_id`, `uploader_role`);
