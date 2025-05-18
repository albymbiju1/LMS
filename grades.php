<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config/database.php";

$user_id = $_SESSION["id"];
$role    = $_SESSION["role"];
$grades  = [];

if ($role === "student") {
    //
    // 1) Get each assignment’s latest grade
    //
    $sql_assign = "
        SELECT 
            c.title        AS course_title,
            a.title        AS item_title,
            (
                SELECT grade_value
                FROM grades g
                WHERE g.user_id     = ?
                  AND g.assignment_id = a.assignment_id
                ORDER BY graded_at DESC
                LIMIT 1
            )               AS grade_value,
            'Assignment'   AS item_type
        FROM enrollments e
        JOIN courses c     ON e.course_id     = c.course_id
        JOIN assignments a ON c.course_id     = a.course_id
        WHERE e.user_id    = ?
    ";

    //
    // 2) Get each quiz’s latest grade
    //
    $sql_quiz = "
        SELECT 
            c.title        AS course_title,
            q.title        AS item_title,
            (
                SELECT grade_value
                FROM grades g
                WHERE g.user_id = ?
                  AND g.quiz_id = q.quiz_id
                ORDER BY graded_at DESC
                LIMIT 1
            )               AS grade_value,
            'Quiz'         AS item_type
        FROM enrollments e
        JOIN courses c   ON e.course_id = c.course_id
        JOIN quizzes q   ON c.course_id = q.course_id
        WHERE e.user_id  = ?
    ";

    // execute both
    foreach ([ $sql_assign, $sql_quiz ] as $sql) {
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // bind user_id twice for the two placeholders
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $grades[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    }
}
else {
    // instructor logic unchanged...
    // (omitted here for brevity)
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Grades - LMS</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    .wrapper { max-width: 900px; margin: 30px auto; }
    .grade { font-weight: bold; }
    .grade-a { color: #28a745; }
    .grade-b { color: #17a2b8; }
    .grade-c { color: #ffc107; }
    .grade-d { color: #fd7e14; }
    .grade-f { color: #dc3545; }
    .not-attempted { color: #6c757d; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <a class="navbar-brand" href="#">LMS</a>
  <div class="collapse navbar-collapse">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="assignments.php">Assignments</a></li>
      <li class="nav-item"><a class="nav-link" href="quizzes.php">Quizzes</a></li>
      <li class="nav-item active"><a class="nav-link" href="grades.php">Grades</a></li>
    </ul>
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
      <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
    </ul>
  </div>
</nav>

<div class="wrapper">
  <h2>Grades</h2>

  <?php if (empty($grades)): ?>
    <div class="alert alert-info">No grades available.</div>
  <?php else: ?>
    <?php
      // group by course
      $by_course = [];
      foreach ($grades as $r) {
        $by_course[$r['course_title']][] = $r;
      }
    ?>

    <?php foreach ($by_course as $course => $items): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5><?= htmlspecialchars($course) ?></h5>
        </div>
        <div class="card-body">
          <?php foreach ($items as $row): ?>
            <div class="mb-3">
              <strong><?= htmlspecialchars($row['item_type']) ?>:</strong>
              <?= htmlspecialchars($row['item_title']) ?><br>

              <?php if ($row['grade_value'] !== null): 
                $score = floatval($row['grade_value']);
                if      ($score >= 90) $cl = 'grade-a';
                elseif  ($score >= 80) $cl = 'grade-b';
                elseif  ($score >= 70) $cl = 'grade-c';
                elseif  ($score >= 60) $cl = 'grade-d';
                else                   $cl = 'grade-f';
              ?>
                <span class="grade <?= $cl ?>">
                  <?= number_format($score, 1) ?>%
                </span>
              <?php else: ?>
                <span class="not-attempted">Not Attempted</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>
</body>
</html>
