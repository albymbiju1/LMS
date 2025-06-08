<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

$user_id = $_SESSION["id"];
$role = $_SESSION["role"];

// Initialize variables
$username = $full_name = $email = $role_display = "";
$error = $success = "";

// Fetch user data
$sql = "SELECT username, full_name, email, role FROM users WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)){
            $username = $row["username"];
            $full_name = $row["full_name"];
            $email = $row["email"];
            $role_display = ucfirst($row["role"]);
        }
    }
    mysqli_stmt_close($stmt);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate full name
    if(empty(trim($_POST["full_name"]))){
        $error = "Please enter your full name.";
    } else {
        $full_name = trim($_POST["full_name"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $error = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $error = "Please enter a valid email address.";
        }
    }
    
    // If no errors, update the profile
    if(empty($error)){
        $sql = "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssi", $full_name, $email, $user_id);
            if(mysqli_stmt_execute($stmt)){
                $success = "Profile updated successfully!";
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch user's courses if student
$enrolled_courses = [];
if($role === "student"){
    $sql = "SELECT c.course_id, c.title, c.description 
            FROM courses c 
            JOIN enrollments e ON c.course_id = e.course_id 
            WHERE e.user_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $enrolled_courses[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch instructor's courses if instructor
$instructor_courses = [];
if($role === "instructor"){
    $sql = "SELECT course_id, title, description FROM courses WHERE instructor_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $instructor_courses[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - LMS</title>
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

        .profile-header {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(26, 187, 156, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin: 0 auto 1.5rem;
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
            background: var(--primary-color);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(26, 187, 156, 0.15);
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

        .course-card {
            background: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .course-card:hover {
            transform: translateY(-3px);
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
                    <?php if($role === "instructor"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="create_course.php">
                            <i class="fas fa-plus-circle"></i>Create Course
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item active">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i>Profile
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

    <div class="container" style="max-width: 1200px; margin-top: 2rem;">
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>
        <?php if(!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-circle mr-2"></i>Profile</h5>
                    </div>
                    <div class="card-body">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($full_name); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($role_display); ?>" disabled>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <?php if($role === "student"): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-book-open mr-2"></i>Enrolled Courses</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($enrolled_courses)): ?>
                            <div class="alert alert-info">You are not enrolled in any courses yet.</div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach($enrolled_courses as $course): ?>
                                <div class="col-md-6">
                                    <div class="course-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($course["title"]); ?></h5>
                                            <p class="card-text text-muted"><?php echo htmlspecialchars($course["description"]); ?></p>
                                            <a href="course.php?id=<?php echo $course["course_id"]; ?>" 
                                               class="btn btn-primary btn-sm">
                                               View Course <i class="fas fa-arrow-right ml-2"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($role === "instructor"): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chalkboard-teacher mr-2"></i>Your Courses</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($instructor_courses)): ?>
                            <div class="alert alert-info">You haven't created any courses yet.</div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach($instructor_courses as $course): ?>
                                <div class="col-md-6">
                                    <div class="course-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($course["title"]); ?></h5>
                                            <p class="card-text text-muted"><?php echo htmlspecialchars($course["description"]); ?></p>
                                            <a href="course.php?id=<?php echo $course["course_id"]; ?>" 
                                               class="btn btn-primary btn-sm">
                                               View Course <i class="fas fa-arrow-right ml-2"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>