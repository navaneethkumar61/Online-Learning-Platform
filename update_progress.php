<?php
session_start();

// ---------- DB Connection ----------
$host = "localhost";
$user = "root";
$pass = "";
$db = "onlinelearning";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ---------- Get student and course ----------
$student_id = (int)$_SESSION['id'];
$course_id = (int)$_POST['course_id'];

// ---------- Total Materials ----------
$total_materials = $conn->query("
    SELECT COUNT(*) AS total 
    FROM materials 
    WHERE course_id = $course_id
")->fetch_assoc()['total'];

// ---------- Completed Materials ----------
$completed_materials = $conn->query("
    SELECT COUNT(*) AS completed
    FROM student_material_completion smc
    JOIN materials m ON m.id = smc.material_id
    WHERE smc.student_id = $student_id
      AND smc.completed = 1
      AND m.course_id = $course_id
")->fetch_assoc()['completed'];

// ---------- Total Quizzes ----------
$total_quizzes = $conn->query("
    SELECT COUNT(*) AS total
    FROM quizzes
    WHERE course_id = $course_id
")->fetch_assoc()['total'];

// ---------- Completed Quizzes ----------
$completed_quizzes = $conn->query("
    SELECT COUNT(*) AS completed
    FROM quiz_results qr
    JOIN quizzes q ON q.id = qr.quiz_id
    WHERE qr.student_id = $student_id
      AND qr.status = 'passed'
      AND q.course_id = $course_id
")->fetch_assoc()['completed'];

// ---------- Calculate Totals and Progress ----------
$total_items = $total_materials + $total_quizzes;
$completed_total = $completed_materials + $completed_quizzes;
$progress_percent = $total_items > 0 ? ($completed_total / $total_items) * 100 : 0;

// ---------- Insert or Update Progress ----------
$stmt = $conn->prepare("
    INSERT INTO course_progress (student_id, course_id, completed_items, total_items, progress_percent)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE completed_items=?, total_items=?, progress_percent=?
");
$stmt->bind_param(
    "iiiiiii",
    $student_id, $course_id, $completed_total, $total_items, $progress_percent,
    $completed_total, $total_items, $progress_percent
);
$stmt->execute();
$stmt->close();

// ---------- Return Progress ----------
echo json_encode(['progress' => round($progress_percent)]);
?>
