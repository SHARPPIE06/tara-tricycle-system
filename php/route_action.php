<?php
// route_action.php
require_once 'session_init.php';
require_once 'db_connect.php';

// Ensure user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_route') {
        $toda_name = $_POST['toda_name'] ?? '';
        $base_fare = $_POST['base_fare'] ?? 0;
        $per_km_fare = $_POST['per_km_fare'] ?? 0;
        $terminal_lat = $_POST['terminal_lat'] ?? null;
        $terminal_lng = $_POST['terminal_lng'] ?? null;

        if (empty($toda_name) || empty($terminal_lat) || empty($terminal_lng)) {
            header("Location: ../manage_routes.php?error=Please fill all required fields and pick a location on the map.");
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO routes (toda_name, terminal_lat, terminal_lng, base_fare, per_km_fare) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdddd", $toda_name, $terminal_lat, $terminal_lng, $base_fare, $per_km_fare);

        if ($stmt->execute()) {
            header("Location: ../manage_routes.php?success=Route added successfully.");
        } else {
            header("Location: ../manage_routes.php?error=Failed to add route.");
        }
        $stmt->close();
        exit();
    }
    elseif ($action === 'edit_route') {
        $route_id = $_POST['route_id'] ?? 0;
        $toda_name = $_POST['toda_name'] ?? '';
        $base_fare = $_POST['base_fare'] ?? 0;
        $per_km_fare = $_POST['per_km_fare'] ?? 0;
        $terminal_lat = $_POST['terminal_lat'] ?? null;
        $terminal_lng = $_POST['terminal_lng'] ?? null;

        if (empty($toda_name) || $route_id == 0) {
            header("Location: ../manage_routes.php?error=Invalid route data.");
            exit();
        }

        if ($terminal_lat && $terminal_lng) {
            $stmt = $conn->prepare("UPDATE routes SET toda_name = ?, terminal_lat = ?, terminal_lng = ?, base_fare = ?, per_km_fare = ? WHERE id = ?");
            $stmt->bind_param("sddddi", $toda_name, $terminal_lat, $terminal_lng, $base_fare, $per_km_fare, $route_id);
        } else {
            $stmt = $conn->prepare("UPDATE routes SET toda_name = ?, base_fare = ?, per_km_fare = ? WHERE id = ?");
            $stmt->bind_param("sddi", $toda_name, $base_fare, $per_km_fare, $route_id);
        }

        if ($stmt->execute()) {
            header("Location: ../manage_routes.php?success=Route updated successfully.");
        } else {
            header("Location: ../manage_routes.php?error=Failed to update route.");
        }
        $stmt->close();
        exit();
    }
    elseif ($action === 'delete_route') {
        $route_id = $_POST['route_id'] ?? 0;

        $stmt = $conn->prepare("DELETE FROM routes WHERE id = ?");
        $stmt->bind_param("i", $route_id);

        if ($stmt->execute()) {
            header("Location: ../manage_routes.php?success=Route deleted.");
        } else {
            header("Location: ../manage_routes.php?error=Failed to delete route.");
        }
        $stmt->close();
        exit();
    }
    elseif ($action === 'add_stop') {
        $route_id = $_POST['route_id'] ?? 0;
        $stop_name = $_POST['stop_name'] ?? '';
        $lat = $_POST['lat'] ?? 0;
        $lng = $_POST['lng'] ?? 0;

        if (empty($stop_name) || $lat == 0 || $lng == 0) {
            header("Location: ../manage_stops.php?route_id=$route_id&error=Please fill all fields and pick location on map.");
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO stops (route_id, stop_name, lat, lng) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isdd", $route_id, $stop_name, $lat, $lng);

        if ($stmt->execute()) {
            header("Location: ../manage_stops.php?route_id=$route_id&success=Stop added.");
        } else {
            header("Location: ../manage_stops.php?route_id=$route_id&error=Failed to add stop.");
        }
        $stmt->close();
        exit();
    }
    elseif ($action === 'delete_stop') {
        $stop_id = $_POST['stop_id'] ?? 0;
        $route_id = $_POST['route_id'] ?? 0;

        $stmt = $conn->prepare("DELETE FROM stops WHERE id = ?");
        $stmt->bind_param("i", $stop_id);

        if ($stmt->execute()) {
            header("Location: ../manage_stops.php?route_id=$route_id&success=Stop deleted.");
        } else {
            header("Location: ../manage_stops.php?route_id=$route_id&error=Failed to delete stop.");
        }
        $stmt->close();
        exit();
    }
    else {
        header("Location: ../manage_routes.php");
        exit();
    }
} else {
    header("Location: ../manage_routes.php");
    exit();
}
$conn->close();
?>
