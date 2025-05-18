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