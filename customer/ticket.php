<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id) {
    header('Location: booking.php');
    exit();
}

// Get booking details
$stmt = $conn->prepare(
    'SELECT b.*, t.departure_time, t.trip_date, t.arrival_time, 
            bus.bus_number, bus.vehicle_grade,
            d1.destination_name as from_destination,
            d2.destination_name as to_destination,
            tk.ticket_number, tk.qr_code
     FROM bookings b
     JOIN trips t ON b.trip_id = t.id
     JOIN buses bus ON t.bus_id = bus.id
     JOIN routes r ON t.route_id = r.id
     JOIN destinations d1 ON r.from_destination_id = d1.id
     JOIN destinations d2 ON r.to_destination_id = d2.id
     LEFT JOIN tickets tk ON b.id = tk.booking_id
     WHERE b.id = ? AND b.customer_id = ?'
);

$user_id = $_SESSION['user_id'];
$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die('Booking not found');
}

// Get seats for this booking
$stmt = $conn->prepare(
    'SELECT s.seat_number FROM seats s
     JOIN trip_seats ts ON s.id = ts.seat_id
     WHERE ts.trip_id = ? AND ts.booking_reference = ?'
);

$stmt->bind_param('is', $booking['trip_id'], $booking['booking_reference']);
$stmt->execute();
$seats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Ticket - SafeWay Transport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .ticket-container {
            max-width: 500px;
            margin: 40px auto;
        }

        .ticket {
            background: white;
            border: 2px dashed #0066cc;
            border-radius: 12px;
            padding: 30px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.8;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .ticket-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .ticket-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .ticket-subtitle {
            font-size: 11px;
            color: #666;
        }

        .ticket-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #ccc;
        }

        .ticket-row:last-child {
            border-bottom: none;
        }

        .ticket-label {
            font-weight: bold;
            flex: 1;
        }

        .ticket-value {
            flex: 1;
            text-align: right;
            word-break: break-word;
        }

        .ticket-section {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dotted #ccc;
        }

        .ticket-section-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
            margin-bottom: 8px;
            color: #333;
        }

        .qr-code-section {
            text-align: center;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px dotted #ccc;
            border-bottom: 1px dotted #ccc;
        }

        .qr-code-section img {
            max-width: 150px;
            height: auto;
        }

        .ticket-footer {
            text-align: center;
            border-top: 2px solid #333;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 11px;
            font-style: italic;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-action {
            flex: 1;
            min-width: 150px;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-print {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
        }

        .btn-print:hover {
            background: linear-gradient(135deg, #0052a3, #003d7a);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 102, 204, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-home {
            background: #f5f7fa;
            border: 2px solid #0066cc;
            color: #0066cc;
        }

        .btn-home:hover {
            background: #e8f0ff;
            text-decoration: none;
        }

        .success-message {
            background: linear-gradient(135deg, rgba(0, 176, 80, 0.1), rgba(0, 176, 80, 0.05));
            border-left: 4px solid #00b050;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message i {
            font-size: 32px;
            color: #00b050;
            margin-bottom: 10px;
            display: block;
        }

        .success-message h4 {
            color: #00b050;
            margin-bottom: 5px;
        }

        @media print {
            body {
                background: white;
            }

            .success-message,
            .action-buttons,
            .navbar {
                display: none;
            }

            .ticket-container {
                margin: 0;
                max-width: 80mm;
            }

            .ticket {
                box-shadow: none;
                page-break-after: avoid;
            }
        }

        @media (max-width: 768px) {
            .ticket {
                padding: 20px;
                font-size: 12px;
            }

            .btn-action {
                min-width: auto;
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
        </div>
    </nav>

    <div class="ticket-container">
        <!-- Success Message -->
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <h4>Booking Confirmed!</h4>
            <p class="mb-0">Your ticket has been successfully generated</p>
        </div>

        <!-- Ticket -->
        <div class="ticket" id="ticketContent">
            <div class="ticket-header">
                <i class="fas fa-bus" style="font-size: 24px; color: #0066cc; margin-bottom: 5px; display: block;"></i>
                <div class="ticket-title">PUBLIC TRANSPORT TICKET</div>
                <div class="ticket-title" style="font-size: 12px; font-weight: normal;">የሕዝብ ትራንስፖርት ትኬት</div>
            </div>

            <div class="ticket-row">
                <span class="ticket-label">Ticket No:</span>
                <span class="ticket-value"><?php echo htmlspecialchars($booking['ticket_number']); ?></span>
            </div>

            <div class="ticket-row">
                <span class="ticket-label">Booking Ref:</span>
                <span class="ticket-value"><?php echo htmlspecialchars($booking['booking_reference']); ?></span>
            </div>

            <div class="ticket-section">
                <div class="ticket-section-title">Journey Details</div>
                <div class="ticket-row">
                    <span class="ticket-label">FROM:</span>
                    <span class="ticket-value"><?php echo htmlspecialchars($booking['from_destination']); ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">TO:</span>
                    <span class="ticket-value"><?php echo htmlspecialchars($booking['to_destination']); ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">ROUTE:</span>
                    <span class="ticket-value"><?php echo htmlspecialchars($booking['from_destination']) . ' - ' . htmlspecialchars($booking['to_destination']); ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">TRAVEL DATE:</span>
                    <span class="ticket-value"><?php echo formatDate($booking['trip_date']); ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">DEPARTURE:</span>
                    <span class="ticket-value"><?php echo formatTime($booking['departure_time']); ?></span>
                </div>
            </div>

            <div class="ticket-section">
                <div class="ticket-section-title">Passenger Information</div>
                <div class="ticket-row">
                    <span class="ticket-label">NAME:</span>
                    <span class="ticket-value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">GENDER:</span>
                    <span class="ticket-value"><?php echo htmlspecialchars($booking['customer_gender'] ?: 'N/A'); ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">AGE:</span>
                    <span class="ticket-value"><?php echo $booking['customer_age'] ?: 'N/A'; ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">PHONE:</span>
                    <span class="ticket-value"><?php echo htmlspecialchars($booking['customer_phone']); ?></span>
                </div>
            </div>

            <div class="ticket-section">
                <div class="ticket-section-title">Vehicle Information</div>
                <div class="ticket-row">
                    <span class="ticket-label">BUS NO:</span>
                    <span class="ticket-value"><?php echo htmlspecialchars($booking['bus_number']); ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">GRADE:</span>
                    <span class="ticket-value"><?php echo htmlspecialchars($booking['vehicle_grade']); ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">SEATS:</span>
                    <span class="ticket-value"><?php echo implode(', ', array_map(function($s) { return $s['seat_number']; }, $seats)); ?></span>
                </div>
            </div>

            <div class="ticket-section">
                <div class="ticket-section-title">Fare Information</div>
                <div class="ticket-row">
                    <span class="ticket-label">FARE/SEAT:</span>
                    <span class="ticket-value"><?php echo formatCurrency($booking['fare']); ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">SEATS:</span>
                    <span class="ticket-value"><?php echo count($seats); ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">SUBTOTAL:</span>
                    <span class="ticket-value"><?php echo formatCurrency($booking['fare'] * count($seats)); ?></span>
                </div>
                <div class="ticket-row" style="border-top: 1px solid #333; padding-top: 8px; margin-top: 5px;">
                    <span class="ticket-label" style="font-size: 14px;">TOTAL:</span>
                    <span class="ticket-value" style="font-size: 14px; font-weight: bold;"><?php echo formatCurrency($booking['fare']); ?></span>
                </div>
            </div>

            <div class="ticket-section">
                <div class="ticket-section-title">Payment Details</div>
                <div class="ticket-row">
                    <span class="ticket-label">PAYMENT METHOD:</span>
                    <span class="ticket-value"><?php echo ucfirst(str_replace('_', ' ', $booking['payment_method'])); ?></span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">STATUS:</span>
                    <span class="ticket-value" style="color: #00b050; font-weight: bold;">PAID</span>
                </div>
            </div>

            <div class="qr-code-section">
                <div style="font-size: 11px; margin-bottom: 8px; font-weight: bold;">SCAN TO VERIFY</div>
                <svg id="qrcode" style="max-width: 140px; height: auto;"></svg>
            </div>

            <div class="ticket-footer">
                <div>Thank you for travelling with SafeWay Transport</div>
                <div>ስላገልግልዎ አመሰግናለሁ</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn-action btn-print" onclick="printTicket()">
                <i class="fas fa-print"></i> Print Ticket
            </button>
            <a href="my-bookings.php" class="btn-action btn-home">
                <i class="fas fa-list"></i> My Bookings
            </a>
            <a href="../index.php" class="btn-action btn-home">
                <i class="fas fa-home"></i> Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // Generate QR Code
        const qrData = '<?php echo htmlspecialchars($booking['ticket_number']); ?>';
        new QRCode(document.getElementById('qrcode'), {
            text: qrData,
            width: 140,
            height: 140,
            colorDark: '#0066cc',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });

        function printTicket() {
            window.print();
        }
    </script>
</body>
</html>
