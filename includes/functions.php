<?php
// Helper functions for the system

/**
 * Generate unique booking reference
 */
function generateBookingReference() {
    return 'BK' . date('YmdHis') . mt_rand(1000, 9999);
}

/**
 * Generate unique ticket number
 */
function generateTicketNumber() {
    return 'TKT-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 6, '0', STR_PAD_LEFT);
}

/**
 * Generate QR Code data
 */
function generateQRCodeData($ticketNumber) {
    return $ticketNumber . '|' . date('Y-m-d H:i:s');
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return 'ETB ' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Format time
 */
function formatTime($time, $format = 'H:i A') {
    return date($format, strtotime($time));
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check user role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Redirect if not authorized
 */
function requireRole($roles) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (!in_array($_SESSION['role'], $roles)) {
        header('HTTP/1.0 403 Forbidden');
        echo 'Access Denied';
        exit();
    }
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number
 */
function isValidPhone($phone) {
    return preg_match('/^\d{10}$/', preg_replace('/\D/', '', $phone));
}

/**
 * Generate password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Log audit trail
 */
function logAudit($conn, $user_id, $action, $entity_type, $entity_id, $old_value = null, $new_value = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $stmt = $conn->prepare(
        'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_value, new_value, ip_address) 
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    
    $stmt->bind_param(
        'ississs',
        $user_id,
        $action,
        $entity_type,
        $entity_id,
        $old_value,
        $new_value,
        $ip_address
    );
    
    return $stmt->execute();
}

/**
 * Get occupancy rate
 */
function getOccupancyRate($conn, $trip_id) {
    $stmt = $conn->prepare(
        'SELECT 
            (SELECT COUNT(*) FROM trip_seats WHERE trip_id = ? AND status IN ("booked", "paid")) as booked,
            (SELECT COUNT(*) FROM trip_seats WHERE trip_id = ?) as total'
    );
    
    $stmt->bind_param('ii', $trip_id, $trip_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        return ($row['booked'] / $row['total']) * 100;
    }
    
    return 0;
}

/**
 * Get available seats count
 */
function getAvailableSeatsCount($conn, $trip_id) {
    $stmt = $conn->prepare(
        'SELECT COUNT(*) as count FROM trip_seats WHERE trip_id = ? AND status = "available"'
    );
    
    $stmt->bind_param('i', $trip_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] ?? 0;
}

/**
 * Send email notification
 */
function sendEmail($to, $subject, $message, $headers = []) {
    $default_headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: noreply@safeway.com'
    ];
    
    $headers = array_merge($default_headers, $headers);
    $header_string = implode("\r\n", $headers);
    
    return mail($to, $subject, $message, $header_string);
}

/**
 * Generate seat map for bus
 */
function getSeatMap($conn, $bus_id, $trip_id = null) {
    $stmt = $conn->prepare(
        'SELECT s.id, s.seat_number, s.row_number, s.column_number, s.seat_type, 
                COALESCE(ts.status, "available") as trip_status
         FROM seats s
         LEFT JOIN trip_seats ts ON s.id = ts.seat_id AND ts.trip_id = ?
         WHERE s.bus_id = ?
         ORDER BY s.row_number, s.column_number'
    );
    
    $stmt->bind_param('ii', $trip_id, $bus_id);
    $stmt->execute();
    
    return $stmt->get_result();
}

/**
 * Calculate distance between two points (Haversine formula)
 */
function calculateDistance($from_lat, $from_lng, $to_lat, $to_lng) {
    $earth_radius = 6371; // Radius of the earth in km
    
    $lat_diff = deg2rad($to_lat - $from_lat);
    $lng_diff = deg2rad($to_lng - $from_lng);
    
    $a = sin($lat_diff / 2) * sin($lat_diff / 2) +
        cos(deg2rad($from_lat)) * cos(deg2rad($to_lat)) *
        sin($lng_diff / 2) * sin($lng_diff / 2);
    
    $c = 2 * asin(sqrt($a));
    $distance = $earth_radius * $c;
    
    return round($distance, 2);
}

/**
 * Get trip details with all related information
 */
function getTripDetails($conn, $trip_id) {
    $stmt = $conn->prepare(
        'SELECT t.*, 
                b.bus_number, b.bus_name, b.total_seats, b.vehicle_grade,
                r.route_code,
                d1.destination_name as from_destination,
                d2.destination_name as to_destination,
                u.full_name as cashier_name
         FROM trips t
         JOIN buses b ON t.bus_id = b.id
         JOIN routes r ON t.route_id = r.id
         JOIN destinations d1 ON r.from_destination_id = d1.id
         JOIN destinations d2 ON r.to_destination_id = d2.id
         LEFT JOIN users u ON t.assigned_cashier_id = u.id
         WHERE t.id = ?'
    );
    
    $stmt->bind_param('i', $trip_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}
?>