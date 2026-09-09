<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    logAudit($conn, $_SESSION['user_id'], 'LOGOUT', 'user', $_SESSION['user_id']);
}

session_destroy();
header('Location: index.php');
exit();
?>