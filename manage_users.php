<?php
// manage_users.php - Admin User Management
require_once 'php/session_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'] ?? 'user';
if ($role !== 'admin') {
    header("Location: dashboard_user.php");
    exit();
}

require_once 'php/db_connect.php';

// Handle success/error messages
$successMsg = $_GET['success'] ?? '';
$errorMsg = $_GET['error'] ?? '';
$typeFilter = $_GET['type'] ?? 'all'; // all, driver, commuter

// Build query based on type
$whereClause = "";
$title = "User Management";

if ($typeFilter === 'driver') {
    $whereClause = "WHERE classifications @> '[\"Driver\"]'::jsonb";
    $title = "Manage Drivers";
} elseif ($typeFilter === 'commuter') {
    $whereClause = "WHERE NOT (classifications @> '[\"Driver\"]'::jsonb)";
    $title = "Manage Commuters";
}

// Fetch users
$usersQuery = "SELECT * FROM users $whereClause ORDER BY created_at DESC";
$users = $conn->query($usersQuery);

// Counts for status filters (within the filtered type if applicable)
$countWhere = $whereClause ? "$whereClause AND " : "WHERE ";
$pendingCount = $conn->query("SELECT COUNT(*) as c FROM users " . ($whereClause ? "$whereClause AND " : "WHERE ") . "status = 'pending'")->fetch(PDO::FETCH_ASSOC)['c'];
$verifiedCount = $conn->query("SELECT COUNT(*) as c FROM users " . ($whereClause ? "$whereClause AND " : "WHERE ") . "status = 'verified'")->fetch(PDO::FETCH_ASSOC)['c'];
$rejectedCount = $conn->query("SELECT COUNT(*) as c FROM users " . ($whereClause ? "$whereClause AND " : "WHERE ") . "status = 'rejected'")->fetch(PDO::FETCH_ASSOC)['c'];
$totalCount = $conn->query("SELECT COUNT(*) as c FROM users " . ($whereClause ?: ""))->fetch(PDO::FETCH_ASSOC)['c'];

