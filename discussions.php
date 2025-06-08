<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Retrieve user ID and role from session
$user_id = $_SESSION["id"];
$role    = $_SESSION["role"];

// Get all discussions
$sql = "SELECT d.*, u.username as author_name, c.title as course_title 
        FROM discussions d 
        JOIN users u ON d.author_id = u.user_id 
        JOIN courses c ON d.course_id = c.course_id 
        ORDER BY d.created_at DESC";

$discussions = [];
if($stmt = mysqli_prepare($conn, $sql)){
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $discussions[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Only instructors can post new discussions: fetch courses accordingly
if($role === "instructor"){
    $sql = "SELECT * FROM courses WHERE instructor_id = ?";
    $courses = [];
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $courses[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle new discussion submission (instructors only)
if($_SERVER["REQUEST_METHOD"] === "POST" && $role === "instructor"){
    $title     = trim($_POST["title"]);
    $content   = trim($_POST["content"]);
    $course_id = trim($_POST["course_id"]);
    
    if(!empty($title) && !empty($content) && !empty($course_id)){
        $sql = "INSERT INTO discussions (title, content, author_id, course_id, created_at) VALUES (?, ?, ?, ?, NOW())";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssii", $title, $content, $user_id, $course_id);
            if(mysqli_stmt_execute($stmt)){
                header("Location: discussions.php");
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
    <title>Discussions - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2A3F54;
            --secondary-color: #1ABB9C;
            --accent-color: #337AB7;
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .discussion-card {
            background: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .discussion-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .card-title a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .card-title a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
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

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(26, 187, 156, 0.15);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">LMS</a>
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
                    <li class="nav-item active">
                        <a class="nav-link" href="discussions.php">
                            <i class="fas fa-comments"></i>Discussions
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user-circle"></i>Profile
                        </a>
                    </li>
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
        <div class="row">
            <div class="col-lg-8">
                <h2 class="mb-4" style="color: var(--primary-color);">Discussions</h2>
                
                <?php if(empty($discussions)): ?>
                    <div class="alert alert-info">No discussions available.</div>
                <?php else: ?>
                    <?php foreach($discussions as $discussion): ?>
                    <div class="discussion-card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <a href="view_discussion.php?id=<?php echo $discussion['id']; ?>">
                                    <?php echo htmlspecialchars($discussion['title']); ?>
                                </a>
                            </h5>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="badge badge-primary" style="background: var(--secondary-color);">
                                    <?php echo htmlspecialchars($discussion['course_title']); ?>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('M d, Y', strtotime($discussion['created_at'])); ?>
                                </small>
                            </div>
                            <p class="card-text text-muted">
                                <?php $snippet = htmlspecialchars($discussion['content']);
                                      echo strlen($snippet) > 200 ? substr($snippet,0,200) . '...' : $snippet; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle text-secondary mr-2"></i>
                                    <small><?php echo htmlspecialchars($discussion['author_name']); ?></small>
                                </div>
                                <a href="view_discussion.php?id=<?php echo $discussion['id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    View Discussion <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($role === 'instructor'): ?>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header" style="background: var(--primary-color); color: white;">
                        <h5 class="mb-0">New Discussion</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Course</label>
                                <select name="course_id" class="form-control" required>
                                    <option value="">Select Course</option>
                                    <?php foreach($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Content</label>
                                <textarea name="content" class="form-control" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                Post Discussion <i class="fas fa-paper-plane ml-2"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>