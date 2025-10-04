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

// Get course ID
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($course_id <= 0) {
    echo '<p class="message danger">Invalid course ID.</p>';
    exit;
}

// Get student ID
$student_id = null;
$resolveStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND role = 'student' LIMIT 1");
if ($resolveStmt) {
    $resolveStmt->bind_param("s", $_SESSION['username']);
    $resolveStmt->execute();
    $result = $resolveStmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $student_id = (int)$row['id'];
    }
    $resolveStmt->close();
}
if (!$student_id) {
    echo '<p class="message danger">Student not found.</p>';
    exit;
}

// Check enrollment
$enrollment_check = $conn->query("SELECT * FROM enrollments WHERE student_id = $student_id AND course_id = $course_id");
if ($enrollment_check->num_rows == 0) {
    echo '<p class="message danger">You are not enrolled in this course.</p>';
    exit;
}

// Get course name
$course_result = $conn->query("SELECT course_name FROM courses WHERE id = $course_id");
$course_name = $course_result->fetch_assoc()['course_name'];

// BACK BUTTON
echo '<div style="margin-bottom:20px;">';
echo '<button onclick="goBack()" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to Courses</button>';
echo '</div>';

echo '<h4 class="page-title">Quizzes for: ' . htmlspecialchars($course_name) . '</h4>';

// Get quizzes
$quizzes = $conn->query("SELECT * FROM quizzes WHERE course_id = $course_id ORDER BY id DESC");

if ($quizzes && $quizzes->num_rows > 0) {
    echo '<div class="course-grid">';
    while ($quiz = $quizzes->fetch_assoc()) {
        echo '<div class="course-card">';
        echo '<h4>' . htmlspecialchars($quiz['quiz_name']) . '</h4>';
        
        // Question count
        $question_count = $conn->query("SELECT COUNT(*) as count FROM quiz_questions WHERE quiz_id = {$quiz['id']}")->fetch_assoc()['count'];
        
        // Last attempt
        $quiz_result = $conn->query("SELECT score, status FROM quiz_results WHERE student_id = $student_id AND quiz_id = {$quiz['id']} ORDER BY id DESC LIMIT 1");

        echo '<p><strong>Questions:</strong> ' . $question_count . '</p>';
        echo '<div class="course-actions">';
        if ($quiz_result->num_rows > 0) {
            $result = $quiz_result->fetch_assoc();
            echo '<span class="message ' . ($result['status'] == 'passed' ? 'success' : 'warn') . '">';
            echo 'Score: ' . $result['score'] . '% (' . ucfirst($result['status']) . ')</span>';
            echo '<button onclick="retakeQuiz(' . $quiz['id'] . ')" class="btn btn-small">Retake Quiz</button>';
        } else {
            echo '<button onclick="startQuiz(' . $quiz['id'] . ')" class="btn btn-small">Start Quiz</button>';
        }
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<p class="message">No quizzes available for this course yet.</p>';
}

$conn->close();
?>

<!-- Include Font Awesome -->
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
.course-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.course-card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    flex: 1 1 250px;
    transition: transform 0.2s ease;
}
.course-card:hover {
    transform: translateY(-5px);
}
.course-card h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #222;
}
.course-actions {
    margin-top: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.btn {
    padding: 8px 15px;
    border: none;
    border-radius: 6px;
    background: #ff4081;
    color: #fff;
    cursor: pointer;
    transition: background 0.2s ease;
}
.btn:hover {
    background: #e73370;
}
.btn-small {
    font-size: 0.9rem;
    padding: 6px 12px;
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
function startQuiz(quizId) {
    window.location.href = 'get_quiz_questions.php?quiz_id=' + quizId;
}

function retakeQuiz(quizId) {
    if (confirm('Are you sure you want to retake this quiz?')) {
        window.location.href = 'get_quiz_questions.php?quiz_id=' + quizId + '&retake=1';
    }
}

// Back button
function goBack() {
    window.history.back(); // Or redirect to courses page if needed
}
</script>
