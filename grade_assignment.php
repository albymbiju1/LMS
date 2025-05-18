<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and is an instructor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'instructor'){
    header("location: login.php");
    exit;
}

// Check if submission_id is provided
if (!isset($_GET['submission_id'])) {
    $_SESSION['error'] = "Submission ID is required";
    header("Location: assignments.php");
    exit();
}

$submission_id = (int)$_GET['submission_id'];
$instructor_id = (int)$_SESSION["id"];  // Changed from user_id to id to match other files

// Get submission details
$sql = "SELECT s.*, a.title as assignment_title, a.course_id, c.title as course_title, 
        u.full_name as student_name 
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
    $_SESSION['error'] = "Submission not found or you are not the instructor for this course";
    header("Location: assignments.php");
    exit();
}

$submission = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = (float)$_POST['grade'];
    $feedback = trim($_POST['feedback']);

    // Validate grade
    if ($grade < 0 || $grade > 100) {
        $_SESSION['error'] = "Grade must be between 0 and 100";
    } else {
        // Update submission
        $sql = "UPDATE submissions SET grade = ?, feedback = ? WHERE submission_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "dsi", $grade, $feedback, $submission_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Insert grade into grades table
            $sql = "INSERT INTO grades (user_id, course_id, assignment_id, grade_value, graded_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiid", $submission['student_id'], $submission['course_id'], 
                                 $submission['assignment_id'], $grade);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "Assignment graded successfully";
                header("Location: assignments.php");
                exit();
            } else {
                $_SESSION['error'] = "Error saving grade";
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['error'] = "Error grading assignment";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Assignment - LMS</title>
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
                <h4>Grade Assignment: <?php echo htmlspecialchars($submission['assignment_title']); ?></h4>
                <p class="mb-0">Course: <?php echo htmlspecialchars($submission['course_title']); ?></p>
                <p class="mb-0">Student: <?php echo htmlspecialchars($submission['student_name']); ?></p>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <?php if ($submission['file_path']): ?>
                        <p>
                            <strong>Attached File:</strong>
                            <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank">
                                Download File
                            </a>
                        </p>
                    <?php endif; ?>

                    <p>
                        <strong>Submitted:</strong>
                        <?php echo date('F j, Y g:i A', strtotime($submission['submitted_at'])); ?>
                    </p>
                </div>

                <form action="grade_assignment.php?submission_id=<?php echo $submission_id; ?>" method="POST">
                    <div class="mb-3">
                        <label for="grade" class="form-label">Grade (0-100)</label>
                        <input type="number" class="form-control" id="grade" name="grade" 
                               min="0" max="100" step="0.1" required
                               value="<?php echo $submission['grade'] ?? ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="feedback" class="form-label">Feedback</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="5"><?php 
                            echo htmlspecialchars($submission['feedback'] ?? ''); 
                        ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Grade</button>
                    <a href="assignments.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 