<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

$user_id = $_SESSION["id"];
$role = $_SESSION["role"];

// Fetch assignments based on role
$assignments = [];
if($role === "student"){
    $sql = "SELECT a.*, c.title as course_title, c.course_id 
            FROM assignments a 
            JOIN courses c ON a.course_id = c.course_id 
            JOIN enrollments e ON c.course_id = e.course_id 
            WHERE e.user_id = ? 
            ORDER BY a.due_date ASC";
} else if($role === "instructor"){
    $sql = "SELECT a.*, c.title as course_title, c.course_id 
            FROM assignments a 
            JOIN courses c ON a.course_id = c.course_id 
            WHERE c.instructor_id = ? 
            ORDER BY a.due_date ASC";
}

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $assignments[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignments - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .wrapper { width: 1200px; margin: 0 auto; padding: 20px; }
        .assignment-card {
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
            margin-bottom: 1.5rem;
        }
        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .assignment-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .status-pending {
            background-color: var(--warning-color);
            color: white;
        }
        .status-submitted {
            background-color: var(--success-color);
            color: white;
        }
        .status-graded {
            background-color: var(--primary-color);
            color: white;
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
                <li class="nav-item active"><a class="nav-link" href="assignments.php">Assignments</a></li>
                <li class="nav-item"><a class="nav-link" href="quizzes.php">Quizzes</a></li>
                <li class="nav-item"><a class="nav-link" href="grades.php">Grades</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Assignments</h2>
            <?php if($role === "instructor"): ?>
            <a href="create_assignment.php" class="btn btn-primary">Create Assignment</a>
            <?php endif; ?>
        </div>

        <?php if(empty($assignments)): ?>
            <div class="alert alert-info">No assignments available.</div>
        <?php else: ?>
            <?php foreach($assignments as $assignment): ?>
            <div class="card assignment-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title"><?php echo htmlspecialchars($assignment["title"]); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($assignment["course_title"]); ?></h6>
                            <p class="card-text"><?php echo htmlspecialchars($assignment["description"]); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="due-date mb-2">
                                <i class="fas fa-clock"></i> Due: <?php echo date('M d, Y', strtotime($assignment["due_date"])); ?>
                            </p>
                            <?php if($role === "student"): ?>
                                <a href="submit_assignment.php?id=<?php echo $assignment["assignment_id"]; ?>" class="btn btn-primary">Submit Assignment</a>
                            <?php else: ?>
                                <a href="view_submissions.php?id=<?php echo $assignment["assignment_id"]; ?>" class="btn btn-info">View Submissions</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 