<?php
// manage_routes.php — Admin Route Management
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Admin';
$initials = strtoupper(substr($username, 0, 1));

require_once 'php/db_connect.php';

// Fetch all routes
$routes = $conn->query("SELECT * FROM routes ORDER BY toda_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes - TARA Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <style>
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-row .form-group { flex: 1; margin-bottom: 0; }
        .map-picker { height: 300px; border-radius: var(--border-radius); border: 1px solid #ddd; margin-bottom: 15px; }
    </style>
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
                <a href="dashboard_admin.php" class="nav-link" id="navDashboard">
                    <span class="nav-icon">📊</span> Dashboard
                </a>

                <span class="nav-section-title">Management</span>
                <a href="#" class="nav-link" id="navUsers">
                    <span class="nav-icon">👥</span> Users
                </a>
                <a href="manage_routes.php" class="nav-link active" id="navRoutesMgmt">
                    <span class="nav-icon">🛤️</span> Routes & TODA
                </a>
                
                <span class="nav-section-title">Analytics</span>
                <a href="#" class="nav-link" id="navReports">
                    <span class="nav-icon">📈</span> Reports
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
                    <h2>Manage Routes & TODA</h2>
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
                
                <div id="actionAlert" class="alert"></div>

                <div class="stats-grid" style="grid-template-columns: 1fr 2fr;">
                    <!-- Add New Route Form -->
                    <div class="content-card" style="margin-bottom:0;">
                        <div class="card-header">
                            <h3>➕ Add New TODA / Route</h3>
                        </div>
                        <div class="card-body">
                            <form action="php/route_action.php" method="POST" id="routeForm">
                                <input type="hidden" name="action" value="add_route">
                                
                                <div class="form-group">
                                    <label for="toda_name">TODA / Terminal Name</label>
                                    <input type="text" id="toda_name" name="toda_name" class="form-control" required placeholder="e.g. Antipolo Public Market TODA">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="base_fare">Base Fare (₱)</label>
                                        <input type="number" step="0.5" id="base_fare" name="base_fare" class="form-control" required placeholder="e.g. 15.00">
                                    </div>
                                    <div class="form-group">
                                        <label for="per_km_fare">Per Km Fare (₱)</label>
                                        <input type="number" step="0.5" id="per_km_fare" name="per_km_fare" class="form-control" required placeholder="e.g. 2.00">
                                    </div>
                                </div>

                                <label style="font-weight:600; font-size:0.95rem; display:block; margin-bottom:8px;">Pick Terminal Location on Map</label>
                                <div id="mapPicker" class="map-picker"></div>
                                
                                <input type="hidden" id="terminal_lat" name="terminal_lat" required>
                                <input type="hidden" id="terminal_lng" name="terminal_lng" required>
                                
                                <button type="submit" class="btn btn-secondary auth-btn" style="width:100%; margin-top:10px;">Save Route</button>
                            </form>
                        </div>
                    </div>

                    <!-- Routes List -->
                    <div class="content-card" style="margin-bottom:0;">
                        <div class="card-header">
                            <h3>🛤️ Existing Routes</h3>
                        </div>
                        <div class="card-body" style="padding:0; overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>TODA Name</th>
                                        <th>Base Fare</th>
                                        <th>Per Km</th>
                                        <th>Coordinates</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($routes->num_rows > 0): ?>
                                        <?php while ($row = $routes->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['toda_name']); ?></td>
                                            <td>₱<?php echo number_format($row['base_fare'], 2); ?></td>
                                            <td>₱<?php echo number_format($row['per_km_fare'], 2); ?></td>
                                            <td style="font-size:0.75rem; color:#666;">
                                                <?php echo number_format($row['terminal_lat'], 4) . ', ' . number_format($row['terminal_lng'], 4); ?>
                                            </td>
                                            <td>
                                                <!-- Action to add stops (Phase 2 expansion) -->
                                                <a href="#" class="btn btn-primary" style="padding:4px 10px; font-size:0.75rem; border-radius:4px;">Stops</a>
                                                <form action="php/route_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this route?');">
                                                    <input type="hidden" name="action" value="delete_route">
                                                    <input type="hidden" name="route_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-secondary" style="padding:4px 10px; font-size:0.75rem; border-radius:4px; background:#ef4444; color:white;">Del</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="text-align:center; padding:20px;">No routes found. Create one to get started.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Check for URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const errorMsg = urlParams.get('error');
            const successMsg = urlParams.get('success');
            const actionAlert = document.getElementById('actionAlert');

            if (errorMsg) {
                actionAlert.textContent = errorMsg;
                actionAlert.className = 'alert error';
            } else if (successMsg) {
                actionAlert.textContent = successMsg;
                actionAlert.className = 'alert success';
            }

            // Map Picker Init
            const mapPicker = L.map("mapPicker").setView([14.5995, 121.1023], 13); // Center to Rizal
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "© OpenStreetMap contributors"
            }).addTo(mapPicker);

            let marker;

            mapPicker.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                
                document.getElementById('terminal_lat').value = lat;
                document.getElementById('terminal_lng').value = lng;

                if (marker) {
                    mapPicker.removeLayer(marker);
                }
                marker = L.marker([lat, lng]).addTo(mapPicker);
            });

            // Sidebar toggle
            document.getElementById('sidebarToggle')?.addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('open');
            });
        });
    </script>
</body>
</html>
