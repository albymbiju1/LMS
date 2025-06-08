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
    <title>Submit Assignment - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #2A3F54;
            --secondary-color: #1ABB9C;
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
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .submit-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }

        .assignment-info {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }

        .due-date {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.2);
            margin-top: 1rem;
        }

        .upload-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: var(--border-radius);
            border: 2px dashed #dee2e6;
            text-align: center;
            transition: var(--transition);
        }

        .upload-section:hover {
            border-color: var(--primary-color);
            background: #fff;
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .file-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: white;
            border-radius: var(--border-radius);
            display: none;
        }

        .file-preview.active {
            display: block;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: var(--border-radius);
        }

        .file-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 63, 84, 0.2);
        }

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

        .alert {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
        }

        .alert-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .alert-danger {
            background: linear-gradient(135deg, #dc3545, #f72a3f);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">LMS</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
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

        <div class="submit-header">
            <h2><i class="fas fa-upload"></i> Submit Assignment</h2>
            <p class="mb-0">Course: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
            <div class="due-date">
                <i class="fas fa-clock"></i>
                Due: <?php echo date('F j, Y g:i A', strtotime($assignment['due_date'])); ?>
            </div>
        </div>

        <div class="assignment-info">
            <h4><i class="fas fa-file-alt"></i> Assignment Details</h4>
            <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
        </div>

        <div class="upload-section">
            <form action="submit_assignment.php?id=<?php echo $assignment_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h4>Upload Your Submission</h4>
                <p class="text-muted">Drag and drop your file here or click to browse</p>
                
                <div class="form-group">
                    <input type="file" class="form-control-file" id="submission_file" name="submission_file" required>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Maximum file size: 10MB
                    </small>
                </div>

                <div class="file-preview" id="filePreview">
                    <div class="file-info">
                        <i class="fas fa-file file-icon"></i>
                        <span id="fileName"></span>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Assignment
                    </button>
                    <a href="assignments.php" class="btn btn-secondary ml-2">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File preview functionality
        document.getElementById('submission_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('filePreview').classList.add('active');
            } else {
                document.getElementById('filePreview').classList.remove('active');
            }
        });
    </script>
</body>
</html> 
</html> 

