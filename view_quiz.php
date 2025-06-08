<?php
session_start();
require_once "config/database.php";

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Validate quiz ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid quiz ID.";
    header('Location: quizzes.php');
    exit;
}
$quiz_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['id'];
$role = $_SESSION['role'];

// GET: display quiz
$qzStmt = mysqli_prepare($conn, 
    "SELECT q.title, q.description, c.title as course_title 
     FROM quizzes q 
     JOIN courses c ON q.course_id = c.course_id 
     WHERE q.quiz_id = ?");
mysqli_stmt_bind_param($qzStmt, 'i', $quiz_id);
mysqli_stmt_execute($qzStmt);
mysqli_stmt_bind_result($qzStmt, $quiz_title, $quiz_description, $course_title);
mysqli_stmt_fetch($qzStmt);
mysqli_stmt_close($qzStmt);

$qsStmt = mysqli_prepare($conn,
    "SELECT question_id, question_text, option_a, option_b, option_c, option_d
     FROM quiz_questions WHERE quiz_id = ? ORDER BY question_id");
mysqli_stmt_bind_param($qsStmt, 'i', $quiz_id);
mysqli_stmt_execute($qsStmt);
$questions = mysqli_stmt_get_result($qsStmt);
mysqli_stmt_close($qsStmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Quiz - <?php echo htmlspecialchars($quiz_title); ?></title>
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
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh; }
        .navbar { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important; box-shadow: var(--box-shadow); }
        .nav-link { color: rgba(255,255,255,0.8) !important; transition: var(--transition); display: flex; align-items: center; gap: 8px; }
        .nav-link:hover { color: white !important; transform: translateX(3px); }
        .wrapper { max-width: 900px; margin: 2rem auto; padding: 0 20px; }
        .card { border: none; border-radius: var(--border-radius); box-shadow: var(--box-shadow); transition: var(--transition); }
        .card:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
        .card-header { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; border-radius: var(--border-radius) var(--border-radius) 0 0; padding: 1rem 1.5rem; }
        .card-body { padding: 1.5rem; }
        .btn-primary { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border: none; border-radius: var(--border-radius); font-weight: 600; letter-spacing: 0.5px; transition: var(--transition); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(42,63,84,0.2); }
        .btn-secondary { background: #6c757d; border: none; border-radius: var(--border-radius); font-weight: 600; }
        .question { margin-bottom: 2rem; }
        .options { margin-top: 1rem; }
        .custom-control { margin-bottom: 0.5rem; }
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
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="assignments.php"><i class="fas fa-tasks"></i>Assignments</a></li>
                    <li class="nav-item active"><a class="nav-link" href="quizzes.php"><i class="fas fa-question-circle"></i>Quizzes</a></li>
                    <li class="nav-item"><a class="nav-link" href="grades.php"><i class="fas fa-chart-line"></i>Grades</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user-circle"></i>Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="wrapper">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header">
                <h4>Take Quiz: <?php echo htmlspecialchars($quiz_title); ?></h4>
            </div>
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($quiz_title); ?></h5>
                <h6 class="card-subtitle mb-2 text-muted">
                    <i class="fas fa-book"></i> <?php echo htmlspecialchars($course_title); ?>
                </h6>
                <p class="card-text"><?php echo htmlspecialchars($quiz_description); ?></p>
                
                <?php if ($role === 'student'): ?>
                    <form action="take_quiz.php?id=<?php echo $quiz_id; ?>" method="post">
                        <?php $i = 1; while ($question = mysqli_fetch_assoc($questions)): ?>
                            <div class="question mb-4">
                                <p class="font-weight-bold">Q<?php echo $i++; ?>: <?php echo htmlspecialchars($question['question_text']); ?></p>
                                <div class="options">
                                    <?php foreach (['A','B','C','D'] as $opt): ?>
                                        <div class="custom-control custom-radio">
                                            <input type="radio" 
                                                   class="custom-control-input" 
                                                   id="q<?php echo $question['question_id']; ?>_<?php echo $opt; ?>" 
                                                   name="answer_<?php echo $question['question_id']; ?>" 
                                                   value="<?php echo $opt; ?>" 
                                                   required>
                                            <label class="custom-control-label" 
                                                   for="q<?php echo $question['question_id']; ?>_<?php echo $opt; ?>">
                                                <?php echo htmlspecialchars($question['option_'.strtolower($opt)]); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Quiz
                        </button>
                        <a href="quizzes.php" class="btn btn-secondary ml-2">
                            <i class="fas fa-arrow-left"></i> Back to Quizzes
                        </a>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> As an instructor, you can view quiz results but cannot take the quiz.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
