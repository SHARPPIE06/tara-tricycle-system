<?php
// rate_driver.php — Driver Rating & Session End Screen
require_once 'php/session_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'User';
$userId = $_SESSION['user_id'];
$userStatus = $_SESSION['status'] ?? 'pending';

// Check if user is verified
if ($userStatus !== 'verified') {
    header("Location: dashboard_user.php?error=Your account must be verified to rate drivers.");
    exit();
}

// Get simulated trip data from URL or defaults
$driverName = $_GET['driver'] ?? 'Juan Dela Cruz';
$startPoint = $_GET['start'] ?? 'Antipolo Public Market';
$endPoint = $_GET['end'] ?? 'SM City Masinag';
$totalFare = $_GET['fare'] ?? '25.00';

require_once 'php/db_connect.php';

// Fetch routes for the dropdown
$routes = $conn->query("SELECT id, toda_name FROM routes ORDER BY toda_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Driver - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="assets/icon.png">
    <style>
        body {
            background-image: url('assets/welcomebg-tricycle.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(8, 14, 49, 0.5);
            z-index: 0;
        }

        .rating-page {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* TARA Logo */
        .rating-logo {
            width: 120px;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }

        /* Main Card */
        .rating-card {
            background: var(--white);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            overflow: hidden;
            border: 3px solid var(--yellow);
        }

        .rating-card-body {
            padding: 30px 28px;
            text-align: center;
        }

        /* Driver Avatar */
        .driver-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--yellow);
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            border: 4px solid var(--orange);
            box-shadow: 0 6px 20px rgba(232, 127, 36, 0.25);
        }

        .driver-name {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 2px;
        }

        .driver-label {
            font-size: 0.85rem;
            color: var(--orange);
            font-weight: 600;
            margin-bottom: 20px;
        }

        /* Route Details */
        .route-details {
            text-align: left;
            margin-bottom: 24px;
            padding: 16px;
            background: #fffbeb;
            border-radius: 14px;
            border: 1px solid #fef08a;
        }

        .route-details-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--orange);
            margin-bottom: 10px;
        }

        .route-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }

        .route-item-label {
            font-weight: 700;
            color: var(--navy);
            flex-shrink: 0;
            margin-right: 8px;
        }

        .route-item-value {
            color: #555;
            text-align: right;
        }

        .route-item.total {
            border-top: 2px solid #fde68a;
            padding-top: 8px;
            margin-top: 4px;
        }

        .route-item.total .route-item-label,
        .route-item.total .route-item-value {
            font-weight: 800;
            color: var(--orange);
            font-size: 0.95rem;
        }

        /* Tip Section */
        .tip-section {
            margin-bottom: 24px;
        }

        .tip-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .tip-options {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .tip-btn {
            padding: 8px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 20px;
            background: var(--white);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--navy);
        }

        .tip-btn:hover { border-color: var(--yellow); }
        .tip-btn.active {
            background: var(--yellow);
            border-color: var(--yellow);
            color: var(--navy);
        }

        /* Star Rating */
        .rating-section {
            margin-bottom: 20px;
        }

        .rating-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--orange);
            margin-bottom: 12px;
        }

        .star-container {
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .star {
            font-size: 2.6rem;
            cursor: pointer;
            color: #e0e0e0;
            transition: all 0.2s ease;
            user-select: none;
        }

        .star:hover,
        .star.active {
            color: var(--yellow);
            transform: scale(1.15);
            filter: drop-shadow(0 2px 6px rgba(255, 200, 30, 0.5));
        }

        .star.active {
            animation: starPop 0.3s ease;
        }

        @keyframes starPop {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1.15); }
        }

        /* Comment Box */
        .comment-box {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-family: var(--font-body);
            font-size: 0.88rem;
            resize: vertical;
            min-height: 80px;
            transition: border-color 0.2s;
            margin-bottom: 20px;
        }

        .comment-box:focus {
            outline: none;
            border-color: var(--yellow);
        }

        /* Buttons */
        .rating-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-submit-rating {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 30px;
            background: var(--yellow);
            color: var(--navy);
            font-family: var(--font-title);
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(255, 200, 30, 0.3);
        }

        .btn-submit-rating:hover {
            background: #ffda58;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 200, 30, 0.4);
        }

        .btn-submit-rating:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-back {
            width: 100%;
            padding: 14px;
            border: 2px solid #e5e7eb;
            border-radius: 30px;
            background: var(--white);
            color: var(--navy);
            font-family: var(--font-title);
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            border-color: var(--navy);
            background: #f9fafb;
        }

        /* Success State */
        .success-overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: var(--white);
            border-radius: 24px;
            z-index: 10;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
            animation: fadeIn 0.3s ease;
        }

        .success-overlay.show { display: flex; }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 16px;
            animation: starPop 0.5s ease;
        }

        .success-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .success-msg {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 24px;
        }

        /* Error message */
        .rating-error {
            display: none;
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 16px;
        }

        .rating-error.show { display: block; }

        /* Responsive */
        @media (max-width: 480px) {
            .rating-card-body { padding: 24px 20px; }
            .driver-avatar { width: 80px; height: 80px; font-size: 2rem; }
            .driver-name { font-size: 1.15rem; }
            .star { font-size: 2.2rem; }
            .rating-logo { width: 100px; }
        }
    </style>
