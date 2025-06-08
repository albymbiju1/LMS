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

// Get quiz details and grade
$sql = "SELECT q.*, c.title as course_title, g.grade_value 
        FROM quizzes q 
        JOIN courses c ON q.course_id = c.course_id 
        LEFT JOIN grades g ON q.quiz_id = g.quiz_id AND g.user_id = ?
        WHERE q.quiz_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $quiz_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Quiz results not found";
    header("Location: quizzes.php");
    exit();
}

$quiz = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Get questions and responses
$sql = "SELECT q.*, r.chosen_option 
        FROM quiz_questions q 
        LEFT JOIN quiz_responses r ON q.question_id = r.question_id AND r.student_id = ? 
        WHERE q.quiz_id = ? 
        ORDER BY q.question_id";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $quiz_id);
mysqli_stmt_execute($stmt);
$questions = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Results - LMS</title>
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

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            padding: 1.5rem;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 600;
        }

        .card-body {
            padding: 2rem;
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

        .alert {
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
        }

        .score-display {
            text-align: center;
            margin: 2rem 0;
        }

        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            position: relative;
            box-shadow: var(--box-shadow);
            border: 4px solid var(--secondary-color);
        }

        .score-value {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .score-label {
            position: absolute;
            bottom: -2rem;
            font-size: 1.2rem;
            color: var(--secondary-color);
            font-weight: 600;
        }

        .question-review {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .question-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .status-correct {
            background: var(--secondary-color);
            color: white;
        }

        .status-incorrect {
            background: #dc3545;
            color: white;
        }

        .option-review {
            padding: 1rem;
            border: 2px solid #eee;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .option-review.correct {
            border-color: var(--secondary-color);
            background: rgba(26, 187, 156, 0.1);
        }

        .option-review.incorrect {
            border-color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }

        .option-review.selected {
            border-width: 3px;
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

    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-chart-bar"></i> Quiz Results: <?php echo htmlspecialchars($quiz['title']); ?></h4>
                <p class="mb-0"><i class="fas fa-book"></i> Course: <?php echo htmlspecialchars($quiz['course_title']); ?></p>
            </div>
            <div class="card-body">
                <div class="score-display">
                    <div class="score-circle">
                        <div class="score-value"><?php echo number_format($quiz['grade_value'], 1); ?>%</div>
                        <div class="score-label">Your Score</div>
                    </div>
                </div>

                <h5 class="mb-4"><i class="fas fa-list"></i> Question Details</h5>
                <?php while ($question = mysqli_fetch_assoc($questions)): ?>
                    <div class="question-review">
                        <div class="question-header">
                            <h6 class="mb-0"><?php echo htmlspecialchars($question['question_text']); ?></h6>
                            <span class="question-status <?php echo ($question['chosen_option'] === $question['correct_option']) ? 'status-correct' : 'status-incorrect'; ?>">
                                <i class="fas <?php echo ($question['chosen_option'] === $question['correct_option']) ? 'fa-check' : 'fa-times'; ?>"></i>
                                <?php echo ($question['chosen_option'] === $question['correct_option']) ? 'Correct' : 'Incorrect'; ?>
                            </span>
                        </div>
                        
                        <div class="mt-3">
                            <div class="option-review <?php echo ($question['chosen_option'] === $question['correct_option']) ? 'correct' : 'incorrect'; ?>">
                                <strong><i class="fas fa-user"></i> Your Answer:</strong><br>
                                <?php
                                $chosen_option = strtolower($question['chosen_option'] ?? '');
                                $option_key = 'option_' . $chosen_option;

                                if (isset($question[$option_key])) {
                                    echo strtoupper($chosen_option) . '. ' . htmlspecialchars($question[$option_key]);
                                } else {
                                    echo '<em>No answer recorded</em>';
                                }
                                ?>
                            </div>

                            <div class="option-review correct">
                                <strong><i class="fas fa-check"></i> Correct Answer:</strong><br>
                                <?php
                                $correct_option = strtolower($question['correct_option'] ?? '');
                                $correct_key = 'option_' . $correct_option;

                                if (isset($question[$correct_key])) {
                                    echo strtoupper($correct_option) . '. ' . htmlspecialchars($question[$correct_key]);
                                } else {
                                    echo '<em>Correct answer not found</em>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <div class="text-center mt-4">
                    <a href="quizzes.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Quizzes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
