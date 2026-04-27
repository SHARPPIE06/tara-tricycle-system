<?php
// feedback.php
require_once 'php/session_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$initials = strtoupper(substr($username, 0, 1));
$role = $_SESSION['role'] ?? 'user';
$classifications = $_SESSION['classifications'] ?? [];
$isDriver = in_array('Driver', $classifications);

require_once 'php/db_connect.php';

$routes = $conn->query("SELECT id, toda_name FROM routes ORDER BY toda_name ASC");

// Fetch verified drivers for the dropdown
$driversQuery = $conn->query("SELECT id, first_name, last_name, username FROM users WHERE status = 'verified' AND classifications @> '[\"Driver\"]'::jsonb");
$verifiedDrivers = $driversQuery->fetchAll(PDO::FETCH_ASSOC);

if ($role === 'admin') {
    $reviews = $conn->query("SELECT r.*, rt.toda_name, u.username as reviewer_name FROM reviews r LEFT JOIN routes rt ON r.route_id = rt.id LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
    $my_reviews = $reviews;
    // Mark as read when admin visits
    $conn->query("UPDATE reviews SET is_read = 1 WHERE is_read = 0");
} elseif ($isDriver) {
    // Driver viewing reviews about themselves
    try {
        $reviews = $conn->prepare("SELECT r.*, rt.toda_name, u.username as reviewer_name FROM reviews r LEFT JOIN routes rt ON r.route_id = rt.id LEFT JOIN users u ON r.user_id = u.id WHERE r.driver_id = ? ORDER BY r.created_at DESC");
        $reviews->execute([$user_id]);
        $my_reviews = $reviews;
    } catch (PDOException $e) {
        // Handle case where driver_id doesn't exist yet
        $my_reviews = new PDOStatement(); // Empty statement
    }
} else {
    // Commuter viewing reviews they wrote
    $reviews = $conn->prepare("SELECT r.*, rt.toda_name FROM reviews r LEFT JOIN routes rt ON r.route_id = rt.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
    $reviews->execute([$user_id]);
    $my_reviews = $reviews;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .rating-stars {
            display: flex;
            gap: 5px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #ccc;
        }
        .rating-stars span:hover, .rating-stars span.active {
            color: var(--yellow);
        }
    </style>
    <link rel="icon" type="image/png" href="assets/icon.png">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>TARA</h1>
                <p><?php echo $role === 'admin' ? 'Admin Panel' : ($isDriver ? 'Driver Portal' : 'Commuter Portal'); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <?php if ($role === 'admin'): ?>
                <span class="nav-section-title">Overview</span>
                <a href="dashboard_admin.php" class="nav-link">
                    <span class="nav-icon">&#x1F4CA;</span> Dashboard
                </a>
                <span class="nav-section-title">User Account Management</span>
                <a href="manage_users.php?type=commuter" class="nav-link">
                    <span class="nav-icon">👥</span> Manage Commuters
                </a>
                <a href="manage_users.php?type=driver" class="nav-link">
                    <span class="nav-icon">🚗</span> Manage Drivers
                </a>
                <span class="nav-section-title">Management</span>
                <a href="manage_routes.php" class="nav-link">
                    <span class="nav-icon">🛤️</span> Manage Routes
                </a>
                <a href="manage_stops.php" class="nav-link">
                    <span class="nav-icon">🏢</span> Add TODAs & Terminals
                </a>
                <a href="feedback.php" class="nav-link active">
                    <span class="nav-icon">💬</span> Feedback
                </a>
                <?php else: ?>
                <span class="nav-section-title">Main</span>
                <a href="<?php echo $isDriver ? 'dashboard_driver.php' : 'dashboard_user.php'; ?>" class="nav-link">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="route_map.php" class="nav-link">
                    <span class="nav-icon">🗺️</span> Route Map
                </a>
                <a href="feedback.php" class="nav-link active">
                    <span class="nav-icon">⭐</span> Feedback
                </a>
                <a href="profile.php" class="nav-link">
                    <span class="nav-icon">👤</span> Profile
                </a>
                <?php endif; ?>
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
                    <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                    <h2><?php echo $isDriver ? 'My Ratings & Feedback' : 'Driver Feedback'; ?></h2>
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
                <div class="stats-grid" style="grid-template-columns: 1fr;">
                    <?php if (!$role === 'admin' && !$isDriver): ?>
                    <!-- Rate Form (Only for Commuters) -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>⭐ Rate a Driver</h3>
                        </div>
                        <div class="card-body">
                            <form action="php/feedback_action.php" method="POST">
                                <input type="hidden" name="action" value="submit">
                                <input type="hidden" name="rating" id="ratingInput" value="5">
                                
                                <div class="form-group">
                                    <label>Rating</label>
                                    <div class="rating-stars" id="starContainer">
                                        <span data-val="1" class="active">★</span>
                                        <span data-val="2" class="active">★</span>
                                        <span data-val="3" class="active">★</span>
                                        <span data-val="4" class="active">★</span>
                                        <span data-val="5" class="active">★</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Select Driver</label>
                                    <select name="driver_id" id="driverSelect" class="form-control" required>
                                        <option value="">-- Select a Driver --</option>
                                        <?php foreach ($verifiedDrivers as $d): 
                                            $dName = ($d['first_name'] || $d['last_name']) ? ($d['first_name'] . ' ' . $d['last_name']) : $d['username'];
                                        ?>
                                            <option value="<?php echo $d['id']; ?>" data-name="<?php echo htmlspecialchars($dName ?? ''); ?>">
                                                <?php echo htmlspecialchars($dName ?? ''); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="driver_name" id="driverNameHidden">
                                </div>
                                <div class="form-group">
                                    <label>Route / TODA</label>
                                    <select name="route_id" class="form-control" required>
                                        <option value="">Select Route</option>
                                        <?php while($row = $routes->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['toda_name'] ?? ''); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Comments</label>
                                    <textarea name="comment" class="form-control" rows="5" placeholder="How was your ride?"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary auth-btn">Submit Review</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Reviews Table -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>
                                <?php 
                                    if ($role === 'admin') echo 'All User Feedback';
                                    elseif ($isDriver) echo 'Recent Reviews from Customers';
                                    else echo 'My Submitted Reviews'; 
                                ?>
                            </h3>
                        </div>
                        <div class="card-body" style="padding:0; overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th><?php echo $isDriver ? 'Reviewer' : 'Driver/Route'; ?></th>
                                        <th>Rating</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($my_reviews->rowCount() > 0): ?>
                                        <?php while ($row = $my_reviews->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><small><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small></td>
                                            <td>
                                                <?php if ($isDriver): ?>
                                                    <strong style="display:block;"><?php echo htmlspecialchars($row['reviewer_name'] ?: 'Anonymous Commuter'); ?></strong>
                                                <?php else: ?>
                                                    <strong style="display:block;"><?php echo htmlspecialchars($row['driver_name'] ?: 'Unknown Driver'); ?></strong>
                                                    <small style="color:#666;"><?php echo htmlspecialchars($row['toda_name'] ?? ''); ?></small>
                                                <?php endif; ?>
                                                
                                                <?php if($role === 'admin'): ?>
                                                    <small style="display:block; color:var(--primary); font-size:0.75rem;">By: <?php echo htmlspecialchars($row['reviewer_name'] ?? ''); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="color:var(--yellow); font-size:1.2rem;">
                                                <?php echo str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['comment'] ?? ''); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="text-align:center; padding:20px;">No reviews found.</td>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const stars = document.querySelectorAll('#starContainer span');
            const ratingInput = document.getElementById('ratingInput');
            
            stars.forEach(star => {
                star.addEventListener('click', () => {
                    const val = parseInt(star.getAttribute('data-val'));
                    if (ratingInput) ratingInput.value = val;
                    
                    stars.forEach(s => {
                        if (parseInt(s.getAttribute('data-val')) <= val) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });
            });

            document.getElementById('sidebarToggle')?.addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('open');
            });

            // Update hidden driver name when select changes
            const driverSelect = document.getElementById('driverSelect');
            const driverNameHidden = document.getElementById('driverNameHidden');
            if (driverSelect && driverNameHidden) {
                driverSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    driverNameHidden.value = selectedOption.getAttribute('data-name') || '';
                });
            }
        });
    </script>
</body>
</html>
