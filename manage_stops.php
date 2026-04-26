<?php
// manage_stops.php — Admin Stop Management
require_once 'php/session_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'php/db_connect.php';

$route_id = $_GET['route_id'] ?? 0;
if ($route_id == 0) {
    header("Location: manage_routes.php");
    exit();
}

// Fetch route info
$stmt = $conn->prepare("SELECT toda_name FROM routes WHERE id = ?");
$stmt->execute([$route_id]);
$route = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = null;

if (!$route) {
    header("Location: manage_routes.php?error=Route not found");
    exit();
}

// Fetch existing stops
$stmt = $conn->prepare("SELECT * FROM stops WHERE route_id = ? ORDER BY id ASC");
$stmt->execute([$route_id]);
$stops = $stmt;

$username = $_SESSION['username'] ?? 'Admin';
$initials = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Stops - TARA Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <style>
        .map-picker { height: 350px; border-radius: var(--border-radius); border: 1px solid #ddd; margin-bottom: 15px; }
    </style>
    <link rel="icon" type="image/png" href="assets/icon.png"></head>
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
                    <span class="nav-icon">&#x1F4CA;</span> Dashboard
                </a>

                <span class="nav-section-title">User Account Management</span>
                <a href="manage_users.php" class="nav-link" id="navUsers">
                    <span class="nav-icon">&#x1F465;</span> Manage Users
                </a>

                <span class="nav-section-title">Routes &amp; Fare Management</span>
                <a href="manage_routes.php" class="nav-link" id="navRoutesMgmt">
                    <span class="nav-icon">&#x1F6E4;&#xFE0F;</span> Manage Routes
                </a>

                <span class="nav-section-title">TODAs &amp; Terminals</span>
                <a href="manage_stops.php" class="nav-link active" id="navTODAMgmt">
                    <span class="nav-icon">&#x1F3E2;</span> Add TODAs &amp; Terminals
                </a>

                <span class="nav-section-title">Analytics</span>
                <a href="route_map.php" class="nav-link" id="navMapAdmin">
                    <span class="nav-icon">&#x1F5FA;&#xFE0F;</span> Map Overview
                </a>
                <a href="feedback.php" class="nav-link" id="navFeedback">
                    <span class="nav-icon">&#x1F4AC;</span> Feedback
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="php/logout.php" class="nav-link">
                    <span class="nav-icon">🚪</span> Logout
                </a>
            </div>
        </aside>

        <div class="main-content">
            <header class="top-bar">
                <div style="display:flex;align-items:center;gap:12px;">
                    <a href="manage_routes.php" style="text-decoration:none; color:var(--navy); font-size:1.5rem;">←</a>
                    <h2>Stops for <?php echo htmlspecialchars($route['toda_name']); ?></h2>
                </div>
            </header>

            <div class="page-content">
                <?php 
                if(isset($_GET['success'])) echo "<div class='alert success' style='display:block'>".htmlspecialchars($_GET['success'])."</div>";
                if(isset($_GET['error'])) echo "<div class='alert error' style='display:block'>".htmlspecialchars($_GET['error'])."</div>";
                ?>

                <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
                    <!-- Add Stop Form -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>📍 Add New Stop</h3>
                        </div>
                        <div class="card-body">
                            <form action="php/route_action.php" method="POST">
                                <input type="hidden" name="action" value="add_stop">
                                <input type="hidden" name="route_id" value="<?php echo $route_id; ?>">
                                <input type="hidden" id="stop_lat" name="lat" required>
                                <input type="hidden" id="stop_lng" name="lng" required>
                                
                                <div class="form-group">
                                    <label>Stop Name</label>
                                    <input type="text" name="stop_name" class="form-control" required placeholder="e.g. Near Shell Station">
                                </div>

                                <div id="mapPicker" class="map-picker"></div>
                                <p><small id="coordDisplay">Click map to pick stop location</small></p>
                                
                                <button type="submit" class="btn btn-primary auth-btn">Add Stop</button>
                            </form>
                        </div>
                    </div>

                    <!-- Stops List -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>Existing Stops</h3>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Coordinates</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($stops->rowCount() > 0): ?>
                                        <?php while ($row = $stops->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['stop_name']); ?></td>
                                            <td><small><?php echo number_format($row['lat'], 4) . ', ' . number_format($row['lng'], 4); ?></small></td>
                                            <td>
                                                <form action="php/route_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this stop?');">
                                                    <input type="hidden" name="action" value="delete_stop">
                                                    <input type="hidden" name="stop_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="route_id" value="<?php echo $route_id; ?>">
                                                    <button type="submit" class="btn" style="background:#ff4d4d; color:white; padding:4px 10px; border-radius:4px; font-size:0.75rem; border:none; cursor:pointer;">Del</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center; padding:20px;">No stops added yet.</td>
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

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const map = L.map("mapPicker").setView([14.5995, 121.1023], 13);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "© OpenStreetMap"
            }).addTo(map);

            let marker;
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                document.getElementById('stop_lat').value = lat;
                document.getElementById('stop_lng').value = lng;
                document.getElementById('coordDisplay').innerText = `Selected: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                
                if (marker) map.removeLayer(marker);
                marker = L.marker([lat, lng]).addTo(map);
            });
        });
    </script>
</body>
</html>
