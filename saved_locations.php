<?php
// saved_locations.php
require_once 'php/session_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$initials = strtoupper(substr($username, 0, 1));
$role = $_SESSION['role'] ?? 'user';

require_once 'php/db_connect.php';

$saved = $conn->prepare("SELECT * FROM saved_locations WHERE user_id = ? ORDER BY created_at DESC");
$saved->execute([$user_id]);
$result = $saved;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Locations - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <link rel="icon" type="image/png" href="assets/icon.png"></head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>TARA</h1>
                <p>Commuter Portal</p>
            </div>
            
            <nav class="sidebar-nav">
                <span class="nav-section-title">Main</span>
                <a href="dashboard_user.php" class="nav-link" id="navDashboard">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="route_map.php" class="nav-link" id="navMap">
                    <span class="nav-icon">🗺️</span> Route Map
                </a>
                <a href="fare_estimator.php" class="nav-link" id="navFare">
                    <span class="nav-icon">💰</span> Fare Estimator
                </a>
                
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
                <a href="saved_locations.php" class="nav-link active" id="navSaved">
                    <span class="nav-icon">📌</span> Saved Locations
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
                    <h2>Saved Locations</h2>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </header>

            <div class="page-content">
                <?php 
                if(isset($_GET['success'])) echo "<div class='alert success' style='display:block'>".htmlspecialchars($_GET['success'])."</div>";
                if(isset($_GET['error'])) echo "<div class='alert error' style='display:block'>".htmlspecialchars($_GET['error'])."</div>";
                ?>
                <div class="stats-grid" style="grid-template-columns: 1fr 2fr;">
                    <!-- Add Location -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>📌 Add Location</h3>
                        </div>
                        <div class="card-body">
                            <form action="php/saved_action.php" method="POST">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="lat" id="formLat">
                                <input type="hidden" name="lng" id="formLng">
                                <div class="form-group">
                                    <label>Location Label (e.g., Home, Work)</label>
                                    <input type="text" name="label" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Pick Location on Map</label>
                                    <div id="miniMap" style="height: 200px; border-radius: var(--border-radius); border: 1px solid #ccc; margin-bottom: 15px;"></div>
                                    <small id="coordDisplay">Click map to select coordinates</small>
                                </div>
                                <button type="submit" class="btn btn-primary auth-btn">Save Location</button>
                            </form>
                        </div>
                    </div>

                    <!-- My Locations -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>My Saved Places</h3>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Label</th>
                                        <th>Coordinates</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($result->rowCount() > 0): ?>
                                        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td style="font-weight:600;"><?php echo htmlspecialchars($row['label']); ?></td>
                                            <td><small><?php echo htmlspecialchars($row['lat']) . ', ' . htmlspecialchars($row['lng']); ?></small></td>
                                            <td>
                                                <form action="php/saved_action.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn" style="background:#ff4d4d; color:white; padding:4px 10px; border-radius:4px; font-size:0.75rem; border:none; cursor:pointer;" onclick="return confirm('Delete this location?')">Delete</button>
                                                </form>
                                                <a href="fare_estimator.php?lat=<?php echo $row['lat']; ?>&lng=<?php echo $row['lng']; ?>" class="btn btn-primary" style="padding:4px 10px; font-size:0.75rem; border-radius:4px;">Estimate Fare Here</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center; padding:20px;">No saved locations yet.</td>
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
            const map = L.map("miniMap").setView([14.5995, 121.1023], 13);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "© OpenStreetMap"
            }).addTo(map);

            let marker;
            map.on('click', function(e) {
                const lat = e.latlng.lat.toFixed(6);
                const lng = e.latlng.lng.toFixed(6);
                
                if (marker) map.removeLayer(marker);
                marker = L.marker([lat, lng]).addTo(map);
                
                document.getElementById('formLat').value = lat;
                document.getElementById('formLng').value = lng;
                document.getElementById('coordDisplay').innerText = `Selected: ${lat}, ${lng}`;
            });

            document.getElementById('sidebarToggle')?.addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('open');
            });
        });
    </script>
</body>
</html>
