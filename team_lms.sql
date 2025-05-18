-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2025 at 10:14 PM
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
-- Database: `team_lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignment_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assignment_id`, `course_id`, `title`, `description`, `due_date`, `created_at`) VALUES
(1, 1, 'First assigment', 'First assignment', '2025-05-19 21:11:00', '2025-05-17 15:41:05'),
(2, 2, 'First assigment', 'First Assignment', '2025-05-19 01:38:00', '2025-05-17 20:09:16');

-- --------------------------------------------------------

--
-- Stand-in structure for view `assignment_submissions`
-- (See below for the actual view)
--
CREATE TABLE `assignment_submissions` (
`submission_id` int(11)
,`assignment_id` int(11)
,`student_id` int(11)
,`file_path` varchar(255)
,`submitted_at` datetime
,`grade` float
,`feedback` text
);

-- --------------------------------------------------------

--
-- Table structure for table `choices`
--

CREATE TABLE `choices` (
  `choice_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `choice_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_items`
--

CREATE TABLE `content_items` (
  `content_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `content_type` enum('video','document','link','text') NOT NULL DEFAULT 'document',
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_items`
--

INSERT INTO `content_items` (`content_id`, `lesson_id`, `title`, `content_type`, `file_path`, `created_at`) VALUES
(3, 2, 'UI Basics Video', 'video', 'uploads/assignments/1747567976_JQuery3.pdf', '2025-05-18 19:54:33');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `instructor_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `title`, `description`, `instructor_id`, `created_at`) VALUES
(1, 'UI/UX Designing', 'UI/UX Designing Course', 2, '2025-05-17 14:49:08'),
(2, 'Introduction to Cloud Computing', 'Introduction to Cloud Computing', 3, '2025-05-17 20:07:26');

-- --------------------------------------------------------

--
-- Table structure for table `discussions`
--

CREATE TABLE `discussions` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discussions`
--

INSERT INTO `discussions` (`id`, `title`, `content`, `author_id`, `course_id`, `created_at`) VALUES
(1, 'Poster Design', 'Poster Design Course', 2, 1, '2025-05-17 14:50:40');

-- --------------------------------------------------------

--
-- Table structure for table `discussion_replies`
--

CREATE TABLE `discussion_replies` (
  `reply_id` int(11) NOT NULL,
  `discussion_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reply` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discussion_replies`
--

INSERT INTO `discussion_replies` (`reply_id`, `discussion_id`, `user_id`, `reply`, `created_at`) VALUES
(1, 1, 1, 'Great', '2025-05-17 15:33:27');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `enroll_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `course_id`, `user_id`, `enroll_date`) VALUES
(6, 2, 4, '2025-05-18 11:24:28'),
(8, 1, 1, '2025-05-18 12:30:35'),
(10, 2, 1, '2025-05-18 12:32:10'),
(12, 1, 4, '2025-05-18 16:55:53');

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

CREATE TABLE `forum_posts` (
  `post_id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `posted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_threads`
--

CREATE TABLE `forum_threads` (
  `thread_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `grade_value` float NOT NULL,
  `graded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(15, 1, 2, NULL, 2, 100, '2025-05-19 00:15:28');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `lesson_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(150) DEFAULT NULL,
  `content` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `module_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`module_id`, `course_id`, `title`, `description`, `file_path`) VALUES
(1, 1, 'Fundamentals of UI Design', 'Fundamentals of UI Design', 'uploads/modules/6828eafb8819a_ALBY_AI_ASMNT_Final.pdf'),
(2, 1, 'Fundamentals of UI Design', 'Fundamentals of UI Design', 'uploads/modules/6828eb13ca7fd_ALBY_AI_ASMNT_Final.pdf'),
(3, 2, 'Introduction to Cloud Computing', 'Introduction to Cloud Computing', 'uploads/modules/6828ecaec72c1_ALBY_AI_ASMNT_Final.pdf'),
(4, 2, 'Introduction to Cloud Computing', 'Introduction to Cloud Computing', 'uploads/modules/682a2953a098c_1-768176b1-b47d-4d4b-973a-6d62411c2e01.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `quiz_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`quiz_id`, `course_id`, `title`, `description`, `created_at`) VALUES
(1, 1, 'First Quiz', 'First Quiz', '2025-05-17 15:41:38'),
(2, 2, 'Introduction to Cloud Computing', 'Introduction to Cloud Computing', '2025-05-17 20:09:32');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `attempt_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` float DEFAULT NULL,
  `max_score` float DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`attempt_id`, `quiz_id`, `user_id`, `score`, `max_score`, `started_at`, `completed_at`) VALUES
