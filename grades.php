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
    // 1) Get each assignment's latest grade
    //
    $sql_assign = "
        SELECT 
            c.title        AS course_title,
            a.title        AS item_title,
            (
                SELECT grade
                FROM submissions s
                WHERE s.student_id = ?
                  AND s.assignment_id = a.assignment_id
                ORDER BY submitted_at DESC
                LIMIT 1
            )               AS grade_value,
            'Assignment'   AS item_type
        FROM enrollments e
        JOIN courses c     ON e.course_id     = c.course_id
        JOIN assignments a ON c.course_id     = a.course_id
        WHERE e.user_id    = ?
    ";

    //
    // 2) Get each quiz's latest grade
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
      background: #f8f9fa;
    }

    .navbar {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
      box-shadow: var(--box-shadow);
    }

    .wrapper {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 20px;
    }

    .grade-card {
      background: white;
      border: none;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      margin-bottom: 1.5rem;
      transition: var(--transition);
    }

    .grade-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }

    .grade-badge {
      padding: 8px 20px;
      border-radius: 20px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .grade-a { background: rgba(40, 167, 69, 0.15); color: #28a745; }
    .grade-b { background: rgba(23, 162, 184, 0.15); color: #17a2b8; }
    .grade-c { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
    .grade-d { background: rgba(253, 126, 20, 0.15); color: #fd7e14; }
    .grade-f { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
    .not-attempted { background: rgba(108, 117, 125, 0.15); color: #6c757d; }

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

    .course-header {
      border-bottom: 2px solid rgba(0,0,0,0.05);
      padding-bottom: 1rem;
      margin-bottom: 1.5rem;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="#">LMS</a>
    <div class="collapse navbar-collapse">
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
        <li class="nav-item">
          <a class="nav-link" href="quizzes.php">
            <i class="fas fa-question-circle"></i>Quizzes
          </a>
        </li>
        <li class="nav-item active">
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
  <h2 class="mb-4" style="color: var(--primary-color);">Academic Progress</h2>

  <?php if (empty($grades)): ?>
    <div class="alert alert-info">No grades available yet.</div>
  <?php else: ?>
    <?php
      // Group by course (existing logic)
      $by_course = [];
      foreach ($grades as $r) {
        $by_course[$r['course_title']][] = $r;
      }
    ?>

    <?php foreach ($by_course as $course => $items): ?>
      <div class="grade-card">
        <div class="card-body">
          <div class="course-header">
            <h5><?= htmlspecialchars($course) ?></h5>
          </div>
          
          <div class="row">
            <?php foreach ($items as $row): ?>
              <div class="col-md-6 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="text-muted small mb-1">
                      <?= htmlspecialchars($row['item_type']) ?>
                    </div>
                    <h6 class="mb-0"><?= htmlspecialchars($row['item_title']) ?></h6>
                  </div>
                  
                  <?php if ($row['grade_value'] !== null): 
                    $score = floatval($row['grade_value']);
                    if ($score >= 90) $cl = 'grade-a';
                    elseif ($score >= 80) $cl = 'grade-b';
                    elseif ($score >= 70) $cl = 'grade-c';
                    elseif ($score >= 60) $cl = 'grade-d';
                    else $cl = 'grade-f';
                  ?>
                    <div class="grade-badge <?= $cl ?>">
                      <i class="fas fa-percent"></i>
                      <?= number_format($score, 1) ?>%
                    </div>
                  <?php else: ?>
                    <div class="grade-badge not-attempted">
                      <i class="fas fa-clock"></i>
                      Pending
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
</body>
</html>