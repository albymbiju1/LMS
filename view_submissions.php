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

// Debug information
error_log("User ID: " . $user_id);
error_log("Assignment Instructor ID: " . $assignment['instructor_id']);
error_log("User Role: " . $_SESSION["role"]);

// Check if user is instructor and owns the course
if ($_SESSION["role"] !== "instructor" || $assignment['instructor_id'] != $user_id) {
    $_SESSION['error'] = "You don't have permission to view submissions";
    header("Location: assignments.php");
    exit();
}

// Get all submissions for this assignment
$sql = "SELECT s.*, u.full_name, u.username, s.file_path
        FROM submissions s
        JOIN users u ON s.student_id = u.user_id
        WHERE s.assignment_id = ? 
        ORDER BY s.submitted_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $assignment_id);
mysqli_stmt_execute($stmt);
$submissions = mysqli_stmt_get_result($stmt);

// Debug information
error_log("Assignment ID: " . $assignment_id);
error_log("Number of submissions found: " . mysqli_num_rows($submissions));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions - LMS</title>
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .submissions-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }

        .submissions-table {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .submissions-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 1rem;
        }

        .submissions-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .submissions-table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .submissions-table tr:hover {
            background: #e9ecef;
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
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="submissions-header">
            <h4><i class="fas fa-file-alt"></i> Submissions for: <?php echo htmlspecialchars($assignment['title']); ?></h4>
            <p class="mb-0"><i class="fas fa-book"></i> Course: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
        </div>

        <div class="submissions-table">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Student Name</th>
                            <th><i class="fas fa-id-card"></i> Username</th>
                            <th><i class="fas fa-clock"></i> Submitted At</th>
                            <th><i class="fas fa-file"></i> File</th>
                            <th><i class="fas fa-star"></i> Grade</th>
                            <th><i class="fas fa-tasks"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($submission = mysqli_fetch_assoc($submissions)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($submission['full_name'] ?: $submission['username']); ?></td>
                                <td><?php echo htmlspecialchars($submission['username']); ?></td>
                                <td><?php echo date('F j, Y g:i A', strtotime($submission['submitted_at'])); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($submission['file_path'])) {
                                        if (file_exists($submission['file_path'])) {
                                            echo '<a href="' . htmlspecialchars($submission['file_path']) . '" target="_blank" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View Submission
                                            </a>';
                                        } else {
                                            echo '<span class="text-danger">File not found</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">No file submitted</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (isset($submission['grade'])): ?>
                                        <span class="badge <?php 
                                            echo $submission['grade'] >= 90 ? 'badge-success' : 
                                                ($submission['grade'] >= 70 ? 'badge-warning' : 'badge-danger'); 
                                        ?>">
                                            <?php echo number_format($submission['grade'], 1); ?>%
                                        </span>
                                        <?php if (!empty($submission['feedback'])): ?>
                                            <i class="fas fa-comment text-info ml-2" data-toggle="tooltip" 
                                               title="<?php echo htmlspecialchars($submission['feedback']); ?>"></i>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Not Graded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($submission['grade'])): ?>
                                        <a href="grade_submission.php?id=<?php echo $submission['submission_id']; ?>" 
                                           class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Update Grade
                                        </a>
                                    <?php else: ?>
                                        <a href="grade_submission.php?id=<?php echo $submission['submission_id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-check-circle"></i> Grade
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
