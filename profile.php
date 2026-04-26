<?php
// profile.php
require_once 'php/session_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

require_once 'php/db_connect.php';

// Fetch current user data
$stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = null;

$initials = strtoupper(substr($userData['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="icon" type="image/png" href="assets/icon.png"></head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>TARA</h1>
                <p><?php echo $role === 'admin' ? 'Admin Panel' : 'Commuter Portal'; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <span class="nav-section-title">Main</span>
                <a href="<?php echo $role === 'admin' ? 'dashboard_admin.php' : 'dashboard_user.php'; ?>" class="nav-link" id="navDashboard">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="route_map.php" class="nav-link" id="navMap">
                    <span class="nav-icon">🗺️</span> Route Map
                </a>
                <a href="fare_estimator.php" class="nav-link" id="navFare">
                    <span class="nav-icon">💰</span> Fare Estimator
                </a>
                
                <?php if ($role === 'user'): ?>
                <span class="nav-section-title">Search</span>
                <a href="route_map.php" class="nav-link" id="navRoutes">
                    <span class="nav-icon">🛤️</span> Routes
                </a>
                <a href="terminals.php" class="nav-link" id="navTODA">
                    <span class="nav-icon">🏢</span> TODA / Terminals
                </a>

                <span class="nav-section-title">Account</span>
                <a href="profile.php" class="nav-link active" id="navProfile">
                    <span class="nav-icon">👤</span> My Profile
                </a>
                <a href="saved_locations.php" class="nav-link" id="navSaved">
                    <span class="nav-icon">📌</span> Saved Locations
                </a>
                <?php else: ?>
                <span class="nav-section-title">Management</span>
                <a href="manage_routes.php" class="nav-link" id="navRoutesMgmt">
                    <span class="nav-icon">🛤️</span> Routes & TODA
                </a>
                <?php endif; ?>
            </nav>
            
            <div class="sidebar-footer">
                <a href="php/logout.php" class="nav-link" id="navLogout">
                    <span class="nav-icon">🚪</span> Logout
                </a>
            </div>
        </aside>

        <div class="main-content">
            <header class="top-bar">
                <div style="display:flex;align-items:center;gap:12px;">
                    <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                    <h2>My Profile</h2>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($userData['username']); ?></div>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </header>

            <div class="page-content">
                <?php 
                if(isset($_GET['success'])) echo "<div class='alert success' style='display:block'>".htmlspecialchars($_GET['success'])."</div>";
                if(isset($_GET['error'])) echo "<div class='alert error' style='display:block'>".htmlspecialchars($_GET['error'])."</div>";
                ?>
                <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="content-card">
                        <div class="card-header">
                            <h3>👤 Update Profile</h3>
                        </div>
                        <div class="card-body">
                            <form action="php/profile_action.php" method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['email']); ?>" disabled style="background:#f4f6f9; cursor:not-allowed;">
                                </div>
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary auth-btn">Save Changes</button>
                            </form>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="card-header">
                            <h3>🔒 Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form action="php/profile_action.php" method="POST">
                                <input type="hidden" name="action" value="update_password">
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                </div>
                                <button type="submit" class="btn btn-secondary auth-btn">Update Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>
