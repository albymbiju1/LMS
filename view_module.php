<?php
session_start();

// 1) Ensure user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

// 2) Validate module_id parameter; redirect to dashboard if missing/invalid
if (empty($_GET['module_id']) || !ctype_digit($_GET['module_id'])) {
    header("Location: index.php");
    exit;
}
$module_id = intval($_GET['module_id']);

// 3) Fetch module info (including course_id if you later want it)
$sqlModule = "
    SELECT m.course_id,
           m.title       AS module_title,
           m.description
    FROM modules m
    WHERE m.module_id = ?
";
if (!($stmt = mysqli_prepare($conn, $sqlModule))) {
    header("Location: index.php");
    exit;
}
mysqli_stmt_bind_param($stmt, "i", $module_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$module = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$module) {
    // Module not found
    header("Location: index.php");
    exit;
}

// 4) Fetch its content items
$sqlContent = "
    SELECT ci.content_type,
           ci.title,
           ci.file_path
    FROM content_items ci
    JOIN lessons l ON ci.lesson_id = l.lesson_id
    WHERE l.module_id = ?
    ORDER BY ci.created_at
";
if (!($stmt = mysqli_prepare($conn, $sqlContent))) {
    header("Location: index.php");
    exit;
}
mysqli_stmt_bind_param($stmt, "i", $module_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$items = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Module: <?= htmlspecialchars($module['module_title']) ?></title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    .wrapper { max-width: 800px; margin: 30px auto; }
    .content-item { margin-bottom: 15px; }
    .content-item h6 { margin-bottom: 5px; }
    .content-type { font-size: 0.9em; color: #6c757d; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <a class="navbar-brand" href="index.php">LMS</a>
  <div class="collapse navbar-collapse">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item">
        <a class="nav-link" href="index.php">← Back to Dashboard</a>
      </li>
    </ul>
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
    </ul>
  </div>
</nav>

<div class="wrapper">
  <h2><?= htmlspecialchars($module['module_title']) ?></h2>
  <p><?= nl2br(htmlspecialchars($module['description'])) ?></p>

  <?php if (empty($items)): ?>
    <div class="alert alert-info">No content available for this module.</div>
  <?php else: ?>
    <?php foreach ($items as $it): ?>
      <div class="content-item">
        <h6><?= htmlspecialchars($it['title']) ?></h6>
        <div class="content-type"><?= ucfirst($it['content_type']) ?></div>

        <?php if ($it['content_type'] === 'video' || $it['content_type'] === 'document'): ?>
          <a href="<?= htmlspecialchars($it['file_path']) ?>" target="_blank">
            View <?= $it['content_type'] === 'video' ? 'Video' : 'Document' ?>
          </a>

        <?php elseif ($it['content_type'] === 'link'): ?>
          <a href="<?= htmlspecialchars($it['file_path']) ?>" target="_blank">External Link</a>

        <?php else: /* text fallback */ ?>
          <p><?= nl2br(htmlspecialchars($it['file_path'])) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
</body>
</html>
