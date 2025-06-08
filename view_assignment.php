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
    <title>View Assignment - LMS</title>
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
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
            box-shadow: var(--box-shadow);
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

        .wrapper {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            padding: 1.5rem;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 600;
        }

        .card-body {
            padding: 2rem;
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

        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: var(--transition);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.2);
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
        }

        .table {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table thead th {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.02);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,.04);
        }

        .info-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            margin-right: 1rem;
            margin-bottom: 0.5rem;
        }

        .info-item i {
            font-size: 1.1rem;
        }

        .description-box {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin: 1rem 0;
        }

        .description-box p {
            margin-bottom: 0;
            line-height: 1.6;
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
                    <li class="nav-item active">
                        <a class="nav-link" href="assignments.php">
                            <i class="fas fa-tasks"></i>Assignments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quizzes.php">
                            <i class="fas fa-question-circle"></i>Quizzes
                        </a>
                    </li>
                    <li class="nav-item">
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

        <div class="card mb-4">
            <div class="card-header">
                <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                <div class="mt-2">
                    <span class="info-item">
                        <i class="fas fa-book"></i>
                        <?php echo htmlspecialchars($assignment['course_title']); ?>
                    </span>
                    <span class="info-item">
                        <i class="fas fa-clock"></i>
                        Due: <?php echo date('F j, Y g:i A', strtotime($assignment['due_date'])); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="description-box">
                    <h5><i class="fas fa-align-left"></i> Assignment Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                </div>

                <?php if ($role === 'student'): ?>
                    <?php if ($submission): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Submission Status: Submitted</h5>
                            <div class="mt-3">
                                <p><i class="fas fa-clock"></i> <strong>Submitted on:</strong> <?php echo date('F j, Y g:i A', strtotime($submission['submitted_at'])); ?></p>
                                <?php if ($submission['file_path']): ?>
                                    <p><i class="fas fa-file"></i> <strong>File:</strong> <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank">View Submission</a></p>
                                <?php endif; ?>
                                <?php if ($submission['grade'] !== null): ?>
                                    <p><i class="fas fa-star"></i> <strong>Grade:</strong> <?php echo htmlspecialchars($submission['grade']); ?></p>
                                <?php else: ?>
                                    <p><i class="fas fa-hourglass-half"></i> <strong>Status:</strong> Awaiting grading</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-circle"></i> Submission Status: Not Submitted</h5>
                            <p class="mt-3">You haven't submitted this assignment yet.</p>
                            <div class="mt-3">
                                <a href="submit_assignment.php?id=<?php echo $assignment_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Submit Assignment
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="submissions-section">
                        <h5><i class="fas fa-users"></i> Student Submissions</h5>
                        <?php if (empty($all_submissions)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No submissions yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-user"></i> Student Name</th>
                                            <th><i class="fas fa-at"></i> Username</th>
                                            <th><i class="fas fa-clock"></i> Submitted At</th>
                                            <th><i class="fas fa-file"></i> File</th>
                                            <th><i class="fas fa-star"></i> Grade</th>
                                            <th><i class="fas fa-cog"></i> Actions</th>
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
                                                        <a href="<?php echo htmlspecialchars($sub['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> View Submission
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No file submitted</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $sub['grade'] ? htmlspecialchars($sub['grade']) : 'Not graded'; ?>
                                                </td>
                                                <td>
                                                    <a href="grade_submission.php?id=<?php echo $sub['submission_id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-star"></i> Grade
                                                    </a>
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
            <a href="course.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Course
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 