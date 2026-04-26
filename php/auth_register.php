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
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        header("Location: ../register.php?error=Email already exists");
        exit();
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");

    if ($stmt->execute([$username, $email, $password_hash, $role])) {
        header("Location: ../login.php?success=Account created successfully. Please login.");
    } else {
        header("Location: ../register.php?error=Registration failed. Please try again.");
    }

    $stmt = null;
    $conn = null;
} else {
    header("Location: ../register.php");
}
?>
