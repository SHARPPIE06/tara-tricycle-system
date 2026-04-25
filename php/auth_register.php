<?php
// auth_register.php
require_once 'session_init.php';
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    // Input validation
    if (empty($username) || empty($email) || empty($password)) {
        header("Location: ../register.php?error=Please fill all fields");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../register.php?error=Invalid email format");
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: ../register.php?error=Email already exists");
        exit();
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $password_hash, $role);

    if ($stmt->execute()) {
        header("Location: ../login.php?success=Account created successfully. Please login.");
    } else {
        header("Location: ../register.php?error=Registration failed. Please try again.");
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../register.php");
}
?>