$username = $_SESSION['username'] ?? 'Admin';
$initials = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - TARA Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="icon" type="image/png" href="assets/icon.png">
    <style>
        /* Status filter tabs */
        .status-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .status-tab {
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            background: var(--white);
            color: #555;
            transition: all 0.2s ease;
        }
        .status-tab:hover { border-color: var(--orange); }
        .status-tab.active { background: var(--navy); color: var(--white); border-color: var(--navy); }
        .status-tab .tab-count {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.72rem;
            margin-left: 6px;
        }
        .status-tab.active .tab-count { background: rgba(255,255,255,0.2); }

        /* Action Buttons */
        .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-sm {
            padding: 5px 12px;
            font-size: 0.75rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.15s ease;
        }
        .btn-approve { background: #dcfce7; color: #15803d; }
        .btn-approve:hover { background: #bbf7d0; }
        .btn-reject { background: #fee2e2; color: #b91c1c; }
        .btn-reject:hover { background: #fecaca; }
        .btn-view { background: #dbeafe; color: #1d4ed8; }
        .btn-view:hover { background: #bfdbfe; }
        .btn-edit { background: #fef9c3; color: #a16207; }
        .btn-edit:hover { background: #fef08a; }
        .btn-delete { background: #fee2e2; color: #b91c1c; }
        .btn-delete:hover { background: #fecaca; }
        .btn-activate { background: #dcfce7; color: #15803d; }
        .btn-activate:hover { background: #bbf7d0; }

        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal-overlay.show { display: flex; }

        .modal-box {
            background: var(--white);
            border-radius: 16px;
            width: 100%;
            max-width: 650px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { font-size: 1.15rem; color: var(--navy); }
        .modal-close {
            background: none; border: none;
            font-size: 1.5rem; cursor: pointer;
            color: #999; transition: color 0.2s;
        }
        .modal-close:hover { color: #333; }

        .modal-body { padding: 24px; }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .detail-item { margin-bottom: 12px; }
        .detail-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 0.95rem;
            font-weight: 500;
            color: #333;
        }
        .detail-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--navy);
            padding-bottom: 6px;
            border-bottom: 2px solid #f0f0f0;
            margin-top: 20px;
            margin-bottom: 14px;
        }

        .doc-list { list-style: none; padding: 0; margin: 0; }
        .doc-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        .doc-list li a {
            color: #1d4ed8;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .class-tag {
            display: inline-block;
            padding: 3px 10px;
            background: #ede9fe;
            color: #6d28d9;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 5px;
            margin-bottom: 4px;
        }

        .modal-actions {
            padding: 16px 24px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* Alerts */
        .alert-bar {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .alert-bar.success { background: #dcfce7; color: #15803d; }
        .alert-bar.error { background: #fee2e2; color: #b91c1c; }

        @media (max-width: 768px) {
            .detail-grid { grid-template-columns: 1fr; }
            .modal-box { max-width: 100%; margin: 10px; }
        }
    </style>
</head>
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
                <a href="manage_users.php?type=commuter" class="nav-link <?php echo $typeFilter === 'commuter' ? 'active' : ''; ?>" id="navCommuters">
                    <span class="nav-icon">👥</span> Manage Commuters
                </a>
                <a href="manage_users.php?type=driver" class="nav-link <?php echo $typeFilter === 'driver' ? 'active' : ''; ?>" id="navDrivers">
                    <span class="nav-icon">🚗</span> Manage Drivers
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
                <a href="feedback.php" class="nav-link" id="navFeedback">
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
                    <h2><?php echo $title; ?></h2>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </header>

            <div class="page-content">
                
                <?php if ($successMsg): ?>
                    <div class="alert-bar success"><?php echo htmlspecialchars($successMsg); ?></div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div class="alert-bar error"><?php echo htmlspecialchars($errorMsg); ?></div>
                <?php endif; ?>

                <!-- Status Filter Tabs -->
                <div class="status-tabs">
                    <button class="status-tab active" data-filter="all">All<span class="tab-count"><?php echo $totalCount; ?></span></button>
                    <button class="status-tab" data-filter="pending">Pending<span class="tab-count"><?php echo $pendingCount; ?></span></button>
                    <button class="status-tab" data-filter="verified">Verified<span class="tab-count"><?php echo $verifiedCount; ?></span></button>
                    <button class="status-tab" data-filter="rejected">Rejected<span class="tab-count"><?php echo $rejectedCount; ?></span></button>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h3>👥 Registered Accounts</h3>
                    </div>
                    <div class="card-body" style="padding:0; overflow-x:auto;">
                        <table class="data-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Classification</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $users->fetch(PDO::FETCH_ASSOC)): ?>
                                <?php 
                                    $fullName = $row['first_name'] ? htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) : htmlspecialchars($row['username'] ?? 'Unknown');
                                    $classifications = $row['classifications'] ? json_decode($row['classifications'], true) : [];
                                    $statusVal = $row['status'] ?? 'pending';
                                    $statusClass = $statusVal === 'verified' ? 'badge-active' : ($statusVal === 'rejected' ? 'badge-inactive' : 'badge-pending');
                                ?>
                                <tr data-status="<?php echo $statusVal; ?>"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-firstname="<?php echo htmlspecialchars($row['first_name'] ?? ''); ?>"
                                    data-middlename="<?php echo htmlspecialchars($row['middle_name'] ?? ''); ?>"
                                    data-lastname="<?php echo htmlspecialchars($row['last_name'] ?? ''); ?>"
                                    data-age="<?php echo $row['age'] ?? ''; ?>"
                                    data-birthdate="<?php echo $row['birthdate'] ?? ''; ?>"
                                    data-email="<?php echo htmlspecialchars($row['email'] ?? ''); ?>"
                                    data-role="<?php echo $row['role']; ?>"
                                    data-status-val="<?php echo $statusVal; ?>"
                                    data-classifications='<?php echo htmlspecialchars($row['classifications'] ?? '[]'); ?>'
                                    data-toda="<?php echo htmlspecialchars($row['toda_name'] ?? ''); ?>"
                                    data-address="<?php echo htmlspecialchars($row['home_address'] ?? ''); ?>"
                                    data-member="<?php echo htmlspecialchars($row['member_number'] ?? ''); ?>"
                                    data-docs='<?php echo htmlspecialchars($row['id_documents'] ?? '{}'); ?>'
                                    data-joined="<?php echo date('M d, Y', strtotime($row['created_at'])); ?>"
                                >
                                    <td><?php echo $row['id']; ?></td>
                                    <td style="font-weight:600;"><?php echo $fullName; ?></td>
                                    <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                    <td><span class="badge <?php echo $row['role'] === 'admin' ? 'badge-pending' : 'badge-active'; ?>"><?php echo ucfirst($row['role']); ?></span></td>
                                    <td>
                                        <?php foreach ($classifications as $cls): ?>
                                            <span class="class-tag"><?php echo htmlspecialchars($cls); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (empty($classifications)): ?>
                                            <span style="color:#aaa; font-size:0.8rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($statusVal); ?></span></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn-sm btn-view" onclick="viewUser(this.closest('tr'))">View</button>
                                            <?php if ($statusVal === 'pending'): ?>
                                                <a href="php/admin_actions.php?action=approve&id=<?php echo $row['id']; ?>" class="btn-sm btn-approve">Approve</a>
                                                <a href="php/admin_actions.php?action=reject&id=<?php echo $row['id']; ?>" class="btn-sm btn-reject">Reject</a>
                                            <?php elseif ($statusVal === 'verified'): ?>
                                                <a href="php/admin_actions.php?action=reject&id=<?php echo $row['id']; ?>" class="btn-sm btn-reject">Deactivate</a>
                                            <?php elseif ($statusVal === 'rejected'): ?>
                                                <a href="php/admin_actions.php?action=approve&id=<?php echo $row['id']; ?>" class="btn-sm btn-activate">Activate</a>
                                            <?php endif; ?>
                                            <a href="php/admin_actions.php?action=delete&id=<?php echo $row['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Detail Modal -->
    <div class="modal-overlay" id="userModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="modalTitle">User Details</h3>
                <button class="modal-close" onclick="closeModal()">✕</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Populated dynamically -->
            </div>
            <div class="modal-actions" id="modalActions">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Filter tabs
        document.querySelectorAll('.status-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.status-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const filter = tab.dataset.filter;
                document.querySelectorAll('#usersTable tbody tr').forEach(row => {
                    if (filter === 'all' || row.dataset.status === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // View user detail modal
        function viewUser(row) {
            const d = row.dataset;
            const classifications = JSON.parse(d.classifications || '[]');
            const docs = JSON.parse(d.docs || '{}');
            const statusVal = d.statusVal;

            let classHtml = classifications.length > 0
                ? classifications.map(c => `<span class="class-tag">${c}</span>`).join('')
                : '<span style="color:#aaa;">None selected</span>';

            let docsHtml = '';
            const docKeys = Object.keys(docs);
            if (docKeys.length > 0) {
                docsHtml = '<ul class="doc-list">';
                docKeys.forEach(key => {
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    const docUrl = docs[key];
                    if (docUrl.startsWith('data:')) {
                        docsHtml += `<li><span>${label}</span><a href="${docUrl}" download="${key}_document">Download ↓</a></li>`;
                    } else {
                        docsHtml += `<li><span>${label}</span><a href="${docUrl}" target="_blank">View File →</a></li>`;
                    }
                });
                docsHtml += '</ul>';
            } else {
                docsHtml = '<p style="color:#aaa; font-size:0.85rem;">No documents uploaded.</p>';
            }

            let driverHtml = '';
            if (classifications.includes('Driver')) {
                driverHtml = `
                    <div class="detail-section-title">🚗 Driver Information</div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">TODA Name</div>
                            <div class="detail-value">${d.toda || '-'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Home Address</div>
                            <div class="detail-value">${d.address || '-'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Member Number</div>
                            <div class="detail-value">${d.member || '-'}</div>
                        </div>
                    </div>
                `;
            }

            const statusBadge = statusVal === 'verified'
                ? '<span class="badge badge-active">Verified</span>'
                : statusVal === 'rejected'
                    ? '<span class="badge badge-inactive">Rejected</span>'
                    : '<span class="badge badge-pending">Pending</span>';

            document.getElementById('modalTitle').textContent = `${d.firstname || 'User'} ${d.lastname || ''}`;
            document.getElementById('modalBody').innerHTML = `
                <div class="detail-section-title">👤 Personal Information</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">First Name</div>
                        <div class="detail-value">${d.firstname || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Middle Name</div>
                        <div class="detail-value">${d.middlename || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Last Name</div>
                        <div class="detail-value">${d.lastname || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Age</div>
                        <div class="detail-value">${d.age || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Birthdate</div>
                        <div class="detail-value">${d.birthdate || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">${d.email}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Role</div>
                        <div class="detail-value">${d.role}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">${statusBadge}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Joined</div>
                        <div class="detail-value">${d.joined}</div>
                    </div>
                </div>

                <div class="detail-section-title">🏷️ Classifications</div>
                <div style="margin-bottom:12px;">${classHtml}</div>

                ${driverHtml}

                <div class="detail-section-title">📄 Uploaded Documents</div>
                ${docsHtml}
            `;

            // Modal action buttons
            let actionsHtml = '';
            if (statusVal === 'pending') {
                actionsHtml = `
                    <a href="php/admin_actions.php?action=approve&id=${d.id}" class="btn-sm btn-approve" style="padding:8px 20px; font-size:0.85rem;">✅ Approve & Verify</a>
                    <a href="php/admin_actions.php?action=reject&id=${d.id}" class="btn-sm btn-reject" style="padding:8px 20px; font-size:0.85rem;">❌ Reject</a>
                `;
            } else if (statusVal === 'verified') {
                actionsHtml = `<a href="php/admin_actions.php?action=reject&id=${d.id}" class="btn-sm btn-reject" style="padding:8px 20px; font-size:0.85rem;">Deactivate</a>`;
            } else {
                actionsHtml = `<a href="php/admin_actions.php?action=approve&id=${d.id}" class="btn-sm btn-activate" style="padding:8px 20px; font-size:0.85rem;">Activate</a>`;
            }
            document.getElementById('modalActions').innerHTML = actionsHtml;

            document.getElementById('userModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('show');
        }

        // Close modal on overlay click
        document.getElementById('userModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeModal();
        });

        // Sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>
