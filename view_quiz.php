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
$role = $_SESSION["role"];

// Check if quiz exists and user is enrolled in the course
$sql = "SELECT q.*, c.title as course_title, c.course_id, c.instructor_id 
        FROM quizzes q 
        JOIN courses c ON q.course_id = c.course_id 
        JOIN enrollments e ON c.course_id = e.course_id 
        WHERE q.quiz_id = ? AND e.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Quiz not found or you are not enrolled in this course";
    header("Location: quizzes.php");
    exit();
}

$quiz = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Get quiz questions
$sql = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_id ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$questions = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Get user's attempt if exists
$sql = "SELECT * FROM quiz_attempts WHERE quiz_id = ? AND user_id = ? ORDER BY completed_at DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
mysqli_stmt_execute($stmt);
$attempt = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

// Get user's attempt count
$sql = "SELECT COUNT(*) as attempt_count FROM quiz_attempts WHERE quiz_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
mysqli_stmt_execute($stmt);
$attempt_count = mysqli_stmt_get_result($stmt)->fetch_assoc()['attempt_count'];
mysqli_stmt_close($stmt);

// Get all attempts if instructor
$all_attempts = [];
if ($role === 'instructor' && $quiz['instructor_id'] === $user_id) {
    $sql = "SELECT qa.*, u.username, u.first_name, u.last_name 
            FROM quiz_attempts qa 
            JOIN users u ON qa.student_id = u.id 
            WHERE qa.quiz_id = ? 
            ORDER BY qa.attempted_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $quiz_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $all_attempts[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Get all participants if instructor
$participants = [];
if ($role === 'instructor' && $quiz['instructor_id'] === $user_id) {
    $sql = "SELECT u.user_id, u.username, u.full_name, 
            COUNT(qa.attempt_id) as attempt_count,
            MAX(qa.score) as best_score
            FROM enrollments e
            JOIN users u ON e.user_id = u.user_id
            LEFT JOIN quiz_attempts qa ON qa.quiz_id = ? AND qa.user_id = u.user_id
            WHERE e.course_id = ?
            GROUP BY u.user_id";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $quiz['course_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $participants[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Quiz - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .quiz-header {
            background: linear-gradient(135deg, var(--warning-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }
        .quiz-info {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
        }
        .question-card {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }
        .question-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .options-list {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }
        .option-item {
            padding: 1rem;
            border: 2px solid #eee;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }
        .option-item:hover {
            border-color: var(--primary-color);
            background: #f8f9fa;
        }
        .option-item.correct {
            border-color: var(--success-color);
            background: rgba(46, 204, 113, 0.1);
        }
        .option-item.incorrect {
            border-color: var(--danger-color);
            background: rgba(231, 76, 60, 0.1);
        }
        .timer-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--warning-color);
            text-align: center;
            margin: 1rem 0;
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
                <h4><?php echo htmlspecialchars($quiz['title']); ?></h4>
                <p class="mb-0">Course: <?php echo htmlspecialchars($quiz['course_title']); ?></p>
            </div>
            <div class="card-body">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Quiz Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Attempts:</strong> <?php echo $attempt_count; ?> / 5</p>
                        <?php if ($attempt_count >= 5): ?>
                            <div class="alert alert-warning">
                                You have reached the maximum number of attempts (5) for this quiz.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($role === 'student'): ?>
                    <?php if ($attempt): ?>
                        <div class="alert alert-success">
                            <h5>Quiz Status: Completed</h5>
                            <p><strong>Last Attempt:</strong> <?php echo date('F j, Y g:i A', strtotime($attempt['completed_at'])); ?></p>
                            <p><strong>Score:</strong> <?php echo htmlspecialchars($attempt['score']); ?>%</p>
                            <a href="quiz_results.php?id=<?php echo $quiz_id; ?>" class="btn btn-primary">View Detailed Results</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h5>Quiz Status: Not Attempted</h5>
                            <p>You haven't attempted this quiz yet.</p>
                            <a href="take_quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-primary">Take Quiz</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="attempts-section">
                        <h5>Student Attempts</h5>
                        <?php if (empty($all_attempts)): ?>
                            <p>No attempts yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Username</th>
                                            <th>Attempted At</th>
                                            <th>Score</th>
                                            <th>Time Taken</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_attempts as $att): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($att['first_name'] . ' ' . $att['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($att['username']); ?></td>
                                                <td><?php echo date('F j, Y g:i A', strtotime($att['attempted_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($att['score']); ?>%</td>
                                                <td><?php echo htmlspecialchars($att['time_taken']); ?> minutes</td>
                                                <td>
                                                    <a href="view_quiz_attempt.php?id=<?php echo $att['attempt_id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($role === 'instructor' && $quiz['instructor_id'] === $user_id): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Quiz Participants</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Username</th>
                                        <th>Attempts</th>
                                        <th>Best Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($participants as $participant): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($participant['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($participant['username']); ?></td>
                                        <td><?php echo htmlspecialchars($participant['attempt_count']); ?></td>
                                        <td><?php echo $participant['best_score'] ? htmlspecialchars($participant['best_score']) . '%' : 'No attempts'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-3">
            <a href="course.php?id=<?php echo $quiz['course_id']; ?>" class="btn btn-secondary">Back to Course</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 