<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Ticket - SafeWay Transport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .booking-stepper {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }

        .stepper-item {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .stepper-item.active .step-circle {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
        }

        .stepper-item.completed .step-circle {
            background: #00b050;
            color: white;
        }

        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .stepper-line {
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: -1;
        }

        .step-label {
            font-weight: 600;
            font-size: 14px;
            color: #666;
        }

        .stepper-item.active .step-label {
            color: #0066cc;
        }

        .search-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .bus-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .bus-card:hover {
            border-color: #0066cc;
            box-shadow: 0 8px 20px rgba(0, 102, 204, 0.2);
            transform: translateY(-5px);
        }

        .bus-card.selected {
            border-color: #0066cc;
            background: rgba(0, 102, 204, 0.05);
        }

        .bus-header {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 15px 20px;
        }

        .bus-info {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
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
        }

        .info-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }

        .fare-badge {
            background: #00b050;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
        }

        .seat-selection-area {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .seat-map-container {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 30px;
            margin: 20px 0;
        }

        .seat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(50px, 1fr));
            gap: 10px;
            margin-bottom: 30px;
        }

        .seat-item {
            width: 50px;
            height: 50px;
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
        }

        .seat-item.occupied {
            background: #ffebee;
            border-color: #d32f2f;
            color: #d32f2f;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .seat-legend {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .legend-box {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 2px solid;
        }

        .passenger-form {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

        .summary-card {
            background: linear-gradient(135deg, #f5f7fa, #ffffff);
            border-radius: 12px;
            padding: 25px;
            border-left: 4px solid #0066cc;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 600;
            color: #666;
        }

        .summary-value {
            font-weight: 700;
            color: #333;
        }

        .total-row {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 18px;
        }

        .total-row .summary-label,
        .total-row .summary-value {
            color: white;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: #0066cc;
            background: rgba(0, 102, 204, 0.05);
        }

        .payment-method.selected {
            border-color: #0066cc;
            background: rgba(0, 102, 204, 0.1);
        }

        .payment-method i {
            font-size: 32px;
            color: #0066cc;
            margin-bottom: 10px;
        }

        .btn-book {
            background: linear-gradient(135deg, #00b050, #00a047);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 40px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-book:hover {
            background: linear-gradient(135deg, #00a047, #008c3f);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 176, 80, 0.3);
            color: white;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .no-results i {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .search-card {
                padding: 20px;
            }

            .bus-info {
                grid-template-columns: 1fr 1fr;
            }

            .seat-grid {
                grid-template-columns: repeat(auto-fit, minmax(45px, 1fr));
            }

            .seat-item {
                width: 45px;
                height: 45px;
                font-size: 11px;
            }

            .booking-stepper {
                margin-bottom: 20px;
            }

            .stepper-item {
                font-size: 12px;
            }
        }
    </style>
</head>
<body style="background: #f5f7fa;">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #0066cc, #0052a3); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
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
                        <a class="nav-link" href="../index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my-bookings.php"><i class="fas fa-history"></i> My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link" style="cursor: default;"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Booking Container -->
    <div class="container-fluid" style="padding: 40px 20px;">
        <!-- Stepper -->
        <div class="booking-stepper">
            <div class="stepper-item active">
                <div class="step-circle">1</div>
                <div class="step-label">Search</div>
            </div>
            <div class="stepper-item">
                <div class="step-circle">2</div>
                <div class="step-label">Select Bus</div>
            </div>
            <div class="stepper-item">
                <div class="step-circle">3</div>
                <div class="step-label">Choose Seat</div>
            </div>
            <div class="stepper-item">
                <div class="step-circle">4</div>
                <div class="step-label">Passenger Info</div>
            </div>
            <div class="stepper-item">
                <div class="step-circle">5</div>
                <div class="step-label">Confirm</div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-card">
            <h3 class="mb-4"><i class="fas fa-search"></i> Search for Buses</h3>
            <form id="searchForm" method="GET" action="search-results.php" novalidate>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="from_destination" class="form-label"><i class="fas fa-map-marker-alt"></i> From</label>
                        <select class="form-select" id="from_destination" name="from_destination" required>
                            <option value="">Select Departure City</option>
                            <?php
                            $result = $conn->query('SELECT id, destination_name FROM destinations WHERE status = "active" ORDER BY destination_name');
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['destination_name']) . '</option>';
                            }
                            ?>
                        </select>
                        <div class="invalid-feedback">Please select a departure city.</div>
                    </div>

                    <div class="col-md-3">
                        <label for="to_destination" class="form-label"><i class="fas fa-map-marker-alt"></i> To</label>
                        <select class="form-select" id="to_destination" name="to_destination" required>
                            <option value="">Select Destination City</option>
                            <?php
                            $result = $conn->query('SELECT id, destination_name FROM destinations WHERE status = "active" ORDER BY destination_name');
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['destination_name']) . '</option>';
                            }
                            ?>
                        </select>
                        <div class="invalid-feedback">Please select a destination city.</div>
                    </div>

                    <div class="col-md-3">
                        <label for="travel_date" class="form-label"><i class="fas fa-calendar"></i> Travel Date</label>
                        <input type="date" class="form-control" id="travel_date" name="travel_date" required>
                        <div class="invalid-feedback">Please select a travel date.</div>
                    </div>

                    <div class="col-md-3">
                        <label for="passengers" class="form-label"><i class="fas fa-users"></i> Passengers</label>
                        <select class="form-select" id="passengers" name="passengers">
                            <option value="1">1 Passenger</option>
                            <option value="2">2 Passengers</option>
                            <option value="3">3 Passengers</option>
                            <option value="4">4 Passengers</option>
                            <option value="5">5+ Passengers</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg mt-4 w-100">
                    <i class="fas fa-search"></i> Search Buses
                </button>
            </form>
        </div>

        <!-- Info Section -->
        <div class="row mt-5">
            <div class="col-md-4">
                <div class="card border-0 text-center" style="background: linear-gradient(135deg, rgba(0, 102, 204, 0.1), transparent); border-radius: 12px;">
                    <div class="card-body">
                        <i class="fas fa-clock" style="font-size: 32px; color: #0066cc; margin-bottom: 15px;"></i>
                        <h5 class="card-title">Easy Booking</h5>
                        <p class="card-text">Book your ticket in just 5 simple steps</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 text-center" style="background: linear-gradient(135deg, rgba(0, 176, 80, 0.1), transparent); border-radius: 12px;">
                    <div class="card-body">
                        <i class="fas fa-lock" style="font-size: 32px; color: #00b050; margin-bottom: 15px;"></i>
                        <h5 class="card-title">Secure Payment</h5>
                        <p class="card-text">Your payment is 100% safe and secure</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 text-center" style="background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), transparent); border-radius: 12px;">
                    <div class="card-body">
                        <i class="fas fa-ticket-alt" style="font-size: 32px; color: #ffc107; margin-bottom: 15px;"></i>
                        <h5 class="card-title">Instant Ticket</h5>
                        <p class="card-text">Get your ticket instantly on your phone</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('travel_date').setAttribute('min', today);

        // Form validation
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });

        // Prevent same city selection
        document.getElementById('from_destination').addEventListener('change', function() {
            const toSelect = document.getElementById('to_destination');
            if (this.value === toSelect.value && this.value !== '') {
                toSelect.value = '';
                alert('Please select different cities');
            }
        });

        document.getElementById('to_destination').addEventListener('change', function() {
            const fromSelect = document.getElementById('from_destination');
            if (this.value === fromSelect.value && this.value !== '') {
                fromSelect.value = '';
                alert('Please select different cities');
            }
        });
    </script>
</body>
</html>
