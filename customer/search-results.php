<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Get search parameters
$from_destination = isset($_GET['from_destination']) ? (int)$_GET['from_destination'] : 0;
$to_destination = isset($_GET['to_destination']) ? (int)$_GET['to_destination'] : 0;
$travel_date = isset($_GET['travel_date']) ? sanitize($_GET['travel_date']) : '';

$buses = [];
$error = '';

if ($from_destination && $to_destination && $travel_date) {
    // Get route
    $stmt = $conn->prepare(
        'SELECT id FROM routes WHERE from_destination_id = ? AND to_destination_id = ? AND status = "active"'
    );
    $stmt->bind_param('ii', $from_destination, $to_destination);
    $stmt->execute();
    $route_result = $stmt->get_result();
    
    if ($route_result->num_rows > 0) {
        $route = $route_result->fetch_assoc();
        $route_id = $route['id'];
        
        // Get available trips
        $stmt = $conn->prepare(
            'SELECT t.id as trip_id, t.departure_time, t.fare, b.bus_number, b.bus_name, b.vehicle_grade, b.total_seats,
                    (SELECT COUNT(*) FROM trip_seats WHERE trip_id = t.id AND status IN ("booked", "paid")) as booked_seats
             FROM trips t
             JOIN buses b ON t.bus_id = b.id
             WHERE t.route_id = ? AND t.trip_date = ? AND t.status = "scheduled"
             ORDER BY t.departure_time'
        );
        $stmt->bind_param('is', $route_id, $travel_date);
        $stmt->execute();
        $buses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($buses)) {
            $error = 'No buses available for this route on the selected date.';
        }
    } else {
        $error = 'No route found for the selected cities.';
    }
} else {
    header('Location: booking.php');
    exit();
}

// Get destination names
$stmt = $conn->prepare('SELECT destination_name FROM destinations WHERE id = ?');
$stmt->bind_param('i', $from_destination);
$stmt->execute();
$from_name = $stmt->get_result()->fetch_assoc()['destination_name'];

