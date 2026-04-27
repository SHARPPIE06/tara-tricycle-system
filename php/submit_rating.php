<?php
// submit_rating.php — Handles driver rating submissions via AJAX
require_once 'session_init.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in to submit a rating.']);
    exit();
}

// Only verified users can rate
if (($_SESSION['status'] ?? 'pending') !== 'verified') {
    echo json_encode(['success' => false, 'error' => 'Your account must be verified to rate drivers.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

$userId = $_SESSION['user_id'];
$driverId = (int)($_POST['driver_id'] ?? 0);
$driverName = trim($_POST['driver_name'] ?? '');
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$tip = (int)($_POST['tip'] ?? 0);

// Validation
if (empty($driverName)) {
    echo json_encode(['success' => false, 'error' => 'Driver name is required.']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Please select a rating between 1 and 5 stars.']);
    exit();
}

// Sanitize comment
$comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
if (strlen($comment) > 500) {
    $comment = substr($comment, 0, 500);
}

// Append tip info to comment if given
if ($tip > 0) {
    $comment = $comment . ($comment ? ' | ' : '') . 'Tip: ₱' . $tip;
}

try {
    // Insert with driver_id if provided (greater than 0)
    if ($driverId > 0) {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, driver_id, driver_name, rating, comment, is_read) VALUES (?, ?, ?, ?, ?, 0)");
        $success = $stmt->execute([$userId, $driverId, $driverName, $rating, $comment]);
    } else {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, driver_name, rating, comment, is_read) VALUES (?, ?, ?, ?, 0)");
        $success = $stmt->execute([$userId, $driverName, $rating, $comment]);
    }
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Server error. Please try again later.']);
}

$conn = null;
?>
