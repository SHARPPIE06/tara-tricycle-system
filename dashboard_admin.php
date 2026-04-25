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
$userCount = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$routeCount = $conn->query("SELECT COUNT(*) as c FROM routes")->fetch_assoc()['c'];
$stopCount = $conn->query("SELECT COUNT(*) as c FROM stops")->fetch_assoc()['c'];

// Fetch recent users
$recentUsers = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
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
</head>
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

                <span class="nav-section-title">Management</span>
                <a href="#" class="nav-link" id="navUsers">
                    <span class="nav-icon">👥</span> Users
                </a>
                <a href="manage_routes.php" class="nav-link" id="navRoutesMgmt">
                    <span class="nav-icon">🛤️</span> Routes & TODA
                </a>
                <a href="#" class="nav-link" id="navFareMgmt">
                    <span class="nav-icon">💰</span> Fares
                </a>
                <a href="#" class="nav-link" id="navStopsMgmt">
                    <span class="nav-icon">📍</span> Stops & Terminals
                </a>
                <a href="#" class="nav-link" id="navTODAMgmt">
                    <span class="nav-icon">🏢</span> TODA
                </a>

                <span class="nav-section-title">Analytics</span>
                <a href="#" class="nav-link" id="navMapAdmin">
                    <span class="nav-icon">🗺️</span> Map Overview
                </a>
                <a href="#" class="nav-link" id="navReports">
                    <span class="nav-icon">📈</span> Reports
                </a>
                <a href="#" class="nav-link" id="navFeedback">
                    <span class="nav-icon">💬</span> Feedback
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
                            <p>Stops</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">💬</div>
                        <div class="stat-info">
                            <h3>0</h3>
                            <p>Feedback</p>
                        </div>
                    </div>
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

                <!-- Recent Users Table -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>👥 Recent Users</h3>
                        <a href="#" class="btn btn-primary" style="padding:8px 20px;font-size:0.85rem;">View All</a>
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
                                <?php while ($row = $recentUsers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><span class="badge <?php echo $row['role'] === 'admin' ? 'badge-pending' : 'badge-active'; ?>"><?php echo ucfirst($row['role']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td><span class="badge badge-active">Active</span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>⚡ Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <div class="action-card" id="actionAddRoute">
                                <span class="action-icon">➕</span>
                                <span>Add Route</span>
                            </div>
                            <div class="action-card" id="actionManageFares">
                                <span class="action-icon">💰</span>
                                <span>Manage Fares</span>
                            </div>
                            <div class="action-card" id="actionAddStop">
                                <span class="action-icon">📍</span>
                                <span>Add Stop</span>
                            </div>
                            <div class="action-card" id="actionViewReports">
                                <span class="action-icon">📊</span>
                                <span>View Reports</span>
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
