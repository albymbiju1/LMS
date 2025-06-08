<?php
// Enable error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "config/database.php";

$email = '';
$email_err = '';
$success_msg = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = "Please enter your email address.";
    } else {
        $email = trim($_POST['email']);
        
        // Check if email exists in database
        $sql = "SELECT user_id, email FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) === 1) {
                    // Email exists, generate reset token
                    $token = bin2hex(random_bytes(32)); // Generate secure random token
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
                    
                    // Update user's reset token
                    $update_sql = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?";
                    if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($update_stmt, "sss", $token, $expires, $email);
                        
                        if (mysqli_stmt_execute($update_stmt)) {
                            // In a real application, you would send an email here with the reset link
                            // For now, we'll just show a success message
                            $success_msg = "If an account exists with that email, you will receive password reset instructions.";
                        }
                        mysqli_stmt_close($update_stmt);
                    }
                } else {
                    // Don't reveal if email exists or not for security
                    $success_msg = "If an account exists with that email, you will receive password reset instructions.";
                }
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
    <title>Forgot Password - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2A3F54;
            --secondary-color: #1ABB9C;
            --accent-color: #337AB7;
            --border-radius: 8px;
            --box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wrapper {
            width: 400px;
            padding: 40px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .wrapper:hover {
            transform: translateY(-5px);
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .forgot-header i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .forgot-header h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }

        .forgot-header p {
            color: #6c757d;
            margin-bottom: 0;
        }

        .form-control {
            height: 45px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            padding-left: 40px;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(26, 187, 156, 0.15);
        }

        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-group label {
            position: absolute;
            top: -10px;
            left: 15px;
            background: white;
            padding: 0 5px;
            color: var(--primary-color);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            height: 45px;
            border-radius: var(--border-radius);
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 63, 84, 0.2);
        }

        .invalid-feedback {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 12px 15px;
        }

        .additional-links {
            margin-top: 1.5rem;
            text-align: center;
        }

        .additional-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .additional-links a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="forgot-header">
            <i class="fas fa-key"></i>
            <h2>Forgot Password</h2>
            <p>Enter your email to reset password</p>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                    value="<?php echo htmlspecialchars($email); ?>"
                    placeholder="Enter your email address"
                >
                <span class="invalid-feedback"><?php echo $email_err; ?></span>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
            </div>

            <div class="additional-links">
                <a href="login.php">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html> 