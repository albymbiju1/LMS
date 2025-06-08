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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2A3F54;
            --secondary-color: #1ABB9C;
            --accent-color: #337AB7;
            --border-radius: 8px;
            --box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
            box-shadow: var(--box-shadow);
        }

        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover {
            color: white !important;
            transform: translateX(3px);
        }

        .wrapper {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .quiz-card {
            background: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--secondary-color);
        }

        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .card-body {
            padding: 1.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 63, 84, 0.2);
        }

        .btn-info {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: var(--transition);
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(51, 122, 183, 0.2);
        }

        .attempts {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(108, 117, 125, 0.1);
            border-radius: 20px;
            color: #6c757d;
            font-weight: 500;
        }

        .attempts i {
            color: var(--secondary-color);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h2 {
            color: var(--primary-color);
            margin: 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">LMS</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assignments.php">
                            <i class="fas fa-tasks"></i>Assignments
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="quizzes.php">
                            <i class="fas fa-question-circle"></i>Quizzes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="grades.php">
                            <i class="fas fa-chart-line"></i>Grades
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user-circle"></i>Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="wrapper">
        <div class="page-header">
            <h2><i class="fas fa-question-circle"></i> Quizzes</h2>
            <?php if($role === "instructor"): ?>
            <a href="create_quiz.php?course_id=<?php echo $quizzes[0]['course_id'] ?? ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Create Quiz
            </a>
            <?php endif; ?>
        </div>

        <?php if(empty($quizzes)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No quizzes available.
            </div>
        <?php else: ?>
            <?php foreach($quizzes as $quiz): ?>
            <div class="quiz-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title"><?php echo htmlspecialchars($quiz["title"]); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <i class="fas fa-book"></i> <?php echo htmlspecialchars($quiz["course_title"]); ?>
                            </h6>
                            <p class="card-text"><?php echo htmlspecialchars($quiz["description"]); ?></p>
                        </div>
                        <div class="text-right">
                            <?php if($role === "student"): ?>
                                <p class="attempts mb-2">
                                    <i class="fas fa-check-circle"></i> Attempts: <?php echo $quiz["attempts"]; ?>
                                </p>
                                <a href="take_quiz.php?id=<?php echo $quiz["quiz_id"]; ?>" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Take Quiz
                                </a>
                            <?php else: ?>
                                <p class="attempts mb-2">
                                    <i class="fas fa-users"></i> Total Attempts: <?php echo $quiz["total_attempts"]; ?>
                                </p>
                                <a href="view_quiz_results.php?id=<?php echo $quiz["quiz_id"]; ?>" class="btn btn-info">
                                    <i class="fas fa-chart-bar"></i> View Results
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 