<?php
session_start();

// ---------- Check if student ----------
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    echo '<p class="message danger">Access denied. Please log in as a student.</p>';
    exit;
}

// ---------- DB Connection ----------
$host = "localhost";
$user = "root";
$pass = "";
$db = "onlinelearning";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("DB connection failed: " . $conn->connect_error);

// ---------- Get course_id ----------
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($course_id <= 0) { echo "Invalid course ID"; exit; }

// ---------- Get student ID ----------
$stmt = $conn->prepare("SELECT id FROM users WHERE username=? AND role='student' LIMIT 1");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $student_id = $row['id'];
} else {
    echo "Student not found"; exit;
}
$stmt->close();

// ---------- Check enrollment ----------
$stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id=? AND course_id=? LIMIT 1");
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows == 0) {
    echo "You are not enrolled in this course."; exit;
}
$stmt->close();

// ---------- Get course name ----------
$stmt = $conn->prepare("SELECT course_name FROM courses WHERE id=? LIMIT 1");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$res = $stmt->get_result();
$course_name = $res->fetch_assoc()['course_name'];
$stmt->close();

// ---------- Get all materials ----------
$stmt = $conn->prepare("SELECT id, title, file_path FROM materials WHERE course_id=? ORDER BY id ASC");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$materials_result = $stmt->get_result();
$materials = [];
while ($row = $materials_result->fetch_assoc()) {
    $materials[] = $row;
}
$stmt->close();

// ---------- Function: Check if material is accessible ----------
function is_material_accessible($conn, $student_id, $course_id, $materials, $index) {
    // First material is always accessible
    if ($index === 0) return true;

    $prev_id = $materials[$index - 1]['id'];

    // 1️⃣ Check previous material completion
    $prev_completed_res = $conn->query("
        SELECT completed 
        FROM student_material_completion 
        WHERE student_id=$student_id 
          AND course_id=$course_id 
          AND material_id=$prev_id
    ")->fetch_assoc();

    if (!$prev_completed_res || !$prev_completed_res['completed']) {
        return false;
    }

    // 2️⃣ Check if all course quizzes are passed
    $quiz_res = $conn->query("
        SELECT id
        FROM quizzes
        WHERE course_id=$course_id
    ");
    while ($quiz = $quiz_res->fetch_assoc()) {
        $quiz_id = $quiz['id'];
        $passed_res = $conn->query("
            SELECT status 
            FROM quiz_results 
            WHERE student_id=$student_id AND quiz_id=$quiz_id
        ")->fetch_assoc();
        if (!$passed_res || $passed_res['status'] !== 'passed') {
            return false; // Quiz not passed → lock material
        }
    }

    return true; // Previous material completed & all quizzes passed
}


// ---------- Handle material view & mark as completed ----------
if (isset($_GET['material_id'])) {
    $material_id = (int)$_GET['material_id'];

    // Find index of this material
    $index = array_search($material_id, array_column($materials, 'id'));

    if ($index === false) {
        echo "Material not found"; exit;
    }

    // Check if accessible
    if (!is_material_accessible($conn, $student_id, $course_id, $materials, $index)) {
        echo '<p class="message warn">You must complete the previous material and pass all related quizzes before accessing this one.</p>';
        exit;
    }

    // Mark current material as completed
    $conn->query("
        INSERT INTO student_material_completion (student_id, course_id, material_id, completed, completed_at)
        VALUES ($student_id, $course_id, $material_id, 1, NOW())
        ON DUPLICATE KEY UPDATE completed=1, completed_at=NOW()
    ");

    // Redirect to actual file
    $file_path = $conn->query("SELECT file_path FROM materials WHERE id=$material_id")->fetch_assoc()['file_path'];
    $file_url = str_replace('C:\\xampp\\htdocs\\onlinelearning\\', '', $file_path);
    $file_url = str_replace('\\','/',$file_url);
    header("Location: $file_url");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Materials - <?= htmlspecialchars($course_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f9f9f9; color: #333; margin: 0; padding: 20px; }
        h2 { margin-bottom: 20px; color: #222; }
        .btn-back { display: inline-flex; align-items: center; padding: 8px 14px; background: #555; color: #fff; border-radius: 8px; border: none; cursor: pointer; margin-bottom: 20px; font-size: 0.95rem; transition: background 0.2s ease; }
        .btn-back i { margin-right: 8px; }
        .btn-back:hover { background: #333; }
        .course-grid { display: flex; flex-wrap: wrap; gap: 20px; }
        .course-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); flex: 1 1 300px; transition: transform 0.2s ease; }
        .course-card:hover { transform: translateY(-5px); }
        .course-card h4 { margin-top: 0; margin-bottom: 10px; color: #222; }
        .course-actions { margin-top: 15px; }
        .btn { padding: 10px 18px; border: none; border-radius: 8px; background: #ff4081; color: #fff; cursor: pointer; transition: background 0.2s ease; text-decoration: none; font-size: 0.95rem; display: inline-block; }
        .btn:hover { background: #e73370; }
        .message { padding: 6px 12px; border-radius: 6px; font-size: 0.9rem; display: inline-block; }
        .message.success { background: #d4edda; color: #155724; }
        .message.warn { background: #fff3cd; color: #856404; }
        .message.danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<button class="btn-back" onclick="goBack()"><i class="fas fa-arrow-left"></i> Back to Courses</button>

<h2>Course Materials: <?= htmlspecialchars($course_name) ?></h2>

<div class="course-grid">
<?php
if (count($materials) > 0) {
    foreach ($materials as $i => $mat) {
        $completed_res = $conn->query("
            SELECT completed 
            FROM student_material_completion 
            WHERE student_id=$student_id 
              AND course_id=$course_id 
              AND material_id={$mat['id']}
        ")->fetch_assoc();
        $completed = $completed_res && $completed_res['completed'];

        $accessible = is_material_accessible($conn, $student_id, $course_id, $materials, $i);

        echo '<div class="course-card">';
        echo '<h4>' . htmlspecialchars($mat['title']) . '</h4>';
        echo '<div class="course-actions">';
        if ($accessible) {
            echo '<a href="get_course_materials.php?course_id=' . $course_id . '&material_id=' . $mat['id'] . '" class="btn" target="_blank">View</a> ';
            if ($completed) echo '<span class="message success" style="margin-left:10px;">Completed</span>';
        } else {
            echo '<span class="message warn">Locked</span>';
        }
        echo '</div></div>';
    }
} else {
    echo '<p class="message">No materials uploaded yet.</p>';
}
$conn->close();
?>
</div>

<script>
function goBack() { window.history.back(); }
</script>
</body>
</html>
