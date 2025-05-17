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