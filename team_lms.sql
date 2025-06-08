-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 27, 2025 at 07:03 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `team_lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `archived_submissions`
--

DROP TABLE IF EXISTS `archived_submissions`;
CREATE TABLE IF NOT EXISTS `archived_submissions` (
  `submission_id` int NOT NULL AUTO_INCREMENT,
  `assignment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_general_ci,
  `archive_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`submission_id`),
  KEY `assignment_id` (`assignment_id`),
  KEY `student_id` (`student_id`),
  KEY `idx_submissions_time` (`submitted_at`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
CREATE TABLE IF NOT EXISTS `assignments` (
  `assignment_id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`assignment_id`),
  KEY `course_id` (`course_id`),
  KEY `idx_assignments_due` (`due_date`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assignment_id`, `course_id`, `title`, `description`, `due_date`, `created_at`) VALUES
(1, 1, 'First assigment', 'First assignment', '2025-05-19 21:11:00', '2025-05-17 15:41:05'),
(2, 2, 'First assigment', 'First Assignment', '2025-05-19 01:38:00', '2025-05-17 20:09:16'),
(3, 2, 'Assignment 3', 'new assignment', '2025-05-28 19:47:00', '2025-05-27 14:17:18'),
(4, 3, 'Java assignment', 'Java assignment', '2025-05-29 23:59:00', '2025-05-27 18:03:44');

-- --------------------------------------------------------

--
-- Stand-in structure for view `assignment_submissions`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `assignment_submissions`;
CREATE TABLE IF NOT EXISTS `assignment_submissions` (
`submission_id` int
,`assignment_id` int
,`student_id` int
,`file_path` varchar(255)
,`submitted_at` datetime
,`grade` decimal(5,2)
,`feedback` text
,`username` varchar(50)
,`email` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `choices`
--

DROP TABLE IF EXISTS `choices`;
CREATE TABLE IF NOT EXISTS `choices` (
  `choice_id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `choice_text` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`choice_id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_items`
--

DROP TABLE IF EXISTS `content_items`;
CREATE TABLE IF NOT EXISTS `content_items` (
  `content_id` int NOT NULL AUTO_INCREMENT,
  `lesson_id` int NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `content_type` enum('video','document','link','text') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'document',
  `file_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`content_id`),
  KEY `lesson_id` (`lesson_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_items`
--

INSERT INTO `content_items` (`content_id`, `lesson_id`, `title`, `content_type`, `file_path`, `created_at`) VALUES
(3, 2, 'UI Basics Video', 'video', 'uploads/assignments/1747567976_JQuery3.pdf', '2025-05-18 19:54:33');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `course_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `instructor_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`course_id`),
  KEY `instructor_id` (`instructor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `title`, `description`, `instructor_id`, `created_at`) VALUES
(1, 'UI/UX Designing', 'UI/UX Designing Course', 2, '2025-05-17 14:49:08'),
(2, 'Introduction to Cloud Computing', 'Introduction to Cloud Computing', 3, '2025-05-17 20:07:26'),
(3, 'Introduction to Java', 'Introduction to Java', 7, '2025-05-27 17:54:53');

-- --------------------------------------------------------

--
-- Table structure for table `discussions`
--

DROP TABLE IF EXISTS `discussions`;
CREATE TABLE IF NOT EXISTS `discussions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `author_id` int NOT NULL,
  `course_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `course_id` (`course_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discussions`
--

INSERT INTO `discussions` (`id`, `title`, `content`, `author_id`, `course_id`, `created_at`) VALUES
(1, 'Poster Design', 'Poster Design Course', 2, 1, '2025-05-17 14:50:40');

-- --------------------------------------------------------

--
-- Table structure for table `discussion_replies`
--

DROP TABLE IF EXISTS `discussion_replies`;
CREATE TABLE IF NOT EXISTS `discussion_replies` (
  `reply_id` int NOT NULL AUTO_INCREMENT,
  `discussion_id` int NOT NULL,
  `user_id` int NOT NULL,
  `reply` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reply_id`),
  KEY `discussion_id` (`discussion_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discussion_replies`
--

INSERT INTO `discussion_replies` (`reply_id`, `discussion_id`, `user_id`, `reply`, `created_at`) VALUES
(1, 1, 1, 'Great', '2025-05-17 15:33:27'),
(2, 1, 1, 'Thank you', '2025-05-20 17:27:05'),
(3, 1, 2, 'Welcome', '2025-05-20 17:34:02');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE IF NOT EXISTS `enrollments` (
  `enrollment_id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `user_id` int NOT NULL,
  `enroll_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','completed','dropped') COLLATE utf8mb4_general_ci DEFAULT 'active',
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `course_id` (`course_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_course_enrollments` (`course_id`,`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `course_id`, `user_id`, `enroll_date`, `status`) VALUES
(6, 2, 4, '2025-05-18 11:24:28', 'active'),
(10, 2, 1, '2025-05-18 12:32:10', 'active'),
(12, 1, 4, '2025-05-18 16:55:53', 'active'),
(15, 1, 1, '2025-05-27 18:41:33', 'active'),
(17, 2, 5, '2025-05-27 19:21:12', 'active'),
(19, 1, 5, '2025-05-27 20:20:01', 'active'),
(22, 1, 6, '2025-05-27 23:12:16', 'active'),
(23, 2, 6, '2025-05-27 23:12:22', 'active'),
(24, 3, 6, '2025-05-27 23:27:43', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

DROP TABLE IF EXISTS `forum_posts`;
CREATE TABLE IF NOT EXISTS `forum_posts` (
  `post_id` int NOT NULL AUTO_INCREMENT,
  `thread_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `posted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  KEY `thread_id` (`thread_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_threads`
--

DROP TABLE IF EXISTS `forum_threads`;
CREATE TABLE IF NOT EXISTS `forum_threads` (
  `thread_id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`thread_id`),
  KEY `course_id` (`course_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE IF NOT EXISTS `grades` (
  `grade_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `assignment_id` int DEFAULT NULL,
  `quiz_id` int DEFAULT NULL,
  `grade_value` float NOT NULL,
  `graded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`grade_id`),
  KEY `user_id` (`user_id`),
  KEY `course_id` (`course_id`),
  KEY `assignment_id` (`assignment_id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `idx_assignment_grades` (`assignment_id`,`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`grade_id`, `user_id`, `course_id`, `assignment_id`, `quiz_id`, `grade_value`, `graded_at`) VALUES
(1, 1, 2, NULL, 2, 0, '2025-05-18 13:28:33'),
(2, 1, 2, NULL, 2, 0, '2025-05-18 13:32:08'),
(3, 1, 1, NULL, 1, 0, '2025-05-18 13:32:31'),
(4, 1, 2, NULL, 2, 0, '2025-05-18 13:35:40'),
(5, 1, 2, NULL, 2, 0, '2025-05-18 13:35:57'),
(6, 1, 2, NULL, 2, 0, '2025-05-18 13:36:09'),
(7, 1, 1, NULL, 1, 0, '2025-05-18 13:36:45'),
(8, 1, 2, NULL, 2, 0, '2025-05-18 13:38:50'),
(9, 1, 1, NULL, 1, 100, '2025-05-18 13:43:55'),
(10, 4, 2, NULL, 2, 100, '2025-05-18 16:58:20'),
(11, 4, 1, NULL, 1, 100, '2025-05-18 16:58:39'),
(12, 4, 2, NULL, 2, 100, '2025-05-18 17:02:27'),
(13, 4, 2, NULL, 2, 100, '2025-05-18 17:05:18'),
(14, 4, 1, NULL, 1, 100, '2025-05-18 17:05:33'),
(15, 1, 2, NULL, 2, 100, '2025-05-19 00:15:28'),
(16, 4, 1, NULL, 1, 100, '2025-05-27 19:13:43'),
(17, 6, 1, NULL, 1, 100, '2025-05-27 23:16:10'),
(18, 6, 2, NULL, 2, 100, '2025-05-27 23:17:01'),
(19, 6, 3, NULL, 4, 0, '2025-05-27 23:45:22'),
(20, 6, 2, NULL, 2, 200, '2025-05-27 23:48:55'),
(21, 6, 3, NULL, 4, 0, '2025-05-27 23:49:51'),
(22, 6, 3, NULL, 4, 0, '2025-05-27 23:52:05'),
(23, 6, 3, NULL, 4, 0, '2025-05-27 23:58:35'),
(24, 6, 3, NULL, 4, 0, '2025-05-27 23:58:39'),
(26, 6, 3, NULL, 4, 0, '2025-05-28 00:22:19'),
(27, 6, 3, NULL, 4, 0, '2025-05-28 00:22:26'),
(28, 6, 3, NULL, 4, 100, '2025-05-28 00:24:45'),
(29, 6, 3, NULL, 4, 100, '2025-05-28 00:25:08'),
(30, 6, 3, NULL, 4, 100, '2025-05-28 00:26:24'),
(31, 6, 3, NULL, 5, 0, '2025-05-28 00:29:12'),
(32, 6, 3, NULL, 5, 0, '2025-05-28 00:29:57');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

DROP TABLE IF EXISTS `lessons`;
CREATE TABLE IF NOT EXISTS `lessons` (
  `lesson_id` int NOT NULL AUTO_INCREMENT,
  `module_id` int NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`lesson_id`),
  KEY `module_id` (`module_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`lesson_id`, `module_id`, `title`, `content`) VALUES
(1, 1, 'Intro to UI', 'What is UI design?'),
(2, 1, 'Introduction to UI Design', 'Overview of basic UI principles.');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `subject` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
CREATE TABLE IF NOT EXISTS `modules` (
  `module_id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`module_id`),
  KEY `course_id` (`course_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`module_id`, `course_id`, `title`, `description`, `file_path`) VALUES
(1, 1, 'Fundamentals of UI Design', 'Fundamentals of UI Design', 'uploads/modules/6828eafb8819a_ALBY_AI_ASMNT_Final.pdf'),
(2, 1, 'Fundamentals of UI Design', 'Fundamentals of UI Design', 'uploads/modules/6828eb13ca7fd_ALBY_AI_ASMNT_Final.pdf'),
(3, 2, 'Introduction to Cloud Computing', 'Introduction to Cloud Computing', 'uploads/modules/6828ecaec72c1_ALBY_AI_ASMNT_Final.pdf'),
(4, 2, 'Introduction to Cloud Computing', 'Introduction to Cloud Computing', 'uploads/modules/682a2953a098c_1-768176b1-b47d-4d4b-973a-6d62411c2e01.pdf'),
(5, 3, 'First Module', 'First Module', 'uploads/modules/6835fca1855a4_alby_admbs_front.pdf'),
(6, 3, 'Second Module', 'Second Module', 'uploads/modules/6835fe196b258_Alby_report.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
CREATE TABLE IF NOT EXISTS `questions` (
  `question_id` int NOT NULL AUTO_INCREMENT,
  `quiz_id` int NOT NULL,
  `question_text` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`question_id`),
  KEY `quiz_id` (`quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

DROP TABLE IF EXISTS `quizzes`;
CREATE TABLE IF NOT EXISTS `quizzes` (
  `quiz_id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`quiz_id`),
  KEY `course_id` (`course_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`quiz_id`, `course_id`, `title`, `description`, `created_at`) VALUES
(1, 1, 'First Quiz', 'First Quiz', '2025-05-17 15:41:38'),
(2, 2, 'Introduction to Cloud Computing', 'Introduction to Cloud Computing', '2025-05-17 20:09:32'),
(4, 3, 'Java Quiz', 'Java Quiz', '2025-05-27 18:10:33'),
(5, 3, 'Java Quiz 2', 'Java Quiz 2', '2025-05-27 18:57:50');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

DROP TABLE IF EXISTS `quiz_attempts`;
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
  `attempt_id` int NOT NULL AUTO_INCREMENT,
  `quiz_id` int NOT NULL,
  `user_id` int NOT NULL,
  `score` float DEFAULT NULL,
  `max_score` float DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`attempt_id`),
  KEY `idx_quiz_user` (`quiz_id`,`user_id`),
  KEY `fk_qa_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`attempt_id`, `quiz_id`, `user_id`, `score`, `max_score`, `started_at`, `completed_at`) VALUES
(1, 2, 1, 100, 1, '2025-05-19 00:23:48', '2025-05-19 00:23:48'),
(2, 1, 1, 100, 2, '2025-05-19 00:31:46', '2025-05-19 00:31:46'),
(3, 2, 1, 100, 1, '2025-05-19 00:41:58', '2025-05-19 00:41:58'),
(4, 1, 1, 100, 2, '2025-05-19 00:42:13', '2025-05-19 00:42:13'),
(5, 2, 1, 100, 1, '2025-05-19 00:50:15', '2025-05-19 00:50:15'),
(6, 1, 4, 100, 2, '2025-05-27 19:13:43', '2025-05-27 19:13:43'),
(7, 1, 6, 100, 2, '2025-05-27 23:16:10', '2025-05-27 23:16:10'),
(8, 2, 6, 100, 1, '2025-05-27 23:17:01', '2025-05-27 23:17:01'),
(9, 4, 6, 0, 1, '2025-05-27 23:45:22', '2025-05-27 23:45:22'),
(10, 2, 6, 100, 1, '2025-05-27 23:48:55', '2025-05-27 23:48:55'),
(16, 4, 6, 0, 100, '2025-05-28 00:22:19', '2025-05-28 00:22:19'),
(17, 4, 6, 0, 100, '2025-05-28 00:22:26', '2025-05-28 00:22:26'),
(18, 4, 6, 100, 100, '2025-05-28 00:24:45', '2025-05-28 00:24:45'),
(19, 4, 6, 100, 100, '2025-05-28 00:25:08', '2025-05-28 00:25:08'),
(20, 4, 6, 100, 100, '2025-05-28 00:26:24', '2025-05-28 00:26:24'),
(21, 5, 6, 0, 100, '2025-05-28 00:29:12', '2025-05-28 00:29:12'),
(22, 5, 6, 0, 100, '2025-05-28 00:29:57', '2025-05-28 00:29:57');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

DROP TABLE IF EXISTS `quiz_questions`;
CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `question_id` int NOT NULL AUTO_INCREMENT,
  `quiz_id` int NOT NULL,
  `question_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `option_a` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `option_b` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `option_c` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `option_d` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correct_option` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`question_id`),
  KEY `quiz_id` (`quiz_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`question_id`, `quiz_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `created_at`) VALUES
(1, 1, 'Which of the following principles is most important when designing a user-friendly interface?', 'Using complex animations and transitions', 'Prioritizing aesthetic design over functionality', 'Ensuring consistency and simplicity across the interface', 'Adding as many features as possible on a single screen', 'c', '2025-05-17 16:07:52'),
(2, 1, 'Which of the following principles is most important when designing a user-friendly interface?', 'Using complex animations and transitions', 'Prioritizing aesthetic design over functionality', 'Ensuring consistency and simplicity across the interface', 'Adding as many features as possible on a single screen', 'c', '2025-05-17 16:10:18'),
(3, 2, 'Which of the following is not a characteristic of cloud computing?', 'On-demand self-service', 'Resource pooling', 'Manual scaling', 'Broad network access', 'c', '2025-05-17 20:11:45'),
(4, 4, 'Which of the following is the correct syntax to print \"Hello, World!\" in Java?', 'print(\"Hello, World!\");', 'printf(\"Hello, World!\");', 'System.out.println(\"Hello, World!\");', 'echo(\"Hello, World!\");', 'C', '2025-05-27 18:13:38'),
(5, 5, 'Which data type is used to store whole numbers in Java?', 'Float', 'Char', 'Int', 'Boolean', '3', '2025-05-27 18:58:23');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_responses`
--

DROP TABLE IF EXISTS `quiz_responses`;
CREATE TABLE IF NOT EXISTS `quiz_responses` (
  `response_id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `student_id` int NOT NULL,
  `chosen_option` char(1) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Stores A, B, C, or D',
  `answered_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`response_id`),
  KEY `question_id` (`question_id`),
  KEY `student_id` (`student_id`),
  KEY `chosen_choice_id` (`chosen_option`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_responses`
--

INSERT INTO `quiz_responses` (`response_id`, `question_id`, `student_id`, `chosen_option`, `answered_at`) VALUES
(10, 1, 1, 'B', '2025-05-18 13:19:04'),
(11, 3, 1, 'c', '2025-05-18 13:28:33'),
(12, 3, 1, 'c', '2025-05-18 13:32:08'),
(13, 1, 1, 'c', '2025-05-18 13:32:31'),
(14, 2, 1, 'c', '2025-05-18 13:32:31'),
(15, 3, 1, 'b', '2025-05-18 13:35:40'),
(16, 3, 1, 'a', '2025-05-18 13:35:57'),
(17, 3, 1, 'd', '2025-05-18 13:36:09'),
(18, 1, 1, 'a', '2025-05-18 13:36:45'),
(19, 2, 1, 'c', '2025-05-18 13:36:45'),
(20, 3, 1, 'c', '2025-05-18 13:38:50'),
(21, 1, 1, 'c', '2025-05-18 13:43:55'),
(22, 2, 1, 'c', '2025-05-18 13:43:55'),
(23, 3, 4, 'c', '2025-05-18 16:58:20'),
(24, 1, 4, 'c', '2025-05-18 16:58:39'),
(25, 2, 4, 'c', '2025-05-18 16:58:39'),
(26, 3, 4, 'c', '2025-05-18 17:02:27'),
(27, 3, 4, 'c', '2025-05-18 17:05:18'),
(28, 1, 4, 'c', '2025-05-18 17:05:33'),
(29, 2, 4, 'c', '2025-05-18 17:05:33'),
(30, 3, 1, 'c', '2025-05-19 00:15:28'),
(31, 3, 1, 'c', '2025-05-19 00:23:48'),
(32, 2, 1, 'c', '2025-05-19 00:31:46'),
(33, 1, 1, 'c', '2025-05-19 00:31:46'),
(34, 3, 1, 'c', '2025-05-19 00:41:58'),
(35, 2, 1, 'c', '2025-05-19 00:42:13'),
(36, 1, 1, 'c', '2025-05-19 00:42:13'),
(37, 3, 1, 'c', '2025-05-19 00:50:15'),
(38, 1, 4, 'c', '2025-05-27 19:13:43'),
(39, 2, 4, 'c', '2025-05-27 19:13:43'),
(40, 1, 6, 'c', '2025-05-27 23:16:10'),
(41, 2, 6, 'c', '2025-05-27 23:16:10'),
(42, 3, 6, 'c', '2025-05-27 23:17:01'),
(44, 3, 6, 'c', '2025-05-27 23:48:55'),
(54, 4, 6, 'C', '2025-05-28 00:26:24'),
(56, 5, 6, 'C', '2025-05-28 00:29:57');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

DROP TABLE IF EXISTS `submissions`;
CREATE TABLE IF NOT EXISTS `submissions` (
  `submission_id` int NOT NULL AUTO_INCREMENT,
  `assignment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`submission_id`),
  KEY `assignment_id` (`assignment_id`),
  KEY `student_id` (`student_id`),
  KEY `idx_submissions_time` (`submitted_at`)
) ;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`submission_id`, `assignment_id`, `student_id`, `file_path`, `submitted_at`, `grade`, `feedback`) VALUES
(1, 2, 1, 'uploads/assignments/1747552940_s2 (1).pdf', '2025-05-18 12:52:20', 100.00, ''),
(2, 2, 1, 'uploads/assignments/1747553491_1-84b5eb20-cdef-4eea-a9b1-ba8038289c8d.pdf', '2025-05-18 13:01:31', 98.00, ''),
(3, 2, 1, 'uploads/assignments/1747553645_Human Computer Interaction (In English).pdf', '2025-05-18 13:04:05', 97.00, ''),
(4, 1, 4, 'uploads/assignments/1747567598_1-84b5eb20-cdef-4eea-a9b1-ba8038289c8d.pdf', '2025-05-18 16:56:38', 98.00, 'Good'),
(5, 2, 4, 'uploads/assignments/1747567628_1-6509b2e9-24d3-4d40-b133-bb9ecd268d6f.pdf', '2025-05-18 16:57:08', 95.00, ''),
(6, 2, 4, 'uploads/assignments/1747567976_JQuery3.pdf', '2025-05-18 17:02:56', 93.00, ''),
(7, 1, 1, 'uploads/assignments/1747594138_1-82453184-cce7-4869-888c-b2ff72af48c7_page-0001.pdf', '2025-05-19 00:18:58', 95.00, 'Good'),
(11, 2, 5, 'uploads/assignments/1748358522_Alby_report.pdf', '2025-05-27 20:38:42', NULL, NULL),
(12, 1, 6, NULL, '2025-05-27 23:13:33', NULL, NULL),
(13, 4, 6, 'uploads/assignments/1748369697_lec01_overview.pptx', '2025-05-27 23:44:57', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('student','instructor','admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'student',
  `full_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `full_name`, `created_at`, `reset_token`, `reset_expires`) VALUES
(1, 'user1', 'user1@gmail.com', '$2y$10$jq90F.wRSChS/j6mCzKkmuYw/yamMiz4OKhsX.iGfgzb4Kc7F0uG2', 'student', 'user1', '2025-05-17 14:14:08', NULL, NULL),
(2, 'abey', 'abey@gmail.com', '$2y$10$ICvcvjecoQqlQw4NNUFBZejOsM4MXbl6SDKBdCeAR6S4LmFad50ia', 'instructor', 'Abey M Biju', '2025-05-17 14:32:31', NULL, NULL),
(3, 'alby', 'alby@gmail.com', '$2y$10$Uxpeb9UG2Fcl0dg6qrfKl.md0PCTlHvGE3jBAo7ut0n8fyQE5f3Zm', 'instructor', 'Alby M Biju', '2025-05-17 15:27:30', NULL, NULL),
(4, 'user2', 'user2@gmail.com', '$2y$10$eLvN03lYzmT/pGea9C9DeuD6DfqMsy2qhlMbAJHU0X74A3vswT5OW', 'student', 'user2', '2025-05-18 05:43:28', NULL, NULL),
(5, 'user3', 'user3@gmail.com', '$2y$10$U9xd3EhEJBpU.0yfhIRiGehC0ww4Nz.5EsfiojfPJQeNSzJ/xLGsu', 'student', 'user3', '2025-05-27 13:50:46', NULL, NULL),
(6, 'user4', 'user4@gmail.com', '$2y$10$Nkjwo8P/2W/iFzWAb4AUg.QmhWpWz3ySmEfsPlbgcb2y87BIi4iNa', 'student', 'user4', '2025-05-27 17:32:41', NULL, NULL),
(7, 'Jose', 'jose@gmail.com', '$2y$10$6rG3caaw49diBrHV.k.Qw.bdXWICbHTy7SqMaiLwX/wswA6C4kdk2', 'instructor', 'Jose', '2025-05-27 17:48:19', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure for view `assignment_submissions`
--
DROP TABLE IF EXISTS `assignment_submissions`;

DROP VIEW IF EXISTS `assignment_submissions`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `assignment_submissions`  AS SELECT `s`.`submission_id` AS `submission_id`, `s`.`assignment_id` AS `assignment_id`, `s`.`student_id` AS `student_id`, `s`.`file_path` AS `file_path`, `s`.`submitted_at` AS `submitted_at`, `s`.`grade` AS `grade`, `s`.`feedback` AS `feedback`, `u`.`username` AS `username`, `u`.`email` AS `email` FROM (`submissions` `s` join `users` `u` on((`s`.`student_id` = `u`.`user_id`))) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `choices`
--
ALTER TABLE `choices`
  ADD CONSTRAINT `choices_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `content_items`
--
ALTER TABLE `content_items`
  ADD CONSTRAINT `content_items_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `discussions`
--
ALTER TABLE `discussions`
  ADD CONSTRAINT `discussions_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `discussions_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  ADD CONSTRAINT `discussion_replies_ibfk_1` FOREIGN KEY (`discussion_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `discussion_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD CONSTRAINT `forum_posts_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `forum_threads` (`thread_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `forum_posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `forum_threads`
--
ALTER TABLE `forum_threads`
  ADD CONSTRAINT `forum_threads_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `forum_threads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `grades_ibfk_3` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `grades_ibfk_4` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `fk_qa_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_qa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_responses`
--
ALTER TABLE `quiz_responses`
  ADD CONSTRAINT `fk_qr_quiz_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`question_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_response_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`question_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_responses_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
