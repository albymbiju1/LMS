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

// Check if assignment exists and user is enrolled in the course
$sql = "SELECT a.*, c.title as course_title 
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

// Check if user has already submitted
$sql = "SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $user_id);
mysqli_stmt_execute($stmt);
$existing_submission = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if ($existing_submission) {
    $_SESSION['error'] = "You have already submitted this assignment. Multiple submissions are not allowed.";
    header("Location: view_assignment.php?id=" . $assignment_id);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file_path = null;

    // Handle file upload if present
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/assignments/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['submission_file']['name']);
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $target_path)) {
            $file_path = $target_path;
        } else {
            $_SESSION['error'] = "Error uploading file";
            header("Location: submit_assignment.php?id=" . $assignment_id);
            exit();
        }
    }

    // Insert submission
    $sql = "INSERT INTO submissions (assignment_id, student_id, file_path, submitted_at) 
            VALUES (?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iis", $assignment_id, $user_id, $file_path);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Assignment submitted successfully!";
        header("Location: assignments.php");
        exit();
    } else {
        $_SESSION['error'] = "Error submitting assignment";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment - LMS</title>
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
                <h4>Submit Assignment: <?php echo htmlspecialchars($assignment['title']); ?></h4>
                <p class="mb-0">Course: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h5>Assignment Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                    <p><strong>Due Date:</strong> <?php echo date('F j, Y g:i A', strtotime($assignment['due_date'])); ?></p>
                </div>

                <form action="submit_assignment.php?id=<?php echo $assignment_id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="submission_file" class="form-label">Upload File</label>
                        <input type="file" class="form-control" id="submission_file" name="submission_file" required>
                        <div class="form-text">Maximum file size: 10MB</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Assignment</button>
                    <a href="assignments.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 