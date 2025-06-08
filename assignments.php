<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

$user_id = $_SESSION["id"] ?? null;
$role = $_SESSION["role"] ?? '';

// Fetch assignments with submission status
$assignments = [];
try {
    if($role === "student"){
        $sql = "SELECT a.*, c.title as course_title, c.course_id, 
                CASE WHEN s.submission_id IS NOT NULL THEN 1 ELSE 0 END as is_submitted
                FROM assignments a 
                JOIN courses c ON a.course_id = c.course_id 
                JOIN enrollments e ON c.course_id = e.course_id 
                LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id AND s.student_id = ?
                WHERE e.user_id = ? 
                ORDER BY a.due_date ASC";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    } 
    elseif($role === "instructor"){
        $sql = "SELECT a.*, c.title as course_title, c.course_id 
                FROM assignments a 
                JOIN courses c ON a.course_id = c.course_id 
                WHERE c.instructor_id = ? 
                ORDER BY a.due_date ASC";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    }

    if(isset($stmt)){
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $assignments[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
} catch(Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Error loading assignments. Please try again later.";
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignments - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .assignment-card {
            background: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .status-submitted { background: rgba(40, 167, 69, 0.2); color: #28a745; }

        .due-date {
            color: var(--primary-color);
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">LMS</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item active"><a class="nav-link" href="assignments.php">Assignments</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="wrapper">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Assignments</h2>
            <?php if($role === "instructor"): ?>
                <a href="create_assignment.php?course_id=<?php echo $assignments[0]['course_id'] ?? ''; ?>" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> New Assignment
                </a>
            <?php endif; ?>
        </div>

        <?php if(empty($assignments)): ?>
            <div class="alert alert-info">No assignments available</div>
        <?php else: ?>
            <div class="row">
                <?php foreach($assignments as $assignment): ?>
                <div class="col-md-6">
                    <div class="assignment-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title"><?= htmlspecialchars($assignment['title']) ?></h5>
                                    <h6 class="card-subtitle text-muted">
                                        <?= htmlspecialchars($assignment['course_title']) ?>
                                    </h6>
                                </div>
                                <small class="due-date">
                                    <i class="fas fa-clock"></i>
                                    <?= date('M d, Y', strtotime($assignment['due_date'])) ?>
                                </small>
                            </div>
                            
                            <p class="card-text"><?= htmlspecialchars($assignment['description']) ?></p>
                            
                            <?php if($role === "student"): ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <?php if($assignment['is_submitted'] ?? 0): ?>
                                        <span class="status-badge status-submitted">
                                            <i class="fas fa-check"></i> Submitted
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                    
                                    <a href="submit_assignment.php?id=<?= $assignment['assignment_id'] ?>" 
                                       class="btn btn-primary btn-sm">
                                        <?= ($assignment['is_submitted'] ?? 0) ? 'Resubmit' : 'Submit' ?>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-right">
                                    <a href="view_submissions.php?id=<?= $assignment['assignment_id'] ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View Submissions
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>