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
$title_err = $description_err = $file_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter a title.";
    } else{
        $title = trim($_POST["title"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Validate file
    if(!isset($_FILES["file"]) || $_FILES["file"]["error"] == UPLOAD_ERR_NO_FILE){
        $file_err = "Please select a file to upload.";
    } else{
        $file = $_FILES["file"];
        $file_name = $file["name"];
        $file_tmp = $file["tmp_name"];
        $file_size = $file["size"];
        $file_error = $file["error"];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowed = array("pdf", "doc", "docx", "txt", "ppt", "pptx", "xls", "xlsx", "zip", "rar");
        
        if(!in_array($file_ext, $allowed)){
            $file_err = "File type not allowed. Allowed types: " . implode(", ", $allowed);
        } else if($file_error !== 0){
            $file_err = "Error uploading file.";
        } else if($file_size > 10485760){ // 10MB max
            $file_err = "File size too large. Maximum size is 10MB.";
        }
    }
    
    // Check input errors before uploading file and inserting in database
    if(empty($title_err) && empty($description_err) && empty($file_err)){
        // Generate unique file name
        $new_file_name = uniqid() . "." . $file_ext;
        $upload_path = "uploads/" . $new_file_name;
        
        // Upload file
        if(move_uploaded_file($file_tmp, $upload_path)){
            $sql = "INSERT INTO resources (title, description, file_name, original_name, file_type, file_size, course_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
             
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "sssssii", $param_title, $param_description, $param_file_name, $param_original_name, $param_file_type, $param_file_size, $param_course_id);
                
                $param_title = $title;
                $param_description = $description;
                $param_file_name = $new_file_name;
                $param_original_name = $file_name;
                $param_file_type = $file["type"];
                $param_file_size = $file_size;
                $param_course_id = $course_id;
                
                if(mysqli_stmt_execute($stmt)){
                    header("location: course.php?id=" . $course_id);
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        } else{
            $file_err = "Error uploading file.";
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Resource - LMS</title>
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
        <h2>Upload Resource</h2>
        <p>Please fill this form to upload a resource.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?course_id=" . $course_id); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>">
                <span class="invalid-feedback"><?php echo $title_err; ?></span>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>"><?php echo $description; ?></textarea>
                <span class="invalid-feedback"><?php echo $description_err; ?></span>
            </div>
            <div class="form-group">
                <label>File</label>
                <input type="file" name="file" class="form-control-file <?php echo (!empty($file_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $file_err; ?></span>
                <small class="form-text text-muted">
                    Maximum file size: 10MB<br>
                    Allowed file types: PDF, DOC, DOCX, TXT, PPT, PPTX, XLS, XLSX, ZIP, RAR
                </small>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Upload Resource">
                <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary ml-2">Cancel</a>
            </div>
        </form>
    </div>    
</body>
</html> 