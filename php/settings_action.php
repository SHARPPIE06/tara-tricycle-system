<?php
// settings_action.php
require_once 'session_init.php';
require_once 'db_connect.php';

// Ensure user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pwd_enabled = isset($_POST['pwd_discount_enabled']) ? '1' : '0';

    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'pwd_discount_enabled'");
    
    if ($stmt->execute([$pwd_enabled])) {
        header("Location: ../dashboard_admin.php?settings_success=1");
    } else {
        header("Location: ../dashboard_admin.php?error=Failed to update settings");
    }
    $stmt = null;
} else {
    header("Location: ../dashboard_admin.php");
}
$conn = null;
?>
