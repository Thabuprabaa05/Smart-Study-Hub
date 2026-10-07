-- ==============================================================
-- Smart Study Hub - Database Schema & Initial Data
-- College Study Group & Peer Notes Sharing Portal
-- Target MySQL / MariaDB (Compatible with phpMyAdmin & XAMPP)
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `smart_study_hub` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `smart_study_hub`;

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `student_id` VARCHAR(50) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `semester` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin') DEFAULT 'student',
  `profile_image` VARCHAR(255) DEFAULT 'default_avatar.png',
  `bio` TEXT DEFAULT NULL,
  `status` ENUM('active', 'suspended') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. SUBJECTS TABLE
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_code` VARCHAR(20) NOT NULL UNIQUE,
  `subject_name` VARCHAR(150) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `semester` VARCHAR(20) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. NOTES TABLE
CREATE TABLE IF NOT EXISTS `notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `tags` VARCHAR(255) DEFAULT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,
  `file_size` INT NOT NULL,
  `downloads` INT DEFAULT 0,
  `average_rating` DECIMAL(3,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`subject_id`),
  CONSTRAINT `fk_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notes_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BOOKMARKS TABLE
CREATE TABLE IF NOT EXISTS `bookmarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `note_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_note` (`user_id`, `note_id`),
  CONSTRAINT `fk_bookmarks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookmarks_note` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. RATINGS TABLE
CREATE TABLE IF NOT EXISTS `ratings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `note_id` INT NOT NULL,
  `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_rating` (`user_id`, `note_id`),
  CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ratings_note` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. STUDY GROUPS TABLE
CREATE TABLE IF NOT EXISTS `study_groups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `creator_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `group_name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `department` VARCHAR(100) NOT NULL,
  `semester` VARCHAR(20) NOT NULL,
  `max_members` INT DEFAULT 10,
  `meeting_preference` ENUM('Online', 'Offline', 'Both') DEFAULT 'Online',
  `status` ENUM('active', 'archived') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`creator_id`),
  INDEX (`subject_id`),
  CONSTRAINT `fk_groups_creator` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_groups_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. GROUP MEMBERS TABLE
CREATE TABLE IF NOT EXISTS `group_members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `role` ENUM('owner', 'member') DEFAULT 'member',
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_group_member` (`group_id`, `user_id`),
  CONSTRAINT `fk_members_group` FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. GROUP POSTS (DISCUSSIONS) TABLE
CREATE TABLE IF NOT EXISTS `group_posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `parent_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`group_id`),
  INDEX (`user_id`),
  CONSTRAINT `fk_posts_group` FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_posts_parent` FOREIGN KEY (`parent_id`) REFERENCES `group_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. ANNOUNCEMENTS TABLE
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`group_id`),
  CONSTRAINT `fk_announcements_group` FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_announcements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. NOTIFICATIONS TABLE
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `related_id` INT DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. REPORTS TABLE
CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reporter_id` INT NOT NULL,
  `content_type` ENUM('note', 'group_post', 'user', 'study_group') NOT NULL,
  `content_id` INT NOT NULL,
  `reason` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `status` ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
  `reviewed_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`reporter_id`),
  CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reports_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. PORTAL SETTINGS TABLE
CREATE TABLE IF NOT EXISTS `portal_settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- SAMPLE DATA INSERTION
-- ==============================================================

-- Settings
INSERT INTO `portal_settings` (`setting_key`, `setting_value`) VALUES
('portal_name', 'Smart Study Hub'),
('college_name', 'National Institute of Technology & Engineering'),
('academic_year', '2026-2027'),
('max_upload_size_mb', '25'),
('allow_registrations', '1');

