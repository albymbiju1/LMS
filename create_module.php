<?php
session_start();

// Only instructors can access
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'instructor') {
    header('location: login.php');
    exit;
}

require_once 'config/database.php';

// Ensure course context
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header('location: index.php');
    exit;
}
$course_id = (int)$_GET['course_id'];

$title = $description = '';
$title_err = '';
$file_path = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate title
    if (empty(trim($_POST['title']))) {
        $title_err = 'Please enter a module title.';
    } else {
        $title = trim($_POST['title']);
    }

    // Description optional
    $description = trim($_POST['description']);

    // Handle file upload if provided
    if (!empty($_FILES['module_file']['name'])) {
        // Define upload directory (absolute path)
        $upload_dir = __DIR__ . '/uploads/modules/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = basename($_FILES['module_file']['name']);
        $target = $upload_dir . uniqid() . '_' . $filename;
        if (move_uploaded_file($_FILES['module_file']['tmp_name'], $target)) {
            // Store relative path in DB
            $file_path = 'uploads/modules/' . basename($target);
        } else {
            // Upload failed, set null or handle
            $file_path = null;
        }
    }

    // Insert only if no errors
    if (empty($title_err)) {
        $sql = 'INSERT INTO modules (course_id, title, description, file_path) VALUES (?, ?, ?, ?)';
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'isss', $param_course, $param_title, $param_desc, $param_path);
            $param_course = $course_id;
            $param_title = $title;
            $param_desc = $description;
            $param_path = $file_path;

            if (mysqli_stmt_execute($stmt)) {
                header("location: course.php?id={$course_id}");
                exit;
            } else {
                echo 'Oops! Something went wrong. Please try again later.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Module</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Create Module for Course</h2>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Module Title</label>
            <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($title); ?>">
            <span class="invalid-feedback"><?php echo $title_err; ?></span>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
        </div>
        <div class="form-group">
            <label>Upload File (optional)</label>
            <input type="file" name="module_file" class="form-control-file">
        </div>
        <button type="submit" class="btn btn-primary">Create Module</button>
        <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary ml-2">Cancel</a>
    </form>
</div>
</body>
</html>
