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
$sql = "SELECT * FROM quiz_attempts WHERE quiz_id = ? AND student_id = ? ORDER BY attempted_at DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
mysqli_stmt_execute($stmt);
$attempt = mysqli_stmt_get_result($stmt)->fetch_assoc();
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Quiz - LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <div class="mb-4">
                    <h5>Quiz Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($quiz['description'])); ?></p>
                    <p><strong>Time Limit:</strong> <?php echo htmlspecialchars($quiz['time_limit']); ?> minutes</p>
                    <p><strong>Passing Score:</strong> <?php echo htmlspecialchars($quiz['passing_score']); ?>%</p>
                </div>

                <?php if ($role === 'student'): ?>
                    <?php if ($attempt): ?>
                        <div class="alert alert-success">
                            <h5>Quiz Status: Completed</h5>
                            <p><strong>Last Attempt:</strong> <?php echo date('F j, Y g:i A', strtotime($attempt['attempted_at'])); ?></p>
                            <p><strong>Score:</strong> <?php echo htmlspecialchars($attempt['score']); ?>%</p>
                            <p><strong>Time Taken:</strong> <?php echo htmlspecialchars($attempt['time_taken']); ?> minutes</p>
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
            </div>
        </div>

        <div class="mt-3">
            <a href="course.php?id=<?php echo $quiz['course_id']; ?>" class="btn btn-secondary">Back to Course</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 