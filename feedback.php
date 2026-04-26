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

require_once 'php/db_connect.php';

$routes = $conn->query("SELECT id, toda_name FROM routes ORDER BY toda_name ASC");

if ($role === 'admin') {
    $reviews = $conn->query("SELECT r.*, rt.toda_name, u.username as reviewer_name FROM reviews r LEFT JOIN routes rt ON r.route_id = rt.id LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
    $my_reviews = $reviews;
    // Mark as read when admin visits
    $conn->query("UPDATE reviews SET is_read = 1 WHERE is_read = 0");
} else {
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
    <title>Driver Feedback - TARA</title>
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
    <link rel="icon" type="image/png" href="assets/icon.png"></head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>TARA</h1>
                <p>Commuter Portal</p>
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
                <a href="manage_stops.php" class="nav-link" id="navTODAMgmt">
                    <span class="nav-icon">&#x1F3E2;</span> Add TODAs &amp; Terminals
                </a>

                <span class="nav-section-title">Analytics</span>
                <a href="route_map.php" class="nav-link" id="navMapAdmin">
                    <span class="nav-icon">&#x1F5FA;&#xFE0F;</span> Map Overview
                </a>
                <a href="feedback.php" class="nav-link active" id="navFeedback">
                    <span class="nav-icon">&#x1F4AC;</span> Feedback
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
                    <h2>Driver Feedback</h2>
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
                <div class="stats-grid" style="grid-template-columns: <?php echo $role === 'admin' ? '1fr' : '1fr 2fr'; ?>;">
                    <?php if ($role !== 'admin'): ?>
                    <!-- Rate Form -->
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
                                    <label>Driver Name or Body Number</label>
                                    <input type="text" name="driver_name" class="form-control" placeholder="e.g. Body #142">
                                </div>
                                <div class="form-group">
                                    <label>Route / TODA</label>
                                    <select name="route_id" class="form-control" required>
                                        <option value="">Select Route</option>
                                        <?php while($row = $routes->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['toda_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Comments</label>
                                    <textarea name="comment" class="form-control" rows="3" placeholder="How was your ride?"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary auth-btn">Submit Review</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- My Reviews -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><?php echo $role === 'admin' ? 'All User Feedback' : 'My Submitted Reviews'; ?></h3>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Driver/Route</th>
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
                                                <strong style="display:block;"><?php echo htmlspecialchars($row['driver_name'] ?: 'Unknown Driver'); ?></strong>
                                                <small style="color:#666;"><?php echo htmlspecialchars($row['toda_name']); ?></small>
                                                <?php if($role === 'admin'): ?>
                                                    <small style="display:block; color:var(--primary); font-size:0.75rem;">By: <?php echo htmlspecialchars($row['reviewer_name']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="color:var(--yellow); font-size:1.2rem;">
                                                <?php echo str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['comment']); ?></td>
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
                    ratingInput.value = val;
                    
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
        });
    </script>
</body>
</html>
