<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bus_ticketing_system');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Define roles
define('ROLE_ADMIN', 'ADMIN');
define('ROLE_CASHIER', 'CASHIER');
define('ROLE_PASSENGER', 'PASSENGER');
define('ROLE_CUSTOMER', 'CUSTOMER');

// System settings
define('COMPANY_NAME', 'SafeWay Transport');
define('COMPANY_LOGO', 'assets/images/logo.png');
?>
