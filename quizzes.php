<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

$user_id = $_SESSION["id"];
$role = $_SESSION["role"];

// Fetch quizzes based on role
$quizzes = [];
if($role === "student"){
    $sql = "SELECT q.*, c.title as course_title, c.course_id,
            (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.quiz_id AND user_id = ?) as attempts
            FROM quizzes q 
            JOIN courses c ON q.course_id = c.course_id 
            JOIN enrollments e ON c.course_id = e.course_id 
            WHERE e.user_id = ? 
            ORDER BY q.created_at DESC";
} else if($role === "instructor"){
    $sql = "SELECT q.*, c.title as course_title, c.course_id,
            (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.quiz_id) as total_attempts
            FROM quizzes q 
            JOIN courses c ON q.course_id = c.course_id 
            WHERE c.instructor_id = ? 
            ORDER BY q.created_at DESC";
}

if($stmt = mysqli_prepare($conn, $sql)){
    if($role === "student"){
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    }
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $quizzes[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quizzes - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .wrapper { width: 1200px; margin: 0 auto; padding: 20px; }
        .quiz-card {
            transition: var(--transition);
            border-left: 4px solid var(--warning-color);
            margin-bottom: 1.5rem;
        }
        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .quiz-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .status-available {
            background-color: var(--success-color);
            color: white;
        }
        .status-completed {
            background-color: var(--primary-color);
            color: white;
        }
        .status-expired {
            background-color: var(--danger-color);
            color: white;
        }
        .quiz-info {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .quiz-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--secondary-color);
        }
        .attempts { color: #6c757d; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">LMS</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="assignments.php">Assignments</a></li>
                <li class="nav-item active"><a class="nav-link" href="quizzes.php">Quizzes</a></li>
                <li class="nav-item"><a class="nav-link" href="grades.php">Grades</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quizzes</h2>
            <?php if($role === "instructor"): ?>
            <a href="create_quiz.php" class="btn btn-primary">Create Quiz</a>
            <?php endif; ?>
        </div>

        <?php if(empty($quizzes)): ?>
            <div class="alert alert-info">No quizzes available.</div>
        <?php else: ?>
            <?php foreach($quizzes as $quiz): ?>
            <div class="card quiz-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title"><?php echo htmlspecialchars($quiz["title"]); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($quiz["course_title"]); ?></h6>
                            <p class="card-text"><?php echo htmlspecialchars($quiz["description"]); ?></p>
                        </div>
                        <div class="text-right">
                            <?php if($role === "student"): ?>
                                <p class="attempts mb-2">
                                    <i class="fas fa-check-circle"></i> Attempts: <?php echo $quiz["attempts"]; ?>
                                </p>
                                <a href="take_quiz.php?id=<?php echo $quiz["quiz_id"]; ?>" class="btn btn-primary">Take Quiz</a>
                            <?php else: ?>
                                <p class="attempts mb-2">
                                    <i class="fas fa-users"></i> Total Attempts: <?php echo $quiz["total_attempts"]; ?>
                                </p>
                                <a href="view_quiz_results.php?id=<?php echo $quiz["quiz_id"]; ?>" class="btn btn-info">View Results</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 