<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Get all bookings for current customer
$stmt = $conn->prepare(
    'SELECT b.id, b.booking_reference, b.customer_name, b.trip_id, t.trip_date, t.departure_time,
            d1.destination_name as from_destination,
            d2.destination_name as to_destination,
            b.fare, b.payment_status, b.created_at,
            tk.ticket_number
     FROM bookings b
     JOIN trips t ON b.trip_id = t.id
     JOIN routes r ON t.route_id = r.id
     JOIN destinations d1 ON r.from_destination_id = d1.id
     JOIN destinations d2 ON r.to_destination_id = d2.id
     LEFT JOIN tickets tk ON b.id = tk.booking_id
     WHERE b.customer_id = ?
     ORDER BY b.created_at DESC'
);

$user_id = $_SESSION['user_id'];
$stmt->bind_param('i', $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - SafeWay Transport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .bookings-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .booking-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-3px);
        }

        .booking-card-header {
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.1), rgba(0, 102, 204, 0.05));
            border-bottom: 2px solid #e0e0e0;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .booking-ref {
            font-weight: bold;
            color: #0066cc;
            font-size: 14px;
        }

        .booking-date {
            font-size: 12px;
            color: #888;
        }

        .booking-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-paid {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-pending {
            background: #fff3e0;
            color: #e65100;
        }

        .booking-card-body {
            padding: 20px;
        }

        .booking-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 12px;
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 15px;
            font-weight: bold;
            color: #333;
        }

        .booking-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            margin-top: 15px;
        }

        .btn-action {
            flex: 1;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-view-ticket {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
        }

        .btn-view-ticket:hover {
            background: linear-gradient(135deg, #0052a3, #003d7a);
            color: white;
            text-decoration: none;
        }

        .btn-print {
            background: #f5f7fa;
            color: #0066cc;
            border: 1px solid #0066cc;
        }

        .btn-print:hover {
            background: #e8f0ff;
            text-decoration: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
            display: block;
        }

        .empty-state h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #888;
            margin-bottom: 20px;
        }

        .btn-new-booking {
            background: linear-gradient(135deg, #00b050, #00a047);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-new-booking:hover {
            background: linear-gradient(135deg, #00a047, #008c3f);
            color: white;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .booking-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .booking-info-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .booking-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body style="background: #f5f7fa;">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #0066cc, #0052a3);">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-bus"></i> SafeWay Transport
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="booking.php"><i class="fas fa-search"></i> New Booking</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="bookings-container">
        <!-- Page Header -->
        <div class="page-header">
            <h2 class="mb-2"><i class="fas fa-history"></i> My Bookings</h2>
            <p class="mb-0">View and manage your bus tickets</p>
        </div>

        <?php if (!empty($bookings)): ?>
            <!-- Bookings List -->
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <div class="booking-card-header">
                        <div>
                            <div class="booking-ref"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
                            <div class="booking-date"><?php echo formatDate($booking['created_at']); ?></div>
                        </div>
                        <span class="booking-status status-<?php echo strtolower($booking['payment_status']); ?>">
                            <?php echo ucfirst($booking['payment_status']); ?>
                        </span>
                    </div>

                    <div class="booking-card-body">
                        <div class="booking-info-grid">
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-map-marker-alt"></i> Route</span>
                                <span class="info-value"><?php echo htmlspecialchars($booking['from_destination']); ?> → <?php echo htmlspecialchars($booking['to_destination']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-calendar-alt"></i> Date</span>
                                <span class="info-value"><?php echo formatDate($booking['trip_date']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-clock"></i> Departure</span>
                                <span class="info-value"><?php echo formatTime($booking['departure_time']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-ticket-alt"></i> Ticket</span>
                                <span class="info-value"><?php echo htmlspecialchars($booking['ticket_number'] ?? 'N/A'); ?></span>
                            </div>
                        </div>

                        <div class="booking-actions">
                            <a href="ticket.php?booking_id=<?php echo $booking['id']; ?>" class="btn-action btn-view-ticket">
                                <i class="fas fa-ticket-alt"></i> View Ticket
                            </a>
                            <button class="btn-action btn-print" onclick="printBooking(<?php echo $booking['id']; ?>)">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>No Bookings Yet</h4>
                <p>You haven't booked any tickets yet. Start your journey with us today!</p>
                <a href="booking.php" class="btn-new-booking">
                    <i class="fas fa-ticket-alt"></i> Book a Ticket
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printBooking(bookingId) {
            window.open('ticket.php?booking_id=' + bookingId, '_blank');
        }
    </script>
</body>
</html>
