<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Check if resource ID is provided
if(!isset($_GET["id"])){
    header("location: index.php");
    exit;
}

$resource_id = $_GET["id"];
$user_id = $_SESSION["id"];
$role = $_SESSION["role"];

// Get resource details
$sql = "SELECT r.*, c.id as course_id FROM resources r 
        JOIN courses c ON r.course_id = c.id 
        WHERE r.id = ?";

$resource = null;
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resource_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        $resource = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

// Check if user has access to this resource
if($role == "student"){
    $sql = "SELECT * FROM enrollments WHERE course_id = ? AND student_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $resource["course_id"], $user_id);
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) == 0){
                header("location: index.php");
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    }
} else if($role == "teacher"){
    $sql = "SELECT * FROM courses WHERE id = ? AND teacher_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $resource["course_id"], $user_id);
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) == 0){
                header("location: index.php");
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Download the file
if($resource){
    $file_path = "uploads/resources/" . $resource["file_path"];
    if(file_exists($file_path)){
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($resource["file_path"]) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}

// If file doesn't exist or there's an error, redirect to course page
header("location: course.php?id=" . $resource["course_id"]);
exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Download Resource - LMS</title>
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

        .download-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }

        .download-content {
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

        .resource-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .resource-info h4 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .resource-info p {
            margin-bottom: 0.5rem;
            color: #666;
        }

        .resource-info i {
            color: var(--secondary-color);
            margin-right: 8px;
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
        <div class="download-header">
            <h2><i class="fas fa-download"></i> Download Resource</h2>
            <p class="mb-0">Access your course resource</p>
        </div>

        <div class="download-content">
            <div class="resource-info">
                <h4><i class="fas fa-info-circle"></i> Resource Information</h4>
                <p><i class="fas fa-file"></i> <strong>File Name:</strong> <?php echo htmlspecialchars($resource['file_name']); ?></p>
                <p><i class="fas fa-calendar"></i> <strong>Upload Date:</strong> <?php echo date('F j, Y', strtotime($resource['upload_date'])); ?></p>
                <p><i class="fas fa-file-alt"></i> <strong>Description:</strong> <?php echo htmlspecialchars($resource['description']); ?></p>
            </div>

            <div class="text-center">
                <a href="download.php?id=<?php echo $resource_id; ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download Resource
                </a>
                <a href="course.php?id=<?php echo $resource['course_id']; ?>" class="btn btn-secondary ml-2">
                    <i class="fas fa-arrow-left"></i> Back to Course
                </a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 