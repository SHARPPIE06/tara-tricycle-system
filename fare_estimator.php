<?php
// fare_estimator.php
require_once 'php/session_init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'User';
$initials = strtoupper(substr($username, 0, 1));
$role = $_SESSION['role'] ?? 'user';

require_once 'php/db_connect.php';

// Fetch all routes for dropdown
$routes = $conn->query("SELECT id, toda_name, base_fare, per_km_fare, terminal_lat, terminal_lng FROM routes ORDER BY toda_name ASC");
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
    <title>Fare Estimator - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <style>
        .fare-result-box {
            background: rgba(255, 200, 30, 0.15);
            border: 1px solid var(--yellow);
            border-radius: var(--border-radius);
            padding: 20px;
            text-align: center;
            margin-top: 20px;
            display: none;
        }
        .fare-result-box h4 { font-size: 0.9rem; color: #555; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .fare-result-box .fare-amount { font-size: 2.5rem; font-weight: 800; color: var(--navy); }
    </style>
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
                
                <?php if ($role === 'user'): ?>
                <a href="fare_estimator.php" class="nav-link active" id="navFare">
                    <span class="nav-icon">💰</span> Fare Estimator
                </a>
                <?php else: ?>
                <span class="nav-section-title">Management</span>
                <a href="manage_routes.php" class="nav-link" id="navRoutesMgmt">
                    <span class="nav-icon">🛤️</span> Routes & TODA
                </a>
                <a href="fare_estimator.php" class="nav-link active" id="navFare">
                    <span class="nav-icon">💰</span> Fare Estimator
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
                    <h2>Fare Estimator</h2>
                </div>
                <div class="user-info">
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                        <div class="user-role"><?php echo ucfirst($role); ?></div>
                    </div>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </header>

            <div class="page-content">
                <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
                    
                    <!-- Fare Form -->
                    <div class="content-card" style="margin-bottom:0;">
                        <div class="card-header">
                            <h3>💰 Calculate Fare</h3>
                        </div>
                        <div class="card-body">
                            <form id="fareForm">
                                <div class="form-group">
                                    <label for="route_select">Select TODA / Route</label>
                                    <select id="route_select" class="form-control" required>
                                        <option value="">-- Choose a TODA / Terminal --</option>
                                        <?php foreach($routesData as $route): ?>
                                            <option value="<?php echo $route['id']; ?>" 
                                                    data-base="<?php echo $route['base_fare']; ?>" 
                                                    data-perkm="<?php echo $route['per_km_fare']; ?>"
                                                    data-lat="<?php echo $route['terminal_lat']; ?>"
                                                    data-lng="<?php echo $route['terminal_lng']; ?>">
                                                <?php echo htmlspecialchars($route['toda_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="distance_km">Distance (Kilometers)</label>
                                    <input type="number" step="0.1" min="0" id="distance_km" class="form-control" placeholder="e.g. 2.5">
                                    <small style="color:#666; margin-top:5px; display:block;">Enter distance manually, or click on the map to calculate distance from terminal.</small>
                                </div>
                                
                                <button type="button" id="calcBtn" class="btn btn-primary auth-btn" style="width:100%; margin-top:10px;">Calculate Fare</button>
                            </form>

                            <div class="fare-result-box" id="fareResultBox">
                                <h4>Estimated Fare</h4>
                                <div class="fare-amount" id="fareAmount">₱0.00</div>
                                <p style="font-size:0.8rem; color:#666; margin-top:10px;" id="fareBreakdown"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Map -->
                    <div class="content-card" style="margin-bottom:0;">
                        <div class="card-header">
                            <h3>🗺️ Select Destination</h3>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <div id="map" style="height: 450px;"></div>
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
            // Sidebar toggle
            document.getElementById('sidebarToggle')?.addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('open');
            });

            // Map Init
            const map = L.map("map").setView([14.5995, 121.1023], 13);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "© OpenStreetMap contributors"
            }).addTo(map);

            let terminalMarker = null;
            let destMarker = null;
            let routeLine = null;

            const routeSelect = document.getElementById('route_select');
            const distanceInput = document.getElementById('distance_km');
            const calcBtn = document.getElementById('calcBtn');
            const fareResultBox = document.getElementById('fareResultBox');
            const fareAmount = document.getElementById('fareAmount');
            const fareBreakdown = document.getElementById('fareBreakdown');

            // Haversine formula to calculate distance in km
            function calculateDistance(lat1, lon1, lat2, lon2) {
                const R = 6371; // Earth's radius in km
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                        Math.sin(dLon/2) * Math.sin(dLon/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return R * c;
            }

            // Draw line between terminal and destination
            function drawRoute() {
                if(routeLine) map.removeLayer(routeLine);
                if(terminalMarker && destMarker) {
                    const latlngs = [terminalMarker.getLatLng(), destMarker.getLatLng()];
                    routeLine = L.polyline(latlngs, {color: '#E87F24', weight: 4, dashArray: '5, 10'}).addTo(map);
                    map.fitBounds(routeLine.getBounds(), {padding: [50, 50]});
                }
            }

            // When a TODA is selected, place its marker on the map
            routeSelect.addEventListener('change', function() {
                if (terminalMarker) map.removeLayer(terminalMarker);
                if (routeLine) map.removeLayer(routeLine);
                if (destMarker) map.removeLayer(destMarker);
                distanceInput.value = '';
                fareResultBox.style.display = 'none';

                const selectedOption = this.options[this.selectedIndex];
                if (!selectedOption.value) return;

                const lat = parseFloat(selectedOption.getAttribute('data-lat'));
                const lng = parseFloat(selectedOption.getAttribute('data-lng'));

                terminalMarker = L.marker([lat, lng]).addTo(map);
                terminalMarker.bindPopup(`<strong>Terminal: ${selectedOption.text}</strong>`).openPopup();
                map.setView([lat, lng], 14);
            });

            // Map click handler - sets destination
            map.on('click', function(e) {
                if (!terminalMarker) {
                    alert("Please select a TODA/Terminal first.");
                    return;
                }

                if (destMarker) map.removeLayer(destMarker);
                destMarker = L.marker(e.latlng, {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
                    })
                }).addTo(map);
                destMarker.bindPopup('<strong>Destination</strong>').openPopup();

                const tLatLng = terminalMarker.getLatLng();
                const distKm = calculateDistance(tLatLng.lat, tLatLng.lng, e.latlng.lat, e.latlng.lng);
                
                distanceInput.value = distKm.toFixed(2);
                drawRoute();
            });

            // Calculate Fare Logic
            calcBtn.addEventListener('click', () => {
                const selectedOption = routeSelect.options[routeSelect.selectedIndex];
                if (!selectedOption.value) {
                    alert("Please select a TODA/Terminal.");
                    return;
                }

                const dist = parseFloat(distanceInput.value);
                if (isNaN(dist) || dist <= 0) {
                    alert("Please enter a valid distance or click on the map.");
                    return;
                }

                const baseFare = parseFloat(selectedOption.getAttribute('data-base'));
                const perKmFare = parseFloat(selectedOption.getAttribute('data-perkm'));

                // Basic Logic: Base fare covers first 1km.
                let totalFare = baseFare;
                let extraKm = 0;
                
                if (dist > 1) {
                    extraKm = dist - 1;
                    totalFare += (extraKm * perKmFare);
                }

                fareAmount.textContent = `₱${totalFare.toFixed(2)}`;
                fareBreakdown.textContent = `Base Fare: ₱${baseFare.toFixed(2)} (1st KM) + ₱${(extraKm * perKmFare).toFixed(2)} (${extraKm.toFixed(2)} KM extra)`;
                fareResultBox.style.display = 'block';
            });
        });
    </script>
</body>
</html>
