<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "onlinelearning";

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if OTP is verified
if (!isset($_POST['otp_verified']) || $_POST['otp_verified'] !== "true") {
    echo "OTP not verified.";
    exit;
}

// Get form data
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role = $_POST['role'];

// Validate role
if ($role !== "instructor" && $role !== "student") {
    echo "Invalid role.";
    exit;
}

// Prepare SQL statement
$stmt = $conn->prepare("INSERT INTO users (username, email, password, role, otp_verified) VALUES (?, ?, ?, ?, 1)");
$stmt->bind_param("ssss", $username, $email, $password, $role);

// Execute and respond
if ($stmt->execute()) {
    echo "success";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
