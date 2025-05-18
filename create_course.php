<?php
session_start();

// Check if the user is logged in and is an instructor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "instructor"){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

$title = $description = "";
$title_err = $description_err = "";

if($_SERVER["REQUEST_METHOD"] === "POST"){
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter a course title.";
    } else{
        $title = trim($_POST["title"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a course description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Insert into database if no errors
    if(empty($title_err) && empty($description_err)){
        $sql = "INSERT INTO courses (title, description, instructor_id) VALUES (?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssi", $param_title, $param_description, $param_instructor_id);
            
            $param_title = $title;
            $param_description = $description;
            $param_instructor_id = $_SESSION["id"];  // Use 'id' from session
            
            if(mysqli_stmt_execute($stmt)){
                header("location: index.php");
                exit;
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
    <title>Create Course - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .create-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }
        .form-card {
            background: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            max-width: 600px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 0.75rem;
        }
        .form-group label {
            color: var(--secondary-color);
            font-weight: 500;
            margin-bottom: 0.25rem;
            display: block;
            font-size: 0.9rem;
        }
        .form-control {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.375rem 0.5rem;
            transition: var(--transition);
            width: 100%;
            font-size: 0.9rem;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        .form-text {
            color: #6c757d;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
        .preview-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }
        .preview-title {
            color: var(--secondary-color);
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }
        .preview-content {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 0.375rem 1rem;
            border-radius: 0.375rem;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 0.375rem 1rem;
            border-radius: 0.375rem;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .form-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        .form-row {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .form-col {
            flex: 1;
        }
        .form-check {
            margin-top: 0.25rem;
        }
        .form-check-input {
            margin-right: 0.25rem;
        }
        .form-check-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        .wrapper {
            max-width: 600px;
            margin: 1rem auto;
            padding: 0 1rem;
        }
        .wrapper h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .wrapper p {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .invalid-feedback {
            font-size: 0.8rem;
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
                <li class="nav-item active">
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
        <h2>Create New Course</h2>
        <p>Please fill this form to create a new course.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Course Title</label>
                <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($title); ?>">
                <span class="invalid-feedback"><?php echo $title_err; ?></span>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>"><?php echo htmlspecialchars($description); ?></textarea>
                <span class="invalid-feedback"><?php echo $description_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Create Course">
                <a href="index.php" class="btn btn-secondary ml-2">Cancel</a>
            </div>
        </form>
    </div>    
</body>
</html>
