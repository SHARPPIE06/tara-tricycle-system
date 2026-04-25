<?php
// route_map.php
require_once 'php/session_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'User';
$initials = strtoupper(substr($username, 0, 1));
$role = $_SESSION['role'] ?? 'user';

require_once 'php/db_connect.php';

// Fetch all routes
$routes = $conn->query("SELECT id, toda_name, terminal_lat, terminal_lng, base_fare, per_km_fare FROM routes ORDER BY toda_name ASC");
$routesData = [];
while ($row = $routes->fetch_assoc()) {
    $routesData[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Map - TARA</title>
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
                <p><?php echo $role === 'admin' ? 'Admin Panel' : 'Commuter Portal'; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <span class="nav-section-title">Main</span>
                <a href="<?php echo $role === 'admin' ? 'dashboard_admin.php' : 'dashboard_user.php'; ?>" class="nav-link" id="navDashboard">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="route_map.php" class="nav-link active" id="navMap">
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
                <a href="profile.php" class="nav-link" id="navProfile">
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

        <!-- Main Content -->
        <div class="main-content">
            <header class="top-bar">
                <div style="display:flex;align-items:center;gap:12px;">
                    <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                    <h2>Full Route Map</h2>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </header>

            <div class="page-content" style="height: calc(100vh - 70px); padding: 20px;">
                <div class="content-card" style="height: 100%; margin: 0; display: flex; flex-direction: column;">
                    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <h3>🗺️ Interactive Map</h3>
                        <select id="routeFilter" class="form-control" style="width: 250px; padding: 6px 12px;">
                            <option value="all">Show All Terminals</option>
                            <?php foreach($routesData as $route): ?>
                                <option value="<?php echo $route['id']; ?>" data-lat="<?php echo $route['terminal_lat']; ?>" data-lng="<?php echo $route['terminal_lng']; ?>">
                                    <?php echo htmlspecialchars($route['toda_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="card-body" style="padding:0; flex: 1;">
                        <div id="fullMap" style="height: 100%; width: 100%; border-bottom-left-radius: var(--border-radius-lg); border-bottom-right-radius: var(--border-radius-lg);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const map = L.map("fullMap").setView([14.5995, 121.1023], 13);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "© OpenStreetMap contributors"
            }).addTo(map);

            const routesData = <?php echo json_encode($routesData); ?>;
            const markers = {};

            // Add all markers
            routesData.forEach(route => {
                const lat = parseFloat(route.terminal_lat);
                const lng = parseFloat(route.terminal_lng);
                
                if (lat && lng) {
                    const marker = L.marker([lat, lng]).addTo(map);
                    marker.bindPopup(`
                        <div style="text-align:center;">
                            <h4 style="margin:0 0 5px 0;">${route.toda_name}</h4>
                            <p style="margin:0; font-size:0.85rem; color:#666;">Base Fare: ₱${route.base_fare}</p>
                            <a href="fare_estimator.php" style="display:inline-block; margin-top:8px; background:#080E31; color:white; padding:4px 10px; border-radius:4px; text-decoration:none; font-size:0.75rem;">Estimate Fare</a>
                        </div>
                    `);
                    markers[route.id] = marker;
                }
            });

            // Filter dropdown
            document.getElementById('routeFilter').addEventListener('change', function() {
                const val = this.value;
                if (val === 'all') {
                    map.setView([14.5995, 121.1023], 13);
                    Object.values(markers).forEach(m => map.addLayer(m));
                } else {
                    const option = this.options[this.selectedIndex];
                    const lat = parseFloat(option.getAttribute('data-lat'));
                    const lng = parseFloat(option.getAttribute('data-lng'));
                    
                    Object.values(markers).forEach(m => map.removeLayer(m));
                    
                    if (markers[val]) {
                        map.addLayer(markers[val]);
                        markers[val].openPopup();
                        map.setView([lat, lng], 15);
                    }
                }
            });

            document.getElementById('sidebarToggle')?.addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('open');
            });
        });
    </script>
</body>
</html>
