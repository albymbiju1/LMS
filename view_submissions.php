<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if assignment_id is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Assignment ID is required";
    header("Location: assignments.php");
    exit();
}

$assignment_id = (int)$_GET['id'];
$user_id = (int)$_SESSION["id"];

// Check if user is instructor for this course
$sql = "SELECT a.*, c.title as course_title, c.instructor_id 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.course_id 
        WHERE a.assignment_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $assignment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Assignment not found";
    header("Location: assignments.php");
    exit();
}

$assignment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Check if user is instructor
if ($assignment['instructor_id'] != $user_id) {
    $_SESSION['error'] = "You don't have permission to view submissions";
    header("Location: assignments.php");
    exit();
}

// Get all submissions for this assignment
$sql = "SELECT s.*, u.full_name, u.username
        FROM submissions s
        JOIN users u ON s.student_id = u.user_id
        WHERE s.assignment_id = ? 
        ORDER BY s.submitted_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $assignment_id);
mysqli_stmt_execute($stmt);
$submissions = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions - LMS</title>
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
    <?php if (!empty($_SESSION['error'])): ?>
      <div class="alert alert-danger">
        <?php 
        echo htmlspecialchars($_SESSION['error']);
        unset($_SESSION['error']); // Clear it immediately after showing
        ?>
    </div>
        <?php endif; ?>


    <div class="card">
        <div class="card-header">
            <h4>Submissions for: <?php echo htmlspecialchars($assignment['title']); ?></h4>
            <p class="mb-0">Course: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Username</th>
                        <th>Submitted At</th>
                        <th>File</th>
                        <th>Grade</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($submission = mysqli_fetch_assoc($submissions)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['full_name'] ?: $submission['username']); ?></td>
                            <td><?php echo htmlspecialchars($submission['username']); ?></td>
                            <td><?php echo date('F j, Y g:i A', strtotime($submission['submitted_at'])); ?></td>
                            <td>
                                <?php if ($submission['file_path']): ?>
                                    <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank">View Submission</a>
                                <?php else: ?>
                                    No file submitted
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $submission['grade'] ? htmlspecialchars($submission['grade']) : 'Not graded'; ?>
                            </td>
                            <td>
                                <a href="grade_submission.php?id=<?php echo $submission['submission_id']; ?>" class="btn btn-primary btn-sm">Grade</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
