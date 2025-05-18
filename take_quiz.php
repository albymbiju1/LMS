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

// Check if quiz exists and user is enrolled in the course
$sql = "SELECT q.*, c.title as course_title, c.course_id 
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

// Check attempt count
$sql = "SELECT COUNT(*) as attempt_count FROM quiz_attempts WHERE quiz_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
mysqli_stmt_execute($stmt);
$attempt_count = mysqli_stmt_get_result($stmt)->fetch_assoc()['attempt_count'];
mysqli_stmt_close($stmt);

if ($attempt_count >= 5) {
    $_SESSION['error'] = "You have reached the maximum number of attempts (5) for this quiz";
    header("Location: view_quiz.php?id=" . $quiz_id);
    exit();
}

// Get quiz questions
$sql = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY RAND()";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$questions = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $score = 0;
    $total_questions = 0;
    $responses = [];
    
    // Calculate score
    foreach ($_POST['answers'] as $question_id => $answer) {
        $sql = "SELECT correct_option FROM quiz_questions WHERE question_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $question_id);
        mysqli_stmt_execute($stmt);
        $correct_answer = mysqli_stmt_get_result($stmt)->fetch_assoc()['correct_option'];
        mysqli_stmt_close($stmt);
        
        if ($answer === $correct_answer) {
            $score++;
        }
        $total_questions++;
        
        // Store response
        $responses[] = [
            'question_id' => $question_id,
            'chosen_option' => $answer
        ];
    }
    
    $percentage_score = ($score / $total_questions) * 100;
    
    // Record attempt
    $sql = "INSERT INTO quiz_attempts (quiz_id, user_id, score, max_score, started_at, completed_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iidd", $quiz_id, $user_id, $percentage_score, $total_questions);
    mysqli_stmt_execute($stmt);
    $attempt_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    // Record responses
    foreach ($responses as $response) {
        $sql = "INSERT INTO quiz_responses (question_id, student_id, chosen_option) 
                VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $response['question_id'], $user_id, $response['chosen_option']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    $_SESSION['success'] = "Quiz completed successfully! Your score: " . number_format($percentage_score, 1) . "%";
    header("Location: view_quiz.php?id=" . $quiz_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz - LMS</title>
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

        <div class="card">
            <div class="card-header">
                <h4><?php echo htmlspecialchars($quiz['title']); ?></h4>
                <p class="mb-0">Course: <?php echo htmlspecialchars($quiz['course_title']); ?></p>
                <p class="mb-0">Attempt: <?php echo $attempt_count + 1; ?> of 5</p>
            </div>
            <div class="card-body">
                <form method="post" id="quizForm">
                    <?php 
                    $question_number = 1;
                    while ($question = mysqli_fetch_assoc($questions)): 
                    ?>
                        <div class="mb-4">
                            <h5>Question <?php echo $question_number; ?></h5>
                            <p><?php echo htmlspecialchars($question['question_text']); ?></p>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="a" required>
                                <label class="form-check-label">
                                    <?php echo htmlspecialchars($question['option_a']); ?>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="b">
                                <label class="form-check-label">
                                    <?php echo htmlspecialchars($question['option_b']); ?>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="c">
                                <label class="form-check-label">
                                    <?php echo htmlspecialchars($question['option_c']); ?>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="d">
                                <label class="form-check-label">
                                    <?php echo htmlspecialchars($question['option_d']); ?>
                                </label>
                            </div>
                        </div>
                    <?php 
                    $question_number++;
                    endwhile; 
                    ?>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Submit Quiz</button>
                        <a href="view_quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent accidental navigation away
        window.onbeforeunload = function() {
            return "Are you sure you want to leave? Your answers will be lost.";
        };
        
        // Remove warning when submitting form
        document.getElementById('quizForm').onsubmit = function() {
            window.onbeforeunload = null;
        };
    </script>
</body>
</html> 