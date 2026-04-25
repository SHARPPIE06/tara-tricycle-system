<?php
// db_connect.php
$servername = "localhost";
$username = "root"; // Update for InfinityFree
$password = "";     // Update for InfinityFree
$dbname = "tara_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