(1, 2, 1, 100, 1, '2025-05-19 00:23:48', '2025-05-19 00:23:48'),
(2, 1, 1, 100, 2, '2025-05-19 00:31:46', '2025-05-19 00:31:46'),
(3, 2, 1, 100, 1, '2025-05-19 00:41:58', '2025-05-19 00:41:58'),
(4, 1, 1, 100, 2, '2025-05-19 00:42:13', '2025-05-19 00:42:13'),
(5, 2, 1, 100, 1, '2025-05-19 00:50:15', '2025-05-19 00:50:15');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `question_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `correct_option` char(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`question_id`, `quiz_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `created_at`) VALUES
(1, 1, 'Which of the following principles is most important when designing a user-friendly interface?', 'Using complex animations and transitions', 'Prioritizing aesthetic design over functionality', 'Ensuring consistency and simplicity across the interface', 'Adding as many features as possible on a single screen', 'c', '2025-05-17 16:07:52'),
(2, 1, 'Which of the following principles is most important when designing a user-friendly interface?', 'Using complex animations and transitions', 'Prioritizing aesthetic design over functionality', 'Ensuring consistency and simplicity across the interface', 'Adding as many features as possible on a single screen', 'c', '2025-05-17 16:10:18'),
(3, 2, 'Which of the following is not a characteristic of cloud computing?', 'On-demand self-service', 'Resource pooling', 'Manual scaling', 'Broad network access', 'c', '2025-05-17 20:11:45');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_responses`
--

CREATE TABLE `quiz_responses` (
  `response_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `chosen_option` char(1) NOT NULL COMMENT 'Stores A, B, C, or D',
  `answered_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(37, 3, 1, 'c', '2025-05-19 00:50:15');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `submission_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `grade` float DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`submission_id`, `assignment_id`, `student_id`, `file_path`, `submitted_at`, `grade`, `feedback`) VALUES
(1, 2, 1, 'uploads/assignments/1747552940_s2 (1).pdf', '2025-05-18 12:52:20', 100, ''),
(2, 2, 1, 'uploads/assignments/1747553491_1-84b5eb20-cdef-4eea-a9b1-ba8038289c8d.pdf', '2025-05-18 13:01:31', 98, ''),
(3, 2, 1, 'uploads/assignments/1747553645_Human Computer Interaction (In English).pdf', '2025-05-18 13:04:05', 97, ''),
(4, 1, 4, 'uploads/assignments/1747567598_1-84b5eb20-cdef-4eea-a9b1-ba8038289c8d.pdf', '2025-05-18 16:56:38', 98, 'Good'),
(5, 2, 4, 'uploads/assignments/1747567628_1-6509b2e9-24d3-4d40-b133-bb9ecd268d6f.pdf', '2025-05-18 16:57:08', 95, ''),
(6, 2, 4, 'uploads/assignments/1747567976_JQuery3.pdf', '2025-05-18 17:02:56', 93, ''),
(7, 1, 1, 'uploads/assignments/1747594138_1-82453184-cce7-4869-888c-b2ff72af48c7_page-0001.pdf', '2025-05-19 00:18:58', 95, 'Good');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','instructor','admin') NOT NULL DEFAULT 'student',
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `full_name`, `created_at`) VALUES
(1, 'user1', 'user1@gmail.com', '$2y$10$jq90F.wRSChS/j6mCzKkmuYw/yamMiz4OKhsX.iGfgzb4Kc7F0uG2', 'student', NULL, '2025-05-17 14:14:08'),
(2, 'abey', 'abey@gmail.com', '$2y$10$ICvcvjecoQqlQw4NNUFBZejOsM4MXbl6SDKBdCeAR6S4LmFad50ia', 'instructor', 'Abey M Biju', '2025-05-17 14:32:31'),
(3, 'alby', 'alby@gmail.com', '$2y$10$Uxpeb9UG2Fcl0dg6qrfKl.md0PCTlHvGE3jBAo7ut0n8fyQE5f3Zm', 'instructor', 'Alby M Biju', '2025-05-17 15:27:30'),
(4, 'user2', 'user2@gmail.com', '$2y$10$eLvN03lYzmT/pGea9C9DeuD6DfqMsy2qhlMbAJHU0X74A3vswT5OW', 'student', 'user2', '2025-05-18 05:43:28');

-- --------------------------------------------------------

--
-- Structure for view `assignment_submissions`
--
DROP TABLE IF EXISTS `assignment_submissions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `assignment_submissions`  AS SELECT `submissions`.`submission_id` AS `submission_id`, `submissions`.`assignment_id` AS `assignment_id`, `submissions`.`student_id` AS `student_id`, `submissions`.`file_path` AS `file_path`, `submissions`.`submitted_at` AS `submitted_at`, `submissions`.`grade` AS `grade`, `submissions`.`feedback` AS `feedback` FROM `submissions` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `choices`
--
ALTER TABLE `choices`
  ADD PRIMARY KEY (`choice_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `content_items`
--
ALTER TABLE `content_items`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `discussions`
--
ALTER TABLE `discussions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `discussion_id` (`discussion_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `course_id` (`course_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `thread_id` (`thread_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `forum_threads`
--
ALTER TABLE `forum_threads`
  ADD PRIMARY KEY (`thread_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`lesson_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`module_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `idx_quiz_user` (`quiz_id`,`user_id`),
  ADD KEY `fk_qa_user` (`user_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quiz_responses`
--
ALTER TABLE `quiz_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `chosen_choice_id` (`chosen_option`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `choices`
--
ALTER TABLE `choices`
  MODIFY `choice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_items`
--
ALTER TABLE `content_items`
  MODIFY `content_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `discussions`
--
ALTER TABLE `discussions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_threads`
--
ALTER TABLE `forum_threads`
  MODIFY `thread_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `lesson_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quiz_responses`
--
ALTER TABLE `quiz_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
