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
<style>
    .wrapper { width: 1000px; margin: 20px auto; }
    .discussion-header { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
    .reply { border-left: 3px solid #007bff; padding-left: 15px; margin-bottom: 15px; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="#">LMS</a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
            <?php if ($role === 'instructor'): ?>
            <li class="nav-item"><a class="nav-link" href="create_course.php">Create Course</a></li>
            <?php endif; ?>
            <li class="nav-item active"><a class="nav-link" href="discussions.php">Discussions</a></li>
        </ul>
        <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li></ul>
    </div>
</nav>

<div class="wrapper">
    <div class="discussion-header">
        <h2><?php echo htmlspecialchars($discussion['title']); ?></h2>
        <p><?php echo nl2br(htmlspecialchars($discussion['content'])); ?></p>
        <small>
            By <?php echo htmlspecialchars($discussion['author_name']); ?>
            <a href="course.php?id=<?php echo $discussion['course_id']; ?>"><?php echo htmlspecialchars($discussion['course_title']); ?></a>
            on <?php echo date('M d, Y H:i', strtotime($discussion['created_at'])); ?>
        </small>
        <!-- Message author button -->
        <form action="send_message.php" method="get" class="mt-2">
            <input type="hidden" name="to" value="<?php echo $discussion['author_id']; ?>">
            <button type="submit" class="btn btn-link">Message Author</button>
        </form>
    </div>

    <h4>Replies</h4>
    <?php if (empty($replies)): ?>
        <p>No replies yet.</p>
    <?php else: ?>
        <?php foreach ($replies as $r): ?>
        <div class="reply">
            <p><?php echo nl2br(htmlspecialchars($r['reply'])); ?></p>
            <small>
                By <?php echo htmlspecialchars($r['author_name']); ?>
                on <?php echo date('M d, Y H:i', strtotime($r['created_at'])); ?>
            </small>
            <!-- Message replier button -->
            <form action="send_message.php" method="get" style="display:inline;">
                <input type="hidden" name="to" value="<?php echo $r['user_id']; ?>">
                <button type="submit" class="btn btn-sm btn-link">Message User</button>
            </form>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="add-reply mt-4">
        <h4>Add Reply</h4>
        <form method="post">
            <div class="form-group">
                <textarea name="reply" class="form-control <?php echo (!empty($reply_err)) ? 'is-invalid' : ''; ?>" rows="3"><?php echo htmlspecialchars($reply_text); ?></textarea>
                <span class="invalid-feedback"><?php echo $reply_err; ?></span>
            </div>
            <button type="submit" class="btn btn-primary">Post Reply</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
