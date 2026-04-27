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
while ($row = $routes->fetch(PDO::FETCH_ASSOC)) {
    $routesData[] = $row;
}

// Automatic discount logic
require_once 'php/discount_helper.php';
$hasDiscount = userQualifiesForDiscount();
$classifications = $_SESSION['classifications'] ?? [];
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
    <!-- Leaflet Routing Machine CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <link rel="icon" type="image/png" href="assets/icon.png">
    <style>
        .leaflet-routing-geocoders { display: none !important; }
        .leaflet-routing-container { max-height: 50vh; overflow-y: auto; background: var(--white); padding: 10px; border-radius: var(--border-radius); box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-top: 10px !important; }
        
        @media (max-width: 768px) {
            .card-body { display: flex !important; flex-direction: column !important; }
            .custom-routing-panel { position: relative !important; top: auto !important; left: auto !important; width: 100% !important; max-width: 100% !important; border-radius: 0 !important; border-bottom: 1px solid #eee; box-shadow: none !important; margin-bottom: 0 !important; padding: 15px !important; }
            #fullMap { flex: 1 !important; height: auto !important; min-height: 40vh !important; }
            .leaflet-routing-container { max-height: 25vh !important; }
        }
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
                <a href="route_map.php" class="nav-link active" id="navMap">
                    <span class="nav-icon">🗺️</span> Route Map
                </a>
                <a href="fare_estimator.php" class="nav-link" id="navFare">
                    <span class="nav-icon">💰</span> Fare Estimator
                </a>
                
                <?php if ($role === 'user'): ?>
                <span class="nav-section-title">Search</span>
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
                <?php else: ?>
                <span class="nav-section-title">User Account Management</span>
                <a href="manage_users.php?type=commuter" class="nav-link" id="navCommuters">
                    <span class="nav-icon">👥</span> Manage Commuters
                </a>
                <a href="manage_users.php?type=driver" class="nav-link" id="navDrivers">
                    <span class="nav-icon">🚗</span> Manage Drivers
                </a>
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
                                    <?php echo htmlspecialchars($route['toda_name'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="card-body" style="padding:0; flex: 1; position: relative; display: flex; flex-direction: column;">
                        <!-- Custom Routing Panel -->
                        <div class="custom-routing-panel" style="position: absolute; top: 15px; left: 15px; z-index: 1000; background: var(--white); padding: 20px; border-radius: var(--border-radius-lg); box-shadow: 0 4px 15px rgba(0,0,0,0.15); width: 320px; max-width: 90%;">
                            <h4 style="color: var(--navy); margin-bottom: 15px; display:flex; align-items:center; gap:8px;"><span>🔍</span> Route Planner</h4>
                            <div class="form-group" style="position: relative; margin-bottom:12px;">
                                <label style="font-size:0.75rem; font-weight:600; color:#666; display:block; margin-bottom:4px;">Start Location</label>
                                <input type="text" id="customStart" class="form-control" placeholder="e.g. My Location or Morong" style="padding-right: 40px; font-size: 0.85rem;">
                                <button id="btnMyLocation" style="position: absolute; right: 5px; top: 25px; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--orange);" title="Use My Location">📍</button>
                            </div>
                            <div class="form-group" style="margin-bottom:16px;">
                                <label style="font-size:0.75rem; font-weight:600; color:#666; display:block; margin-bottom:4px;">Destination</label>
                                <input type="text" id="customEnd" class="form-control" placeholder="e.g. Sagbat" style="font-size: 0.85rem;">
                            </div>
                            <button id="btnSearchCustom" class="btn btn-primary" style="width: 100%; padding:10px; font-weight:600;">Get Directions</button>
                            <div id="routingStatus" style="font-size:0.8rem; margin-top:10px; text-align:center; display:none; padding: 6px; border-radius: 4px;"></div>
                            
                            <!-- Fare Result Display -->
                            <div id="fareEstimateDisplay" style="margin-top: 15px; padding: 12px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; display: none;">
                                <div style="font-size: 0.75rem; color: #0369a1; font-weight: 700; text-transform: uppercase;">Estimated Fare</div>
                                <div id="fareValue" style="font-size: 1.5rem; font-weight: 800; color: var(--navy); margin-top: 2px;">₱0.00</div>
                                <div id="fareDistance" style="font-size: 0.75rem; color: #666; margin-top: 2px;">Distance: 0.0 km</div>
                                <?php if ($hasDiscount): ?>
                                <div style="margin-top: 5px; font-size: 0.7rem; color: #15803d; font-weight: 700;">✨ 20% Discount Applied</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="fullMap" style="height: 100%; width: 100%; border-bottom-left-radius: var(--border-radius-lg); border-bottom-right-radius: var(--border-radius-lg);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <!-- Leaflet Routing Machine JS -->
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const map = L.map("fullMap", { zoomControl: false }).setView([14.5995, 121.1023], 13);
            L.control.zoom({ position: 'bottomright' }).addTo(map);

            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "© OpenStreetMap contributors"
            }).addTo(map);

            // Add Routing Control (Without geocoder UI)
            var routingControl = L.Routing.control({
                waypoints: [],
                routeWhileDragging: true,
                addWaypoints: false,
                lineOptions: {
                    styles: [{color: '#2563eb', opacity: 0.8, weight: 6}]
                }
            }).addTo(map);

            let userLatLng = null;
            const hasDiscount = <?php echo $hasDiscount ? 'true' : 'false'; ?>;
            const routesData = <?php echo json_encode($routesData); ?>;

            // Calculate fare based on distance and rates
            routingControl.on('routesfound', function(e) {
                const routes = e.routes;
                const summary = routes[0].summary;
                const distanceKm = summary.totalDistance / 1000;
                
                // Rates (Default or from selected TODA)
                let baseFare = 15.00;
                let perKmRate = 2.00;
                
                const filter = document.getElementById('routeFilter');
                if (filter.value !== 'all') {
                    const routeId = filter.value;
                    const r = routesData.find(x => x.id == routeId);
                    if (r) {
                        baseFare = parseFloat(r.base_fare);
                        perKmRate = parseFloat(r.per_km_fare);
                    }
                }
                
                let totalFare = baseFare;
                if (distanceKm > 1) {
                    totalFare += (distanceKm - 1) * perKmRate;
                }
                
                if (hasDiscount) {
                    totalFare = totalFare * 0.8;
                }
                
                const fareDisplay = document.getElementById('fareEstimateDisplay');
                if (fareDisplay) {
                    fareDisplay.style.display = 'block';
                    document.getElementById('fareValue').textContent = '₱' + totalFare.toFixed(2);
                    document.getElementById('fareDistance').textContent = 'Distance: ' + distanceKm.toFixed(2) + ' km';
                }
            });

            // Geocode function with 'Morong, Rizal' viewbox to guarantee accuracy
            function geocodeLocation(query) {
                let sq = query.toLowerCase().trim();
                
                // Manual Overrides for accuracy (Places that geocoding often misses)
                const overrides = {
                    "rizal provincial hospital": { lat: 14.5165896, lng: 121.239384 },
                    "morong hospital": { lat: 14.5165896, lng: 121.239384 },
                    "provincial hospital": { lat: 14.5165896, lng: 121.239384 }
                };

                for (let key in overrides) {
                    if (sq.includes(key)) {
                        return Promise.resolve(L.latLng(overrides[key].lat, overrides[key].lng));
                    }
                }

                let searchStr = query;
                if (!searchStr.toLowerCase().includes('morong') && !searchStr.toLowerCase().includes('rizal')) {
                    searchStr += ' Morong'; // Force Morong if it's a generic term like "Savemore"
                }
                if (!searchStr.toLowerCase().includes('rizal')) {
                    searchStr += ', Rizal, Philippines';
                }
                
                // Add viewbox strongly biased towards Morong area
                const viewbox = "121.20,14.55,121.28,14.48";
                const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(searchStr)}&viewbox=${viewbox}&bounded=0`;
                
                return fetch(url)
                .then(res => res.json())
                .then(data => {
                    if(data.length > 0) return L.latLng(data[0].lat, data[0].lon);
                    throw new Error('Location not found: ' + query);
                });
            }

            // My Location Button
            document.getElementById('btnMyLocation').addEventListener('click', (e) => {
                e.preventDefault();
                const status = document.getElementById('routingStatus');
                status.style.display = 'block';
                status.style.background = '#fef9c3';
                status.style.color = '#a16207';
                status.textContent = "Locating you...";
                
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(pos => {
                        userLatLng = L.latLng(pos.coords.latitude, pos.coords.longitude);
                        document.getElementById('customStart').value = "My Location";
                        status.style.display = 'block';
                        status.style.background = '#dcfce7';
                        status.style.color = '#15803d';
                        status.textContent = "Location found!";
                        map.setView(userLatLng, 15);
                        setTimeout(() => { status.style.display = 'none'; }, 2000);
                    }, err => {
                        status.style.display = 'block';
                        status.style.background = '#fee2e2';
                        status.style.color = '#b91c1c';
                        status.textContent = "Please allow location access.";
                    });
                } else {
                    status.style.display = 'block';
                    status.style.background = '#fee2e2';
                    status.style.color = '#b91c1c';
                    status.textContent = "Geolocation not supported.";
                }
            });

            // Custom Search Route Logic
            document.getElementById('btnSearchCustom').addEventListener('click', async (e) => {
                e.preventDefault();
                const startVal = document.getElementById('customStart').value.trim();
                const endVal = document.getElementById('customEnd').value.trim();
                const status = document.getElementById('routingStatus');

                if (!startVal || !endVal) {
                    status.style.display = 'block';
                    status.style.background = '#fee2e2';
                    status.style.color = '#b91c1c';
                    status.textContent = "Please enter Start and Destination.";
                    return;
                }

                status.style.display = 'block';
                status.style.background = '#fef9c3';
                status.style.color = '#a16207';
                status.textContent = "Searching route...";

                try {
                    let startPoint = (userLatLng && startVal.toLowerCase() === 'my location') ? userLatLng : await geocodeLocation(startVal);
                    let endPoint = await geocodeLocation(endVal);

                    routingControl.setWaypoints([startPoint, endPoint]);
                    status.style.display = 'block';
                    status.style.background = '#dcfce7';
                    status.style.color = '#15803d';
                    status.textContent = "Route found!";
                    setTimeout(() => { status.style.display = 'none'; }, 3000);
                } catch (err) {
                    status.style.display = 'block';
                    status.style.background = '#fee2e2';
                    status.style.color = '#b91c1c';
                    status.textContent = err.message;
                }
            });


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
                const status = document.getElementById('routingStatus');

                if (val === 'all') {
                    map.setView([14.5995, 121.1023], 13);
                    Object.values(markers).forEach(m => map.addLayer(m));
                    routingControl.setWaypoints([]); // Clear route
                    document.getElementById('fareEstimateDisplay').style.display = 'none';
                } else {
                    const option = this.options[this.selectedIndex];
                    const lat = parseFloat(option.getAttribute('data-lat'));
                    const lng = parseFloat(option.getAttribute('data-lng'));
                    const todaName = option.textContent.trim(); // e.g. "Sagbat to URS Morong"
                    
                    Object.values(markers).forEach(m => map.removeLayer(m));
                    
                    if (markers[val]) {
                        map.addLayer(markers[val]);
                        markers[val].openPopup();
                        map.setView([lat, lng], 15);

                        // Extract destination and map the route automatically
                        let parts = todaName.split(/ to /i);
                        if (parts.length > 1) {
                            let destinationStr = parts[1].trim();
                            
                            status.style.display = 'block';
                            status.style.background = '#fef9c3';
                            status.style.color = '#a16207';
                            status.textContent = "Drawing route for " + todaName + "...";
                            
                            let startPoint = L.latLng(lat, lng);
                            
                            geocodeLocation(destinationStr).then(endPoint => {
                                routingControl.setWaypoints([startPoint, endPoint]);
                                status.style.display = 'block';
                                status.style.background = '#dcfce7';
                                status.style.color = '#15803d';
                                status.textContent = "Route displayed!";
                                setTimeout(() => { status.style.display = 'none'; }, 3000);
                            }).catch(err => {
                                status.style.display = 'block';
                                status.style.background = '#fee2e2';
                                status.style.color = '#b91c1c';
                                status.textContent = "Could not map destination exactly.";
                                setTimeout(() => { status.style.display = 'none'; }, 3000);
                            });
                        } else {
                            routingControl.setWaypoints([]); // Clear if it's just a point
                        }
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