-- Users
-- Password for admin: Admin@123
-- Password for students: Student@123
INSERT INTO `users` (`id`, `name`, `email`, `student_id`, `department`, `semester`, `password`, `role`, `bio`) VALUES
(1, 'Administrator', 'admin@smartstudy.edu', 'ADMIN001', 'Computer Science & Engineering', 'Faculty', '$2y$10$mzKwB8neMz2c0A2Yakuhgu7W0W4C5G8V1tn034JgmUpjNctLw22Um', 'admin', 'System Administrator & Academic Portal Coordinator.'),
(2, 'Rahul Sharma', 'rahul.sharma@college.edu', 'CS2023045', 'Computer Science & Engineering', 'Semester 5', '$2y$10$7mCF/TZgPOgk1UWqRaqNYu962NjtWhOX7RjdofZHSigxzy4rjigd.', 'student', 'Pre-final year CSE student passionate about DBMS and Backend Systems.'),
(3, 'Priya Patel', 'priya.patel@college.edu', 'CS2023089', 'Computer Science & Engineering', 'Semester 5', '$2y$10$7mCF/TZgPOgk1UWqRaqNYu962NjtWhOX7RjdofZHSigxzy4rjigd.', 'student', 'Algorithms enthusiast and competitive programmer. Love helping peers with OS and DSA!'),
(4, 'Ananya Sen', 'ananya.sen@college.edu', 'IT2023012', 'Information Technology', 'Semester 5', '$2y$10$7mCF/TZgPOgk1UWqRaqNYu962NjtWhOX7RjdofZHSigxzy4rjigd.', 'student', 'Web developer, UI designer, and tech blogger. Organizing AI/ML study tracks.'),
(5, 'Arjun Verma', 'arjun.verma@college.edu', 'EC2024031', 'Electronics & Communication', 'Semester 3', '$2y$10$7mCF/TZgPOgk1UWqRaqNYu962NjtWhOX7RjdofZHSigxzy4rjigd.', 'student', 'Hardware & embedded systems geek exploring Signal Processing & IoT networks.');

-- Subjects
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `department`, `semester`, `description`) VALUES
(1, 'CS301', 'Database Management Systems', 'Computer Science & Engineering', 'Semester 5', 'Relational models, ER diagrams, Normalization, SQL query optimization, Transactions and ACID properties.'),
(2, 'CS302', 'Operating Systems', 'Computer Science & Engineering', 'Semester 5', 'Process management, CPU scheduling, Memory virtualization, Deadlocks, File systems, and IPC.'),
(3, 'CS303', 'Computer Networks', 'Computer Science & Engineering', 'Semester 5', 'OSI model, TCP/IP stack, routing protocols, subnetting, congestion control, and transport layer security.'),
(4, 'CS304', 'Software Engineering', 'Computer Science & Engineering', 'Semester 5', 'Agile methodologies, SDLC, Design patterns, UML modeling, Software testing and CI/CD pipelines.'),
(5, 'CS305', 'Web Technology', 'Information Technology', 'Semester 5', 'Client-server architecture, modern HTML5/CSS3, JavaScript DOM, PHP backend APIs, and database integration.'),
(6, 'CS306', 'Data Structures & Algorithms', 'Computer Science & Engineering', 'Semester 3', 'Arrays, linked lists, trees, graphs, sorting algorithms, dynamic programming, and complexity analysis.'),
(7, 'CS307', 'Artificial Intelligence', 'Computer Science & Engineering', 'Semester 6', 'Heuristic search, Game playing, Knowledge representation, Machine learning basics, and Expert systems.'),
(8, 'EC301', 'Signals and Systems', 'Electronics & Communication', 'Semester 3', 'Continuous and discrete time signals, Fourier transform, Laplace transform, and Z-transforms.');

-- Notes
INSERT INTO `notes` (`id`, `user_id`, `subject_id`, `title`, `description`, `tags`, `file_name`, `file_path`, `file_type`, `file_size`, `downloads`, `average_rating`, `created_at`) VALUES
(1, 2, 1, 'Complete DBMS SQL & Normalization Cheat Sheet', 'Covers 1NF, 2NF, 3NF, BCNF with step-by-step decomposition examples, plus complex JOIN queries and indexing best practices.', 'SQL, Normalization, ACID, RDBMS', 'DBMS_Normalization_Guide.pdf', 'sample_dbms_normalization.pdf', 'pdf', 2457600, 48, 4.80, '2026-09-15 10:30:00'),
(2, 3, 2, 'Operating Systems Midterm Review & Process Scheduling', 'Handwritten detailed revision notes on FCFS, SJF, Round Robin, Bankers Algorithm for deadlock avoidance with solved numericals.', 'OS, Scheduling, Deadlock, Semaphore', 'OS_Midterm_Solved_Numericals.pdf', 'sample_os_midterm.pdf', 'pdf', 3145728, 35, 4.60, '2026-09-18 14:15:00'),
(3, 4, 3, 'Computer Networks TCP/IP & Subnetting Master Notes', 'Visual subnet masking calculation tables, CIDR notation cheatsheet, packet headers for IPv4/IPv6, and Wireshark capture guides.', 'Networking, Subnetting, TCP, OSI', 'CN_Subnetting_Guide.pdf', 'sample_cn_subnetting.pdf', 'pdf', 1887436, 62, 4.90, '2026-09-20 09:00:00'),
(4, 2, 4, 'Software Engineering Agile & Design Patterns Slide Notes', 'Concise lecture notes summarizing Factory, Singleton, Observer patterns with UML class diagrams and Scrum ceremonial workflows.', 'Agile, Design Patterns, UML, SDLC', 'SE_Design_Patterns.pdf', 'sample_se_patterns.pdf', 'pdf', 1572864, 21, 4.25, '2026-09-22 16:45:00'),
(5, 4, 5, 'Modern Web Tech - PHP, PDO & MySQL Security Notes', 'Covers secure session management, SQL injection prevention using prepared statements, and CSRF token implementation.', 'PHP, Web, PDO, Security, FullStack', 'WebTech_PHP_Security_Notes.pdf', 'sample_webtech_security.pdf', 'pdf', 2097152, 79, 5.00, '2026-09-25 11:20:00'),
(6, 3, 6, 'DSA Tree Traversals & Graph Algorithms Cheatsheet', 'BFS, DFS, Dijkstra, Bellman-Ford, Kruskals, and Prims algorithms with time-complexity breakdown and pseudocode.', 'DSA, Trees, Graphs, Dijkstra, Algorithms', 'DSA_Graph_Algorithms.pdf', 'sample_dsa_graphs.pdf', 'pdf', 2621440, 93, 4.75, '2026-09-28 17:10:00');

