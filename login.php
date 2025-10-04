<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Form not submitted via POST.";
    exit;
}

if (!isset($_POST['role'], $_POST['username'], $_POST['password'])) {
    echo "Please fill out the login form.";
    exit;
}

$role = trim($_POST['role']);
$username = trim($_POST['username']);
$password = $_POST['password'];

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "onlinelearning";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Table is now unified for Instructor/Student
$table = "users"; 
$redirectPage = "";

switch ($role) {
    case "instructor":
        $redirectPage = "instructor_dashboard.php";
        break;
    case "student":
        $redirectPage = "student_dashboard.php";
        break;
    default:
        echo "Invalid role selected.";
        exit;
}

// Prepare and execute statement
$stmt = $conn->prepare("SELECT * FROM $table WHERE username = ? AND role = ?");
if (!$stmt) {
    echo "Prepare failed: " . $conn->error;
    exit;
}

$stmt->bind_param("ss", $username, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        // Successful login
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        header("Location: $redirectPage");
        exit;
    } else {
        echo "Incorrect password.";
    }
} else {
    echo "User not found or role mismatch.";
}

$stmt->close();
$conn->close();
?>
