<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and is an instructor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "instructor"){
    header("location: login.php");
    exit;
}

// Check if submission_id is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Submission ID is required";
    header("Location: assignments.php");
    exit();
}

$submission_id = (int)$_GET['id'];
$instructor_id = (int)$_SESSION["id"];

// Check if submission exists and instructor has permission
$sql = "SELECT s.*, a.title as assignment_title, c.title as course_title, c.instructor_id, u.full_name
        FROM submissions s 
        JOIN assignments a ON s.assignment_id = a.assignment_id
        JOIN courses c ON a.course_id = c.course_id
        JOIN users u ON s.student_id = u.user_id
        WHERE s.submission_id = ? AND c.instructor_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $submission_id, $instructor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Submission not found or you don't have permission to grade it";
    header("Location: assignments.php");
    exit();
}

$submission = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $grade = trim($_POST["grade"]);
    $feedback = trim($_POST["feedback"]);
    
    // Validate grade
    if (!is_numeric($grade) || $grade < 0 || $grade > 100) {
        $grade_err = "Please enter a valid grade between 0 and 100";
    } else {
        // Update submission with grade and feedback
        $sql = "UPDATE submissions SET grade = ?, feedback = ? WHERE submission_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "dsi", $grade, $feedback, $submission_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "Grade submitted successfully";
                header("location: view_submissions.php?id=" . $submission['assignment_id']);
                exit();
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submission - LMS</title>
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
                <h4>Grade Submission</h4>
                <p class="mb-0">Course: <?php echo htmlspecialchars($submission['course_title']); ?></p>
                <p class="mb-0">Assignment: <?php echo htmlspecialchars($submission['assignment_title']); ?></p>
                <p class="mb-0">Student: <?php echo htmlspecialchars($submission['full_name']); ?></p>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $submission_id); ?>" method="post">
                    <div class="mb-3">
                        <label class="form-label">Grade (0-100)</label>
                        <input type="number" name="grade" class="form-control <?php echo (!empty($grade_err)) ? 'is-invalid' : ''; ?>" value="<?php echo isset($grade) ? $grade : ''; ?>" min="0" max="100" step="0.1" required>
                        <span class="invalid-feedback"><?php echo $grade_err ?? ''; ?></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Feedback</label>
                        <textarea name="feedback" class="form-control" rows="4"><?php echo isset($feedback) ? $feedback : ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Submit Grade</button>
                        <a href="view_submissions.php?id=<?php echo $submission['assignment_id']; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 