-- Ratings
INSERT INTO `ratings` (`id`, `user_id`, `note_id`, `rating`, `created_at`) VALUES
(1, 3, 1, 5, '2026-09-16 11:00:00'),
(2, 4, 1, 5, '2026-09-17 15:20:00'),
(3, 5, 1, 4, '2026-09-18 09:40:00'),
(4, 2, 2, 5, '2026-09-19 12:15:00'),
(5, 4, 2, 4, '2026-09-20 18:30:00'),
(6, 2, 3, 5, '2026-09-21 14:10:00'),
(7, 3, 3, 5, '2026-09-22 10:00:00'),
(8, 5, 3, 5, '2026-09-23 16:45:00'),
(9, 3, 4, 4, '2026-09-23 20:00:00'),
(10, 5, 4, 4, '2026-09-24 11:15:00'),
(11, 2, 5, 5, '2026-09-26 13:00:00'),
(12, 3, 5, 5, '2026-09-27 15:30:00'),
(13, 2, 6, 5, '2026-09-29 10:45:00'),
(14, 4, 6, 4, '2026-09-30 14:20:00');

-- Bookmarks
INSERT INTO `bookmarks` (`id`, `user_id`, `note_id`, `created_at`) VALUES
(1, 2, 2, '2026-09-19 12:00:00'),
(2, 2, 3, '2026-09-21 13:50:00'),
(3, 2, 6, '2026-09-29 10:30:00'),
(4, 3, 1, '2026-09-16 10:45:00'),
(5, 3, 5, '2026-09-26 12:30:00'),
(6, 4, 1, '2026-09-17 15:00:00'),
(7, 4, 3, '2026-09-20 09:30:00');

-- Study Groups
INSERT INTO `study_groups` (`id`, `creator_id`, `subject_id`, `group_name`, `description`, `department`, `semester`, `max_members`, `meeting_preference`, `status`, `created_at`) VALUES
(1, 2, 1, 'DBMS Query Crackers & Mini-Project Squad', 'Dedicated group for solving database lab queries, schema design, and collaborating on the semester DBMS project.', 'Computer Science & Engineering', 'Semester 5', 8, 'Both', 'active', '2026-09-16 10:00:00'),
(2, 3, 2, 'OS Kernel & Concurrency Pioneers', 'Weekly problem-solving sessions on synchronization, mutexes, virtual memory paging, and Linux shell scripting.', 'Computer Science & Engineering', 'Semester 5', 10, 'Online', 'active', '2026-09-18 16:30:00'),
(3, 4, 5, 'Full-Stack Web Innovators', 'Building hands-on modern web applications using PHP, JavaScript, and MySQL. We conduct peer code reviews and UI brainstorming.', 'Information Technology', 'Semester 5', 12, 'Both', 'active', '2026-09-20 11:00:00'),
(4, 5, 8, 'Circuits & Signals Study Circle', 'Tackling Fourier and Laplace transform assignments and lab signal representations with MATLAB simulations.', 'Electronics & Communication', 'Semester 3', 6, 'Offline', 'active', '2026-09-22 14:00:00');

