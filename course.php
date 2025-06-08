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
            background: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
            box-shadow: var(--box-shadow);
        }

        .course-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin: 2rem 0;
            box-shadow: var(--box-shadow);
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .card-header {
            background: white;
            border-bottom: 2px solid rgba(0,0,0,0.05);
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .list-group-item {
            border: none;
            margin-bottom: 0.5rem;
            border-radius: var(--border-radius) !important;
            transition: var(--transition);
        }

        .list-group-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: var(--border-radius);
            padding: 8px 20px;
            transition: var(--transition);
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 63, 84, 0.2);
        }

        .btn-danger {
            border-radius: var(--border-radius);
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

        .resource-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: white;
            border-radius: var(--border-radius);
            margin-bottom: 0.75rem;
            box-shadow: var(--box-shadow);
        }

        .resource-item i {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(26, 187, 156, 0.1);
            border-radius: 50%;
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">LMS</a>
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
                    <?php if ($role === 'instructor'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="create_course.php">
                            <i class="fas fa-plus-circle"></i>Create Course
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item active">
                        <a class="nav-link" href="course.php?id=<?php echo $course_id; ?>">
                            <i class="fas fa-book-open"></i>Course
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

    <div class="container" style="max-width: 1200px; margin-top: 2rem;">
        <div class="course-header">
            <h2><?php echo htmlspecialchars($course['title']); ?></h2>
            <p class="lead mb-0"><?php echo htmlspecialchars($course['description']); ?></p>
            <div class="mt-3 d-flex align-items-center">
                <i class="fas fa-chalkboard-teacher mr-2"></i>
                <span>Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></span>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Modules -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Modules</h5>
                        <?php if ($role === 'instructor'): ?>
                        <a href="create_module.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Module
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($modules)): ?>
                            <div class="text-muted">No modules available</div>
                        <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($modules as $m): ?>
                            <a href="view_module.php?module_id=<?php echo $m['module_id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($m['title']); ?></h6>
                                        <p class="mb-0 text-muted small"><?php echo htmlspecialchars($m['description']); ?></p>
                                    </div>
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Assignments -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Assignments</h5>
                        <?php if ($role === 'instructor'): ?>
                        <a href="create_assignment.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> New Assignment
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignments)): ?>
                            <div class="text-muted">No assignments available</div>
                        <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($assignments as $a): ?>
                            <a href="view_assignment.php?id=<?php echo $a['assignment_id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($a['title']); ?></h6>
                                        <small class="text-muted">Due: <?php echo date('M d, Y', strtotime($a['due_date'])); ?></small>
                                    </div>
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quizzes -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quizzes</h5>
                        <?php if ($role === 'instructor'): ?>
                        <a href="create_quiz.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> New Quiz
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($quizzes)): ?>
                            <div class="text-muted">No quizzes available</div>
                        <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($quizzes as $q): 
                                // For instructors, fetch attempt count
                                if ($role === 'instructor') {
                                    $stmt = mysqli_prepare($conn, 
                                        "SELECT COUNT(*) as attempt_count 
                                         FROM quiz_attempts 
                                         WHERE quiz_id = ?");
                                    mysqli_stmt_bind_param($stmt, 'i', $q['quiz_id']);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    $attempts = mysqli_fetch_assoc($result)['attempt_count'];
                                    mysqli_stmt_close($stmt);
                                }
                            ?>
                            <a href="<?php echo $role === 'instructor' ? 'view_quiz_results.php' : 'view_quiz.php'; ?>?id=<?php echo $q['quiz_id']; ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($q['title']); ?></h6>
                                        <p class="mb-0 text-muted small"><?php echo htmlspecialchars($q['description']); ?></p>
                                        <?php if ($role === 'instructor'): ?>
                                            <small class="text-info">
                                                <i class="fas fa-users"></i> <?php echo $attempts; ?> attempt<?php echo $attempts != 1 ? 's' : ''; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <?php if ($role === 'instructor'): ?>
                                            <i class="fas fa-chart-bar text-primary"></i>
                                        <?php else: ?>
                                            <i class="fas fa-chevron-right"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Course Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="resource-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div class="ml-3">
                                <div class="small text-muted">Created</div>
                                <div><?php echo date('M d, Y', strtotime($course['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php if ($role === 'student'): ?>
                        <a href="unenroll.php?course_id=<?php echo $course_id; ?>" 
                           class="btn btn-danger btn-block mt-3"
                           onclick="return confirm('Are you sure you want to unenroll from this course?')">
                            <i class="fas fa-sign-out-alt"></i> Unenroll
                        </a>
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