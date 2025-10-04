<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    echo '<p class="message danger">Access denied. Please log in as a student.</p>';
    exit;
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "onlinelearning";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo '<p class="message danger">Database connection failed.</p>';
    exit;
}

// Get quiz ID
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
if ($quiz_id <= 0) {
    echo '<p class="message danger">Invalid quiz ID.</p>';
    exit;
}

// Get student ID
$student_id = $conn->query("SELECT id FROM users WHERE username='" . $conn->real_escape_string($_SESSION['username']) . "' AND role='student' LIMIT 1")->fetch_assoc()['id'] ?? 0;
if (!$student_id) {
    echo '<p class="message danger">Student not found.</p>';
    exit;
}

// Get quiz info
$quiz_result = $conn->query("SELECT q.*, c.course_name 
                             FROM quizzes q 
                             JOIN courses c ON c.id = q.course_id 
                             WHERE q.id = $quiz_id");
if ($quiz_result->num_rows == 0) {
    echo '<p class="message danger">Quiz not found.</p>';
    exit;
}
$quiz = $quiz_result->fetch_assoc();

// Check if student is enrolled
$enrollment_check = $conn->query("SELECT * FROM enrollments WHERE student_id=$student_id AND course_id={$quiz['course_id']}");
if ($enrollment_check->num_rows == 0) {
    echo '<p class="message danger">You are not enrolled in this course.</p>';
    exit;
}

// BACK BUTTON
echo '<div style="margin-bottom:20px;">';
echo '<button onclick="goBack()" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to Quizzes</button>';
echo '</div>';

echo '<h4 class="page-title">' . htmlspecialchars($quiz['quiz_name']) . '</h4>';
echo '<p><strong>Course:</strong> ' . htmlspecialchars($quiz['course_name']) . '</p>';

// Get questions
$questions = $conn->query("SELECT * FROM quiz_questions WHERE quiz_id=$quiz_id ORDER BY id");

if ($questions && $questions->num_rows > 0) {
    echo '<form id="quiz_form">';
    echo '<input type="hidden" name="quiz_id" value="' . $quiz_id . '">';

    $question_num = 1;
    while ($question = $questions->fetch_assoc()) {
        echo '<div class="course-card">';
        echo '<h4>Question ' . $question_num . '</h4>';
        echo '<p>' . htmlspecialchars($question['question']) . '</p>';
        echo '<div class="options-container">';
        
        for ($i = 1; $i <= 4; $i++) {
            $option = $question['option' . $i];
            if (!empty($option)) {
                echo '<label class="option-label">';
                echo '<input type="radio" name="question_' . $question['id'] . '" value="' . $i . '" required>';
                echo htmlspecialchars($option);
                echo '</label>';
            }
        }

        echo '</div></div>';
        $question_num++;
    }

    echo '<div class="submit-container">';
    echo '<button type="button" onclick="submitQuiz()" class="btn">Submit Quiz</button>';
    echo '</div>';
    echo '</form>';
} else {
    echo '<p class="message">No questions available for this quiz.</p>';
}

$conn->close();
?>

<!-- Include Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- CSS -->
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f9f9f9;
    margin: 0;
    padding: 20px;
}
.page-title {
    color: #333;
    margin-bottom: 20px;
}
.course-card {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.course-card h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #222;
}
.options-container {
    margin-top: 10px;
}
.option-label {
    display: block;
    margin: 8px 0;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.option-label input[type="radio"] {
    margin-right: 10px;
}
.option-label:hover {
    background: #f0f0f0;
    border-color: #ccc;
}
.submit-container {
    text-align: center;
    margin-top: 30px;
}
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    background: #ff4081;
    color: #fff;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.2s ease;
}
.btn:hover {
    background: #e73370;
}
.btn-back {
    background: #555;
    font-size: 0.9rem;
}
.btn-back i {
    margin-right: 8px;
}
.btn-back:hover {
    background: #333;
}
.message {
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.9rem;
}
.message.success {
    background: #d4edda;
    color: #155724;
}
.message.warn {
    background: #fff3cd;
    color: #856404;
}
.message.danger {
    background: #f8d7da;
    color: #721c24;
}
</style>

<!-- JS -->
<script>
function submitQuiz() {
    const form = document.getElementById('quiz_form');
    const formData = new FormData(form);

    fetch('submit_quiz.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert(data);
        window.location.href = 'student_dashboard.php';
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to submit quiz.');
    });
}

// Back button
function goBack() {
    window.history.back();
}
</script>
