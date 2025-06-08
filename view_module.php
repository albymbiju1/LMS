<?php
session_start();

// 1) Ensure user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

// 2) Validate module_id parameter; redirect to dashboard if missing/invalid
if (empty($_GET['module_id']) || !ctype_digit($_GET['module_id'])) {
    header("Location: index.php");
    exit;
}
$module_id = intval($_GET['module_id']);

// 3) Fetch module info (including course_id if you later want it)
$sqlModule = "
    SELECT m.course_id,
           m.title       AS module_title,
           m.description
    FROM modules m
    WHERE m.module_id = ?
";
if (!($stmt = mysqli_prepare($conn, $sqlModule))) {
    header("Location: index.php");
    exit;
}
mysqli_stmt_bind_param($stmt, "i", $module_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$module = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$module) {
    // Module not found
    header("Location: index.php");
    exit;
}

// 4) Fetch its content items
$sqlContent = "
    SELECT 
        CASE 
            WHEN m.file_path IS NOT NULL THEN 'document'
            ELSE ci.content_type 
        END as content_type,
        CASE 
            WHEN m.file_path IS NOT NULL THEN m.title
            ELSE ci.title 
        END as title,
        CASE 
            WHEN m.file_path IS NOT NULL THEN m.file_path
            ELSE ci.file_path 
        END as file_path
    FROM modules m
    LEFT JOIN lessons l ON m.module_id = l.module_id
    LEFT JOIN content_items ci ON l.lesson_id = ci.lesson_id
    WHERE m.module_id = ?
    AND (
        m.file_path IS NOT NULL 
        OR ci.content_id IS NOT NULL
    )
    ORDER BY ci.created_at
";
if (!($stmt = mysqli_prepare($conn, $sqlContent))) {
    header("Location: index.php");
    exit;
}
mysqli_stmt_bind_param($stmt, "i", $module_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$items = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Module - LMS</title>
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

        .module-header {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin: 2rem 0;
        }

        .content-card {
            background: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .content-card:hover {
            transform: translateY(-3px);
        }

        .content-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(26, 187, 156, 0.1);
            color: var(--secondary-color);
            font-size: 1.2rem;
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

        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
            border-radius: var(--border-radius);
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
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
                            <i class="fas fa-arrow-left"></i>Dashboard
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
        <div class="module-header">
            <h2><?= htmlspecialchars($module['module_title']) ?></h2>
            <p class="lead text-muted"><?= nl2br(htmlspecialchars($module['description'])) ?></p>
        </div>

        <?php if (empty($items)): ?>
            <div class="alert alert-info">No content available for this module.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($items as $it): ?>
                <div class="col-md-6">
                    <div class="content-card">
                        <div class="card-body d-flex align-items-start gap-3">
                            <div class="content-icon">
                                <?php switch($it['content_type']):
                                    case 'video': ?>
                                        <i class="fas fa-video"></i>
                                        <?php break; ?>
                                    <?php case 'document': ?>
                                        <i class="fas fa-file-pdf"></i>
                                        <?php break; ?>
                                    <?php case 'link': ?>
                                        <i class="fas fa-link"></i>
                                        <?php break; ?>
                                    <?php default: ?>
                                        <i class="fas fa-align-left"></i>
                                <?php endswitch; ?>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-2"><?= htmlspecialchars($it['title']) ?></h5>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (in_array($it['content_type'], ['video', 'document', 'link'])): ?>
                                        <a href="<?= htmlspecialchars($it['file_path']) ?>" 
                                           target="_blank"
                                           class="btn btn-outline-primary btn-sm">
                                            <?= match($it['content_type']) {
                                                'video' => 'Watch Video',
                                                'document' => 'View Document',
                                                'link' => 'Visit Link',
                                                default => 'View Content'
                                            } ?>
                                        </a>
                                    <?php else: ?>
                                        <div class="text-muted small">
                                            <?= nl2br(htmlspecialchars($it['file_path'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>