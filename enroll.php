<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in and is a student
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "student"){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Check if course_id is provided
if(!isset($_GET["course_id"])) {
    $_SESSION["error"] = "No course selected.";
    header("location: index.php");
    exit;
}

$course_id = intval($_GET["course_id"]); // Ensure course_id is an integer
$user_id = intval($_SESSION["id"]); // Ensure user_id is an integer

// First verify if the course exists
$check_course_sql = "SELECT course_id, title FROM courses WHERE course_id = ?";
if($stmt = mysqli_prepare($conn, $check_course_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) == 0) {
        // Course doesn't exist
        $_SESSION["error"] = "Course not found.";
        header("location: index.php");
        exit;
    }
    $course = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    $_SESSION["error"] = "Database error: " . mysqli_error($conn);
    header("location: index.php");
    exit;
}

// Check if already enrolled
$check_sql = "SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?";
if($stmt = mysqli_prepare($conn, $check_sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $course_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if(mysqli_stmt_num_rows($stmt) > 0) {
        // Already enrolled
        $_SESSION["message"] = "You are already enrolled in this course.";
        header("location: course.php?id=" . $course_id);
        exit;
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION["error"] = "Database error: " . mysqli_error($conn);
    header("location: index.php");
    exit;
}

// Enroll the student
$enroll_sql = "INSERT INTO enrollments (user_id, course_id, enroll_date) VALUES (?, ?, NOW())";
if($stmt = mysqli_prepare($conn, $enroll_sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $course_id);
    
    if(mysqli_stmt_execute($stmt)) {
        // Enrollment successful
        $_SESSION["message"] = "Successfully enrolled in " . htmlspecialchars($course["title"]) . "!";
        $_SESSION["enrolled_course_id"] = $course_id;
        
        // Redirect to success page
        header("location: enrollment_success.php");
        exit;
    } else {
        $_SESSION["error"] = "Error enrolling in course: " . mysqli_error($conn);
        header("location: index.php");
        exit;
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION["error"] = "Error preparing enrollment statement: " . mysqli_error($conn);
    header("location: index.php");
    exit;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enroll - LMS</title>
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

        .enroll-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }

        .enroll-form {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
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
                </ul>
                <ul class="navbar-nav">
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
        <div class="enroll-header">
            <h2><i class="fas fa-user-plus"></i> Enroll in Course</h2>
            <p class="mb-0">Are you sure you want to enroll in this course?</p>
        </div>

        <div class="enroll-form">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?course_id=" . $course_id); ?>" method="post">
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Confirm Enrollment
                    </button>
                    <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary ml-2">
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