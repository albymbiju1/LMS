<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Validate discussion ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: discussions.php');
    exit;
}
$discussion_id = (int)$_GET['id'];

// Session values
$user_id = $_SESSION['id'];
$role    = $_SESSION['role'];

// Fetch discussion with author_id
$sql = "SELECT d.*, d.author_id, u.username AS author_name, c.title AS course_title
        FROM discussions d
        JOIN users u ON d.author_id = u.user_id
        JOIN courses c ON d.course_id = c.course_id
        WHERE d.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $discussion_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$discussion = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
if (!$discussion) {
    header('Location: discussions.php');
    exit;
}

// Access control omitted for brevity...

// Fetch replies with user_id
$sql = "SELECT r.*, r.user_id, u.username AS author_name
        FROM discussion_replies r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.discussion_id = ?
        ORDER BY r.created_at ASC";
$replies = [];
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $discussion_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $replies[] = $row;
}
mysqli_stmt_close($stmt);

// Handle new reply
$reply_text = '';
$reply_err  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty(trim($_POST['reply']))) {
        $reply_err = 'Please enter your reply.';
    } else {
        $reply_text = trim($_POST['reply']);
    }
    if (empty($reply_err)) {
        $sql = "INSERT INTO discussion_replies (discussion_id, user_id, reply, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iis', $discussion_id, $user_id, $reply_text);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: view_discussion.php?id={$discussion_id}");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Discussion - LMS</title>
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

        .discussion-header {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin: 2rem 0;
        }

        .reply-card {
            background: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .reply-card:hover {
            transform: translateY(-3px);
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

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(26, 187, 156, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-color);
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
                    <?php if ($role === 'instructor'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="create_course.php">
                            <i class="fas fa-plus-circle"></i>Create Course
                        </a>
                    </li>
                    <?php endif; ?>
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

    <div class="container" style="max-width: 1200px; margin-top: 2rem;">
        <div class="discussion-header">
            <h2><?php echo htmlspecialchars($discussion['title']); ?></h2>
            <div class="d-flex align-items-center gap-3 my-3">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h5 class="mb-0"><?php echo htmlspecialchars($discussion['author_name']); ?></h5>
                    <small class="text-muted">
                        <i class="fas fa-clock"></i>
                        <?php echo date('M d, Y H:i', strtotime($discussion['created_at'])); ?>
                    </small>
                </div>
            </div>
            <p class="lead"><?php echo nl2br(htmlspecialchars($discussion['content'])); ?></p>
            <div class="d-flex align-items-center gap-2">
                <a href="course.php?id=<?php echo $discussion['course_id']; ?>" 
                   class="badge badge-primary" 
                   style="background: var(--secondary-color);">
                    <?php echo htmlspecialchars($discussion['course_title']); ?>
                </a>
                <form action="send_message.php" method="get" class="ml-2">
                    <input type="hidden" name="to" value="<?php echo $discussion['author_id']; ?>">
                    <button type="submit" class="btn btn-link" style="color: var(--primary-color);">
                        <i class="fas fa-envelope"></i> Message Author
                    </button>
                </form>
            </div>
        </div>

        <h4 class="mb-4" style="color: var(--primary-color);">Replies</h4>
        
        <?php if (empty($replies)): ?>
            <div class="alert alert-info">No replies yet. Be the first to respond!</div>
        <?php else: ?>
            <?php foreach ($replies as $r): ?>
            <div class="reply-card">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0"><?php echo htmlspecialchars($r['author_name']); ?></h6>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('M d, Y H:i', strtotime($r['created_at'])); ?>
                                </small>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($r['reply'])); ?></p>
                            <form action="send_message.php" method="get" class="mt-2">
                                <input type="hidden" name="to" value="<?php echo $r['user_id']; ?>">
                                <button type="submit" class="btn btn-link btn-sm" style="color: var(--primary-color);">
                                    <i class="fas fa-envelope"></i> Message User
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="card mt-5">
            <div class="card-header" style="background: var(--primary-color); color: white;">
                <h5 class="mb-0">Add Reply</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <textarea name="reply" 
                                  class="form-control <?php echo (!empty($reply_err)) ? 'is-invalid' : ''; ?>" 
                                  rows="4"
                                  placeholder="Write your reply..."><?php echo htmlspecialchars($reply_text); ?></textarea>
                        <span class="invalid-feedback"><?php echo $reply_err; ?></span>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i> Post Reply
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>