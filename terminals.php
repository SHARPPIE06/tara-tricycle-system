<?php
// terminals.php
require_once 'php/session_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'User';
$initials = strtoupper(substr($username, 0, 1));
$role = $_SESSION['role'] ?? 'user';

require_once 'php/db_connect.php';

$routes = $conn->query("SELECT * FROM routes ORDER BY toda_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TODA Terminals - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="icon" type="image/png" href="assets/icon.png"></head>
<body>
    <div class="dashboard-wrapper">
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
                <a href="route_map.php" class="nav-link" id="navMap">
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
                <a href="terminals.php" class="nav-link active" id="navTODA">
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

        <div class="main-content">
            <header class="top-bar">
                <div style="display:flex;align-items:center;gap:12px;">
                    <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                    <h2>TODA / Terminals Directory</h2>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </header>

            <div class="page-content">
                <div class="content-card">
                    <div class="card-header">
                        <h3>🏢 All Registered TODA Terminals</h3>
                    </div>
                    <div class="card-body" style="padding:0; overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Terminal Name</th>
                                    <th>Base Fare</th>
                                    <th>Per Km Fare</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($routes->rowCount() > 0): ?>
                                    <?php while ($row = $routes->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?php echo htmlspecialchars($row['toda_name'] ?? ''); ?></td>
                                        <td>₱<?php echo number_format($row['base_fare'], 2); ?></td>
                                        <td>₱<?php echo number_format($row['per_km_fare'], 2); ?></td>
                                        <td>
                                            <a href="fare_estimator.php" class="btn btn-primary" style="padding:4px 10px; font-size:0.75rem; border-radius:4px;">Calculate Fare</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding:20px;">No terminals found in the system.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>
