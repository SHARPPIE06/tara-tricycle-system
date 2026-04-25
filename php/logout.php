<?php
// logout.php — Destroys session and redirects to login page
session_start();
session_unset();
session_destroy();
header("Location: ../login.php?success=You have been logged out.");
exit();
?>
