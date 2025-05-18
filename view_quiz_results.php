<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Check if quiz_id is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Quiz ID is required";
    header("Location: quizzes.php");
    exit();
}

$quiz_id = (int)$_GET['id'];
$user_id = (int)$_SESSION["id"];

// Check if user is instructor for this course
$sql = "SELECT q.*, c.title as course_title, c.instructor_id 
        FROM quizzes q 
        JOIN courses c ON q.course_id = c.course_id 
        WHERE q.quiz_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Quiz not found";
    header("Location: quizzes.php");
    exit();
}

$quiz = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Check if user is instructor
if ($quiz['instructor_id'] != $user_id) {
    $_SESSION['error'] = "You don't have permission to view quiz results";
    header("Location: quizzes.php");
    exit();
}

// Get all quiz attempts for this quiz with time_taken calculation
$sql = "SELECT qa.*, 
               TIMESTAMPDIFF(MINUTE, qa.started_at, qa.completed_at) AS time_taken,
               u.username, u.full_name 
        FROM quiz_attempts qa 
        JOIN users u ON qa.user_id = u.user_id 
        WHERE qa.quiz_id = ? 
        ORDER BY qa.completed_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$attempts = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Quiz Results - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .results-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }
        .results-table {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        .results-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 1rem;
        }
        .results-table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .results-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .results-table tr:hover {
            background: #e9ecef;
        }
        .score-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            min-width: 100px;
            justify-content: center;
        }
        .score-high {
            background: var(--success-color);
            color: white;
        }
        .score-medium {
            background: var(--warning-color);
            color: white;
        }
        .score-low {
            background: var(--danger-color);
            color: white;
        }
        .attempt-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .status-completed {
            background: var(--success-color);
            color: white;
        }
        .status-in-progress {
            background: var(--warning-color);
            color: white;
        }
        .status-not-started {
            background: var(--secondary-color);
            color: white;
        }
        .results-actions {
            display: flex;
            gap: 0.5rem;
        }
        .action-button {
            padding: 0.5rem;
            border: none;
            background: none;
            color: var(--secondary-color);
            transition: var(--transition);
        }
        .action-button:hover {
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        .results-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            flex: 1;
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        .progress-section {
            margin-top: 2rem;
        }
        .progress-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        .progress-title {
            color: var(--secondary-color);
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 4px;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">LMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assignments.php">Assignments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quizzes.php">Quizzes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="grades.php">Grades</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4>Quiz Results: <?php echo htmlspecialchars($quiz['title']); ?></h4>
                <p class="mb-0">Course: <?php echo htmlspecialchars($quiz['course_title']); ?></p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Username</th>
                                <th>Attempted At</th>
                                <th>Score</th>
                                <th>Time Taken</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($attempt = mysqli_fetch_assoc($attempts)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attempt['full_name'] ?: $attempt['username']); ?></td>
                                    <td><?php echo htmlspecialchars($attempt['username']); ?></td>
                                    <td><?php echo date('F j, Y g:i A', strtotime($attempt['completed_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($attempt['score']); ?>%</td>
                                    <td>
                                        <?php echo isset($attempt['time_taken']) ? htmlspecialchars($attempt['time_taken']) . ' minutes' : 'N/A'; ?>
                                    </td>
                    
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
