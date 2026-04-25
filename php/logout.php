<?php
// logout.php — Destroys session and redirects to login page
require_once 'session_init.php';
session_unset();
session_destroy();
header("Location: ../login.php?success=You have been logged out.");
exit();
?>