-- Group Members
INSERT INTO `group_members` (`id`, `group_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 2, 'owner', '2026-09-16 10:00:00'),
(2, 1, 3, 'member', '2026-09-16 14:20:00'),
(3, 1, 4, 'member', '2026-09-17 09:15:00'),
(4, 2, 3, 'owner', '2026-09-18 16:30:00'),
(5, 2, 2, 'member', '2026-09-19 11:00:00'),
(6, 2, 4, 'member', '2026-09-19 15:40:00'),
(7, 3, 4, 'owner', '2026-09-20 11:00:00'),
(8, 3, 2, 'member', '2026-09-21 08:30:00'),
(9, 3, 3, 'member', '2026-09-21 16:00:00'),
(10, 4, 5, 'owner', '2026-09-22 14:00:00'),
(11, 4, 2, 'member', '2026-09-23 10:15:00');

-- Announcements
INSERT INTO `announcements` (`id`, `group_id`, `user_id`, `title`, `message`, `created_at`) VALUES
(1, 1, 2, 'Internal Assessment Lab Revision Session', 'We are organizing an online meeting this Thursday at 6:00 PM on Google Meet to review complex SQL subqueries and indexing problems.', '2026-10-01 18:00:00'),
(2, 2, 3, 'Deadlock Numericals Doubt Clearing', 'Don’t forget to check the OS midterm solved numericals note before tomorrow’s tutorial class! We will discuss questions 4 and 7.', '2026-10-02 12:30:00'),
(3, 3, 4, 'Project Sprint 1 Code Review', 'Sprint 1 database schema files have been uploaded. Please review the table relationships before Friday.', '2026-10-03 15:00:00');

-- Group Posts (Discussions)
INSERT INTO `group_posts` (`id`, `group_id`, `user_id`, `message`, `parent_id`, `created_at`) VALUES
(1, 1, 3, 'Hey everyone! Has anyone solved question 5 from the Assignment 2 set on BCNF decomposition?', NULL, '2026-10-02 10:15:00'),
(2, 1, 2, 'Yes Priya! The relation decomposes into R1(A, B, C) and R2(C, D). I have verified it with Prof. Rao.', 1, '2026-10-02 10:35:00'),
(3, 1, 4, 'Thanks Rahul! That matches my solution too. That saved a lot of time!', 1, '2026-10-02 11:00:00'),
(4, 2, 2, 'In the Round Robin scheduling algorithm, how do we decide the optimal time quantum when process bursts vary wildly?', NULL, '2026-10-03 09:20:00'),
(5, 2, 3, 'Rule of thumb is roughly 80% of CPU bursts should be shorter than the time quantum to prevent excessive context switching.', 4, '2026-10-03 09:45:00'),
(6, 3, 2, 'Which library should we recommend for client-side validation, or should we stick to pure modern JavaScript?', NULL, '2026-10-04 14:10:00'),
(7, 3, 4, 'Pure modern JS is best for learning foundations and avoiding dependency bloat!', 6, '2026-10-04 14:25:00');

-- Notifications
INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `related_id`, `is_read`, `created_at`) VALUES
(1, 2, 'Priya Patel rated your note "Complete DBMS SQL & Normalization Cheat Sheet" with 5 stars.', 'rating', 1, 0, '2026-09-16 11:00:00'),
(2, 2, 'Priya Patel joined your study group "DBMS Query Crackers & Mini-Project Squad".', 'group_join', 1, 0, '2026-09-16 14:20:00'),
(3, 2, 'Ananya Sen replied to your discussion in "Full-Stack Web Innovators".', 'post_reply', 3, 0, '2026-10-04 14:25:00'),
(4, 3, 'New announcement posted in "DBMS Query Crackers & Mini-Project Squad": Internal Assessment Lab Revision Session.', 'announcement', 1, 0, '2026-10-01 18:00:00'),
(5, 4, 'Rahul Sharma rated your note "Computer Networks TCP/IP & Subnetting Master Notes" with 5 stars.', 'rating', 3, 1, '2026-09-21 14:10:00');

-- Reports (Sample pending report for admin review)
INSERT INTO `reports` (`id`, `reporter_id`, `content_type`, `content_id`, `reason`, `description`, `status`, `reviewed_by`, `created_at`) VALUES
(1, 5, 'note', 4, 'Wrong subject', 'This note belongs to Software Engineering but was briefly listed in IT elective semester 4.', 'pending', NULL, '2026-10-04 16:30:00');
