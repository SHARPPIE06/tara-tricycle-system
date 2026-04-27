<?php
// dashboard_driver.php — Driver Dashboard
require_once 'php/session_init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user info from session
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';
$userStatus = $_SESSION['status'] ?? 'pending';
$isVerified = ($userStatus === 'verified');
$classifications = $_SESSION['classifications'] ?? [];

// Redirect admins to admin dashboard
if ($role === 'admin') {
    header("Location: dashboard_admin.php");
    exit();
}

// Ensure user is a driver
$isDriver = false;
if (is_array($classifications)) {
    if (in_array('Driver', $classifications)) $isDriver = true;
} elseif (is_string($classifications)) {
    $decoded = json_decode($classifications, true);
    if (is_array($decoded) && in_array('Driver', $decoded)) $isDriver = true;
}

if (!$isDriver) {
    header("Location: dashboard_user.php");
    exit();
}

require_once 'php/db_connect.php';

// Fetch driver specific details
$stmt = $conn->prepare("SELECT toda_name, member_number FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$driverDetails = $stmt->fetch(PDO::FETCH_ASSOC);

$todaName = $driverDetails['toda_name'] ?? 'Not Assigned';
$memberNum = $driverDetails['member_number'] ?? 'N/A';

// Get user initials for avatar
$initials = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <link rel="icon" type="image/png" href="assets/icon.png">
</head>
<body>
    <div class="dashboard-wrapper">
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>TARA</h1>
                <p>Driver Portal</p>
            </div>
            
            <nav class="sidebar-nav">
                <span class="nav-section-title">Main</span>
                <a href="dashboard_driver.php" class="nav-link active" id="navDashboard">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="route_map.php" class="nav-link" id="navMap">
                    <span class="nav-icon">🗺️</span> Route Map
                </a>
                <a href="fare_estimator.php" class="nav-link" id="navFare">
                    <span class="nav-icon">💰</span> Fare Estimator
                </a>
                
                <span class="nav-section-title">Reference</span>
                <a href="routes.php" class="nav-link" id="navRoutes">
                    <span class="nav-icon">🛤️</span> Routes
                </a>
                <a href="terminals.php" class="nav-link" id="navTODA">
                    <span class="nav-icon">🏢</span> TODA / Terminals
                </a>

                <span class="nav-section-title">Account</span>
                <a href="profile.php" class="nav-link" id="navProfile">
                    <span class="nav-icon">👤</span> My Profile
                </a>
                <a href="saved_locations.php" class="nav-link" id="navSaved">
                    <span class="nav-icon">📌</span> Saved Locations
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="php/logout.php" class="nav-link" id="navLogout">
                    <span class="nav-icon">🚪</span> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div style="display:flex;align-items:center;gap:12px;">
                    <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                    <h2>Driver Dashboard</h2>
                </div>
                <div class="user-info">
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                        <div class="user-role">Driver</div>
                    </div>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                <?php if (!$isVerified): ?>
                <div style="background: #fef9c3; border: 1px solid #fde68a; border-radius: 12px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span style="font-size: 1.3rem;">⏳</span>
                    <div>
                        <strong style="color: #a16207;">Account Pending Verification</strong>
                        <p style="font-size: 0.82rem; color: #92400e; margin-top: 2px;">Your driver account is under review. You can access reference tools, but full system features will be unlocked after admin approval.</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Driver Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">🚕</div>
                        <div class="stat-info">
                            <h3><?php echo htmlspecialchars($todaName); ?></h3>
                            <p>Assigned TODA</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">🔢</div>
                        <div class="stat-info">
                            <h3><?php echo htmlspecialchars($memberNum); ?></h3>
                            <p>Member Number</p>
                        </div>
                    </div>
                    <a href="feedback.php" class="stat-card" style="text-decoration:none; color:inherit;">
                        <div class="stat-icon purple">⭐</div>
                        <div class="stat-info">
                            <h3>Reviews</h3>
                            <p>Customer Feedback</p>
                        </div>
                    </a>
                </div>

                <!-- Map Preview -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>🗺️ Route Overview</h3>
                        <a href="route_map.php" class="btn btn-primary" style="padding:8px 20px;font-size:0.85rem;">View Full Map</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <div id="map" style="height: 400px; width: 100%;"></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3>⚡ Driver Tools</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <div class="action-card" onclick="window.location.href='fare_estimator.php'">
                                <span class="action-icon">💰</span>
                                <span>Fare Estimator</span>
                            </div>
                            <div class="action-card" onclick="window.location.href='route_map.php'">
                                <span class="action-icon">🔍</span>
                                <span>Search Route</span>
                            </div>
                            <div class="action-card" onclick="window.location.href='terminals.php'">
                                <span class="action-icon">🏢</span>
                                <span>Terminal List</span>
                            </div>
                            <div class="action-card" onclick="window.location.href='profile.php'">
                                <span class="action-icon">👤</span>
                                <span>Edit Profile</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Reviews -->
                <div class="content-card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3>⭐ Recent Feedback</h3>
                        <a href="feedback.php" class="btn btn-primary" style="padding:8px 20px;font-size:0.85rem;">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php
                        $recentReviews = [];
                        try {
                            $revStmt = $conn->prepare("SELECT r.*, u.username as reviewer_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.driver_id = ? ORDER BY r.created_at DESC LIMIT 3");
                            $revStmt->execute([$_SESSION['user_id']]);
                            $recentReviews = $revStmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            // Fallback if driver_id column doesn't exist yet
                            // You can also log this or show a notice to admin
                            $recentReviews = [];
                        }
                        ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Commuter</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentReviews as $rev): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($rev['reviewer_name'] ?: 'Anonymous'); ?></strong></td>
                                    <td style="color:var(--yellow);"><?php echo str_repeat('★', $rev['rating']); ?></td>
                                    <td style="font-size:0.85rem;"><?php echo htmlspecialchars($rev['comment']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentReviews)): ?>
                                <tr>
                                    <td colspan="3" style="text-align:center; padding:20px; color:#999;">No reviews yet.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const map = L.map("map").setView([14.5995, 121.1023], 13);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "© OpenStreetMap contributors"
            }).addTo(map);

            // Sidebar toggle for mobile
            document.getElementById('sidebarToggle')?.addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('open');
            });
        });
    </script>
</body>
</html>
