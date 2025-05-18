<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Get the enrolled course details
$course_id = isset($_SESSION["enrolled_course_id"]) ? $_SESSION["enrolled_course_id"] : null;
$course_details = null;

if($course_id) {
    $sql = "SELECT c.*, u.full_name as instructor_name 
            FROM courses c 
            LEFT JOIN users u ON c.instructor_id = u.user_id 
            WHERE c.course_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $course_id);
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $course_details = mysqli_fetch_assoc($result);
        }
        mysqli_stmt_close($stmt);
    }
}

// If no course details found, redirect to index
if(!$course_details) {
    header("location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Success - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .wrapper {
            width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .success-icon {
            font-size: 64px;
            color: #28a745;
        }
        .course-card {
            margin-top: 20px;
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
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Dashboard</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="wrapper">
        <div class="text-center mb-4">
            <i class="fas fa-check-circle success-icon"></i>
            <h2 class="mt-3">Enrollment Successful!</h2>
            <?php if(isset($_SESSION["message"])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION["message"];
                        unset($_SESSION["message"]);
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card course-card">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($course_details["title"]); ?></h5>
                <p class="card-text"><?php echo htmlspecialchars($course_details["description"]); ?></p>
                <?php if(isset($course_details["instructor_name"])): ?>
                    <p class="card-text"><small class="text-muted">Instructor: <?php echo htmlspecialchars($course_details["instructor_name"]); ?></small></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-primary btn-lg mr-3">
                <i class="fas fa-book"></i> View Course
            </a>
            <a href="index.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
// Clear the enrolled course ID from session
unset($_SESSION["enrolled_course_id"]);
?> 