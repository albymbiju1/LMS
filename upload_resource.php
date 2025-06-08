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

        .upload-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }

        .upload-form {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .form-control {
            border: 2px solid #eee;
            border-radius: var(--border-radius);
            padding: 0.75rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(42, 63, 84, 0.25);
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

        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.5rem;
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
                    <li class="nav-item">
                        <a class="nav-link" href="create_course.php">
                            <i class="fas fa-plus-circle"></i>Create Course
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
        <div class="upload-header">
            <h2><i class="fas fa-upload"></i> Upload Resource</h2>
            <p class="mb-0">Please fill this form to upload a resource.</p>
        </div>

        <div class="upload-form">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?course_id=" . $course_id); ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Title</label>
                    <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>">
                    <span class="invalid-feedback"><?php echo $title_err; ?></span>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>"><?php echo $description; ?></textarea>
                    <span class="invalid-feedback"><?php echo $description_err; ?></span>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-file"></i> File</label>
                    <input type="file" name="file" class="form-control-file <?php echo (!empty($file_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $file_err; ?></span>
                    <small class="form-text">
                        <i class="fas fa-info-circle"></i> Maximum file size: 10MB<br>
                        <i class="fas fa-file-alt"></i> Allowed file types: PDF, DOC, DOCX, TXT, PPT, PPTX, XLS, XLSX, ZIP, RAR
                    </small>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Resource
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