<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Validate course ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$course_id = (int)$_GET['id'];

// Use consistent session key
$user_id = $_SESSION['id'];
$role    = $_SESSION['role'];

// Fetch course details with instructor name
$sql = "SELECT c.course_id, c.title, c.description, c.instructor_id, c.created_at,
               u.username AS instructor_name
        FROM courses c
        JOIN users u ON c.instructor_id = u.user_id
        WHERE c.course_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $course = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$course) {
        header('Location: index.php');
        exit;
    }
} else {
    die('Database error: Unable to fetch course');
}

// Access control: students enrolled or instructors owning the course
if ($role === 'student') {
    $sql = "SELECT 1 FROM enrollments WHERE course_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $course_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) === 0) {
        header('Location: index.php');
        exit;
    }
    mysqli_stmt_close($stmt);
} elseif ($role === 'instructor') {
    if ($course['instructor_id'] !== $user_id) {
        header('Location: index.php');
        exit;
    }
}

// Fetch modules
$sql = "SELECT module_id, title, description FROM modules WHERE course_id = ? ORDER BY module_id ASC";
$modules = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $modules[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Fetch assignments
$sql = "SELECT assignment_id, title, description, due_date FROM assignments WHERE course_id = ? ORDER BY due_date ASC";
$assignments = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $assignments[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Fetch quizzes
$sql = "SELECT quiz_id, title, description FROM quizzes WHERE course_id = ? ORDER BY created_at DESC";
$quizzes = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $quizzes[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .course-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }
        .module-list {
            margin-top: 2rem;
        }
        .module-item {
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }
        .module-item:hover {
            transform: translateX(5px);
        }
        .resource-list {
            margin-top: 1rem;
        }
        .resource-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            background: #f8f9fa;
            margin-bottom: 0.5rem;
        }
        .resource-item i {
            margin-right: 1rem;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">LMS</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <?php if ($role === 'instructor'): ?>
                <li class="nav-item"><a class="nav-link" href="create_course.php">Create Course</a></li>
                <?php endif; ?>
                <li class="nav-item active"><a class="nav-link" href="course.php?id=<?php echo $course_id; ?>">Course</a></li>
            </ul>
            <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li></ul>
        </div>
    </nav>

    <div class="wrapper">
        <div class="course-header">
            <h2><?php echo htmlspecialchars($course['title']); ?></h2>
            <p class="lead"><?php echo htmlspecialchars($course['description']); ?></p>
            <p>Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></p>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Modules -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Modules</h5>
                        <?php if ($role === 'instructor'): ?>
                        <a href="create_module.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary btn-sm">Add Module</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($modules)): ?><p>No modules available.</p><?php else: ?>
                        <div class="list-group">
                            <?php foreach ($modules as $m): ?>
                                <a href="view_module.php?module_id=<?php echo $m['module_id']; ?>" class="list-group-item list-group-item-action">

                                <h6 class="mb-1"><?php echo htmlspecialchars($m['title']); ?></h6>
                                <p class="mb-1"><?php echo htmlspecialchars($m['description']); ?></p>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Assignments -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Assignments</h5>
                        <?php if ($role === 'instructor'): ?>
                        <a href="create_assignment.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary btn-sm">New Assignment</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignments)): ?><p>No assignments available.</p><?php else: ?>
                        <div class="list-group">
                            <?php foreach ($assignments as $a): ?>
                            <a href="view_assignment.php?id=<?php echo $a['assignment_id']; ?>" class="list-group-item list-group-item-action">
                                <h6 class="mb-1"><?php echo htmlspecialchars($a['title']); ?></h6>
                                <small>Due: <?php echo date('M d, Y', strtotime($a['due_date'])); ?></small>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quizzes -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Quizzes</h5>
                        <?php if ($role === 'instructor'): ?>
                        <a href="create_quiz.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary btn-sm">New Quiz</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($quizzes)): ?><p>No quizzes available.</p><?php else: ?>
                        <div class="list-group">
                            <?php foreach ($quizzes as $q): ?>
                            <a href="view_quiz.php?id=<?php echo $q['quiz_id']; ?>" class="list-group-item list-group-item-action">
                                <h6 class="mb-1"><?php echo htmlspecialchars($q['title']); ?></h6>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h5>Course Info</h5></div>
                    <div class="card-body">
                        <p><strong>Created:</strong> <?php echo date('M d, Y', strtotime($course['created_at'])); ?></p>
                        <?php if ($role === 'student'): ?>
                        <a href="unenroll.php?course_id=<?php echo $course_id; ?>" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to unenroll from this course?')">Unenroll from Course</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
