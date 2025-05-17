<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";

// Create connection without database
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS team_lms";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("team_lms");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'instructor', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully or already exists<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create courses table
$sql = "CREATE TABLE IF NOT EXISTS courses (
    course_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    instructor_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(user_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Courses table created successfully or already exists<br>";
} else {
    echo "Error creating courses table: " . $conn->error . "<br>";
}

// Create enrollments table
$sql = "CREATE TABLE IF NOT EXISTS enrollments (
    enrollment_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Enrollments table created successfully or already exists<br>";
} else {
    echo "Error creating enrollments table: " . $conn->error . "<br>";
}

// Create assignments table
$sql = "CREATE TABLE IF NOT EXISTS assignments (
    assignment_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    course_id INT NOT NULL,
    due_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Assignments table created successfully or already exists<br>";
} else {
    echo "Error creating assignments table: " . $conn->error . "<br>";
}

// Create quizzes table
$sql = "CREATE TABLE IF NOT EXISTS quizzes (
    quiz_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    course_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Quizzes table created successfully or already exists<br>";
} else {
    echo "Error creating quizzes table: " . $conn->error . "<br>";
}

// Create quiz_questions table
$sql = "CREATE TABLE IF NOT EXISTS quiz_questions (
    question_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT NOT NULL,
    question TEXT NOT NULL,
    option1 TEXT NOT NULL,
    option2 TEXT NOT NULL,
    option3 TEXT NOT NULL,
    option4 TEXT NOT NULL,
    correct_answer CHAR(1) NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Quiz questions table created successfully or already exists<br>";
} else {
    echo "Error creating quiz questions table: " . $conn->error . "<br>";
}

// Create resources table
$sql = "CREATE TABLE IF NOT EXISTS resources (
    resource_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    course_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Resources table created successfully or already exists<br>";
} else {
    echo "Error creating resources table: " . $conn->error . "<br>";
}

// Create discussions table
$sql = "CREATE TABLE IF NOT EXISTS discussions (
    discussion_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Discussions table created successfully or already exists<br>";
} else {
    echo "Error creating discussions table: " . $conn->error . "<br>";
}

// Create discussion_replies table
$sql = "CREATE TABLE IF NOT EXISTS discussion_replies (
    reply_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    discussion_id INT NOT NULL,
    user_id INT NOT NULL,
    reply TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (discussion_id) REFERENCES discussions(discussion_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Discussion replies table created successfully or already exists<br>";
} else {
    echo "Error creating discussion replies table: " . $conn->error . "<br>";
}

// Create uploads directory if it doesn't exist
$upload_dir = "../uploads";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    echo "Uploads directory created successfully<br>";
}

$conn->close();
echo "Database setup completed!";
?> 