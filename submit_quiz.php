<?php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "onlinelearning";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed.");
}

// Check student login
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    die("Access denied.");
}

// Get student ID
$student_id = $conn->query("SELECT id FROM users WHERE username='" . $conn->real_escape_string($_SESSION['username']) . "' AND role='student' LIMIT 1")->fetch_assoc()['id'] ?? 0;
if (!$student_id) die("Student not found.");

// Get quiz ID
$quiz_id = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
if ($quiz_id <= 0) die('<p class="message danger">Invalid quiz ID.</p>');

// Get quiz questions
$questions = $conn->query("SELECT * FROM quiz_questions WHERE quiz_id=$quiz_id");
if (!$questions || $questions->num_rows == 0) die("No questions found.");

// Calculate score
$total = 0;
$score = 0;
while ($q = $questions->fetch_assoc()) {
    $total++;
    $answer = $_POST['question_' . $q['id']] ?? 0;
    if ($answer == $q['answer']) $score++;
}

// Determine status
$percentage = round(($score / $total) * 100);
$status = $percentage >= 50 ? 'passed' : 'failed';

// Save result
$conn->query("INSERT INTO quiz_results (student_id, quiz_id, score, status) 
              VALUES ($student_id, $quiz_id, $percentage, '$status')");

echo "Quiz submitted successfully! Score: $percentage% ($status)";
?>
