<?php
// auth_login.php
require_once 'session_init.php';
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Input validation
    if (empty($email) || empty($password)) {
        header("Location: ../login.php?error=Please fill all fields");
        exit();
    }

    // Fetch user
    $stmt = $conn->prepare("SELECT id, username, password_hash, role, status, classifications FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        
        // Check if account has been rejected/deactivated
        if (($user['status'] ?? '') === 'rejected') {
            header("Location: ../login.php?error=Your account has been deactivated. Please contact admin.");
            exit();
        }

        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status'] = $user['status'] ?? 'pending';
            
            // Handle classifications robustly
            $classRaw = $user['classifications'];
            $classifications = [];
            if (is_string($classRaw)) {
                $classifications = json_decode($classRaw, true) ?: [];
            } elseif (is_array($classRaw)) {
                $classifications = $classRaw;
            }
            $_SESSION['classifications'] = $classifications;
            
            // Redirect based on role and classification
            if ($user['role'] === 'admin') {
                header("Location: ../dashboard_admin.php");
            } else {
                if (in_array('Driver', $classifications)) {
                    header("Location: ../dashboard_driver.php");
                } else {
                    header("Location: ../dashboard_user.php");
                }
            }
            exit();
        } else {
            header("Location: ../login.php?error=Incorrect password");
            exit();
        }
    } else {
        header("Location: ../login.php?error=User not found");
        exit();
    }

    $stmt = null;
    $conn = null;
} else {
    header("Location: ../login.php");
}
?>