</head>
<body>
    <div class="rating-page">
        <img src="assets/tara-logo.png" alt="TARA" class="rating-logo">

        <div class="rating-card" id="ratingCard" style="position: relative;">
            <div class="rating-card-body">
                <!-- Driver Info -->
                <div class="driver-avatar">🚗</div>
                <div class="driver-name" id="driverNameDisplay"><?php echo htmlspecialchars($driverName); ?></div>
                <div class="driver-label">Your Driver</div>

                <!-- Route Details -->
                <div class="route-details">
                    <div class="route-details-title">Route Details</div>
                    <div class="route-item">
                        <span class="route-item-label">Starting Point:</span>
                        <span class="route-item-value" id="startDisplay"><?php echo htmlspecialchars($startPoint); ?></span>
                    </div>
                    <div class="route-item">
                        <span class="route-item-label">End Point:</span>
                        <span class="route-item-value" id="endDisplay"><?php echo htmlspecialchars($endPoint); ?></span>
                    </div>
                    <div class="route-item total">
                        <span class="route-item-label">Total Fare:</span>
                        <span class="route-item-value" id="fareDisplay">₱<?php echo htmlspecialchars($totalFare); ?></span>
                    </div>
                    <div class="route-item" style="margin-bottom:0;">
                        <span class="route-item-label">Tip Given:</span>
                        <span class="route-item-value" id="tipDisplay">None</span>
                    </div>
                </div>

                <!-- Tip Options -->
                <div class="tip-section">
                    <div class="tip-label">Add a Tip?</div>
                    <div class="tip-options">
                        <button type="button" class="tip-btn" data-tip="0">None</button>
                        <button type="button" class="tip-btn" data-tip="5">₱5</button>
                        <button type="button" class="tip-btn" data-tip="10">₱10</button>
                        <button type="button" class="tip-btn" data-tip="20">₱20</button>
                        <button type="button" class="tip-btn" data-tip="50">₱50</button>
                    </div>
                </div>

                <!-- Star Rating -->
                <div class="rating-section">
                    <div class="rating-title">Rate your driver</div>
                    <div class="star-container" id="starContainer">
                        <span class="star" data-value="1">★</span>
                        <span class="star" data-value="2">★</span>
                        <span class="star" data-value="3">★</span>
                        <span class="star" data-value="4">★</span>
                        <span class="star" data-value="5">★</span>
                    </div>
                </div>

                <!-- Comment -->
                <textarea class="comment-box" id="commentBox" placeholder="Leave a comment (optional)..." maxlength="500"></textarea>

                <!-- Error -->
                <div class="rating-error" id="ratingError"></div>

                <!-- Actions -->
                <div class="rating-actions">
                    <button class="btn-submit-rating" id="submitRating">Submit Rating</button>
                    <button class="btn-back" onclick="window.location.href='dashboard_user.php'">Back</button>
                </div>
            </div>

            <!-- Success Overlay -->
            <div class="success-overlay" id="successOverlay">
                <div class="success-icon">🎉</div>
                <div class="success-title">Thank You!</div>
                <div class="success-msg">Your rating has been submitted successfully.</div>
                <button class="btn-submit-rating" onclick="window.location.href='dashboard_user.php'" style="max-width:220px;">Back to Dashboard</button>
            </div>
        </div>
    </div>

    <script>
        let selectedRating = 0;
        let selectedTip = 0;

        // Star Rating Logic
        const stars = document.querySelectorAll('.star');
        stars.forEach(star => {
            star.addEventListener('click', () => {
                selectedRating = parseInt(star.dataset.value);
                stars.forEach(s => {
                    s.classList.toggle('active', parseInt(s.dataset.value) <= selectedRating);
                });
                hideError();
            });

            star.addEventListener('mouseenter', () => {
                const val = parseInt(star.dataset.value);
                stars.forEach(s => {
                    if (parseInt(s.dataset.value) <= val) {
                        s.style.color = 'var(--yellow)';
                        s.style.transform = 'scale(1.1)';
                    }
                });
            });

            star.addEventListener('mouseleave', () => {
                stars.forEach(s => {
                    if (!s.classList.contains('active')) {
                        s.style.color = '#e0e0e0';
                        s.style.transform = 'scale(1)';
                    } else {
                        s.style.color = 'var(--yellow)';
                        s.style.transform = 'scale(1.15)';
                    }
                });
            });
        });

        // Tip Logic
        document.querySelectorAll('.tip-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tip-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                selectedTip = parseInt(btn.dataset.tip);
                document.getElementById('tipDisplay').textContent = selectedTip > 0 ? `₱${selectedTip}` : 'None';
            });
        });

        // Error handling
        function showError(msg) {
            const el = document.getElementById('ratingError');
            el.textContent = msg;
            el.classList.add('show');
        }
        function hideError() {
            document.getElementById('ratingError').classList.remove('show');
        }

        // Submit Logic
        document.getElementById('submitRating').addEventListener('click', async () => {
            hideError();

            if (selectedRating === 0) {
                showError('Please select a star rating before submitting.');
                return;
            }

            const btn = document.getElementById('submitRating');
            btn.disabled = true;
            btn.textContent = 'Submitting...';

            const comment = document.getElementById('commentBox').value.trim();
            const driverName = document.getElementById('driverNameDisplay').textContent;

            const formData = new FormData();
            formData.append('driver_name', driverName);
            formData.append('rating', selectedRating);
            formData.append('comment', comment);
            formData.append('tip', selectedTip);

            try {
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), 10000); // 10s timeout

                const response = await fetch('php/submit_rating.php', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                });
                clearTimeout(timeout);

                const result = await response.json();

                if (result.success) {
                    document.getElementById('successOverlay').classList.add('show');
                } else {
                    showError(result.error || 'Failed to submit rating. Please try again.');
                    btn.disabled = false;
                    btn.textContent = 'Submit Rating';
                }
            } catch (err) {
                if (err.name === 'AbortError') {
                    showError('Connection timed out. Please check your internet and try again.');
                } else {
                    showError('An error occurred. Please try again.');
                }
                btn.disabled = false;
                btn.textContent = 'Submit Rating';
            }
        });
    </script>
</body>
</html>
