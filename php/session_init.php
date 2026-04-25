<?php
// session_init.php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/SessionHandler.php';

$handler = new MySQLSessionHandler($conn);
session_set_save_handler($handler, true);
session_start();
?>
