<?php
session_start();

// ---------- Database Connection ----------
$host = "localhost";
$user = "root";
$pass = "";
$db = "onlinelearning"; // Align with login.php database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------- Check if Instructor ----------
// Expecting login to set username and role. Redirect to UI form on GET if not logged in.
if (!isset($_SESSION['username']) || (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor')) {
    header("Location: SignUp_Login.php");
    exit;
}

// ---------- Resolve Instructor ID from username ----------
$instructor_id = null;
$resolveStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND role = 'instructor' LIMIT 1");
if ($resolveStmt) {
    $resolveStmt->bind_param("s", $_SESSION['username']);
    $resolveStmt->execute();
    $result = $resolveStmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $instructor_id = (int)$row['id'];
    }
    $resolveStmt->close();
}

if (!$instructor_id) {
    header("Location: SignUp_Login.php");
    exit;
}

// ---------- Handle Create Course ----------
if (isset($_POST['create_course'])) {
    $course_name = $_POST['course_name'];
    $description = $_POST['description'];
    $stmt = $conn->prepare("INSERT INTO courses (instructor_id, course_name, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $instructor_id, $course_name, $description);
    $stmt->execute();
    $stmt->close();
    $msg_course = "Course created successfully!";
}

// ---------- Handle Update/Delete Courses ----------
if (isset($_POST['update_course'])) {
    $id = $_POST['course_id'];
    $name = $_POST['course_name_edit'];
    $desc = $_POST['description_edit'];
    $stmt = $conn->prepare("UPDATE courses SET course_name=?, description=? WHERE id=? AND instructor_id=?");
    $stmt->bind_param("ssii", $name, $desc, $id, $instructor_id);
    $stmt->execute();
    $stmt->close();
    $msg_course = "Course updated successfully!";
}

if (isset($_GET['delete_course'])) {
    $id = $_GET['delete_course'];
    $conn->query("DELETE FROM courses WHERE id=$id AND instructor_id=$instructor_id");
    $msg_course = "Course deleted successfully!";
}

// ---------- Handle Upload Materials ----------
if (isset($_POST['upload_material'])) {
    $course_id = $_POST['material_course_id'];
    $title = $_POST['material_title'];
    $file = $_FILES['file']['name'];
    $tmp = $_FILES['file']['tmp_name'];
    $path = "uploads/".$file;
    move_uploaded_file($tmp, $path);

    $stmt = $conn->prepare("INSERT INTO materials (course_id, title, file_path) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $course_id, $title, $path);
    $stmt->execute();
    $stmt->close();
    $msg_material = "Material uploaded!";
}

// ---------- Handle Create Quiz ----------
if (isset($_POST['create_quiz'])) {
    $course_id = $_POST['quiz_course_id'];
    $quiz_name = $_POST['quiz_name'];

    // Insert Quiz
    $stmt = $conn->prepare("INSERT INTO quizzes (course_id, quiz_name) VALUES (?, ?)");
    $stmt->bind_param("is", $course_id, $quiz_name);
    $stmt->execute();
    $quiz_id = $stmt->insert_id;
    $stmt->close();

    // Insert Multiple Questions
    if(isset($_POST['question']) && is_array($_POST['question'])){
        $questions = $_POST['question'];
        $options1 = $_POST['option1'];
        $options2 = $_POST['option2'];
        $options3 = $_POST['option3'];
        $options4 = $_POST['option4'];
        $answers = $_POST['answer'];

        $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question, option1, option2, option3, option4, answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
        for($i=0; $i<count($questions); $i++){
            $stmt->bind_param("isssssi", $quiz_id, $questions[$i], $options1[$i], $options2[$i], $options3[$i], $options4[$i], $answers[$i]);
            $stmt->execute();
        }
        $stmt->close();
    }
    $msg_quiz = "Quiz created with multiple questions!";
}


// ---------- Handle Sending Message to Student ----------
if(isset($_POST['send_message_student'])){
    $student_id = $_POST['student_id'];
    $message_text = $_POST['message_text'];
    $stmt = $conn->prepare("INSERT INTO messages (student_id, instructor_id, sender, message) VALUES (?, ?, 'instructor', ?)");
    $stmt->bind_param("iis", $student_id, $instructor_id, $message_text);
    $stmt->execute();
    $stmt->close();
    $msg_sent_student = "Message sent!";
}

$courses = $conn->query("SELECT * FROM courses WHERE instructor_id=$instructor_id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Instructor Dashboard</title>
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
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica Neue, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            margin: 0;
            background: linear-gradient(180deg, #0b1022, #0f172a);
            color: var(--text);
            min-height: 100vh;
        }

        .container { max-width: 1100px; margin: 0 auto; padding: 24px 20px 48px; }

        h2 {
            margin: 0 0 12px 0;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        /* Layout with collapsible sidebar */
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
        .sidebar:hover { width: 240px; }
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

        .section h3 {
            margin: 0 0 14px 0;
            font-size: 20px;
            font-weight: 700;
        }

        form { max-width: 720px; }

        label { font-size: 12px; color: var(--muted); }

        input, select, textarea {
            margin: 8px 0 14px 0;
            display: block;
            padding: 10px 12px;
            width: 100%;
            max-width: 520px;
            background: #0b1222;
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 10px;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59,130,246,.15);
        }

        input[type="file"] { padding: 8px; }
        textarea { min-height: 110px; resize: vertical; }

        input[type="submit"], .btn {
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
        input[type="submit"]:hover, .btn:hover { transform: translateY(-1px); }
        input[type="submit"]:active, .btn:active { transform: translateY(0); box-shadow: 0 6px 14px rgba(59,130,246,.2); }

        a.link-danger { color: #fca5a5; text-decoration: none; margin-left: 8px; }
        a.link-danger:hover { color: #fecaca; }

        .message { margin: 10px 0 16px; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: #0b1222; }
        .message.success { border-color: rgba(16,185,129,.4); background: rgba(16,185,129,.08); color: #a7f3d0; }
        .message.warn { border-color: rgba(245,158,11,.4); background: rgba(245,158,11,.08); color: #fde68a; }
        .message.danger { border-color: rgba(239,68,68,.4); background: rgba(239,68,68,.08); color: #fecaca; }

        /* Simple list/table styles for students list */
        ul { padding-left: 18px; }
        li { line-height: 1.8; color: #d1d5db; }

        /* Responsive */
        @media (max-width: 600px) {
            .layout { flex-direction: column; }
            .sidebar { position: relative; width: 100%; height: auto; display: flex; }
            .sidebar:hover { width: 100%; }
            .side-menu { flex-direction: row; flex-wrap: wrap; }
            .side-link span { display: none; }
            .main { padding: 12px; }
            .section { margin: 16px 12px; padding: 16px; }
            input, select, textarea { max-width: 100%; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar" aria-label="Sidebar">
        <ul class="side-menu">
            <li><a href="#" class="side-link active" data-tab="home_tab" title="Home"><i class="fa-solid fa-house"></i><span>Home</span></a></li>
            <li><a href="#" class="side-link" data-tab="create_course_tab" title="Create Course"><i class="fa-solid fa-square-plus"></i><span>Create Course</span></a></li>
            <li><a href="#" class="side-link" data-tab="manage_course_tab" title="Manage Courses"><i class="fa-solid fa-list-check"></i><span>Manage Courses</span></a></li>
            <li><a href="#" class="side-link" data-tab="upload_material_tab" title="Upload Materials"><i class="fa-solid fa-upload"></i><span>Upload Materials</span></a></li>
            <li><a href="#" class="side-link" data-tab="view_materials_tab" title="View Materials"><i class="fa-solid fa-folder-open"></i><span>View Materials</span></a></li>
            <li><a href="#" class="side-link" data-tab="create_quiz_tab" title="Create Quiz"><i class="fa-solid fa-clipboard-question"></i><span>Create Quiz</span></a></li>
            <li><a href="#" class="side-link" data-tab="view_students_tab" title="View Students"><i class="fa-solid fa-users"></i><span>View Students</span></a></li>
            <li><a href="#" class="side-link" data-tab="connect_students_tab" title="Connect Students"><i class="fa-solid fa-user-plus"></i><span>Connect Students</span></a></li>
            <li><a href="logout.php" class="side-link" title="Logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>
    <main class="main">
<h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>

<!-- Home -->
<div id="home_tab" class="section active">
    <h3>Welcome</h3>
    <p class="message">This is your instructor dashboard home. Use the sidebar to create and manage courses, upload materials, build quizzes, and view enrolled students. Hover over the icons to reveal the labels.</p>
    <p>Tips:</p>
    <ul>
        <li>Create a course first to unlock materials and quizzes.</li>
        <li>Upload PDFs, slides, or videos under Upload Materials.</li>
        <li>Use Create Quiz to add assessments to a course.</li>
        <li>Track enrollment details under View Students.</li>
    </ul>
</div>

<!-- Create Course -->
<div id="create_course_tab" class="section">
    <h3>Create Course</h3>
    <?php if(isset($msg_course)) echo "<p>$msg_course</p>"; ?>
    <form method="POST">
        <input type="text" name="course_name" placeholder="Course Name" required>
        <textarea name="description" placeholder="Course Description"></textarea>
        <input type="submit" name="create_course" value="Create Course">
    </form>
</div>

<!-- Manage Courses -->
<div id="manage_course_tab" class="section">
    <h3>Manage Courses</h3>
    <?php if(isset($msg_course)) echo "<p>$msg_course</p>"; ?>
    <?php
    $courses = $conn->query("SELECT * FROM courses WHERE instructor_id=$instructor_id");
    while($row = $courses->fetch_assoc()):
    ?>
        <form method="POST">
            <input type="hidden" name="course_id" value="<?= $row['id'] ?>">
            <input type="text" name="course_name_edit" value="<?= $row['course_name'] ?>">
            <input type="text" name="description_edit" value="<?= $row['description'] ?>">
            <input type="submit" name="update_course" value="Update">
            <a href="?delete_course=<?= $row['id'] ?>">Delete</a>
        </form>
    <?php endwhile; ?>
</div>

<!-- Upload Materials -->
<div id="upload_material_tab" class="section">
    <h3>Upload Materials</h3>
    <?php if(isset($msg_material)) echo "<p>$msg_material</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <select name="material_course_id" required>
            <?php $courses = $conn->query("SELECT * FROM courses WHERE instructor_id=$instructor_id");
            while($c = $courses->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>"><?= $c['course_name'] ?></option>
            <?php endwhile; ?>
        </select>
        <input type="text" name="material_title" placeholder="Material Title" required>
        <input type="file" name="file" required>
        <input type="submit" name="upload_material" value="Upload">
    </form>
</div>

<!-- View Materials -->
<div id="view_materials_tab" class="section">
    <h3>View Materials</h3>
    <?php
    $materials = $conn->query("
        SELECT m.id, m.title, m.file_path, c.course_name
        FROM materials m
        JOIN courses c ON c.id = m.course_id
        WHERE c.instructor_id = $instructor_id
        ORDER BY m.id DESC
    ");
    if ($materials && $materials->num_rows > 0) {
        echo '<ul>';
        while ($m = $materials->fetch_assoc()) {
            $title = htmlspecialchars($m['title']);
            $course = htmlspecialchars($m['course_name']);
            $file = htmlspecialchars($m['file_path']);
            echo "<li><strong>{$course}</strong> - {$title} &nbsp; <a href=\"{$file}\" target=\"_blank\">View</a></li>";
        }
        echo '</ul>';
    } else {
        echo '<p>No materials uploaded yet.</p>';
    }
    ?>
</div>

<!-- Create Quiz -->
<div id="create_quiz_tab" class="section">
    <h3>Create Quiz</h3>
    <?php if(isset($msg_quiz)) echo "<p>$msg_quiz</p>"; ?>
    
    <form method="POST" id="quizForm">
        <select name="quiz_course_id" required>
            <?php $courses = $conn->query("SELECT * FROM courses WHERE instructor_id=$instructor_id");
            while($c = $courses->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>"><?= $c['course_name'] ?></option>
            <?php endwhile; ?>
        </select>
        <input type="text" name="quiz_name" placeholder="Quiz Name" required>

        <div id="questions_container">
            <div class="question_block">
                <input type="text" name="question[]" placeholder="Question" required>
                <input type="text" name="option1[]" placeholder="Option 1" required>
                <input type="text" name="option2[]" placeholder="Option 2" required>
                <input type="text" name="option3[]" placeholder="Option 3" required>
                <input type="text" name="option4[]" placeholder="Option 4" required>
                <input type="number" name="answer[]" placeholder="Correct Option (1-4)" required>
            </div>
        </div>

        <button type="button" onclick="addQuestion()" class="btn" style="margin-bottom:12px;">Add Another Question</button>
        <input type="submit" name="create_quiz" value="Create Quiz">
    </form>
</div>


<!-- View Students -->
<div id="view_students_tab" class="section">
    <h3>Enrolled Students</h3>
    <?php
    $students = $conn->query("
        SELECT DISTINCT u.id, u.username, u.email
        FROM enrollments e
        JOIN users u ON u.id = e.student_id
        JOIN courses c ON c.id = e.course_id
        WHERE c.instructor_id = $instructor_id
    ");
    if ($students && $students->num_rows > 0) {
        echo '<ul>';
        while($s = $students->fetch_assoc()) {
            echo "<li>{$s['username']} ({$s['email']})</li>";
        }
        echo '</ul>';
    } else { echo "<p>No students enrolled yet.</p>"; }
    ?>
</div>

<!-- Connect Students (NEW) -->
<div id="connect_students_tab" class="section">
    <h3>Connect Students</h3>
    <?php if(isset($msg_sent_student)) echo "<p class='message success'>$msg_sent_student</p>"; ?>
    <p>List of all registered students:</p>
    <div class="course-grid">
    <?php
    $all_students = $conn->query("SELECT id, username, email FROM users WHERE role='student'");
    if ($all_students && $all_students->num_rows > 0) {
        while($s = $all_students->fetch_assoc()) {
            echo '<div class="course-card">';
            echo "<h4>".htmlspecialchars($s['username'])."</h4>";
            echo "<p>Email: <a href='mailto:".htmlspecialchars($s['email'])."'>".htmlspecialchars($s['email'])."</a></p>";

            // Message Form
            echo '<form method="POST">';
            echo '<input type="hidden" name="student_id" value="'.$s['id'].'">';
            echo '<textarea name="message_text" placeholder="Type your message..." required style="width:100%;margin:8px 0;padding:8px;border-radius:6px;"></textarea>';
            echo '<input type="submit" name="send_message_student" value="Send Message" class="btn">';
            echo '</form>';

            // Display Messages with this student
            $msgs = $conn->query("
                SELECT * FROM messages 
                WHERE student_id={$s['id']} AND instructor_id=$instructor_id
                ORDER BY created_at DESC
            ");

            if($msgs->num_rows > 0){
                echo '<h5 style="margin-top:12px;">Messages:</h5>';
                echo "<table style='width:100%;border-collapse:collapse;'>";
                echo "<tr><th style='border:1px solid #555;padding:6px;'>Sender</th><th style='border:1px solid #555;padding:6px;'>Message</th><th style='border:1px solid #555;padding:6px;'>Date</th></tr>";
                while($m = $msgs->fetch_assoc()){
                    $sender = $m['sender'] === 'instructor' ? 'You' : htmlspecialchars($s['username']);
                    $style = $m['is_read'] == 0 && $m['sender'] == 'student' ? 'background:#1f2937;' : '';
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
        echo "<p class='message'>No students registered yet.</p>";
    }
    ?>
    </div>
</div>


    </main>
</div>

<script>
const links = document.querySelectorAll('.side-link');
const sections = document.querySelectorAll('.section');

links.forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        sections.forEach(s => s.classList.remove('active'));
        links.forEach(l => l.classList.remove('active'));
        const tab = link.getAttribute('data-tab');
        document.getElementById(tab).classList.add('active');
        link.classList.add('active');
    });
});
function addQuestion() {
    const container = document.getElementById('questions_container');
    const block = document.createElement('div');
    block.className = 'question_block';
    block.style = "margin-top:12px;";
    block.innerHTML = `
        <input type="text" name="question[]" placeholder="Question" required>
        <input type="text" name="option1[]" placeholder="Option 1" required>
        <input type="text" name="option2[]" placeholder="Option 2" required>
        <input type="text" name="option3[]" placeholder="Option 3" required>
        <input type="text" name="option4[]" placeholder="Option 4" required>
        <input type="number" name="answer[]" placeholder="Correct Option (1-4)" required>
    `;
    container.appendChild(block);
}
</script>
</body>
</html>
