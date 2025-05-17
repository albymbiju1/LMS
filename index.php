<?php
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Get user's courses
$user_id = $_SESSION["id"];      // Changed from user_id to id to match session key
$role    = $_SESSION["role"];

// Build SQL based on role
if ($role === "student") {
    $sql = "SELECT c.* FROM courses c
            INNER JOIN enrollments e ON c.course_id = e.course_id
            WHERE e.user_id = ?";
} elseif ($role === "instructor") {
    $sql = "SELECT * FROM courses WHERE instructor_id = ?";
} else {
    // For admin or other roles, show all courses
    $sql = "SELECT * FROM courses";
}

$courses = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    // Bind parameter if needed
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .wrapper{
            width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .course-card {
            margin-bottom: 20px;
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
                <li class="nav-item active">
                    <a class="nav-link" href="index.php">Dashboard</a>
                </li>
                <?php if ($role === "instructor"): ?>
                <li class="nav-item">
                    <a class="nav-link" href="create_course.php">Create Course</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="discussions.php">Discussions</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="wrapper">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <h3>Your Courses</h3>
                <?php if (empty($courses)): ?>
                    <p>You are not enrolled in any courses yet.</p>
                <?php endif; ?>
                <div class="row">
                    <?php foreach ($courses as $course): ?>
                    <div class="col-md-6">
                        <div class="card course-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($course["title"]); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($course["description"]); ?></p>
                                <a href="course.php?id=<?php echo $course["course_id"]; ?>" class="btn btn-primary">View Course</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Quick Links</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <a href="assignments.php">View Assignments</a>
                            </li>
                            <li class="list-group-item">
                                <a href="quizzes.php">Take Quizzes</a>
                            </li>
                            <li class="list-group-item">
                                <a href="grades.php">View Grades</a>
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
