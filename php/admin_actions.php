<?php
// admin_actions.php — Handles Approve, Reject, Delete, Edit actions
require_once 'session_init.php';
require_once 'db_connect.php';

// Verify admin access
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../dashboard_user.php");
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($userId === 0) {
    header("Location: ../manage_users.php?error=Invalid user ID");
    exit();
}

// Prevent admins from modifying their own account status
if ($userId === (int)$_SESSION['user_id'] && in_array($action, ['delete', 'reject'])) {
    header("Location: ../manage_users.php?error=You cannot modify your own account");
    exit();
}

switch ($action) {
    case 'approve':
        $stmt = $conn->prepare("UPDATE users SET status = 'verified' WHERE id = ?");
        if ($stmt->execute([$userId])) {
            header("Location: ../manage_users.php?success=User has been verified and granted full access");
        } else {
            header("Location: ../manage_users.php?error=Failed to approve user");
        }
        break;

    case 'reject':
        $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
        if ($stmt->execute([$userId])) {
            header("Location: ../manage_users.php?success=User has been deactivated");
        } else {
            header("Location: ../manage_users.php?error=Failed to reject user");
        }
        break;

    case 'delete':
        // First delete related records (reviews, saved_locations)
        $conn->prepare("DELETE FROM reviews WHERE user_id = ?")->execute([$userId]);
        $conn->prepare("DELETE FROM saved_locations WHERE user_id = ?")->execute([$userId]);
        
        // Then delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$userId])) {
            header("Location: ../manage_users.php?success=User has been permanently deleted");
        } else {
            header("Location: ../manage_users.php?error=Failed to delete user");
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'user';

            $username = $first_name . ' ' . $last_name;
            $stmt = $conn->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, role = ? WHERE id = ?");
            if ($stmt->execute([$username, $first_name, $last_name, $email, $role, $userId])) {
                header("Location: ../manage_users.php?success=User details updated successfully");
            } else {
                header("Location: ../manage_users.php?error=Failed to update user");
            }
        }
        break;

    default:
        header("Location: ../manage_users.php?error=Unknown action");
        break;
}

$conn = null;
?>
