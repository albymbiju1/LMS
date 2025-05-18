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
        JOIN grades g ON q.quiz_id = g.quiz_id 
        WHERE q.quiz_id = ? AND g.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .results-header {
            background: linear-gradient(135deg, var(--warning-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
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
        }
        .results-summary {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }
        .summary-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .summary-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
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
            background: var(--success-color);
            color: white;
        }
        .status-incorrect {
            background: var(--danger-color);
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
            border-color: var(--success-color);
            background: rgba(46, 204, 113, 0.1);
        }
        .option-review.incorrect {
            border-color: var(--danger-color);
            background: rgba(231, 76, 60, 0.1);
        }
        .option-review.selected {
            border-width: 3px;
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="assignments.php">Assignments</a></li>
                    <li class="nav-item"><a class="nav-link" href="quizzes.php">Quizzes</a></li>
                    <li class="nav-item"><a class="nav-link" href="grades.php">Grades</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
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

        <div class="card">
            <div class="card-header">
                <h4>Quiz Results: <?php echo htmlspecialchars($quiz['title']); ?></h4>
                <p class="mb-0">Course: <?php echo htmlspecialchars($quiz['course_title']); ?></p>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Score Summary</h5>
                        <p>Score: <?php echo number_format($quiz['grade_value'], 1); ?>%</p>
                    </div>
                </div>

                <h5>Question Details</h5>
                <?php while ($question = mysqli_fetch_assoc($questions)): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6><?php echo htmlspecialchars($question['question_text']); ?></h6>
                            
                            <div class="mb-2">
                                <strong>Your Answer:</strong><br>
                                <?php
                                $option_letter = strtolower($question['chosen_option'] ?? '');
                                $option_key = 'option_' . $option_letter;

                                if (isset($question[$option_key])) {
                                    echo strtoupper($option_letter) . '. ' . htmlspecialchars($question[$option_key]);
                                } else {
                                    echo '<em>No answer recorded</em>';
                                }
                                ?>
                            </div>

                            <div class="mb-2">
                                <strong>Correct Answer:</strong><br>
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

                            <div>
                                <strong>Result:</strong> 
                                <span class="badge 
                                    <?php echo ($option_letter && $correct_option && $option_letter === $correct_option) ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo ($option_letter && $correct_option && $option_letter === $correct_option) ? 'Correct' : 'Incorrect'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <a href="quizzes.php" class="btn btn-primary">Back to Quizzes</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
