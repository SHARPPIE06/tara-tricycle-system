<?php
// saved_action.php
require_once 'session_init.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $label = trim($_POST['label'] ?? '');
    $lat = $_POST['lat'] ?? '';
    $lng = $_POST['lng'] ?? '';
    
    if (empty($label) || empty($lat) || empty($lng)) {
        header("Location: ../saved_locations.php?error=All fields are required. Please select a location on the map.");
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO saved_locations (user_id, label, lat, lng) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$user_id, $label, $lat, $lng])) {
        header("Location: ../saved_locations.php?success=Location saved!");
    } else {
        header("Location: ../saved_locations.php?error=Failed to save location");
    }
    $stmt = null;
} 
elseif ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    
    $stmt = $conn->prepare("DELETE FROM saved_locations WHERE id = ? AND user_id = ?");
    
    if ($stmt->execute([$id, $user_id])) {
        header("Location: ../saved_locations.php?success=Location deleted");
    } else {
        header("Location: ../saved_locations.php?error=Failed to delete");
    }
    $stmt = null;
}
else {
    header("Location: ../saved_locations.php");
}
$conn = null;
?>
