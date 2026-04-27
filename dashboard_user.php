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
$userStatus = $_SESSION['status'] ?? 'pending';
$isVerified = ($userStatus === 'verified');
$classifications = $_SESSION['classifications'] ?? [];
$isDriver = false;
if (is_array($classifications)) {
    if (in_array('Driver', $classifications)) $isDriver = true;
}

// Strict Separation: Drivers go to Driver Portal, Commuters stay here
if ($isDriver) {
    header("Location: dashboard_driver.php");
    exit();
}

// Redirect admins to admin dashboard
if ($role === 'admin') {
    header("Location: dashboard_admin.php");
    exit();
}

// Get user initials for avatar
$initials = strtoupper(substr($username, 0, 1));

require_once 'php/db_connect.php';

// Fetch verified drivers using PostgreSQL JSONB containment operator
$driversQuery = $conn->query("SELECT id, first_name, last_name, username FROM users WHERE status = 'verified' AND classifications @> '[\"Driver\"]'::jsonb");
$verifiedDrivers = $driversQuery->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="icon" type="image/png" href="assets/icon.png"></head>
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
                <a href="route_map.php" class="nav-link" id="navMap">
                    <span class="nav-icon">🗺️</span> Route Map
                </a>
                <a href="fare_estimator.php" class="nav-link" id="navFare">
                    <span class="nav-icon">💰</span> Fare Estimator
                </a>
                <a href="feedback.php" class="nav-link" id="navFeedback">
                    <span class="nav-icon">⭐</span> Rate a Driver
                </a>
                
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
                <?php if (!$isVerified): ?>
                <div style="background: #fef9c3; border: 1px solid #fde68a; border-radius: 12px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span style="font-size: 1.3rem;">⏳</span>
                    <div>
                        <strong style="color: #a16207;">Account Pending Verification</strong>
                        <p style="font-size: 0.82rem; color: #92400e; margin-top: 2px;">Your account is under review. You can search routes and view fare charts, but Driver Rating & Feedback is locked until verified.</p>
                    </div>
                </div>
                <?php endif; ?>
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
                            <?php if ($isVerified): ?>
                            <div class="action-card" id="actionRateDriver" onclick="window.location.href='feedback.php'" style="cursor:pointer;">
                                <span class="action-icon">⭐</span>
                                <span>Rate a Driver</span>
                            </div>
                            <?php else: ?>
                            <div class="action-card" style="cursor:not-allowed; opacity:0.45;" title="Account verification required">
                                <span class="action-icon">🔒</span>
                                <span>Rate a Driver</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- End Session / Simulate Trip Card -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>🚗 Trip Simulation</h3>
                    </div>
                    <div class="card-body">
                        <p style="font-size:0.88rem; color:#666; margin-bottom:16px;">Simulate a completed trip to trigger the driver rating screen. In the real app, this would appear automatically when a trip ends.</p>
                        <?php if ($isVerified): ?>
                        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                            <div style="flex:1; min-width:140px;">
                                <label style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:4px;">Select Driver</label>
                                <select id="simDriverId" class="form-control" style="padding:10px 12px; border:1px solid #ddd; border-radius:8px; width:100%; font-family:var(--font-body);">
                                    <?php foreach ($verifiedDrivers as $d): 
                                        $dName = ($d['first_name'] || $d['last_name']) ? ($d['first_name'] . ' ' . $d['last_name']) : $d['username'];
                                    ?>
                                        <option value="<?php echo $d['id']; ?>" data-name="<?php echo htmlspecialchars($dName); ?>">
                                            <?php echo htmlspecialchars($dName); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if (empty($verifiedDrivers)): ?>
                                        <option value="0" data-name="Juan Dela Cruz">Juan Dela Cruz (Sample)</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div style="flex:1; min-width:140px;">
                                <label style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:4px;">Start Point</label>
                                <input type="text" id="simStart" value="Antipolo Public Market" class="form-control" style="padding:10px 12px; border:1px solid #ddd; border-radius:8px; width:100%; font-family:var(--font-body);">
                            </div>
                            <div style="flex:1; min-width:140px;">
                                <label style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:4px;">End Point</label>
                                <input type="text" id="simEnd" value="SM City Masinag" class="form-control" style="padding:10px 12px; border:1px solid #ddd; border-radius:8px; width:100%; font-family:var(--font-body);">
                            </div>
                            <div style="min-width:100px;">
                                <label style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:4px;">Fare (₱)</label>
                                <input type="number" id="simFare" value="25" class="form-control" style="padding:10px 12px; border:1px solid #ddd; border-radius:8px; width:100%; font-family:var(--font-body);">
                            </div>
                            <button class="btn btn-secondary" id="endSessionBtn" style="padding:10px 24px; font-size:0.9rem; white-space:nowrap;">🏁 End Session</button>
                        </div>
                        <?php else: ?>
                        <div style="text-align:center; padding:20px; opacity:0.5;">
                            <span style="font-size:2rem;">🔒</span>
                            <p style="font-size:0.85rem; margin-top:8px;">This feature requires account verification.</p>
                        </div>
                        <?php endif; ?>
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

        // End Session button — navigate to rating page with trip data
        document.getElementById('endSessionBtn')?.addEventListener('click', () => {
            const driverSelect = document.getElementById('simDriverId');
            const driverId = driverSelect.value;
            const driverName = driverSelect.options[driverSelect.selectedIndex].getAttribute('data-name');
            const start = document.getElementById('simStart').value.trim();
            const end = document.getElementById('simEnd').value.trim();
            const fare = document.getElementById('simFare').value.trim();

            if (!driverName || !start || !end || !fare) {
                alert('Please fill in all trip details before ending the session.');
                return;
            }

            const params = new URLSearchParams({ 
                driver_id: driverId, 
                driver: driverName, 
                start, 
                end, 
                fare 
            });
            window.location.href = `rate_driver.php?${params.toString()}`;
        });
    </script>
</body>
</html>
