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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid white;
            margin-bottom: 1rem;
            object-fit: cover;
        }
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--box-shadow);
        }
        .stat-card i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary-color);
        }
        .stat-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
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
                <?php if($role === "instructor"): ?>
                <li class="nav-item"><a class="nav-link" href="create_course.php">Create Course</a></li>
                <?php endif; ?>
                <li class="nav-item active"><a class="nav-link" href="profile.php">Profile</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="wrapper">
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($full_name); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($role_display); ?>" disabled>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <?php if($role === "student"): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Enrolled Courses</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($enrolled_courses)): ?>
                            <p>You are not enrolled in any courses yet.</p>
                        <?php else: ?>
                            <?php foreach($enrolled_courses as $course): ?>
                            <div class="card course-card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($course["title"]); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($course["description"]); ?></p>
                                    <a href="course.php?id=<?php echo $course["course_id"]; ?>" class="btn btn-primary">View Course</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($role === "instructor"): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Courses</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($instructor_courses)): ?>
                            <p>You haven't created any courses yet.</p>
                        <?php else: ?>
                            <?php foreach($instructor_courses as $course): ?>
                            <div class="card course-card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($course["title"]); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($course["description"]); ?></p>
                                    <a href="course.php?id=<?php echo $course["course_id"]; ?>" class="btn btn-primary">View Course</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
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