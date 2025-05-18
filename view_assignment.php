<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
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
$role = $_SESSION["role"];

// Check if assignment exists and user is enrolled in the course
$sql = "SELECT a.*, c.title as course_title, c.course_id, c.instructor_id 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.course_id 
        JOIN enrollments e ON c.course_id = e.course_id 
        WHERE a.assignment_id = ? AND e.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Assignment not found or you are not enrolled in this course";
    header("Location: assignments.php");
    exit();
}

$assignment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Get submission if exists
$sql = "SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $user_id);
mysqli_stmt_execute($stmt);
$submission = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

// Get all submissions if instructor
$all_submissions = [];
if ($role === 'instructor' && $assignment['instructor_id'] === $user_id) {
    $sql = "SELECT s.*, u.username, u.first_name, u.last_name 
            FROM submissions s 
            JOIN users u ON s.student_id = u.id 
            WHERE s.assignment_id = ? 
            ORDER BY s.submitted_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $assignment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $all_submissions[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assignment - LMS</title>
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

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                <p class="mb-0">Course: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h5>Assignment Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                    <p><strong>Due Date:</strong> <?php echo date('F j, Y g:i A', strtotime($assignment['due_date'])); ?></p>
                </div>

                <?php if ($role === 'student'): ?>
                    <?php if ($submission): ?>
                        <div class="alert alert-success">
                            <h5>Submission Status: Submitted</h5>
                            <p><strong>Submitted on:</strong> <?php echo date('F j, Y g:i A', strtotime($submission['submitted_at'])); ?></p>
                            <?php if ($submission['file_path']): ?>
                                <p><strong>File:</strong> <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank">View Submission</a></p>
                            <?php endif; ?>
                            <?php if ($submission['grade'] !== null): ?>
                                <p><strong>Grade:</strong> <?php echo htmlspecialchars($submission['grade']); ?></p>
                            <?php else: ?>
                                <p><strong>Status:</strong> Awaiting grading</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h5>Submission Status: Not Submitted</h5>
                            <p>You haven't submitted this assignment yet.</p>
                            <a href="submit_assignment.php?id=<?php echo $assignment_id; ?>" class="btn btn-primary">Submit Assignment</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="submissions-section">
                        <h5>Student Submissions</h5>
                        <?php if (empty($all_submissions)): ?>
                            <p>No submissions yet.</p>
                        <?php else: ?>
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
                                        <?php foreach ($all_submissions as $sub): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($sub['username']); ?></td>
                                                <td><?php echo date('F j, Y g:i A', strtotime($sub['submitted_at'])); ?></td>
                                                <td>
                                                    <?php if ($sub['file_path']): ?>
                                                        <a href="<?php echo htmlspecialchars($sub['file_path']); ?>" target="_blank">View Submission</a>
                                                    <?php else: ?>
                                                        No file submitted
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $sub['grade'] ? htmlspecialchars($sub['grade']) : 'Not graded'; ?>
                                                </td>
                                                <td>
                                                    <a href="grade_submission.php?id=<?php echo $sub['submission_id']; ?>" class="btn btn-primary btn-sm">Grade</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-3">
            <a href="course.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-secondary">Back to Course</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 