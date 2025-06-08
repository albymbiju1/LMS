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

        .grade-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }

        .grade-form {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .form-control {
            border: 2px solid #eee;
            border-radius: var(--border-radius);
            padding: 0.75rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(42, 63, 84, 0.25);
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

        .student-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
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

        <div class="grade-header">
            <h4><i class="fas fa-check-circle"></i> Grade Assignment: <?php echo htmlspecialchars($submission['assignment_title']); ?></h4>
            <p class="mb-0"><i class="fas fa-book"></i> Course: <?php echo htmlspecialchars($submission['course_title']); ?></p>
        </div>

        <div class="grade-form">
            <div class="student-info">
                <div class="student-avatar">
                    <?php echo strtoupper(substr($submission['student_name'], 0, 1)); ?>
                </div>
                <div>
                    <h5 class="mb-0"><?php echo htmlspecialchars($submission['student_name']); ?></h5>
                </div>
            </div>

            <?php if ($submission['file_path']): ?>
                <div class="mb-4">
                    <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank" class="btn btn-info">
                        <i class="fas fa-download"></i> Download Submission
                    </a>
                </div>
            <?php endif; ?>

            <form action="grade_assignment.php?submission_id=<?php echo $submission_id; ?>" method="POST">
                <div class="form-group">
                    <label><i class="fas fa-star"></i> Grade (0-100)</label>
                    <input type="number" class="form-control" id="grade" name="grade" 
                           min="0" max="100" step="0.1" required
                           value="<?php echo $submission['grade'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Feedback</label>
                    <textarea class="form-control" id="feedback" name="feedback" rows="5"><?php 
                        echo htmlspecialchars($submission['feedback'] ?? ''); 
                    ?></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Submit Grade
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
</body>
</html> 
</html> 
</html> 
</html> 