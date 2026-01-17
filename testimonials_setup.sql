-- Create testimonials table
CREATE TABLE IF NOT EXISTS testimonials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    role ENUM('student', 'teacher', 'principal', 'management') NOT NULL,
    quote TEXT NOT NULL,
    photo_path VARCHAR(255),
    rating INT DEFAULT 5 CHECK (rating >= 1 AND rating <= 5),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data (optional)
INSERT INTO testimonials (name, role, quote, rating, status) VALUES
('Aashish Gurung', 'student', 'The result portal is fast and reliable. Analytics helped me track my SGPA and set better targets.', 5, 'active'),
('S. Sharma', 'teacher', 'Publishing UT results and sharing notices is smoother now. Saves time and keeps everyone aligned.', 5, 'active'),
('Principal, PEC', 'principal', 'Digital transparency in results and communication strengthens academic excellence across departments.', 5, 'active'),
('PEC Management Team', 'management', 'Centralized result publishing and notice board improved coordination with students and faculty.', 5, 'active'),
('Prakriti Adhikari', 'student', 'Notes and announcements in one place makes studying easier.', 4, 'active');
