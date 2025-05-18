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

// Get all quiz attempts for this quiz
$sql = "SELECT qa.*, u.username, u.full_name 
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <>
<?php while ($attempt = mysqli_fetch_assoc($attempts)): ?>
    <tr>
        <td><?php echo htmlspecialchars($attempt['full_name'] ?: $attempt['username']); ?></td>
        <td><?php echo htmlspecialchars($attempt['username']); ?></td>
        <td><?php echo date('F j, Y g:i A', strtotime($attempt['attempted_at'])); ?></td>
        <td><?php echo htmlspecialchars($attempt['score']); ?>%</td>
        <td><?php echo htmlspecialchars($attempt['time_taken']); ?> minutes</td>
        <td>
            <a href="view_quiz_attempt.php?id=<?php echo $attempt['attempt_id']; ?>" class="btn btn-primary btn-sm">View Details</a>
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