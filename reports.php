<?php
// reports.php
require_once 'php/session_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'] ?? 'user';
if ($role !== 'admin') {
    header("Location: dashboard_user.php");
    exit();
}

require_once 'php/db_connect.php';

// Fetch stats
$userCount = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$routeCount = $conn->query("SELECT COUNT(*) as c FROM routes")->fetch_assoc()['c'];
$stopCount = $conn->query("SELECT COUNT(*) as c FROM stops")->fetch_assoc()['c'];
$reviewCount = $conn->query("SELECT COUNT(*) as c FROM reviews")->fetch_assoc()['c'];
$avgRating = $conn->query("SELECT AVG(rating) as a FROM reviews")->fetch_assoc()['a'] ?? 0;

$username = $_SESSION['username'] ?? 'Admin';
$initials = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - TARA Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>TARA</h1>
                <p>Admin Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <span class="nav-section-title">Overview</span>
                <a href="dashboard_admin.php" class="nav-link" id="navDashboard">
                    <span class="nav-icon">📊</span> Dashboard
                </a>

                <span class="nav-section-title">Management</span>
                <a href="manage_users.php" class="nav-link" id="navUsers">
                    <span class="nav-icon">👥</span> Users
                </a>
                <a href="manage_routes.php" class="nav-link" id="navRoutesMgmt">
                    <span class="nav-icon">🛤️</span> Routes & TODA
                </a>

                <span class="nav-section-title">Analytics</span>
                <a href="route_map.php" class="nav-link" id="navMapAdmin">
                    <span class="nav-icon">🗺️</span> Map Overview
                </a>
                <a href="reports.php" class="nav-link active" id="navReports">
                    <span class="nav-icon">📈</span> Reports
                </a>
                <a href="feedback.php" class="nav-link" id="navFeedback">
                    <span class="nav-icon">💬</span> Feedback
                </a>
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
                    <h2>System Reports</h2>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </header>

            <div class="page-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">👥</div>
                        <div class="stat-info">
                            <h3><?php echo $userCount; ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">🛤️</div>
                        <div class="stat-info">
                            <h3><?php echo $routeCount; ?></h3>
                            <p>Routes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">📍</div>
                        <div class="stat-info">
                            <h3><?php echo $stopCount; ?></h3>
                            <p>Terminals/Stops</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">⭐</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($avgRating, 1); ?> / 5</h3>
                            <p>Average Rating</p>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h3>📊 Data Overview</h3>
                    </div>
                    <div class="card-body">
                        <p style="margin-bottom:20px;">The system currently has <strong><?php echo $reviewCount; ?></strong> submitted reviews and feedback entries.</p>
                        <div style="display:flex; gap:10px;">
                            <a href="manage_users.php" class="btn btn-primary">Download User CSV</a>
                            <a href="manage_routes.php" class="btn btn-secondary">Export Route Data</a>
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
