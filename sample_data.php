<?php
require_once "config/database.php";

// Sample assignments
$assignments = [
    [
        'course_id' => 1, // Make sure this course_id exists in your courses table
        'title' => 'Introduction to PHP',
        'description' => 'Create a simple PHP script that demonstrates basic syntax and control structures.',
        'due_date' => date('Y-m-d H:i:s', strtotime('+1 week'))
    ],
    [
        'course_id' => 1,
        'title' => 'Database Design',
        'description' => 'Design a database schema for a simple e-commerce website.',
        'due_date' => date('Y-m-d H:i:s', strtotime('+2 weeks'))
    ]
];

foreach ($assignments as $assignment) {
    $sql = "INSERT INTO assignments (course_id, title, description, due_date) 
            VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isss", 
        $assignment['course_id'],
        $assignment['title'],
        $assignment['description'],
        $assignment['due_date']
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Sample quizzes
$quizzes = [
    [
        'course_id' => 1,
        'title' => 'PHP Basics Quiz',
        'description' => 'Test your knowledge of PHP fundamentals',
        'time_limit' => 30,
        'passing_score' => 70
    ],
    [
        'course_id' => 1,
        'title' => 'MySQL Quiz',
        'description' => 'Test your knowledge of MySQL database',
        'time_limit' => 45,
        'passing_score' => 75
    ]
];

foreach ($quizzes as $quiz) {
    $sql = "INSERT INTO quizzes (course_id, title, description, time_limit, passing_score) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issii", 
        $quiz['course_id'],
        $quiz['title'],
        $quiz['description'],
        $quiz['time_limit'],
        $quiz['passing_score']
    );
    mysqli_stmt_execute($stmt);
    $quiz_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Add questions for each quiz
    if ($quiz_id) {
        $questions = [
            [
                'quiz_id' => $quiz_id,
                'question_text' => 'What does PHP stand for?',
                'question_type' => 'multiple_choice',
                'points' => 2
            ],
            [
                'quiz_id' => $quiz_id,
                'question_text' => 'Is PHP a server-side scripting language?',
                'question_type' => 'true_false',
                'points' => 1
            ]
        ];

        foreach ($questions as $question) {
            $sql = "INSERT INTO quiz_questions (quiz_id, question_text, question_type, points) 
                    VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "issi", 
                $question['quiz_id'],
                $question['question_text'],
                $question['question_type'],
                $question['points']
            );
            mysqli_stmt_execute($stmt);
            $question_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Add options for multiple choice questions
            if ($question['question_type'] === 'multiple_choice' && $question_id) {
                $options = [
                    ['option_text' => 'Personal Home Page', 'is_correct' => true],
                    ['option_text' => 'PHP Hypertext Processor', 'is_correct' => false],
                    ['option_text' => 'Preprocessed Hypertext Page', 'is_correct' => false]
                ];

                foreach ($options as $option) {
                    $sql = "INSERT INTO quiz_options (question_id, option_text, is_correct) 
                            VALUES (?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "isi", 
                        $question_id,
                        $option['option_text'],
                        $option['is_correct']
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

echo "Sample data inserted successfully!";
mysqli_close($conn);
?> 