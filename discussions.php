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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .wrapper{ width: 1200px; margin: 0 auto; padding: 20px; }
        .discussion-card {
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
            margin-bottom: 1.5rem;
        }
        .discussion-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .discussion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .discussion-meta {
            display: flex;
            gap: 1rem;
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        .discussion-meta i {
            margin-right: 0.5rem;
        }
        .discussion-stats {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--secondary-color);
        }
        .stat i {
            color: var(--primary-color);
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
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item active"><a class="nav-link" href="discussions.php">Discussions</a></li>
            </ul>
            <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li></ul>
        </div>
    </nav>

    <div class="wrapper">
        <div class="row">
            <div class="col-md-8">
                <h2>Discussions</h2>
                <!-- Discussions List -->
                <?php if(empty($discussions)): ?>
                    <p>No discussions available.</p>
                <?php else: ?>
                    <?php foreach($discussions as $discussion): ?>
                    <div class="card discussion-card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="view_discussion.php?id=<?php echo $discussion['id']; ?>">
                                    <?php echo htmlspecialchars($discussion['title']); ?>
                                </a>
                            </h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                Course: <?php echo htmlspecialchars($discussion['course_title']); ?>
                            </h6>
                            <p class="card-text">
                                <?php $snippet = htmlspecialchars($discussion['content']);
                                      echo strlen($snippet) > 200 ? substr($snippet,0,200) . '...' : $snippet; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Posted by <?php echo htmlspecialchars($discussion['author_name']); ?> 
                                    on <?php echo date('M d, Y', strtotime($discussion['created_at'])); ?>
                                </small>
                                <a href="view_discussion.php?id=<?php echo $discussion['id']; ?>" class="btn btn-primary btn-sm">
                                    View Discussion
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($role === 'instructor'): ?>
            <div class="col-md-4">
                <!-- New Discussion Form for Instructors Only -->
                <div class="card">
                    <div class="card-header"><h5>Start New Discussion</h5></div>
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
                                    <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Content</label>
                                <textarea name="content" class="form-control" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Post Discussion</button>
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
