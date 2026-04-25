<?php
// feedback_action.php
require_once 'session_init.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
$action = $_POST['action'] ?? '';

if ($role === 'admin') {
    header("Location: ../feedback.php?error=Admins cannot submit feedback.");
    exit();
}

if ($action === 'submit') {
    $rating = intval($_POST['rating'] ?? 5);
    $driver_name = trim($_POST['driver_name'] ?? '');
    $route_id = intval($_POST['route_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5 || $route_id === 0) {
        header("Location: ../feedback.php?error=Invalid rating or route.");
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, driver_name, route_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiis", $user_id, $driver_name, $route_id, $rating, $comment);
    
    if ($stmt->execute()) {
        header("Location: ../feedback.php?success=Review submitted successfully!");
    } else {
        header("Location: ../feedback.php?error=Failed to submit review");
    }
    $stmt->close();
} else {
    header("Location: ../feedback.php");
}
$conn->close();
?>
