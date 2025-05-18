<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

// Optional: If you want modules PER COURSE, pass ?course_id= in the URL
$course_id = isset($_GET['course_id']) && ctype_digit($_GET['course_id'])
    ? intval($_GET['course_id'])
    : null;

// Build SQL
$sql = "
    SELECT 
        m.module_id,
        m.title       AS module_title,
        m.description,
        c.title       AS course_title
    FROM modules m
    JOIN courses c ON m.course_id = c.course_id
    "
    . ($course_id ? "WHERE m.course_id = ?" : "") . "
    ORDER BY c.title, m.title
";

if ($stmt = mysqli_prepare($conn, $sql)) {
    if ($course_id) {
        mysqli_stmt_bind_param($stmt, "i", $course_id);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $modules = mysqli_fetch_all($res, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    die("Could not load modules.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Modules <?= $course_id ? "for “" . htmlspecialchars($modules[0]['course_title'] ?? '') . "”" : "" ?></title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>.wrapper { max-width:800px; margin:30px auto; }</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <a class="navbar-brand" href="index.php">LMS</a>
  <div class="collapse navbar-collapse">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
    </ul>
  </div>
</nav>

<div class="wrapper">
  <h2>Modules <?= $course_id ? "for “" . htmlspecialchars($modules[0]['course_title'] ?? '') . "”" : "" ?></h2>

  <?php if (empty($modules)): ?>
    <div class="alert alert-info">No modules found.</div>
  <?php else: ?>
    <?php foreach ($modules as $m): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title"><?= htmlspecialchars($m['module_title']) ?></h5>
          <p class="card-text"><?= nl2br(htmlspecialchars($m['description'])) ?></p>
          <a href="view_module.php?module_id=<?= $m['module_id'] ?>" class="btn btn-primary">
            View Content
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
</body>
</html>

