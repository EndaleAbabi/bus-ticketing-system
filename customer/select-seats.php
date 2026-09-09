<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

if (!$trip_id) {
    header('Location: booking.php');
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
    <title>Select Seats - SafeWay Transport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .seat-selection-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .trip-info-header {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .trip-info-grid {
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

        .bus-layout {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .bus-front {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #333;
        }

        .bus-front i {
            font-size: 40px;
            color: #0066cc;
        }

        .bus-front-text {
            font-weight: bold;
            font-size: 14px;
            color: #333;
            margin-top: 10px;
        }

        .seats-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }

        .seat-row {
            display: flex;
            justify-content: center;
            gap: 15px;
            align-items: center;
        }

        .row-label {
            width: 30px;
            text-align: center;
            font-weight: bold;
            color: #666;
            font-size: 12px;
        }

        .seat-item {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
            background: white;
            position: relative;
        }

        .seat-item.available {
            background: #e8f5e9;
            border-color: #00b050;
            color: #00b050;
        }

        .seat-item.available:hover {
            background: #00b050;
            color: white;
            transform: scale(1.15);
            box-shadow: 0 4px 12px rgba(0, 176, 80, 0.3);
        }

        .seat-item.selected {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            border-color: #0066cc;
            color: white;
            transform: scale(1.15);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
        }

        .seat-item.booked {
            background: #ffebee;
            border-color: #d32f2f;
            color: #d32f2f;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .seat-item.disabled {
            background: #f0f0f0;
            border-color: #ccc;
            cursor: not-allowed;
            opacity: 0.4;
        }

        .seat-legend {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 30px;
            padding: 20px;
            background: #f5f7fa;
            border-radius: 12px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .legend-box {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 2px solid;
        }

        .summary-section {
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.05), rgba(0, 102, 204, 0.02));
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #0066cc;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
        }

        .summary-row.total {
            border-top: 2px solid #0066cc;
            padding-top: 15px;
            font-size: 16px;
            font-weight: bold;
            color: #0066cc;
        }

        .selected-seats-display {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .seat-badge {
            background: #0066cc;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-continue {
            flex: 1;
            background: linear-gradient(135deg, #00b050, #00a047);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-continue:hover:not(:disabled) {
            background: linear-gradient(135deg, #00a047, #008c3f);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 176, 80, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-continue:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-back {
            background: white;
            border: 2px solid #0066cc;
            color: #0066cc;
            font-weight: 600;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #f5f7fa;
            text-decoration: none;
        }

        .error-message {
            background: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            border-left: 4px solid #d32f2f;
        }

        @media (max-width: 768px) {
            .seat-selection-container {
                padding: 20px;
            }

            .seat-item {
                width: 40px;
                height: 40px;
                font-size: 11px;
            }

            .seat-row {
                gap: 10px;
            }

            .trip-info-grid {
                grid-template-columns: repeat(2, 1fr);
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
                        <a class="nav-link" href="booking.php"><i class="fas fa-search"></i> Search Buses</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my-bookings.php"><i class="fas fa-history"></i> My Bookings</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="seat-selection-container">
        <!-- Trip Info -->
        <div class="trip-info-header">
            <div class="trip-info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-bus"></i> Bus</span>
                    <span class="info-value"><?php echo htmlspecialchars($trip['bus_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-map-marker-alt"></i> Route</span>
                    <span class="info-value"><?php echo htmlspecialchars($trip['from_destination']); ?> → <?php echo htmlspecialchars($trip['to_destination']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-clock"></i> Departure</span>
                    <span class="info-value"><?php echo formatTime($trip['departure_time']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-money-bill"></i> Fare</span>
                    <span class="info-value"><?php echo formatCurrency($trip['fare']); ?></span>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div class="error-message" id="errorMessage"></div>

        <!-- Bus Layout -->
        <div class="bus-layout">
            <div class="bus-front">
                <i class="fas fa-truck-front"></i>
                <div class="bus-front-text">← DRIVER →</div>
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
                                    data-seat-price="<?php echo $trip['fare']; ?>"
                                    onclick="toggleSeat(this)"
                                    <?php echo ($seat['trip_status'] !== 'available') ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($seat['seat_number']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Legend -->
        <div class="seat-legend">
            <div class="legend-item">
                <div class="legend-box" style="background: #e8f5e9; border-color: #00b050;"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-box" style="background: linear-gradient(135deg, #0066cc, #0052a3); border-color: #0066cc;"></div>
                <span>Selected</span>
            </div>
            <div class="legend-item">
                <div class="legend-box" style="background: #ffebee; border-color: #d32f2f; opacity: 0.6;"></div>
                <span>Booked</span>
            </div>
        </div>

        <!-- Summary -->
        <div class="summary-section">
            <div class="summary-row">
                <span><i class="fas fa-chair"></i> Selected Seats:</span>
                <span id="selectedSeatsCount">None</span>
            </div>
            <div id="selectedSeatsList" class="selected-seats-display"></div>
            <div class="summary-row">
                <span>Fare per Seat:</span>
                <span><?php echo formatCurrency($trip['fare']); ?></span>
            </div>
            <div class="summary-row total">
                <span>Total Price:</span>
                <span id="totalPrice">ETB 0.00</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn-back" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <form id="seatForm" method="POST" action="passenger-info.php" style="flex: 1;">
                <input type="hidden" name="trip_id" value="<?php echo $trip_id; ?>">
                <input type="hidden" name="selected_seats" id="selectedSeatsInput" value="">
                <button type="submit" class="btn-continue" id="continueBtn" disabled>
                    <i class="fas fa-arrow-right"></i> Continue to Passenger Info
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedSeats = [];
        const farePerSeat = <?php echo $trip['fare']; ?>;
        const maxSeats = 5; // Maximum seats per booking

        function toggleSeat(element) {
            const seatId = element.getAttribute('data-seat-id');
            const seatNumber = element.getAttribute('data-seat-number');
            const seatPrice = parseFloat(element.getAttribute('data-seat-price'));

            if (element.classList.contains('selected')) {
                // Deselect
                element.classList.remove('selected');
                selectedSeats = selectedSeats.filter(s => s.id !== seatId);
            } else {
                // Check if max seats reached
                if (selectedSeats.length >= maxSeats) {
                    showError('You can select maximum ' + maxSeats + ' seats');
                    return;
                }
                // Select
                element.classList.add('selected');
                selectedSeats.push({
                    id: seatId,
                    number: seatNumber,
                    price: seatPrice
                });
            }

            updateSummary();
        }

        function updateSummary() {
            const count = selectedSeats.length;
            const total = selectedSeats.reduce((sum, seat) => sum + seat.price, 0);

            // Update count
            document.getElementById('selectedSeatsCount').textContent = count > 0 ? count + ' seat' + (count > 1 ? 's' : '') : 'None';

            // Update seats list
            const seatsList = document.getElementById('selectedSeatsList');
            seatsList.innerHTML = selectedSeats.map(seat => 
                '<span class="seat-badge">' + seat.number + '</span>'
            ).join('');

            // Update total
            document.getElementById('totalPrice').textContent = 'ETB ' + total.toFixed(2);

            // Update hidden input
            document.getElementById('selectedSeatsInput').value = selectedSeats.map(s => s.id).join(',');

            // Enable/disable continue button
            const continueBtn = document.getElementById('continueBtn');
            continueBtn.disabled = count === 0;
        }

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 3000);
        }

        // Keyboard support
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && selectedSeats.length > 0) {
                document.getElementById('seatForm').submit();
            }
        });
    </script>
</body>
</html>
