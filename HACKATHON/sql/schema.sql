-- Teacher Evaluation System Database Schema

CREATE DATABASE IF NOT EXISTS teacher_eval;
USE teacher_eval;

-- Departments
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Programs
CREATE TABLE programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- Users (Students, Program Heads, Deans, Admins)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('student','program_head','dean','admin') NOT NULL,
    program_id INT NULL,
    department_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- Teachers
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    department_id INT NOT NULL,
    program_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL
);

-- Evaluation Periods
CREATE TABLE evaluation_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Evaluations (Student responses are anonymous: rater_id NULL for students)
CREATE TABLE evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    rater_id INT NULL,
    rater_role ENUM('student','program_head','dean') NOT NULL,
    evaluation_period_id INT NOT NULL,
    
    -- Student criteria (50%)
    teaching_clarity INT CHECK(teaching_clarity BETWEEN 1 AND 5),
    engagement INT CHECK(engagement BETWEEN 1 AND 5),
    fairness INT CHECK(fairness BETWEEN 1 AND 5),
    
    -- Program Head criteria (30%)
    curriculum INT CHECK(curriculum BETWEEN 1 AND 5),
    assessment INT CHECK(assessment BETWEEN 1 AND 5),
    mentoring INT CHECK(mentoring BETWEEN 1 AND 5),
    
    -- Dean criteria (20%)
    attendance INT CHECK(attendance BETWEEN 1 AND 5),
    commitment INT CHECK(commitment BETWEEN 1 AND 5),
    quality INT CHECK(quality BETWEEN 1 AND 5),
    
    comments TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (rater_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (evaluation_period_id) REFERENCES evaluation_periods(id) ON DELETE CASCADE
);

-- Audit Logs
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Email Queue for Reminders
CREATE TABLE email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending','sent','failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO departments (name) VALUES 
('Computer Science'),
('Engineering'),
('Business Administration');

INSERT INTO programs (department_id, name) VALUES 
(1, 'BS Computer Science'),
(1, 'BS Information Technology'),
(2, 'BS Civil Engineering'),
(2, 'BS Mechanical Engineering'),
(3, 'BS Business Administration');

-- Admin: admin@school.edu / admin123
-- Dean: dean@school.edu / dean123
-- Program Head: ph@school.edu / ph123
-- Student: student@school.edu / student123
INSERT INTO users (email, password, full_name, role, program_id, department_id) VALUES
('admin@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin', NULL, NULL),
('dean@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'College Dean', 'dean', NULL, 1),
('ph@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Program Head', 'program_head', 1, 1),
('student@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Student', 'student', 1, 1);

INSERT INTO teachers (full_name, email, department_id, program_id) VALUES
('Dr. Alice Smith', 'alice@school.edu', 1, 1),
('Prof. Bob Jones', 'bob@school.edu', 1, 2),
('Dr. Carol White', 'carol@school.edu', 2, 3);

INSERT INTO evaluation_periods (title, start_date, end_date, is_active) VALUES
('First Semester 2024', '2024-01-01', '2024-05-31', 1);

-- AI Generated Teacher Summaries
CREATE TABLE teacher_summaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    evaluation_period_id INT NOT NULL,
    summary_text TEXT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT NULL,
    UNIQUE KEY unique_summary (teacher_id, evaluation_period_id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluation_period_id) REFERENCES evaluation_periods(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
);

