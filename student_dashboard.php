<?php
session_start();

// ---------- Database Connection ----------
$host = "localhost";
$user = "root";
$pass = "";
$db = "onlinelearning";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------- Check if Student ----------
if (!isset($_SESSION['username']) || (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student')) {
    header("Location: SignUp_Login.php");
    exit;
}

// ---------- Resolve Student ID ----------
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
    header("Location: SignUp_Login.php");
    exit;
}

// ---------- Handle Sending Message ----------
if(isset($_POST['send_message'])){
    $instructor_id = $_POST['instructor_id'];
    $message_text = $_POST['message_text'];

    $stmt = $conn->prepare("INSERT INTO messages (student_id, instructor_id, sender, message) VALUES (?, ?, 'student', ?)");
    $stmt->bind_param("iis", $student_id, $instructor_id, $message_text);
    $stmt->execute();
    $stmt->close();
    $msg_sent = "Message sent!";
}


// ---------- Handle Enrollment ----------
if (isset($_POST['enroll_course'])) {
    $course_id = $_POST['course_id'];
    $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id, status) VALUES (?, ?, 'enrolled')");
    $stmt->bind_param("ii", $student_id, $course_id);
    $stmt->execute();
    $stmt->close();
    $msg_enroll = "Successfully enrolled in course!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg: #0f172a;
            --card: #111827;
            --muted: #6b7280;
            --text: #e5e7eb;
            --primary: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --border: #1f2937;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { box-sizing: border-box; }

        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica Neue, Arial;
            margin: 0;
            background: linear-gradient(180deg, #0b1022, #0f172a);
            color: var(--text);
            min-height: 100vh;
        }

        .layout { display: flex; min-height: 100vh; }

        .sidebar {
            width: 68px;
            background: #0b1222;
            border-right: 1px solid var(--border);
            position: sticky;
            top: 0;
            height: 100vh;
            padding: 14px 10px;
            transition: width .2s ease;
            overflow: hidden;
        }
        .sidebar:hover { width: 220px; }
        .side-menu { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
        .side-link {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none; color: var(--text);
            padding: 10px 12px; border-radius: 10px;
            border: 1px solid transparent;
            transition: background .2s ease, color .2s ease, border-color .2s ease;
            white-space: nowrap;
        }
        .side-link i { width: 20px; text-align: center; font-size: 16px; }
        .side-link span { opacity: 0; transform: translateX(-6px); transition: opacity .2s ease, transform .2s ease; }
        .sidebar:hover .side-link span { opacity: 1; transform: translateX(0); }
        .side-link:hover { background: #0f1933; border-color: #1b2740; }
        .side-link.active {
            background: linear-gradient(180deg, var(--primary), var(--primary-600));
            color: #fff; box-shadow: 0 8px 20px rgba(59,130,246,.25);
        }

        .main { flex: 1; padding: 20px; }

        .section {
            display: none;
            margin: 24px 20px;
            background: linear-gradient(180deg, #0c1226, #0a1122);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 10px 24px rgba(0,0,0,.35);
        }
        .section.active { display: block; }

        .section h3 { margin: 0 0 14px 0; font-size: 20px; font-weight: 700; }

        .btn {
            appearance: none;
            border: 0;
            padding: 10px 16px;
            background: linear-gradient(180deg, var(--primary), var(--primary-700));
            color: #fff;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 10px 18px rgba(59,130,246,.25);
            transition: transform .12s ease, box-shadow .12s ease, opacity .2s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn:active { transform: translateY(0); box-shadow: 0 6px 14px rgba(59,130,246,.2); }

        .message { margin: 10px 0 16px; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: #0b1222; }
        .message.success { border-color: rgba(16,185,129,.4); background: rgba(16,185,129,.08); color: #a7f3d0; }

        .course-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(280px,1fr)); gap: 16px; }
        .course-card {
            background: #111827;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 6px 14px rgba(0,0,0,.3);
        }

        @media (max-width: 600px) {
            .layout { flex-direction: column; }
            .sidebar { position: relative; width: 100%; height: auto; display: flex; }
            .sidebar:hover { width: 100%; }
            .side-menu { flex-direction: row; flex-wrap: wrap; }
            .side-link span { display: none; }
            .main { padding: 12px; }
            .section { margin: 16px 12px; padding: 16px; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <ul class="side-menu">
            <li><a href="#" class="side-link active" data-tab="home_tab"><i class="fa-solid fa-house"></i><span>Home</span></a></li>
            <li><a href="#" class="side-link" data-tab="browse_courses_tab"><i class="fa-solid fa-search"></i><span>Browse Courses</span></a></li>
            <li><a href="#" class="side-link" data-tab="my_enrollments_tab"><i class="fa-solid fa-graduation-cap"></i><span>My Enrollments</span></a></li>
            <li><a href="#" class="side-link" data-tab="connect_instructor_tab"><i class="fa-solid fa-chalkboard-teacher"></i><span>Connect Instructor</span></a></li>
            <li><a href="logout.php" class="side-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>
    <main class="main">
        <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>

        <!-- Home -->
        <div id="home_tab" class="section active">
            <h3>Welcome to Your Learning Journey</h3>
            <p class="message">This is your student dashboard home. Here you can explore available courses, manage your enrollments, and track your learning progress. Use the sidebar navigation to access different features.</p>
            <h4>Getting Started</h4>
            <p>Welcome to our online learning platform! This dashboard is designed to help you make the most of your educational experience...</p>
            <p><b style="color:red">Note: If you want to unlock any material, you need to complete all previous materials and quizzes.</b></p>
        </div>

        <!-- Browse Courses -->
        <div id="browse_courses_tab" class="section">
            <h3>Browse Courses</h3>
            <?php if(isset($msg_enroll)) echo "<p class='message success'>$msg_enroll</p>"; ?>
            <div class="course-grid">
                <?php
                $courses = $conn->query("SELECT c.*, u.username as instructor_name FROM courses c JOIN users u ON u.id = c.instructor_id ORDER BY c.id DESC");
                while($course = $courses->fetch_assoc()):
                    $enrolled = $conn->query("SELECT * FROM enrollments WHERE student_id=$student_id AND course_id={$course['id']}")->num_rows > 0;
                ?>
                    <div class="course-card">
                        <h4><?= htmlspecialchars($course['course_name']) ?></h4>
                        <p><?= htmlspecialchars($course['description']) ?></p>
                        <?php if ($enrolled): ?>
                            <p class="message success">Already Enrolled</p>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                <input type="submit" name="enroll_course" value="Enroll" class="btn">
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- My Enrollments -->
        <div id="my_enrollments_tab" class="section">
            <h3>My Enrollments</h3>
            <?php
            $enrollments = $conn->query("SELECT c.* FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.student_id=$student_id");
            if ($enrollments->num_rows > 0) {
                echo '<div class="course-grid">';
                while($row = $enrollments->fetch_assoc()){
                    $course_id = $row['id'];
                    $total_materials = $conn->query("SELECT COUNT(*) as total FROM materials WHERE course_id=$course_id")->fetch_assoc()['total'];
                    $completed_materials = $conn->query("SELECT COUNT(*) as completed FROM student_material_completion WHERE student_id=$student_id AND course_id=$course_id AND completed=1")->fetch_assoc()['completed'];
                    $progress = $total_materials > 0 ? round(($completed_materials / $total_materials) * 100) : 0;

                    echo '<div class="course-card">';
                    echo '<h4>'.htmlspecialchars($row['course_name']).'</h4>';
                    echo '<p>'.htmlspecialchars($row['description']).'</p>';

                    // Progress bar
                    echo '<div style="background:#ddd;border-radius:6px;height:10px;margin:10px 0;">';
                    echo '<div style="width:'.$progress.'%;height:100%;background:#10b981;border-radius:6px;"></div>';
                    echo '</div>';
                    echo '<p>Progress: '.$progress.'%</p>';

                    // Buttons
                    echo '<button class="btn" onclick="showCourseDetails('.$course_id.')">Materials</button> ';
                    echo '<button class="btn" onclick="showQuizzes('.$course_id.')">Quizzes</button>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo "<p class='message'>Not enrolled in any course yet.</p>";
            }
            ?>
        </div>

        <!-- Connect Instructor -->
        <div id="connect_instructor_tab" class="section">
            <h3>Connect with Instructors</h3>
            <p>Here you can message your course instructors directly or see their contact information.</p>
            <div class="course-grid">
<?php
$instructors = $conn->query("
    SELECT DISTINCT u.id, u.username, u.email 
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE e.student_id = $student_id
");
if ($instructors->num_rows > 0) {
    while($inst = $instructors->fetch_assoc()) {
        echo '<div class="course-card">';
        echo '<h4>'.htmlspecialchars($inst['username']).'</h4>';
        echo '<p>Email: <a href="mailto:'.htmlspecialchars($inst['email']).'">'.htmlspecialchars($inst['email']).'</a></p>';

        // Message Form
        echo '<form method="POST">';
        echo '<input type="hidden" name="instructor_id" value="'.$inst['id'].'">';
        echo '<textarea name="message_text" placeholder="Type your message..." required style="width:100%;margin:8px 0;padding:8px;border-radius:6px;"></textarea>';
        echo '<input type="submit" name="send_message" value="Send Message" class="btn">';
        echo '</form>';

        // Display Messages
        // Display Messages
$msgs = $conn->query("
SELECT * FROM messages 
WHERE student_id=$student_id AND instructor_id={$inst['id']} 
ORDER BY created_at DESC
");

if($msgs->num_rows > 0){
echo '<h5 style="margin-top:12px;">Messages:</h5>';
echo "<table style='width:100%;border-collapse:collapse;'>";
echo "<tr><th style='border:1px solid #555;padding:6px;'>Sender</th><th style='border:1px solid #555;padding:6px;'>Message</th><th style='border:1px solid #555;padding:6px;'>Date</th></tr>";
while($m = $msgs->fetch_assoc()){
    $sender = $m['sender'] === 'student' ? 'You' : htmlspecialchars($inst['username']);
    $style = $m['is_read'] == 0 && $m['sender'] == 'instructor' ? 'background:#1f2937;' : '';
    echo "<tr style='$style'>";
    echo "<td style='border:1px solid #555;padding:6px;'>$sender</td>";
    echo "<td style='border:1px solid #555;padding:6px;'>".htmlspecialchars($m['message'])."</td>";
    echo "<td style='border:1px solid #555;padding:6px;'>".$m['created_at']."</td>";
    echo "</tr>";
}
echo "</table>";
} else {
echo "<p class='message'>No messages yet.</p>";
}

        echo '</div>';
    }
} else {
    echo "<p class='message'>No instructors found for your courses yet.</p>";
}
?>
        </div>
        </div>

    </main>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const tabs = document.querySelectorAll(".side-link[data-tab]");
    const sections = document.querySelectorAll(".section");

    tabs.forEach(tab => {
        tab.addEventListener("click", function(e) {
            if (tab.getAttribute('href') === 'logout.php') return;
            e.preventDefault();
            tabs.forEach(t => t.classList.remove("active"));
            sections.forEach(s => s.classList.remove("active"));
            this.classList.add("active");
            document.getElementById(this.dataset.tab).classList.add("active");
        });
    });
});

// Function to show course materials
function showCourseDetails(courseId) {
    window.location.href = 'get_course_materials.php?course_id=' + courseId;
}

function showQuizzes(courseId) {
    window.location.href = 'get_course_quizzes.php?course_id=' + courseId;
}

// Placeholder for instructor chat
function openChat(instructorId) {
    alert("Opening chat with instructor ID: " + instructorId);
    // Future: redirect to chat system
    // window.location.href = 'chat.php?instructor_id=' + instructorId;
}
</script>
</body>
</html>
