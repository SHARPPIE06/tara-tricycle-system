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
    $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../dashboard_admin.php");
            } else {
                header("Location: ../dashboard_user.php");
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

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../login.php");
}
?>
