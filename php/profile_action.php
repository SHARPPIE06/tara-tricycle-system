<?php
// profile_action.php
require_once 'session_init.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $username = trim($_POST['username'] ?? '');
    
    if (empty($username)) {
        header("Location: ../profile.php?error=Username cannot be empty");
        exit();
    }
    
    $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->bind_param("si", $username, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        header("Location: ../profile.php?success=Profile updated successfully");
    } else {
        header("Location: ../profile.php?error=Failed to update profile");
    }
    $stmt->close();
} 
elseif ($action === 'update_password') {
    $new_password = $_POST['new_password'] ?? '';
    
    if (strlen($new_password) < 6) {
        header("Location: ../profile.php?error=Password must be at least 6 characters");
        exit();
    }
    
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $user_id);
    
    if ($stmt->execute()) {
        header("Location: ../profile.php?success=Password updated successfully");
    } else {
        header("Location: ../profile.php?error=Failed to update password");
    }
    $stmt->close();
}
else {
    header("Location: ../profile.php");
}
$conn->close();
?>
