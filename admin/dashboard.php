<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole('ADMIN');

// Get dashboard statistics
$stats = [];

// Total bookings
$result = $conn->query('SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = DATE(NOW())');
$stats['today_bookings'] = $result->fetch_assoc()['count'];

// Total revenue
$result = $conn->query('SELECT COALESCE(SUM(fare), 0) as total FROM bookings WHERE DATE(created_at) = DATE(NOW())');
$stats['today_revenue'] = $result->fetch_assoc()['total'];

// Active trips
$result = $conn->query('SELECT COUNT(*) as count FROM trips WHERE trip_date = DATE(NOW()) AND status != "completed"');
$stats['active_trips'] = $result->fetch_assoc()['count'];

// Total buses
$result = $conn->query('SELECT COUNT(*) as count FROM buses');
$stats['total_buses'] = $result->fetch_assoc()['count'];

// Total routes
$result = $conn->query('SELECT COUNT(*) as count FROM routes');
$stats['total_routes'] = $result->fetch_assoc()['count'];

// Total users
$result = $conn->query('SELECT COUNT(*) as count FROM users');
$stats['total_users'] = $result->fetch_assoc()['count'];

// Recent bookings
$bookings_result = $conn->query(
    'SELECT b.id, b.booking_reference, b.customer_name, b.fare, b.created_at,
            d1.destination_name as from_destination,
            d2.destination_name as to_destination
     FROM bookings b
     JOIN trips t ON b.trip_id = t.id
     JOIN routes r ON t.route_id = r.id
     JOIN destinations d1 ON r.from_destination_id = d1.id
     JOIN destinations d2 ON r.to_destination_id = d2.id
     ORDER BY b.created_at DESC
     LIMIT 10'
);
$recent_bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SafeWay Transport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .sidebar {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            overflow-y: auto;
            padding-top: 70px;
            z-index: 999;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.1), rgba(0, 102, 204, 0.05));
            color: #0066cc;
            border-left-color: #0066cc;
        }

        .sidebar-menu i {
            width: 25px;
            text-align: center;
            margin-right: 15px;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px 20px;
        }

        .navbar-top {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-top h4 {
            margin: 0;
            font-weight: 700;
            font-size: 20px;
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
            font-size: 12px;
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .stat-card.revenue {
            border-left-color: #00b050;
        }

        .stat-card.revenue .stat-icon {
            color: #00b050;
        }

        .stat-card.trips {
            border-left-color: #ffc107;
        }

        .stat-card.trips .stat-icon {
            color: #ffc107;
        }

        .stat-card.users {
            border-left-color: #ff6b6b;
        }

        .stat-card.users .stat-icon {
            color: #ff6b6b;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h5 {
            margin: 0;
            font-weight: 700;
        }

        .section-body {
            padding: 25px;
        }

        .booking-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .booking-row:last-child {
            border-bottom: none;
        }

        .booking-info {
            flex: 1;
        }

        .booking-ref {
            font-weight: bold;
            color: #0066cc;
            font-size: 14px;
        }

        .booking-route {
            font-size: 12px;
            color: #888;
            margin-top: 3px;
        }

        .booking-amount {
            font-weight: bold;
            color: #00b050;
            font-size: 14px;
            text-align: right;
            min-width: 100px;
        }

        .btn-group-action {
            display: flex;
            gap: 5px;
            margin-left: 10px;
        }

        .btn-sm-action {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-sm-action:hover {
            background: linear-gradient(135deg, #0052a3, #003d7a);
            color: white;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-250px);
            }

            .main-content {
                margin-left: 0;
            }

            .booking-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .booking-amount {
                text-align: left;
                margin-top: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="navbar-top">
        <h4><i class="fas fa-bus"></i> SafeWay Transport Admin</h4>
        <div>
            <span style="margin-right: 30px;"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../logout.php" style="color: white; text-decoration: none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="buses.php"><i class="fas fa-bus"></i> Buses</a></li>
            <li><a href="routes.php"><i class="fas fa-route"></i> Routes</a></li>
            <li><a href="trips.php"><i class="fas fa-calendar-alt"></i> Trips</a></li>
            <li><a href="bookings.php"><i class="fas fa-ticket-alt"></i> Bookings</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div style="margin-bottom: 30px;">
            <h2 style="color: #333; font-weight: 700; margin-bottom: 5px;">Dashboard</h2>
            <p style="color: #888; margin: 0;">Welcome to SafeWay Transport Admin Panel</p>
        </div>

        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="stat-label">Today's Bookings</div>
                    <div class="stat-value"><?php echo $stats['today_bookings']; ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card revenue">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-label">Today's Revenue</div>
                    <div class="stat-value">ETB <?php echo number_format($stats['today_revenue'], 0); ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card trips">
                    <div class="stat-icon"><i class="fas fa-bus"></i></div>
                    <div class="stat-label">Active Trips</div>
                    <div class="stat-value"><?php echo $stats['active_trips']; ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card users">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                </div>
            </div>
        </div>

        <!-- System Overview -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card" style="border-left: none; background: linear-gradient(135deg, rgba(0, 176, 80, 0.1), rgba(0, 176, 80, 0.05));">
                    <div class="stat-icon" style="color: #00b050;"><i class="fas fa-bus-alt"></i></div>
                    <div class="stat-label">Total Buses</div>
                    <div class="stat-value"><?php echo $stats['total_buses']; ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card" style="border-left: none; background: linear-gradient(135deg, rgba(255, 107, 107, 0.1), rgba(255, 107, 107, 0.05));">
                    <div class="stat-icon" style="color: #ff6b6b;"><i class="fas fa-route"></i></div>
                    <div class="stat-label">Total Routes</div>
                    <div class="stat-value"><?php echo $stats['total_routes']; ?></div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="section-card">
            <div class="section-header">
                <h5><i class="fas fa-history"></i> Recent Bookings</h5>
                <a href="bookings.php" style="color: white; text-decoration: none; font-size: 12px;">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="section-body">
                <?php if (!empty($recent_bookings)): ?>
                    <?php foreach ($recent_bookings as $booking): ?>
                        <div class="booking-row">
                            <div class="booking-info">
                                <div class="booking-ref"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
                                <div class="booking-route">
                                    <?php echo htmlspecialchars($booking['from_destination']); ?> → <?php echo htmlspecialchars($booking['to_destination']); ?>
                                </div>
                            </div>
                            <div class="booking-amount">ETB <?php echo number_format($booking['fare'], 0); ?></div>
                            <div class="btn-group-action">
                                <a href="bookings.php?search=<?php echo $booking['id']; ?>" class="btn-sm-action">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #888; text-align: center; padding: 30px 0; margin: 0;">
                        <i class="fas fa-inbox"></i> No bookings yet
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-card">
            <div class="section-header">
                <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="buses.php" style="text-decoration: none;">
                            <div style="background: linear-gradient(135deg, #0066cc, #0052a3); color: white; padding: 20px; border-radius: 8px; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 20px rgba(0, 102, 204, 0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                <i class="fas fa-plus" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                                <strong>Add Bus</strong>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="routes.php" style="text-decoration: none;">
                            <div style="background: linear-gradient(135deg, #00b050, #00a047); color: white; padding: 20px; border-radius: 8px; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 20px rgba(0, 176, 80, 0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                <i class="fas fa-plus" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                                <strong>Add Route</strong>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="trips.php" style="text-decoration: none;">
                            <div style="background: linear-gradient(135deg, #ffc107, #ffb300); color: white; padding: 20px; border-radius: 8px; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 20px rgba(255, 193, 7, 0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                <i class="fas fa-plus" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                                <strong>Add Trip</strong>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="users.php" style="text-decoration: none;">
                            <div style="background: linear-gradient(135deg, #ff6b6b, #ff5252); color: white; padding: 20px; border-radius: 8px; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 20px rgba(255, 107, 107, 0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                <i class="fas fa-plus" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                                <strong>Add User</strong>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
