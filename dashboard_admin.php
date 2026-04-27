<?php
// dashboard_admin.php — Admin Dashboard
require_once 'php/session_init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'user';

// Redirect non-admins to user dashboard
if ($role !== 'admin') {
    header("Location: dashboard_user.php");
    exit();
}

$initials = strtoupper(substr($username, 0, 1));

// Fetch stats from DB
require_once 'php/db_connect.php';
$userCount = $conn->query("SELECT COUNT(*) as c FROM users")->fetch(PDO::FETCH_ASSOC)['c'];
$pendingCount = $conn->query("SELECT COUNT(*) as c FROM users WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['c'];
$routeCount = $conn->query("SELECT COUNT(*) as c FROM routes")->fetch(PDO::FETCH_ASSOC)['c'];
$stopCount = $conn->query("SELECT COUNT(*) as c FROM stops")->fetch(PDO::FETCH_ASSOC)['c'];

// Fetch recent users
$recentUsers = $conn->query("SELECT id, username, first_name, last_name, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 5");

// Fetch unread feedback count
$unreadFeedbackCount = $conn->query("SELECT COUNT(*) as c FROM reviews")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;

// Fetch recent feedback
$recentFeedback = $conn->query("SELECT r.*, rt.toda_name, u.username FROM reviews r LEFT JOIN routes rt ON r.route_id = rt.id LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 5");

// Fetch settings
$settings = [];
$settingsResult = $conn->query("SELECT setting_key, setting_value FROM settings");
while($row = $settingsResult ? $settingsResult->fetch(PDO::FETCH_ASSOC) : false) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$pwdEnabled = $settings['pwd_discount_enabled'] ?? '0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <link rel="icon" type="image/png" href="assets/icon.png"></head>
<body>
    <div class="dashboard-wrapper">
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>TARA</h1>
                <p>Admin Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <span class="nav-section-title">Overview</span>
                <a href="dashboard_admin.php" class="nav-link active" id="navDashboard">
                    <span class="nav-icon">📊</span> Dashboard
                </a>

                <span class="nav-section-title">User Account Management</span>
                <a href="manage_users.php?type=commuter" class="nav-link" id="navCommuters">
                    <span class="nav-icon">👥</span> Manage Commuters
                </a>
                <a href="manage_users.php?type=driver" class="nav-link" id="navDrivers">
                    <span class="nav-icon">🚗</span> Manage Drivers
                    <?php if($pendingCount > 0): ?>
                        <span class="nav-badge" style="background:#f87171; color:white; font-size:0.65rem; padding:2px 6px; border-radius:10px; margin-left:auto; font-weight:700;"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </a>

                <span class="nav-section-title">Routes & Fare Management</span>
                <a href="manage_routes.php" class="nav-link" id="navRoutesMgmt">
                    <span class="nav-icon">🛤️</span> Manage Routes
                </a>

                <span class="nav-section-title">TODAs & Terminals</span>
                <a href="manage_stops.php" class="nav-link" id="navTODAMgmt">
                    <span class="nav-icon">🏢</span> Add TODAs & Terminals
                </a>

                <span class="nav-section-title">Analytics</span>
                <a href="route_map.php" class="nav-link" id="navMapAdmin">
                    <span class="nav-icon">🗺️</span> Map Overview
                </a>
                <a href="feedback.php" class="nav-link" id="navFeedback">
                    <span class="nav-icon">💬</span> Feedback
                    <?php if($unreadFeedbackCount > 0): ?>
                        <span class="nav-badge" style="background:#f87171; color:white; font-size:0.65rem; padding:2px 6px; border-radius:10px; margin-left:auto; font-weight:700;"><?php echo $unreadFeedbackCount; ?></span>
                    <?php endif; ?>
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
            <header class="top-bar">
                <div style="display:flex;align-items:center;gap:12px;">
                    <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                    <h2>Admin Dashboard</h2>
                </div>
                <div class="user-info">
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </header>

            <div class="page-content">
                <!-- Stats -->
                <div class="stats-grid">
                    <a href="manage_users.php?type=commuter" class="stat-card" style="text-decoration:none; color:inherit;">
                        <div class="stat-icon blue">👥</div>
                        <div class="stat-info">
                            <h3><?php echo $userCount; ?></h3>
                            <p>Total Users</p>
                        </div>
                    </a>
                    <a href="manage_users.php?type=driver" class="stat-card" style="text-decoration:none; color:inherit;">
                        <div class="stat-icon" style="background:rgba(251,191,36,0.1);color:#f59e0b;">⏳</div>
                        <div class="stat-info">
                            <h3><?php echo $pendingCount; ?></h3>
                            <p>Pending Approval</p>
                        </div>
                    </a>
                    <a href="manage_routes.php" class="stat-card" style="text-decoration:none; color:inherit;">
                        <div class="stat-icon orange">🛤️</div>
                        <div class="stat-info">
                            <h3><?php echo $routeCount; ?></h3>
                            <p>Routes</p>
                        </div>
                    </a>
                    <a href="manage_routes.php" class="stat-card" style="text-decoration:none; color:inherit;">
                        <div class="stat-icon green">📍</div>
                        <div class="stat-info">
                            <h3><?php echo $stopCount; ?></h3>
                            <p>Stops</p>
                        </div>
                    </a>
                    <a href="feedback.php" class="stat-card" style="text-decoration:none; color:inherit;">
                        <div class="stat-icon purple">💬</div>
                        <div class="stat-info">
                            <h3><?php echo $unreadFeedbackCount; ?></h3>
                            <p>New Reviews</p>
                        </div>
                    </a>
                </div>

                <!-- Map Overview -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>🗺️ Route & Terminal Map</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <div id="map"></div>
                    </div>
                </div>

                <!-- Recent Feedback Table -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>💬 Recent User Feedback</h3>
                        <a href="feedback.php" class="btn btn-primary" style="padding:8px 20px;font-size:0.85rem;">Manage Feedback</a>
                    </div>
                    <div class="card-body" style="padding:0;overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Driver/Route</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($recentFeedback && $recentFeedback->rowCount() > 0): ?>
                                    <?php while ($row = $recentFeedback->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['username'] ?? 'Unknown'); ?></strong></td>
                                        <td>
                                            <small><?php echo htmlspecialchars($row['driver_name'] ?: 'N/A'); ?></small>
                                            <small style="display:block; color:#666;"><?php echo htmlspecialchars($row['toda_name'] ?? ''); ?></small>
                                        </td>
                                        <td style="color:var(--yellow);">
                                            <?php echo str_repeat('★', $row['rating']); ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars(substr($row['comment'] ?? '', 0, 50)) . (strlen($row['comment'] ?? '') > 50 ? '...' : ''); ?></small></td>
                                        <td>
                                            <?php if($row['is_read'] == 0): ?>
                                                <span class="badge badge-pending">New</span>
                                            <?php else: ?>
                                                <span class="badge badge-active">Read</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; padding:20px;">No feedback received yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Users Table -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>👥 Recent Users</h3>
                        <a href="manage_users.php" class="btn btn-primary" style="padding:8px 20px;font-size:0.85rem;">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;overflow-x:auto;">
                        <table class="data-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $recentUsers ? $recentUsers->fetch(PDO::FETCH_ASSOC) : false): ?>
                                <?php 
                                    $displayName = $row['first_name'] ? htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) : htmlspecialchars($row['username'] ?? 'Unknown');
                                    $statusVal = $row['status'] ?? 'pending';
                                    $statusClass = $statusVal === 'verified' ? 'badge-active' : ($statusVal === 'rejected' ? 'badge-inactive' : 'badge-pending');
                                ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $displayName; ?></td>
                                    <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                    <td><span class="badge <?php echo $row['role'] === 'admin' ? 'badge-pending' : 'badge-active'; ?>"><?php echo ucfirst($row['role']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($statusVal); ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Automatic Discount Info -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>⚙️ Automatic Discount Policy</h3>
                    </div>
                    <div class="card-body">
                        <div style="display:flex; align-items:flex-start; gap:14px; padding:12px 16px; background:#f0fdf4; border-radius:12px; border:1px solid #bbf7d0;">
                            <span style="font-size:1.6rem;">✅</span>
                            <div>
                                <strong style="color:#15803d;">20% Discount — Always Active</strong>
                                <p style="font-size:0.85rem; color:#166534; margin-top:4px;">A mandatory 20% discount is automatically applied for verified users classified as <strong>Student</strong>, <strong>PWD</strong>, or <strong>Senior Citizen</strong>. No manual toggle is required — the system detects and applies the discount instantly based on the user's account classification.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>⚡ Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <div class="action-card" id="actionAddRoute" onclick="window.location.href='manage_routes.php'" style="cursor:pointer;">
                                <span class="action-icon">➕</span>
                                <span>Add Route</span>
                            </div>
                            <div class="action-card" id="actionManageFares" onclick="window.location.href='manage_routes.php'" style="cursor:pointer;">
                                <span class="action-icon">💰</span>
                                <span>Manage Fares</span>
                            </div>
                            <div class="action-card" id="actionAddStop" onclick="window.location.href='manage_routes.php'" style="cursor:pointer;">
                                <span class="action-icon">📍</span>
                                <span>Add Stop</span>
                            </div>
                            <div class="action-card" id="actionViewProfile" onclick="window.location.href='profile.php'" style="cursor:pointer;">
                                <span class="action-icon">👤</span>
                                <span>My Profile</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Initialize Leaflet Map
        const map = L.map("map").setView([14.5995, 121.1023], 12);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "© OpenStreetMap contributors"
        }).addTo(map);

        // Sample data — terminals and routes in Rizal
        const terminals = [
            { lat: 14.5865, lng: 121.0614, name: "Marikina TODA Terminal" },
            { lat: 14.5995, lng: 121.1023, name: "Antipolo Public Market TODA" },
            { lat: 14.5773, lng: 121.0895, name: "Cogeo TODA" },
            { lat: 14.6234, lng: 121.0855, name: "SM City Masinag" },
            { lat: 14.5536, lng: 121.1215, name: "Taytay TODA" },
        ];

        // Functional programming: use map() and arrow functions
        terminals.map(t => {
            const marker = L.marker([t.lat, t.lng]).addTo(map);
            marker.bindPopup(`<strong>${t.name}</strong><br>TODA Terminal`);
        });

        // Sample route polyline (Antipolo to Marikina)
        const routeCoords = [
            [14.5995, 121.1023],
            [14.6234, 121.0855],
            [14.5865, 121.0614],
        ];
        L.polyline(routeCoords, { color: '#E87F24', weight: 4, dashArray: '8 6' }).addTo(map);

        // Sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>
