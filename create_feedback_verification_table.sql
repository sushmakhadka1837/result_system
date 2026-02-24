-- Table for storing feedback with verification
CREATE TABLE IF NOT EXISTS `student_feedback_pending` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(255) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `feedback` text NOT NULL,
  `verification_token` varchar(64) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `verification_token` (`verification_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update existing student_feedback table to add verified_at column
ALTER TABLE `student_feedback` 
ADD COLUMN IF NOT EXISTS `verified_at` timestamp NULL DEFAULT NULL AFTER `created_at`;
