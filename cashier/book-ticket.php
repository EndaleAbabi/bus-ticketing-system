<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole('CASHIER');

$trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

if (!$trip_id) {
    header('Location: dashboard.php');
    exit();
}

// Get trip details
$trip = getTripDetails($conn, $trip_id);

if (!$trip) {
    die('Trip not found');
}

// Get seat map
$seat_result = getSeatMap($conn, $trip['bus_id'], $trip_id);
$seats = $seat_result->fetch_all(MYSQLI_ASSOC);

// Organize seats by row
$seat_rows = [];
foreach ($seats as $seat) {
    $row = $seat['row_number'];
    if (!isset($seat_rows[$row])) {
        $seat_rows[$row] = [];
    }
    $seat_rows[$row][] = $seat;
}
ksort($seat_rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Ticket - SafeWay Transport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .booking-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .booking-header {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .trip-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            font-weight: 600;
        }

        .info-value {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
        }

        .row-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .seat-section, .passenger-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }

        .section-title {
            color: #0066cc;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0066cc;
        }

        .bus-front {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #333;
        }

        .bus-front i {
            font-size: 32px;
            color: #0066cc;
        }

        .seats-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .seat-row {
            display: flex;
            justify-content: center;
            gap: 12px;
            align-items: center;
        }

        .row-label {
            width: 25px;
            text-align: center;
            font-weight: bold;
            color: #666;
            font-size: 12px;
        }

        .seat-item {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 11px;
            transition: all 0.3s ease;
            background: white;
        }

        .seat-item.available {
            background: #e8f5e9;
            border-color: #00b050;
            color: #00b050;
        }

        .seat-item.available:hover {
            background: #00b050;
            color: white;
            transform: scale(1.1);
        }

        .seat-item.selected {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            border-color: #0066cc;
            color: white;
            transform: scale(1.1);
        }

        .seat-item.booked {
            background: #ffebee;
            border-color: #d32f2f;
            color: #d32f2f;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .form-section {
            margin-bottom: 25px;
        }

        .form-section h6 {
            color: #0066cc;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .summary-panel {
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.05), rgba(0, 102, 204, 0.02));
            border-left: 4px solid #0066cc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
        }

        .summary-row.total {
            border-top: 2px solid #0066cc;
            padding-top: 12px;
            font-size: 15px;
            font-weight: bold;
            color: #0066cc;
        }

        .selected-seats {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .seat-badge {
            background: #0066cc;
            color: white;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-submit {
            flex: 1;
            background: linear-gradient(135deg, #00b050, #00a047);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-submit:hover:not(:disabled) {
            background: linear-gradient(135deg, #00a047, #008c3f);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-back {
            background: white;
            border: 2px solid #0066cc;
            color: #0066cc;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #f5f7fa;
        }

        @media (max-width: 768px) {
            .row-section {
                grid-template-columns: 1fr;
            }

            .seat-item {
                width: 35px;
                height: 35px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body style="background: #f5f7fa;">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #0066cc, #0052a3);">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-bus"></i> SafeWay Transport
            </a>
        </div>
    </nav>

    <div class="booking-container">
        <!-- Trip Info -->
        <div class="booking-header">
            <div class="trip-info">
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-bus"></i> Bus</span>
                    <span class="info-value"><?php echo htmlspecialchars($trip['bus_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-map-marker-alt"></i> Route</span>
                    <span class="info-value"><?php echo htmlspecialchars($trip['from_destination']) . ' → ' . htmlspecialchars($trip['to_destination']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-calendar-alt"></i> Date</span>
                    <span class="info-value"><?php echo formatDate($trip['trip_date']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-clock"></i> Departure</span>
                    <span class="info-value"><?php echo formatTime($trip['departure_time']); ?></span>
                </div>
            </div>
        </div>

        <!-- Booking Form -->
        <form id="bookingForm" method="POST" action="process-cashier-booking.php" novalidate>
            <input type="hidden" name="trip_id" value="<?php echo $trip_id; ?>">
            <input type="hidden" name="selected_seats" id="selectedSeatsInput" value="">

            <div class="row-section">
                <!-- Seat Selection -->
                <div class="seat-section">
                    <h5 class="section-title"><i class="fas fa-chair"></i> Select Seats</h5>

                    <div class="bus-front">
                        <i class="fas fa-truck-front"></i>
                        <div style="font-weight: bold; font-size: 12px; margin-top: 5px;">DRIVER</div>
                    </div>

                    <div class="seats-container">
                        <?php foreach ($seat_rows as $row_num => $row_seats): ?>
                            <div class="seat-row">
                                <div class="row-label"><?php echo chr(64 + $row_num); ?></div>
                                <?php foreach ($row_seats as $seat): ?>
                                    <button type="button" 
                                            class="seat-item <?php echo $seat['trip_status']; ?>" 
                                            id="seat-<?php echo $seat['id']; ?>"
                                            data-seat-id="<?php echo $seat['id']; ?>"
                                            data-seat-number="<?php echo htmlspecialchars($seat['seat_number']); ?>"
                                            onclick="toggleSeat(this)"
                                            <?php echo ($seat['trip_status'] !== 'available') ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($seat['seat_number']); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                        <small style="color: #888; font-weight: 600;">SELECTED SEATS:</small>
                        <div class="selected-seats" id="selectedSeatsDisplay"></div>
                    </div>
                </div>

                <!-- Passenger Information -->
                <div class="passenger-section">
                    <h5 class="section-title"><i class="fas fa-user-check"></i> Passenger Information</h5>

                    <div class="form-section">
                        <h6>Personal Details</h6>
                        <div class="form-row">
                            <div>
                                <label class="form-label">Full Name <span style="color: red;">*</span></label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div>
                                <label class="form-label">Phone Number <span style="color: red;">*</span></label>
                                <input type="tel" class="form-control" name="phone" placeholder="09XXXXXXXX" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Age</label>
                                <input type="number" class="form-control" name="age" min="1" max="120">
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="summary-panel">
                        <div class="summary-row">
                            <span>Fare per Seat:</span>
                            <strong><?php echo formatCurrency($trip['fare']); ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Number of Seats:</span>
                            <strong id="seatCount">0</strong>
                        </div>
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <strong id="subtotal">ETB 0.00</strong>
                        </div>
                        <div class="summary-row total">
                            <span>TOTAL:</span>
                            <span id="totalPrice">ETB 0.00</span>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6>Payment Method</h6>
                        <div class="form-row">
                            <select class="form-select" name="payment_method">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="button" class="btn-back" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn-submit" id="submitBtn" disabled>
                            <i class="fas fa-check"></i> Complete Booking
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedSeats = [];
        const farePerSeat = <?php echo $trip['fare']; ?>;

        function toggleSeat(element) {
            const seatId = element.getAttribute('data-seat-id');
            const seatNumber = element.getAttribute('data-seat-number');

            if (element.classList.contains('selected')) {
                element.classList.remove('selected');
                selectedSeats = selectedSeats.filter(s => s.id !== seatId);
            } else {
                element.classList.add('selected');
                selectedSeats.push({
                    id: seatId,
                    number: seatNumber
                });
            }

            updateSummary();
        }

        function updateSummary() {
            const count = selectedSeats.length;
            const subtotal = farePerSeat * count;
            const total = subtotal;

            document.getElementById('seatCount').textContent = count;
            document.getElementById('subtotal').textContent = 'ETB ' + subtotal.toFixed(2);
            document.getElementById('totalPrice').textContent = 'ETB ' + total.toFixed(2);
            
            const display = document.getElementById('selectedSeatsDisplay');
            display.innerHTML = selectedSeats.map(seat => 
                '<span class="seat-badge">' + seat.number + '</span>'
            ).join('');

            document.getElementById('selectedSeatsInput').value = selectedSeats.map(s => s.id).join(',');
            document.getElementById('submitBtn').disabled = count === 0;
        }

        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });

        document.querySelector('input[name="phone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) value = value.slice(0, 10);
            e.target.value = value;
        });
    </script>
</body>
</html>
