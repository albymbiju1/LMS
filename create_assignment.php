<?php
session_start();

// Check if the user is logged in and is an instructor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "instructor"){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Check if course ID is provided
if(!isset($_GET["course_id"])){
    header("location: index.php");
    exit;
}

$course_id = $_GET["course_id"];
$title = $description = $due_date = "";
$title_err = $description_err = $due_date_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter an assignment title.";
    } else{
        $title = trim($_POST["title"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter an assignment description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Validate due date
    if(empty(trim($_POST["due_date"]))){
        $due_date_err = "Please enter a due date.";
    } else{
        $due_date = trim($_POST["due_date"]);
    }
    
    // Check input errors before inserting in database
    if(empty($title_err) && empty($description_err) && empty($due_date_err)){
        $sql = "INSERT INTO assignments (title, description, due_date, course_id) VALUES (?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sssi", $param_title, $param_description, $param_due_date, $param_course_id);
            
            $param_title = $title;
            $param_description = $description;
            $param_due_date = $due_date;
            $param_course_id = $course_id;
            
            if(mysqli_stmt_execute($stmt)){
                header("location: course.php?id=" . $course_id);
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Assignment - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 600px; padding: 20px; margin: 0 auto; margin-top: 50px; }
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
                <li class="nav-item">
                    <a class="nav-link" href="create_course.php">Create Course</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="wrapper">
        <h2>Create New Assignment</h2>
        <p>Please fill this form to create a new assignment.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?course_id=" . $course_id); ?>" method="post">
            <div class="form-group">
                <label>Assignment Title</label>
                <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>">
                <span class="invalid-feedback"><?php echo $title_err; ?></span>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>"><?php echo $description; ?></textarea>
                <span class="invalid-feedback"><?php echo $description_err; ?></span>
            </div>
            <div class="form-group">
                <label>Due Date</label>
                <input type="datetime-local" name="due_date" class="form-control <?php echo (!empty($due_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $due_date; ?>">
                <span class="invalid-feedback"><?php echo $due_date_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Create Assignment">
                <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary ml-2">Cancel</a>
            </div>
        </form>
    </div>    
</body>
</html> 