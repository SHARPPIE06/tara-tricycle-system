<?php
// dashboard_user.php — Commuter Dashboard
require_once 'php/session_init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user info from session
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';

// Redirect admins to admin dashboard
if ($role === 'admin') {
    header("Location: dashboard_admin.php");
    exit();
}

// Get user initials for avatar
$initials = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
</head>
<body>
    <div class="dashboard-wrapper">
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>TARA</h1>
                <p>Commuter Portal</p>
            </div>
            
            <nav class="sidebar-nav">
                <span class="nav-section-title">Main</span>
                <a href="dashboard_user.php" class="nav-link active" id="navDashboard">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="#" class="nav-link" id="navMap">
                    <span class="nav-icon">🗺️</span> Route Map
                </a>
                <a href="fare_estimator.php" class="nav-link" id="navFare">
                    <span class="nav-icon">💰</span> Fare Estimator
                </a>
                
                <span class="nav-section-title">Search</span>
                <a href="#" class="nav-link" id="navRoutes">
                    <span class="nav-icon">🛤️</span> Routes
                </a>
                <a href="#" class="nav-link" id="navTODA">
                    <span class="nav-icon">🏢</span> TODA / Terminals
                </a>

                <span class="nav-section-title">Account</span>
                <a href="#" class="nav-link" id="navProfile">
                    <span class="nav-icon">👤</span> My Profile
                </a>
                <a href="#" class="nav-link" id="navSaved">
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
                    <h2>Dashboard</h2>
                </div>
                <div class="user-info">
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                        <div class="user-role">Commuter</div>
                    </div>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Stats -->
                <div class="stats-grid">
                    <a href="route_map.php" class="stat-card" style="text-decoration:none; color:inherit;">
                        <div class="stat-icon blue">🛤️</div>
                        <div class="stat-info">
                            <h3>Route</h3>
                            <p>Available Routes</p>
                        </div>
                    </a>
                    <a href="terminals.php" class="stat-card" style="text-decoration:none; color:inherit;">
                        <div class="stat-icon orange">🏢</div>
                        <div class="stat-info">
                            <h3>TODA</h3>
                            <p>Terminals</p>
                        </div>
                    </a>
                    <a href="saved_locations.php" class="stat-card" style="text-decoration:none; color:inherit;">
                        <div class="stat-icon green">📌</div>
                        <div class="stat-info">
                            <h3>Saved</h3>
                            <p>Locations</p>
                        </div>
                    </a>
                    <a href="feedback.php" class="stat-card" style="text-decoration:none; color:inherit;">
                        <div class="stat-icon purple">⭐</div>
                        <div class="stat-info">
                            <h3>Reviews</h3>
                            <p>My Feedback</p>
                        </div>
                    </a>
                </div>

                <!-- Map Preview -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>🗺️ Route Map — Rizal Province</h3>
                        <a href="route_map.php" class="btn btn-primary" style="padding:8px 20px;font-size:0.85rem;">Expand Map</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <div id="map"></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>⚡ Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <div class="action-card" id="actionEstimateFare" onclick="window.location.href='fare_estimator.php'" style="cursor:pointer;">
                                <span class="action-icon">💰</span>
                                <span>Estimate Fare</span>
                            </div>
                            <div class="action-card" id="actionSearchRoute" onclick="window.location.href='route_map.php'" style="cursor:pointer;">
                                <span class="action-icon">🔍</span>
                                <span>Search Route</span>
                            </div>
                            <div class="action-card" id="actionFindTODA" onclick="window.location.href='terminals.php'" style="cursor:pointer;">
                                <span class="action-icon">🏢</span>
                                <span>Find TODA</span>
                            </div>
                            <div class="action-card" id="actionRateDriver" onclick="window.location.href='feedback.php'" style="cursor:pointer;">
                                <span class="action-icon">⭐</span>
                                <span>Rate a Driver</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Initialize Leaflet Map — centered on Antipolo, Rizal
        const map = L.map("map").setView([14.5995, 121.1023], 13);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "© OpenStreetMap contributors"
        }).addTo(map);

        // Sample markers for terminals/TODA in Rizal
        const sampleLocations = [
            { lat: 14.5865, lng: 121.0614, name: "Marikina TODA Terminal", type: "terminal" },
            { lat: 14.5995, lng: 121.1023, name: "Antipolo Public Market TODA", type: "toda" },
            { lat: 14.6234, lng: 121.0855, name: "SM City Masinag Stop", type: "stop" },
            { lat: 14.5773, lng: 121.0895, name: "Cogeo TODA", type: "toda" },
            { lat: 14.6076, lng: 121.1215, name: "Antipolo Church Stop", type: "stop" },
        ];

        // Use arrow functions and map() — functional programming
        sampleLocations.map(loc => {
            const marker = L.marker([loc.lat, loc.lng]).addTo(map);
            marker.bindPopup(`<strong>${loc.name}</strong><br>Type: ${loc.type}`);
        });

        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>