$stmt = $conn->prepare('SELECT destination_name FROM destinations WHERE id = ?');
$stmt->bind_param('i', $to_destination);
$stmt->execute();
$to_name = $stmt->get_result()->fetch_assoc()['destination_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Buses - SafeWay Transport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .search-header {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 30px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .search-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-box {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .info-box i {
            font-size: 24px;
            opacity: 0.8;
        }

        .info-text {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 12px;
            opacity: 0.8;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 16px;
            font-weight: bold;
        }

        .bus-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .bus-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }

        .bus-card-header {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bus-name {
            font-size: 18px;
            font-weight: bold;
        }

        .bus-grade {
            font-size: 12px;
            background: rgba(255, 255, 255, 0.3);
            padding: 4px 10px;
            border-radius: 20px;
            margin-left: 10px;
        }

        .bus-card-body {
            padding: 20px;
        }

        .bus-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .availability {
            background: linear-gradient(135deg, rgba(0, 176, 80, 0.1), rgba(0, 176, 80, 0.05));
            padding: 15px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .availability-bar {
            flex: 1;
            margin: 0 15px;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            background: #e0e0e0;
        }

        .progress-bar {
            background: linear-gradient(90deg, #00b050, #00a047);
        }

        .fare-box {
            background: linear-gradient(135deg, #00b050, #00a047);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
        }

        .fare-value {
            font-size: 24px;
            font-weight: bold;
        }

        .fare-label {
            font-size: 12px;
            opacity: 0.9;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-select {
            flex: 1;
            background: linear-gradient(135deg, #0066cc, #0052a3);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-select:hover {
            background: linear-gradient(135deg, #0052a3, #003d7a);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 102, 204, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-back {
            background: white;
            border: 2px solid #0066cc;
            color: #0066cc;
            font-weight: 600;
            padding: 12px 20px;
        }

        .btn-back:hover {
            background: #f5f7fa;
            text-decoration: none;
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

    <div class="container-fluid" style="padding: 40px 20px;">
        <!-- Search Header -->
        <div class="search-header">
            <h2 class="mb-3"><i class="fas fa-bus-alt"></i> Available Buses</h2>
            <div class="search-info">
                <div class="info-box">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="info-text">
                        <span class="info-label">From</span>
                        <span class="info-value"><?php echo htmlspecialchars($from_name); ?></span>
                    </div>
                </div>
                <div class="info-box">
                    <i class="fas fa-arrow-right"></i>
                    <div class="info-text">
                        <span class="info-label">To</span>
                        <span class="info-value"><?php echo htmlspecialchars($to_name); ?></span>
                    </div>
                </div>
                <div class="info-box">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="info-text">
                        <span class="info-label">Date</span>
                        <span class="info-value"><?php echo formatDate($travel_date); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Buses List -->
        <div class="row">
            <div class="col-lg-8">
                <?php if (!empty($buses)): ?>
                    <?php foreach ($buses as $bus): ?>
                        <div class="bus-card">
                            <div class="bus-card-header">
                                <div>
                                    <span class="bus-name"><?php echo htmlspecialchars($bus['bus_name']); ?></span>
                                    <span class="bus-grade"><?php echo htmlspecialchars($bus['vehicle_grade']); ?></span>
                                </div>
                                <div style="font-size: 14px;">
                                    Bus No: <strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong>
                                </div>
                            </div>

                            <div class="bus-card-body">
                                <!-- Availability -->
                                <div class="availability">
                                    <div>
                                        <small><strong><?php echo $bus['booked_seats']; ?>/<?php echo $bus['total_seats']; ?></strong> booked</small>
                                    </div>
                                    <div class="availability-bar">
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo ($bus['booked_seats'] / $bus['total_seats'] * 100); ?>%"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <strong><?php echo ($bus['total_seats'] - $bus['booked_seats']); ?> seats</strong> available
                                    </div>
                                </div>

                                <!-- Details -->
                                <div class="bus-details">
                                    <div class="detail-item">
                                        <span class="detail-label"><i class="fas fa-clock"></i> Departure</span>
                                        <span class="detail-value"><?php echo formatTime($bus['departure_time']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label"><i class="fas fa-chair"></i> Total Seats</span>
                                        <span class="detail-value"><?php echo $bus['total_seats']; ?></span>
                                    </div>
                                </div>

                                <!-- Fare -->
                                <div class="fare-box">
                                    <div class="fare-label">Starting from</div>
                                    <div class="fare-value"><?php echo formatCurrency($bus['fare']); ?></div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="action-buttons">
                                    <a href="select-seats.php?trip_id=<?php echo $bus['trip_id']; ?>" class="btn-select">
                                        <i class="fas fa-ticket-alt"></i> Select Seats
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info" style="text-align: center; padding: 40px;">
                        <i class="fas fa-search" style="font-size: 48px; color: #0066cc; margin-bottom: 15px; display: block;"></i>
                        <h5>No Buses Found</h5>
                        <p>No buses available for this route. Please try different dates or cities.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card" style="border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); position: sticky; top: 20px;">
                    <div class="card-body">
                        <h5 class="card-title" style="color: #0066cc; border-bottom: 2px solid #0066cc; padding-bottom: 15px; margin-bottom: 15px;">
                            <i class="fas fa-info-circle"></i> Journey Details
                        </h5>
                        <div style="margin-bottom: 15px;">
                            <small class="text-muted">FROM</small>
                            <p style="font-weight: bold; font-size: 16px; margin: 5px 0;"><?php echo htmlspecialchars($from_name); ?></p>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <small class="text-muted">TO</small>
                            <p style="font-weight: bold; font-size: 16px; margin: 5px 0;"><?php echo htmlspecialchars($to_name); ?></p>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <small class="text-muted">DATE</small>
                            <p style="font-weight: bold; font-size: 16px; margin: 5px 0;"><?php echo formatDate($travel_date); ?></p>
                        </div>
                        <hr>
                        <a href="booking.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-arrow-left"></i> New Search
                        </a>
                    </div>
                </div>

                <!-- Tips Card -->
                <div class="card mt-4" style="border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); border-left: 4px solid #00b050;">
                    <div class="card-body">
                        <h5 class="card-title" style="color: #00b050; margin-bottom: 15px;">
                            <i class="fas fa-lightbulb"></i> Booking Tips
                        </h5>
                        <ul style="font-size: 13px; line-height: 1.8;">
                            <li>Book early for better seat selection</li>
                            <li>Carry a valid ID for verification</li>
                            <li>Arrive 30 minutes early</li>
                            <li>Check our cancellation policy</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
