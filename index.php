<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

$user_id = $_SESSION["id"];
$role    = $_SESSION["role"];

if ($role === "student") {
    $sql = "SELECT c.*, 
            CASE WHEN e.user_id IS NOT NULL THEN 1 ELSE 0 END as is_enrolled,
            u.full_name as instructor_name
            FROM courses c 
            LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.user_id = ?
            LEFT JOIN users u ON c.instructor_id = u.user_id";
} elseif ($role === "instructor") {
    $sql = "SELECT * FROM courses WHERE instructor_id = ?";
} else {
    $sql = "SELECT * FROM courses";
}

$courses = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    if ($role === "student" || $role === "instructor") {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    }
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - LMS</title>
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

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .wrapper {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .course-card {
            transition: var(--transition);
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .course-card:hover {
            transform: translateY(-5px);
        }

        .card-title {
            color: var(--primary-color);
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
            padding: 10px 25px;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 63, 84, 0.2);
        }

        .alert {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
        }

        .quick-links-card {
            background: #fff;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .list-group-item {
            border: none;
            padding: 15px 20px;
            transition: var(--transition);
            background: transparent;
        }

        .list-group-item a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .list-group-item:hover {
            background: #f8f9fa;
            padding-left: 25px;
        }

        h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 30px;
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

        .nav-link i {
            width: 20px;
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
                    <li class="nav-item active">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i>Dashboard
                        </a>
                    </li>
                    <?php if ($role === "instructor"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="create_course.php">
                            <i class="fas fa-plus-circle"></i>Create Course
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="discussions.php">
                            <i class="fas fa-comments"></i>Discussions
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
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
        
        <?php if(isset($_SESSION["message"])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION["message"];
                    unset($_SESSION["message"]);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION["error"])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION["error"];
                    unset($_SESSION["error"]);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <h3>Your Courses</h3>
                <?php if ($role === "student" && empty($courses)): ?>
                    <div class="alert alert-info">You are not enrolled in any courses yet.</div>
                <?php endif; ?>
                <div class="row">
                    <?php foreach ($courses as $course): ?>
                    <div class="col-md-6">
                        <div class="card course-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($course["title"]); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($course["description"]); ?></p>
                                <?php if (isset($course["instructor_name"])): ?>
                                    <p class="card-text"><small class="text-muted">Instructor: <?php echo htmlspecialchars($course["instructor_name"]); ?></small></p>
                                <?php endif; ?>
                                <?php if ($role === "student"): ?>
                                    <?php if (isset($course["is_enrolled"]) && $course["is_enrolled"]): ?>
                                        <a href="course.php?id=<?php echo $course["course_id"]; ?>" class="btn btn-primary">View Course</a>
                                    <?php else: ?>
                                        <a href="enroll.php?course_id=<?php echo $course["course_id"]; ?>" class="btn btn-success">Enroll Now</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="course.php?id=<?php echo $course["course_id"]; ?>" class="btn btn-primary">View Course</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card quick-links-card">
                    <div class="card-body">
                        <h5 class="card-title" style="color: var(--primary-color);">Quick Links</h5>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <a href="assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
                            </li>
                            <li class="list-group-item">
                                <a href="quizzes.php"><i class="fas fa-question-circle"></i> Quizzes</a>
                            </li>
                            <li class="list-group-item">
                                <a href="grades.php"><i class="fas fa-chart-line"></i> Grades</a>
                            </li>
                        </ul>
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