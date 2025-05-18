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
$title = $description = "";
$title_err = $description_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter a quiz title.";
    } else{
        $title = trim($_POST["title"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a quiz description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Check input errors before inserting in database
    if(empty($title_err) && empty($description_err)){
        $sql = "INSERT INTO quizzes (title, description, course_id) VALUES (?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssi", $param_title, $param_description, $param_course_id);
            
            $param_title = $title;
            $param_description = $description;
            $param_course_id = $course_id;
            
            if(mysqli_stmt_execute($stmt)){
                $quiz_id = mysqli_insert_id($conn);
                header("location: add_questions.php?quiz_id=" . $quiz_id);
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
    <title>Create Quiz - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .create-header {
            background: linear-gradient(135deg, var(--warning-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }
        .form-card {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        .question-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-top: 1rem;
        }
        .question-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border: 2px solid #eee;
            transition: var(--transition);
        }
        .question-card:hover {
            border-color: var(--warning-color);
            transform: translateY(-2px);
        }
        .option-list {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }
        .option-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 2px solid #eee;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }
        .option-item:hover {
            border-color: var(--primary-color);
            background: #f8f9fa;
        }
        .option-item.correct {
            border-color: var(--success-color);
            background: rgba(46, 204, 113, 0.1);
        }
        .option-actions {
            display: flex;
            gap: 0.5rem;
        }
        .option-actions button {
            padding: 0.5rem;
            border: none;
            background: none;
            color: var(--secondary-color);
            transition: var(--transition);
        }
        .option-actions button:hover {
            color: var(--primary-color);
        }
        .timer-settings {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-top: 1rem;
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
        <h2>Create New Quiz</h2>
        <p>Please fill this form to create a new quiz.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?course_id=" . $course_id); ?>" method="post">
            <div class="form-group">
                <label>Quiz Title</label>
                <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>">
                <span class="invalid-feedback"><?php echo $title_err; ?></span>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>"><?php echo $description; ?></textarea>
                <span class="invalid-feedback"><?php echo $description_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Create Quiz">
                <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary ml-2">Cancel</a>
            </div>
        </form>
    </div>    
</body>
</html> 