-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 01, 2025 at 08:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `academic_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `max_points` decimal(5,2) DEFAULT 100.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late') DEFAULT 'present',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) DEFAULT 3,
  `max_students` int(11) DEFAULT 30,
  `instructor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `code`, `title`, `description`, `credits`, `max_students`, `instructor_id`, `created_at`) VALUES
(1, 'CS101', 'Introduction to Computer Science', 'Basic concepts of computer science and programming', 3, 30, 2, '2025-09-01 17:06:58'),
(2, 'IT201', 'Database Systems', 'Fundamentals of database design and SQL', 3, 25, 3, '2025-09-01 17:06:58'),
(3, 'CS301', 'Data Structures', 'Advanced data structures and algorithms', 4, 20, 2, '2025-09-01 17:06:58');

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `material_type` enum('document','video','link','other') DEFAULT 'document',
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('enrolled','dropped','completed') DEFAULT 'enrolled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `enrollment_date`, `status`) VALUES
(1, 4, 1, '2025-09-01 17:06:58', 'enrolled'),
(2, 4, 2, '2025-09-01 17:06:58', 'enrolled'),
(3, 5, 1, '2025-09-01 17:06:58', 'enrolled'),
(4, 5, 3, '2025-09-01 17:06:58', 'enrolled');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `grade_letter` varchar(5) DEFAULT NULL,
  `grade_points` decimal(3,2) DEFAULT NULL,
  `term` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `course_id`, `enrollment_id`, `assignment_id`, `grade`, `grade_letter`, `grade_points`, `term`, `created_at`) VALUES
(1, 4, 1, 1, NULL, 95.50, 'A', 4.00, 'Fall 2023', '2025-09-01 17:06:58'),
(2, 4, 2, 2, NULL, 87.30, 'B+', 3.30, 'Fall 2023', '2025-09-01 17:06:58'),
(3, 5, 1, 3, NULL, 92.10, 'A-', 3.70, 'Fall 2023', '2025-09-01 17:06:58'),
(4, 5, 3, 4, NULL, 78.90, 'C+', 2.30, 'Fall 2023', '2025-09-01 17:06:58');

-- --------------------------------------------------------

--
-- Table structure for table `levels`
--

CREATE TABLE `levels` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `levels`
--

INSERT INTO `levels` (`id`, `name`, `code`, `description`, `status`, `created_at`) VALUES
(1, 'Level 1', 'L1', 'First year undergraduate', 'active', '2025-09-01 17:06:58'),
(2, 'Level 2', 'L2', 'Second year undergraduate', 'active', '2025-09-01 17:06:58'),
(3, 'Level 3', 'L3', 'Third year undergraduate', 'active', '2025-09-01 17:06:58'),
(4, 'Level 4', 'L4', 'Fourth year undergraduate', 'active', '2025-09-01 17:06:58'),
(5, 'Graduate', 'GRAD', 'Graduate level', 'active', '2025-09-01 17:06:58');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `degree_level` enum('BACHELOR','MASTER','PHD','CERTIFICATE') DEFAULT 'BACHELOR',
  `total_credits` int(11) DEFAULT 120,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `code`, `name`, `department`, `degree_level`, `total_credits`, `description`, `status`, `created_at`) VALUES
(1, 'BSCS', 'Bachelor of Science in Computer Science', 'CS', 'BACHELOR', 120, 'Comprehensive computer science program', 'active', '2025-09-01 17:06:58'),
(2, 'BSEE', 'Bachelor of Science in Electrical Engineering', 'EE', 'BACHELOR', 128, 'Electrical engineering fundamentals', 'active', '2025-09-01 17:06:58'),
(3, 'MBA', 'Master of Business Administration', 'BA', 'MASTER', 60, 'Business administration and management', 'active', '2025-09-01 17:06:58'),
(4, 'MSCS', 'Master of Science in Computer Science', 'CS', 'MASTER', 36, 'Advanced computer science studies', 'inactive', '2025-09-01 17:06:58');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `registration_start` date DEFAULT NULL,
  `registration_end` date DEFAULT NULL,
  `status` enum('active','inactive','current') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `name`, `code`, `start_date`, `end_date`, `registration_start`, `registration_end`, `status`, `created_at`) VALUES
(1, 'Fall 2023', 'FA23', '2023-09-01', '2023-12-15', '2023-08-01', '2023-08-31', 'current', '2025-09-01 17:06:58'),
(2, 'Spring 2024', 'SP24', '2024-01-15', '2024-05-15', '2023-12-01', '2023-12-31', 'active', '2025-09-01 17:06:58'),
(3, 'Summer 2024', 'SU24', '2024-06-01', '2024-08-15', '2024-05-01', '2024-05-31', 'active', '2025-09-01 17:06:58');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `grade` varchar(10) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `level_id` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `place_of_birth` varchar(255) DEFAULT NULL,
  `last_school_attended` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `degree` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `id_card_path` varchar(500) DEFAULT NULL,
  `gce_cert_path` varchar(500) DEFAULT NULL,
  `birth_cert_path` varchar(500) DEFAULT NULL,
  `role` enum('student','instructor','admin') NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `program_id`, `semester_id`, `level_id`, `date_of_birth`, `place_of_birth`, `last_school_attended`, `gender`, `degree`, `specialization`, `id_card_path`, `gce_cert_path`, `birth_cert_path`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@system.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', '2025-09-01 17:06:58', '2025-09-01 17:06:58'),
