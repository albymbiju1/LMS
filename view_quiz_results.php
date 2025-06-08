<?php
// view_quiz_results.php
session_start();
require_once "config/database.php";

// 1. Auth check
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php');
    exit;
}

// 2. quiz_id param
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Quiz ID is required";
    header('Location: quizzes.php');
    exit;
}
$quiz_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['id'];
$role = $_SESSION['role'];

// 3. Fetch quiz title & course info
$stmtQ = mysqli_prepare($conn,
    "SELECT q.title       AS quiz_title,
            c.title       AS course_title,
            c.course_id   AS course_id
     FROM quizzes q
     JOIN courses c ON q.course_id = c.course_id
     WHERE q.quiz_id = ?");
mysqli_stmt_bind_param($stmtQ, 'i', $quiz_id);
mysqli_stmt_execute($stmtQ);
mysqli_stmt_bind_result($stmtQ, $quiz_title, $course_title, $course_id);
if (!mysqli_stmt_fetch($stmtQ)) {
    $_SESSION['error'] = "Quiz not found";
    header('Location: quizzes.php');
    exit;
}
mysqli_stmt_close($stmtQ);

// 4. Fetch attempts based on role
if ($role === 'instructor') {
    // For instructors, show all attempts
    $stmtS = mysqli_prepare($conn,
        "SELECT qa.*, u.username, u.full_name,
                TIMESTAMPDIFF(MINUTE, qa.started_at, qa.completed_at) AS time_taken
         FROM quiz_attempts qa
         JOIN users u ON qa.user_id = u.user_id
         WHERE qa.quiz_id = ?
         ORDER BY qa.completed_at DESC");
    mysqli_stmt_bind_param($stmtS, 'i', $quiz_id);
    mysqli_stmt_execute($stmtS);
    $attempts = mysqli_stmt_get_result($stmtS);
    mysqli_stmt_close($stmtS);
} else {
    // For students, show only their latest attempt
    $stmtS = mysqli_prepare($conn,
        "SELECT score,
                TIMESTAMPDIFF(MINUTE, started_at, completed_at) AS time_taken
         FROM quiz_attempts
         WHERE quiz_id = ? AND user_id = ?
         ORDER BY completed_at DESC
         LIMIT 1");
    mysqli_stmt_bind_param($stmtS, 'ii', $quiz_id, $user_id);
    mysqli_stmt_execute($stmtS);
    mysqli_stmt_bind_result($stmtS, $score, $time_taken);
    if (!mysqli_stmt_fetch($stmtS)) {
        $_SESSION['error'] = "You have not attempted this quiz yet.";
        header('Location: quizzes.php');
        exit;
    }
    mysqli_stmt_close($stmtS);
}

// 5. Fetch questions and responses
$stmtR = mysqli_prepare($conn,
    "SELECT q.question_text,
            q.option_a, q.option_b, q.option_c, q.option_d,
            qr.chosen_option,
            q.correct_option
     FROM quiz_questions q
     LEFT JOIN quiz_responses qr ON q.question_id = qr.question_id 
     AND qr.student_id = ?
     WHERE q.quiz_id = ?
     ORDER BY q.question_id");
mysqli_stmt_bind_param($stmtR, 'ii', $user_id, $quiz_id);
mysqli_stmt_execute($stmtR);
$responses = mysqli_stmt_get_result($stmtR);
mysqli_stmt_close($stmtR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quiz Results - <?php echo htmlspecialchars($quiz_title); ?></title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .correct   { color: green;  font-weight: bold; }
    .incorrect { color: red;    font-weight: bold; }
    .attempt-card { margin-bottom: 1rem; }
    .score-high { color: #28a745; }
    .score-medium { color: #ffc107; }
    .score-low { color: #dc3545; }
  </style>
</head>
<body class="p-4">
  <h2>Quiz Results: <?php echo htmlspecialchars($quiz_title); ?></h2>
  <p>Course: <?php echo htmlspecialchars($course_title); ?></p>

  <?php if ($role === 'instructor'): ?>
    <div class="attempts-list">
        <?php if (mysqli_num_rows($attempts) > 0): ?>
            <?php while ($attempt = mysqli_fetch_assoc($attempts)): ?>
                <div class="card attempt-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            Student: <?php echo htmlspecialchars($attempt['full_name']); ?>
                            (<?php echo htmlspecialchars($attempt['username']); ?>)
                        </h5>
                        <p class="card-text">
                            Score: <strong><?php echo number_format($attempt['score'], 1); ?>%</strong>
                            &nbsp;(<em><?php echo (int)$attempt['time_taken']; ?> minutes</em>)
                        </p>
                        <p class="card-text">
                            <small class="text-muted">
                                Completed: <?php echo date('F j, Y g:i a', strtotime($attempt['completed_at'])); ?>
                            </small>
                        </p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No students have attempted this quiz yet.
            </div>
        <?php endif; ?>
    </div>
  <?php else: ?>
    <p>
      Score: <strong><?php echo number_format($score, 1); ?>%</strong>
      &nbsp;(<em><?php echo (int)$time_taken; ?> minutes</em>)
    </p>
    <hr>

    <?php while ($row = mysqli_fetch_assoc($responses)): ?>
      <div class="mb-3">
        <p><strong>Q:</strong> <?php echo htmlspecialchars($row['question_text']); ?></p>
        <ul>
          <?php foreach (['A','B','C','D'] as $opt):
              $txt   = $row['option_'.strtolower($opt)];
              $class = '';
              if ($opt === $row['correct_option'])                 $class = 'correct';
              else if ($opt === $row['chosen_option'])             $class = 'incorrect';
          ?>
            <li class="<?php echo $class; ?>">
              (<?php echo $opt; ?>) <?php echo htmlspecialchars($txt); ?>
              <?php if ($class === 'correct')   echo ' ✓'; ?>
              <?php if ($class === 'incorrect') echo ' ✗'; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <hr>
    <?php endwhile; ?>
  <?php endif; ?>

  <a href="quizzes.php" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Back to Quizzes
  </a>
</body>
</html>
