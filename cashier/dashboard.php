<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole('CASHIER');

// Get today's trips assigned to this cashier
$stmt = $conn->prepare(
    'SELECT t.id, t.trip_date, t.departure_time, t.arrival_time, t.fare, t.status,
            b.bus_number, b.bus_name, b.total_seats,
            d1.destination_name as from_destination,
            d2.destination_name as to_destination,
            (SELECT COUNT(*) FROM trip_seats WHERE trip_id = t.id AND status IN ("booked", "paid")) as booked_seats,
            (SELECT COUNT(*) FROM bookings WHERE trip_id = t.id AND booking_type = "cashier") as cashier_bookings
     FROM trips t
     JOIN buses b ON t.bus_id = b.id
     JOIN routes r ON t.route_id = r.id
     JOIN destinations d1 ON r.from_destination_id = d1.id
     JOIN destinations d2 ON r.to_destination_id = d2.id
     WHERE t.assigned_cashier_id = ? AND t.trip_date >= DATE(NOW())
     ORDER BY t.trip_date, t.departure_time'
);

$user_id = $_SESSION['user_id'];
$stmt->bind_param('i', $user_id);
$stmt->execute();
$trips = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get dashboard statistics
$stats = [
    'total_bookings' => 0,
    'total_revenue' => 0,
    'pending_trips' => 0
];

$stat_stmt = $conn->prepare(
    'SELECT 
        COUNT(DISTINCT b.id) as total_bookings,
        COALESCE(SUM(b.fare), 0) as total_revenue,
        COUNT(DISTINCT t.id) as pending_trips
     FROM trips t
     LEFT JOIN bookings b ON t.id = b.trip_id AND b.booking_type = "cashier"
     WHERE t.assigned_cashier_id = ? AND t.trip_date = DATE(NOW())'
);

$stat_stmt->bind_param('i', $user_id);
$stat_stmt->execute();
$stats = $stat_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard - SafeWay Transport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .sidebar-nav {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-menu li {
            margin-bottom: 10px;
        }

        .nav-menu a {
            display: block;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.1), rgba(0, 102, 204, 0.05));
            color: #0066cc;
            border-left: 4px solid #0066cc;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-left: 4px solid #0066cc;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 32px;
            color: #0066cc;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 13px;
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .trips-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .trips-card-header {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 20px;
        }

        .trip-item {
            border-bottom: 1px solid #e0e0e0;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .trip-item:hover {
            background: #f9f9f9;
        }

        .trip-item:last-child {
            border-bottom: none;
        }

        .trip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .trip-route {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .trip-status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-scheduled {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-in_progress {
            background: #fff3e0;
            color: #e65100;
        }

        .status-completed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .trip-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .trip-info-item {
            display: flex;
            flex-direction: column;
        }

        .trip-info-label {
            font-size: 12px;
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .trip-info-value {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .trip-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .btn-book {
            flex: 1;
            background: linear-gradient(135deg, #00b050, #00a047);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-book:hover {
            background: linear-gradient(135deg, #00a047, #008c3f);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .btn-view {
            flex: 1;
            background: white;
            border: 2px solid #0066cc;
            color: #0066cc;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-view:hover {
            background: #f5f7fa;
            text-decoration: none;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }

        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
            display: block;
        }

        @media (max-width: 768px) {
            .trip-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .trip-info-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .trip-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #0066cc, #0052a3);">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-bus"></i> SafeWay Transport
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid" style="padding: 30px 20px;">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 mb-4">
                <div class="sidebar-nav">
                    <h6 style="color: #0066cc; font-weight: 700; margin-bottom: 15px;"><i class="fas fa-bars"></i> Menu</h6>
                    <ul class="nav-menu">
                        <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="book-ticket.php"><i class="fas fa-ticket-alt"></i> Book Ticket</a></li>
                        <li><a href="my-bookings.php"><i class="fas fa-list"></i> My Bookings</a></li>
                        <li><a href="daily-report.php"><i class="fas fa-chart-bar"></i> Daily Report</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Page Header -->
                <div style="margin-bottom: 30px;">
                    <h2 style="color: #333; font-weight: 700; margin-bottom: 5px;">
                        <i class="fas fa-tachometer-alt"></i> Cashier Dashboard
                    </h2>
                    <p style="color: #888; margin: 0;">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                            <div class="stat-label">Today's Bookings</div>
                            <div class="stat-value"><?php echo $stats['total_bookings']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card" style="border-left-color: #00b050;">
                            <div class="stat-icon" style="color: #00b050;"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="stat-label">Today's Revenue</div>
                            <div class="stat-value">ETB <?php echo number_format($stats['total_revenue'], 0); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card" style="border-left-color: #ffc107;">
                            <div class="stat-icon" style="color: #ffc107;"><i class="fas fa-bus"></i></div>
                            <div class="stat-label">Assigned Trips</div>
                            <div class="stat-value"><?php echo count($trips); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card" style="border-left-color: #ff6b6b;">
                            <div class="stat-icon" style="color: #ff6b6b;"><i class="fas fa-calendar-alt"></i></div>
                            <div class="stat-label">Today's Date</div>
                            <div class="stat-value" style="font-size: 20px;"><?php echo date('d/m/Y'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Trips Section -->
                <div class="trips-card">
                    <div class="trips-card-header">
                        <h4 class="mb-0"><i class="fas fa-bus-alt"></i> Your Assigned Trips</h4>
                    </div>

                    <?php if (!empty($trips)): ?>
                        <?php foreach ($trips as $trip): ?>
                            <div class="trip-item">
                                <div class="trip-header">
                                    <div class="trip-route">
                                        <?php echo htmlspecialchars($trip['from_destination']); ?> 
                                        <i class="fas fa-arrow-right" style="color: #ccc; font-size: 12px;"></i> 
                                        <?php echo htmlspecialchars($trip['to_destination']); ?>
                                    </div>
                                    <span class="trip-status-badge status-<?php echo $trip['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $trip['status'])); ?>
                                    </span>
                                </div>

                                <div class="trip-info-grid">
                                    <div class="trip-info-item">
                                        <span class="trip-info-label"><i class="fas fa-bus"></i> Bus</span>
                                        <span class="trip-info-value"><?php echo htmlspecialchars($trip['bus_name']); ?></span>
                                    </div>
                                    <div class="trip-info-item">
                                        <span class="trip-info-label"><i class="fas fa-calendar-alt"></i> Date</span>
                                        <span class="trip-info-value"><?php echo formatDate($trip['trip_date']); ?></span>
                                    </div>
                                    <div class="trip-info-item">
                                        <span class="trip-info-label"><i class="fas fa-clock"></i> Departure</span>
                                        <span class="trip-info-value"><?php echo formatTime($trip['departure_time']); ?></span>
                                    </div>
                                    <div class="trip-info-item">
                                        <span class="trip-info-label"><i class="fas fa-chair"></i> Seats</span>
                                        <span class="trip-info-value"><?php echo ($trip['total_seats'] - $trip['booked_seats']); ?>/<?php echo $trip['total_seats']; ?></span>
                                    </div>
                                </div>

                                <div class="trip-actions">
                                    <a href="book-ticket.php?trip_id=<?php echo $trip['id']; ?>" class="btn-book">
                                        <i class="fas fa-ticket-alt"></i> Book Ticket
                                    </a>
                                    <a href="trip-details.php?trip_id=<?php echo $trip['id']; ?>" class="btn-view">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No trips assigned to you today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
