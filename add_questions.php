<?php
session_start();

// Check if the user is logged in and is an instructor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "instructor"){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Check if quiz ID is provided
if(!isset($_GET["quiz_id"])){
    header("location: index.php");
    exit;
}

$quiz_id = $_GET["quiz_id"];
$question = $option1 = $option2 = $option3 = $option4 = $correct_answer = "";
$question_err = $option1_err = $option2_err = $option3_err = $option4_err = $correct_answer_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate question
    if(empty(trim($_POST["question"]))){
        $question_err = "Please enter a question.";
    } else{
        $question = trim($_POST["question"]);
    }
    
    // Validate options
    if(empty(trim($_POST["option1"]))){
        $option1_err = "Please enter option 1.";
    } else{
        $option1 = trim($_POST["option1"]);
    }
    
    if(empty(trim($_POST["option2"]))){
        $option2_err = "Please enter option 2.";
    } else{
        $option2 = trim($_POST["option2"]);
    }
    
    if(empty(trim($_POST["option3"]))){
        $option3_err = "Please enter option 3.";
    } else{
        $option3 = trim($_POST["option3"]);
    }
    
    if(empty(trim($_POST["option4"]))){
        $option4_err = "Please enter option 4.";
    } else{
        $option4 = trim($_POST["option4"]);
    }
    
    // Validate correct answer
    if(empty(trim($_POST["correct_answer"]))){
        $correct_answer_err = "Please select the correct answer.";
    } else{
        $correct_answer = trim($_POST["correct_answer"]);
        if(!in_array($correct_answer, ['1', '2', '3', '4'])){
            $correct_answer_err = "Please select a valid option.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($question_err) && empty($option1_err) && empty($option2_err) && empty($option3_err) && empty($option4_err) && empty($correct_answer_err)){
        $sql = "INSERT INTO quiz_questions (question_text, option_a, option_b, option_c, option_d, correct_option, quiz_id) VALUES (?, ?, ?, ?, ?, ?, ?)";

        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssssi", $param_question, $param_option1, $param_option2, $param_option3, $param_option4, $param_correct_answer, $param_quiz_id);
            
            $param_question = $question;
            $param_option1 = $option1;
            $param_option2 = $option2;
            $param_option3 = $option3;
            $param_option4 = $option4;
            $param_correct_answer = $correct_answer;
            $param_quiz_id = $quiz_id;
            
            if(mysqli_stmt_execute($stmt)){
                // Clear form data after successful insertion
                $question = $option1 = $option2 = $option3 = $option4 = $correct_answer = "";
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get existing questions for this quiz
$questions = [];
$sql = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_id ASC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $quiz_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $questions[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Questions - LMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 800px; padding: 20px; margin: 0 auto; margin-top: 50px; }
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
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="create_course.php">Create Course</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="wrapper">
        <h2>Add Questions</h2>
        <p>Please fill this form to add questions to the quiz.</p>
        
        <!-- Display existing questions -->
        <?php if(!empty($questions)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Existing Questions</h5>
            </div>
            <div class="card-body">
                <?php foreach($questions as $index => $q): ?>
                <div class="mb-3">
                    <h6>Question <?php echo $index + 1; ?></h6>
                    <p><?php echo htmlspecialchars($q["question_text"]); ?></p>

                    <div class="ml-3">
                        <p class="mb-1">1. <?php echo htmlspecialchars($q["option_a"]); ?></p>
                        <p class="mb-1">2. <?php echo htmlspecialchars($q["option_b"]); ?></p>
                        <p class="mb-1">3. <?php echo htmlspecialchars($q["option_c"]); ?></p>
                        <p class="mb-1">4. <?php echo htmlspecialchars($q["option_d"]); ?></p>
                        <p class="mb-1"><strong>Correct Answer: Option <?php echo $q["correct_option"]; ?></strong></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Add new question form -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?quiz_id=" . $quiz_id); ?>" method="post">
            <div class="form-group">
                <label>Question</label>
                <textarea name="question" class="form-control <?php echo (!empty($question_err)) ? 'is-invalid' : ''; ?>"><?php echo $question; ?></textarea>
                <span class="invalid-feedback"><?php echo $question_err; ?></span>
            </div>
            <div class="form-group">
                <label>Option 1</label>
                <input type="text" name="option1" class="form-control <?php echo (!empty($option1_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $option1; ?>">
                <span class="invalid-feedback"><?php echo $option1_err; ?></span>
            </div>
            <div class="form-group">
                <label>Option 2</label>
                <input type="text" name="option2" class="form-control <?php echo (!empty($option2_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $option2; ?>">
                <span class="invalid-feedback"><?php echo $option2_err; ?></span>
            </div>
            <div class="form-group">
                <label>Option 3</label>
                <input type="text" name="option3" class="form-control <?php echo (!empty($option3_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $option3; ?>">
                <span class="invalid-feedback"><?php echo $option3_err; ?></span>
            </div>
            <div class="form-group">
                <label>Option 4</label>
                <input type="text" name="option4" class="form-control <?php echo (!empty($option4_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $option4; ?>">
                <span class="invalid-feedback"><?php echo $option4_err; ?></span>
            </div>
            <div class="form-group">
                <label>Correct Answer</label>
                <select name="correct_answer" class="form-control <?php echo (!empty($correct_answer_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Select Correct Answer</option>
                    <option value="1">Option 1</option>
                    <option value="2">Option 2</option>
                    <option value="3">Option 3</option>
                    <option value="4">Option 4</option>
                </select>
                <span class="invalid-feedback"><?php echo $correct_answer_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Add Question">
                <a href="course.php?id=<?php echo $quiz_id; ?>" class="btn btn-secondary ml-2">Finish</a>
            </div>
        </form>
    </div>    
</body>
</html> 