(2, 'Dr. John Smith', 'john@instructor.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Data Structures and Algorithms', NULL, NULL, NULL, 'instructor', '2025-09-01 17:06:58', '2025-09-01 17:06:58'),
(3, 'Prof. Jane Doe', 'jane@instructor.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Database Systems', NULL, NULL, NULL, 'instructor', '2025-09-01 17:06:58', '2025-09-01 17:06:58'),
(4, 'Alice Johnson', 'alice@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 1, 1, 1, NULL, NULL, NULL, 'female', NULL, NULL, NULL, NULL, NULL, 'student', '2025-09-01 17:06:58', '2025-09-01 17:06:58'),
(5, 'Bob Wilson', 'bob@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 1, 1, 2, NULL, NULL, NULL, 'male', NULL, NULL, NULL, NULL, NULL, 'student', '2025-09-01 17:06:58', '2025-09-01 17:06:58'),
(6, '0COLLINSZXO', '0COLLINSZXO@GMAIL.COM', '$2y$10$dOAecRWLJfOzBbDQz05yteFB7/osDDh4V9vKsixsUHyLsBdqQUIrW', '0COLLINSZXO', 2, NULL, 1, '2025-09-23', 'iiiuiuuuu', 'iuiiii', NULL, NULL, NULL, '../uploads/students/68b5d85d5e61a_INSTAGRAM.jpeg', '../uploads/students/68b5d85d5eb81_INSTAGRAM.jpeg', '../uploads/students/68b5d85d5f05e_download.png', 'student', '2025-09-01 17:31:09', '2025-09-01 17:31:09'),
(7, '0COLLINSZXO', '60COLLINSZXO@GMAIL.COM', '$2y$10$1NHfSD/uTfUnYS82E40.DOQkID.IrrbhNa35/OO.sUDrvhZ8NyfBq', '0COLLINSZXO', 2, NULL, 1, '2025-09-23', 'iiiuiuuuu', 'iuiiii', NULL, NULL, NULL, '../uploads/students/68b5d93d9fe3d_INSTAGRAM.jpeg', '../uploads/students/68b5d93da080a_INSTAGRAM.jpeg', '../uploads/students/68b5d93da0efd_download.png', 'student', '2025-09-01 17:34:53', '2025-09-01 17:34:53'),
(8, 'qqqq', '550COLLINSZXO@GMAIL.COM', '$2y$10$XheRlg7YC6Z/x9/ActtlIO3xdlOtzy4VLR4dlb.CBAPIbwQXSoqoS', 'qqqq', 2, NULL, 2, '2025-09-05', 'wqsqs', 'qqq', NULL, NULL, NULL, '../uploads/students/68b5d9c31fdca_download.png', '../uploads/students/68b5d9c320359_INSTAGRAM.jpeg', '../uploads/students/68b5d9c320894_TWITTER (1).png', 'student', '2025-09-01 17:37:07', '2025-09-01 17:37:07'),
(9, 'qqqqqqqq', '0saaaCOLLINSZXO@GMAIL.COM', '$2y$10$mJQgFRh9JDzYw3hVzm50LOPDuSeqHyI9sQ3vI0AbvAhHOVax4.I.u', 'qqqqq', 3, NULL, 4, '2025-09-18', 'qqq', 'qqqqqq', NULL, NULL, NULL, '../uploads/students/68b5da0ebfd48_TWITTER (1).png', '../uploads/students/68b5da0ec02bb_web.jpeg', '../uploads/students/68b5da0ec07b4_web.jpeg', 'student', '2025-09-01 17:38:22', '2025-09-01 17:38:22'),
(10, 'ururu', '2220COLLINSZXO@GMAIL.COM', '$2y$10$vRLt9DACdQLwZanDj3kqEevvuhbt8VvxWXO7OdPD3JxqAnooDF8Ia', 'rr', 1, NULL, 2, '2025-09-05', 'iiiuiuuuu', 'rrr', NULL, NULL, NULL, '../uploads/students/68b5dac9d99a7_TWITTER (1).png', '../uploads/students/68b5dac9d9e2e_download.png', '../uploads/students/68b5dac9da2c7_TWITTER (1).png', 'student', '2025-09-01 17:41:30', '2025-09-01 17:41:30'),
(11, 'NKEM COLLINS', 'rrrrrr0COLLINSZXO@GMAIL.COM', '$2y$10$Vr5sDeWyyvrufUO4iO5L5OFJZzGay93ASvC5DUNouXBOpWlcrM0vy', '651342166', 2, NULL, 2, '2025-09-10', 'iiiuiuuuu', 'rrrrrrrrrrr', NULL, NULL, NULL, '../uploads/students/68b5db0eba59f_TWITTER (1).png', '../uploads/students/68b5db0ebaac3_web.jpeg', '../uploads/students/68b5db0ebb00c_WhatsApp Image 2025-02-06 at 13.29.51_b1f2e34b.jpg', 'student', '2025-09-01 17:42:38', '2025-09-01 17:42:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`course_id`,`date`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `level_id` (`level_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `levels`
--
ALTER TABLE `levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_materials_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_3` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_4` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
