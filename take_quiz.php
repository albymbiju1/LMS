<?php
// ===================================================================
// File: take_quiz.php (updated to fix missing catch block)
// ===================================================================
session_start();
require_once "config/database.php";

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        mysqli_begin_transaction($conn);

        // Fetch all question IDs
        $stmtQ = mysqli_prepare($conn, "SELECT question_id FROM quiz_questions WHERE quiz_id = ?");
        mysqli_stmt_bind_param($stmtQ, 'i', $quiz_id);
        mysqli_stmt_execute($stmtQ);
        $resultQ = mysqli_stmt_get_result($stmtQ);
        $questions = [];
        while ($row = mysqli_fetch_assoc($resultQ)) {
            $questions[] = $row['question_id'];
        }
        mysqli_stmt_close($stmtQ);
        if (empty($questions)) throw new Exception('No questions found.');

        // Delete old responses
        $stmtDel = mysqli_prepare($conn,
            "DELETE r FROM quiz_responses r
             JOIN quiz_questions q ON r.question_id = q.question_id
             WHERE r.student_id = ? AND q.quiz_id = ?");
        mysqli_stmt_bind_param($stmtDel, 'ii', $user_id, $quiz_id);
        mysqli_stmt_execute($stmtDel);
        mysqli_stmt_close($stmtDel);

        // Insert new responses
        $stmtIns = mysqli_prepare($conn,
            "INSERT INTO quiz_responses(question_id, student_id, chosen_option)
             VALUES(?, ?, ?)");
        mysqli_stmt_bind_param($stmtIns, 'iis', $question_id, $user_id, $chosen_option);
        foreach ($questions as $question_id) {
            $field = 'answer_' . $question_id;
            $chosen_option = isset($_POST[$field]) ? strtoupper(trim($_POST[$field])) : '';
            mysqli_stmt_execute($stmtIns);
        }
        mysqli_stmt_close($stmtIns);

        // Calculate score
        $total = count($questions);
        $stmtCorr = mysqli_prepare($conn,
            "SELECT COUNT(*) FROM quiz_responses r
             JOIN quiz_questions q ON r.question_id = q.question_id
             WHERE r.student_id = ? AND q.quiz_id = ? AND UPPER(r.chosen_option) = UPPER(q.correct_option)");
        mysqli_stmt_bind_param($stmtCorr, 'ii', $user_id, $quiz_id);
        mysqli_stmt_execute($stmtCorr);
        mysqli_stmt_bind_result($stmtCorr, $correct_count);
        mysqli_stmt_fetch($stmtCorr);
        mysqli_stmt_close($stmtCorr);
        $score = $total ? round(($correct_count / $total) * 100, 2) : 0.0;

        // Record attempt
        $stmtAtt = mysqli_prepare($conn,
            "INSERT INTO quiz_attempts(quiz_id, user_id, score, max_score, started_at, completed_at)
             VALUES(?, ?, ?, 100, NOW(), NOW())");
        mysqli_stmt_bind_param($stmtAtt, 'iid', $quiz_id, $user_id, $score);
        mysqli_stmt_execute($stmtAtt);
        mysqli_stmt_close($stmtAtt);

        // Get course_id
        $stmtC = mysqli_prepare($conn, "SELECT course_id FROM quizzes WHERE quiz_id = ?");
        mysqli_stmt_bind_param($stmtC, 'i', $quiz_id);
        mysqli_stmt_execute($stmtC);
        mysqli_stmt_bind_result($stmtC, $course_id);
        mysqli_stmt_fetch($stmtC);
        mysqli_stmt_close($stmtC);

        // Update grades
        $stmtGr = mysqli_prepare($conn,
            "REPLACE INTO grades(user_id, quiz_id, course_id, grade_value, graded_at)
             VALUES(?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmtGr, 'iiid', $user_id, $quiz_id, $course_id, $score);
        mysqli_stmt_execute($stmtGr);
        mysqli_stmt_close($stmtGr);

        mysqli_commit($conn);
        $_SESSION['success'] = "Quiz completed successfully! Your score: {$score}%";

        // Determine redirect based on role
        $stmtI = mysqli_prepare($conn,
            "SELECT c.instructor_id FROM quizzes q
             JOIN courses c ON q.course_id = c.course_id
             WHERE q.quiz_id = ?");
        mysqli_stmt_bind_param($stmtI, 'i', $quiz_id);
        mysqli_stmt_execute($stmtI);
        mysqli_stmt_bind_result($stmtI, $instructor_id);
        mysqli_stmt_fetch($stmtI);
        mysqli_stmt_close($stmtI);

        if ((int)$instructor_id === $user_id) {
            header("Location: view_quiz.php?id={$quiz_id}");
        } else {
            header("Location: view_quiz_results.php?id={$quiz_id}");
        }
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log($e->getMessage());
        $_SESSION['error'] = "Submission error: " . $e->getMessage();
        header("Location: take_quiz.php?id={$quiz_id}");
        exit;
    }
}

// GET: Fetch quiz details and questions
$stmt = mysqli_prepare($conn, "SELECT q.title, q.course_id, c.title as course_title 
                              FROM quizzes q 
                              JOIN courses c ON q.course_id = c.course_id 
                              WHERE q.quiz_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $quiz_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$quiz = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$quiz) {
    $_SESSION['error'] = "Quiz not found.";
    header('Location: quizzes.php');
    exit;
}

$quiz_title = $quiz['title'];
$course_id = $quiz['course_id'];
$course_title = $quiz['course_title'];

// Fetch questions
$stmt = mysqli_prepare($conn, "SELECT question_id, question_text, option_a, option_b, option_c, option_d 
                              FROM quiz_questions 
                              WHERE quiz_id = ? 
                              ORDER BY question_id");
mysqli_stmt_bind_param($stmt, 'i', $quiz_id);
mysqli_stmt_execute($stmt);
$questions = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

if (!$questions || mysqli_num_rows($questions) === 0) {
    $_SESSION['error'] = "No questions found for this quiz.";
    header('Location: quizzes.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Quiz - <?php echo htmlspecialchars($quiz_title); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #2A3F54; --secondary-color: #1ABB9C; --border-radius: 8px; --box-shadow: 0 10px 30px rgba(0,0,0,0.1); --transition: all 0.3s; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #f8f9fa, #e9ecef); }
        .navbar { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
        .wrapper { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .card { border: none; border-radius: var(--border-radius); box-shadow: var(--box-shadow); margin-bottom: 1rem; }
        .card-header { background: var(--primary-color); color: #fff; padding: 1rem; border-top-left-radius: var(--border-radius); border-top-right-radius: var(--border-radius); }
        .card-body { padding: 1rem; }
        .btn-primary { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border: none; }
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
        <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="quizzes.php"><i class="fas fa-question-circle"></i> Quizzes</a></li>
        <li class="nav-item"><a class="nav-link" href="grades.php"><i class="fas fa-chart-line"></i> Grades</a></li>
      </ul>
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="wrapper">
  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
  <?php elseif (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><h4>Take Quiz: <?php echo htmlspecialchars($quiz_title); ?></h4></div>
    <div class="card-body">
      <form method="post" action="take_quiz.php?id=<?php echo $quiz_id; ?>">
        <?php $i = 1; while ($q = mysqli_fetch_assoc($questions)): ?>
          <div class="card mb-3">
            <div class="card-header"><strong>Q<?php echo $i++; ?>:</strong> <?php echo htmlspecialchars($q['question_text']); ?></div>
            <div class="card-body">
              <?php foreach (['A','B','C','D'] as $opt): ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="answer_<?php echo $q['question_id']; ?>" id="q<?php echo $q['question_id'] . $opt; ?>" value="<?php echo $opt; ?>">
                  <label class="form-check-label" for="q<?php echo $q['question_id'] . $opt; ?>"><?php echo htmlspecialchars($q['option_' . strtolower($opt)]); ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endwhile; ?>
        <button type="submit" class="btn btn-primary">Submit Quiz</button>
        <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary ml-2"><i class="fas fa-arrow-left"></i> Back</a>
      </form>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
