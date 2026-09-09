<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php');
    exit();
}

$trip_id = isset($_POST['trip_id']) ? (int)$_POST['trip_id'] : 0;
$selected_seats = isset($_POST['selected_seats']) ? array_map('intval', explode(',', $_POST['selected_seats'])) : [];
$full_name = sanitize($_POST['full_name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$gender = sanitize($_POST['gender'] ?? '');
$age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
$payment_method = sanitize($_POST['payment_method'] ?? 'cash');

// Validate inputs
if (!$trip_id || empty($selected_seats) || empty($full_name) || empty($phone)) {
    die('Invalid booking data');
}

// Get trip details
$trip = getTripDetails($conn, $trip_id);
if (!$trip) {
    die('Trip not found');
}

// Calculate total
$total_price = $trip['fare'] * count($selected_seats);

// Start transaction
$conn->begin_transaction();

try {
    // Create booking reference
    $booking_reference = generateBookingReference();
    
    // Insert booking
    $stmt = $conn->prepare(
        'INSERT INTO bookings (booking_reference, trip_id, customer_id, customer_name, customer_gender, customer_age, customer_phone, customer_email, fare, booking_type, payment_status, payment_method, booked_by_user_id, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "online", "paid", ?, ?, NOW())'
    );
    
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param(
        'siiisssssi',
        $booking_reference,
        $trip_id,
        $user_id,
        $full_name,
        $gender,
        $age,
        $phone,
        $email,
        $total_price,
        $payment_method,
        $user_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Booking creation failed');
    }
    
    $booking_id = $conn->insert_id;
    
    // Update trip seats status
    $placeholders = implode(',', $selected_seats);
    $update_stmt = $conn->prepare(
        'UPDATE trip_seats SET status = "paid", booking_reference = ? WHERE trip_id = ? AND seat_id IN (' . $placeholders . ')'
    );
    $update_stmt->bind_param('si', $booking_reference, $trip_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Seat update failed');
    }
    
    // Generate ticket
    $ticket_number = generateTicketNumber();
    $qr_code = generateQRCodeData($ticket_number);
    
    $ticket_stmt = $conn->prepare(
        'INSERT INTO tickets (ticket_number, booking_id, trip_id, qr_code, status, created_at)
         VALUES (?, ?, ?, ?, "active", NOW())'
    );
    $ticket_stmt->bind_param('siis', $ticket_number, $booking_id, $trip_id, $qr_code);
    
    if (!$ticket_stmt->execute()) {
        throw new Exception('Ticket generation failed');
    }
    
    // Insert payment record
    $payment_stmt = $conn->prepare(
        'INSERT INTO payments (booking_id, amount, payment_method, payment_status, processed_by, created_at)
         VALUES (?, ?, ?, "completed", ?, NOW())'
    );
    $payment_stmt->bind_param('idsi', $booking_id, $total_price, $payment_method, $user_id);
    
    if (!$payment_stmt->execute()) {
        throw new Exception('Payment record failed');
    }
    
    // Log audit trail
    logAudit($conn, $user_id, 'BOOKING_CREATED', 'booking', $booking_id, null, 'Booking Reference: ' . $booking_reference);
    
    // Commit transaction
    $conn->commit();
    
    // Redirect to ticket view
    header('Location: ticket.php?booking_id=' . $booking_id);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    die('Booking failed: ' . $e->getMessage());
}
?>
