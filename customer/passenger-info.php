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
$selected_seats = isset($_POST['selected_seats']) ? explode(',', $_POST['selected_seats']) : [];

if (!$trip_id || empty($selected_seats)) {
    header('Location: booking.php');
    exit();
}

// Validate trip
$stmt = $conn->prepare('SELECT id FROM trips WHERE id = ?');
$stmt->bind_param('i', $trip_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    die('Invalid trip');
}

// Get trip details
$trip = getTripDetails($conn, $trip_id);

// Get seat details
$seats = [];
$seat_ids = array_map('intval', $selected_seats);
if (!empty($seat_ids)) {
    $placeholders = implode(',', $seat_ids);
    $stmt = $conn->prepare(
        'SELECT id, seat_number FROM seats WHERE id IN (' . $placeholders . ')'
    );
    $stmt->execute();
    $seats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Calculate total
$total_price = $trip['fare'] * count($seats);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Information - SafeWay Transport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 25px;
        }

        .form-body {
            padding: 30px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h5 {
            color: #0066cc;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0066cc;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .summary-panel {
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.05), rgba(0, 102, 204, 0.02));
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #0066cc;
            margin-bottom: 30px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item.total {
            border-top: 2px solid #0066cc;
            padding-top: 15px;
            font-size: 16px;
            font-weight: bold;
            color: #0066cc;
        }

        .seat-display {
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

        .payment-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .payment-option {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            border-color: #0066cc;
            background: rgba(0, 102, 204, 0.05);
        }

        .payment-option.selected {
            border-color: #0066cc;
            background: rgba(0, 102, 204, 0.1);
        }

        .payment-option i {
            font-size: 32px;
            color: #0066cc;
            margin-bottom: 10px;
            display: block;
        }

        .payment-option-label {
            font-weight: 600;
            font-size: 13px;
            margin-top: 5px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #00b050, #00a047);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            cursor: pointer;
            width: 100%;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #00a047, #008c3f);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 176, 80, 0.3);
            color: white;
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

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .form-body {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
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

    <div class="form-container">
        <div class="form-header">
            <h3><i class="fas fa-user-check"></i> Passenger Information</h3>
            <p class="mb-0" style="font-size: 14px; opacity: 0.9;">Enter your details to complete the booking</p>
        </div>

        <div class="form-body">
            <!-- Summary Panel -->
            <div class="summary-panel">
                <div class="summary-item">
                    <span><i class="fas fa-bus"></i> Bus:</span>
                    <strong><?php echo htmlspecialchars($trip['bus_name']); ?></strong>
                </div>
                <div class="summary-item">
                    <span><i class="fas fa-chair"></i> Seats:</span>
                    <div class="seat-display">
                        <?php foreach ($seats as $seat): ?>
                            <span class="seat-badge"><?php echo htmlspecialchars($seat['seat_number']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="summary-item">
                    <span><i class="fas fa-map-marker-alt"></i> Route:</span>
                    <strong><?php echo htmlspecialchars($trip['from_destination']) . ' → ' . htmlspecialchars($trip['to_destination']); ?></strong>
                </div>
                <div class="summary-item">
                    <span><i class="fas fa-calendar-alt"></i> Date:</span>
                    <strong><?php echo formatDate($trip['trip_date']); ?></strong>
                </div>
                <div class="summary-item">
                    <span><i class="fas fa-clock"></i> Departure:</span>
                    <strong><?php echo formatTime($trip['departure_time']); ?></strong>
                </div>
                <div class="summary-item total">
                    <span>Total Price:</span>
                    <span><?php echo formatCurrency($total_price); ?></span>
                </div>
            </div>

            <form method="POST" action="process-booking.php" novalidate>
                <input type="hidden" name="trip_id" value="<?php echo $trip_id; ?>">
                <input type="hidden" name="selected_seats" value="<?php echo htmlspecialchars(implode(',', $selected_seats)); ?>">

                <!-- Passenger Information Section -->
                <div class="form-section">
                    <h5><i class="fas fa-id-card"></i> Passenger Details</h5>
                    <div class="form-row">
                        <div>
                            <label for="fullName" class="form-label">Full Name <span style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="fullName" name="full_name" required>
                        </div>
                        <div>
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label for="phone" class="form-label">Phone Number <span style="color: red;">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="09XXXXXXXX" required>
                        </div>
                        <div>
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" min="1" max="120">
                        </div>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="form-section">
                    <h5><i class="fas fa-credit-card"></i> Payment Method</h5>
                    <div class="payment-options">
                        <label class="payment-option selected" onclick="selectPaymentMethod(this, 'cash')">
                            <input type="radio" name="payment_method" value="cash" checked style="display: none;">
                            <i class="fas fa-money-bill-wave"></i>
                            <div class="payment-option-label">Cash</div>
                        </label>
                        <label class="payment-option" onclick="selectPaymentMethod(this, 'card')">
                            <input type="radio" name="payment_method" value="card" style="display: none;">
                            <i class="fas fa-credit-card"></i>
                            <div class="payment-option-label">Card</div>
                        </label>
                        <label class="payment-option" onclick="selectPaymentMethod(this, 'mobile_money')">
                            <input type="radio" name="payment_method" value="mobile_money" style="display: none;">
                            <i class="fas fa-mobile-alt"></i>
                            <div class="payment-option-label">Mobile Money</div>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="button" class="btn-back" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-check"></i> Complete Booking
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPaymentMethod(element, method) {
            // Remove selected class from all payment options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            // Add selected class to clicked option
            element.classList.add('selected');
            // Set the radio button value
            element.querySelector('input[type="radio"]').checked = true;
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) value = value.slice(0, 10);
            e.target.value = value;
        });
    </script>
</body>
</html>
