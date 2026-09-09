<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole('ADMIN');

$action = $_GET['action'] ?? 'list';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_number = sanitize($_POST['bus_number']);
    $bus_name = sanitize($_POST['bus_name']);
    $vehicle_grade = sanitize($_POST['vehicle_grade']);
    $total_seats = (int)$_POST['total_seats'];
    $driver_name = sanitize($_POST['driver_name']);
    $driver_phone = sanitize($_POST['driver_phone']);

    $stmt = $conn->prepare(
        'INSERT INTO buses (bus_number, bus_name, vehicle_grade, total_seats, driver_name, driver_phone, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, "active", NOW())'
    );
    $stmt->bind_param('ssssii', $bus_number, $bus_name, $vehicle_grade, $total_seats, $driver_name, $driver_phone);

    if ($stmt->execute()) {
        $bus_id = $conn->insert_id;
        
        // Create seats for this bus
        for ($row = 1; $row <= 5; $row++) {
            for ($col = 1; $col <= 4; $col++) {
                $seat_number = chr(64 + $row) . $col;
                $seat_stmt = $conn->prepare(
                    'INSERT INTO seats (bus_id, seat_number, row_number, column_number, status) VALUES (?, ?, ?, ?, "available")'
                );
                $seat_stmt->bind_param('isii', $bus_id, $seat_number, $row, $col);
                $seat_stmt->execute();
            }
        }

        logAudit($conn, $_SESSION['user_id'], 'BUS_CREATED', 'bus', $bus_id, null, 'Bus: ' . $bus_number);
        header('Location: buses.php?success=Bus added successfully');
        exit();
    } else {
        $error = 'Failed to add bus';
    }
}

// Get all buses
$buses = $conn->query(
    'SELECT b.*, 
            (SELECT COUNT(*) FROM seats WHERE bus_id = b.id) as total_seats_created
     FROM buses b
     ORDER BY b.created_at DESC'
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Management - SafeWay Transport Admin</title>
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
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-card-header {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-add {
            background: white;
            color: #0066cc;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            background: #f5f7fa;
        }

        .table-body {
            padding: 25px;
        }

        .bus-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr 150px;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
            align-items: center;
        }

        .bus-row:last-child {
            border-bottom: none;
        }

        .bus-info {
            display: flex;
            flex-direction: column;
        }

        .bus-number {
            font-weight: bold;
            color: #0066cc;
            font-size: 14px;
        }

        .bus-name {
            font-size: 12px;
            color: #888;
            margin-top: 2px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactive {
            background: #ffebee;
            color: #d32f2f;
        }

        .btn-action {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            margin-right: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-action:hover {
            background: linear-gradient(135deg, #0052a3, #003d7a);
            color: white;
            text-decoration: none;
        }

        .btn-delete {
            background: #ff6b6b;
        }

        .btn-delete:hover {
            background: #ff5252;
        }

        .modal-dialog {
            max-width: 500px;
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
        }

        .form-control:focus, .form-select:focus {
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        @media (max-width: 1024px) {
            .bus-row {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }

            .bus-row {
                grid-template-columns: 1fr;
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
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="buses.php" class="active"><i class="fas fa-bus"></i> Buses</a></li>
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
            <h2 style="color: #333; font-weight: 700; margin-bottom: 5px;">Bus Management</h2>
            <p style="color: #888; margin: 0;">Add, edit, and manage buses</p>
        </div>

        <!-- Buses Table -->
        <div class="table-card">
            <div class="table-card-header">
                <h5 class="mb-0"><i class="fas fa-bus"></i> All Buses</h5>
                <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addBusModal">
                    <i class="fas fa-plus"></i> Add New Bus
                </button>
            </div>

            <div class="table-body">
                <?php if (!empty($buses)): ?>
                    <div style="display: none;">
                        <div class="bus-row" style="font-weight: bold; color: #0066cc; border-bottom: 2px solid #0066cc; padding: 10px 0;">
                            <span>Bus Number</span>
                            <span>Bus Name</span>
                            <span>Grade</span>
                            <span>Seats</span>
                            <span>Status</span>
                            <span>Action</span>
                        </div>
                    </div>
                    <?php foreach ($buses as $bus): ?>
                        <div class="bus-row">
                            <div class="bus-info">
                                <span class="bus-number"><?php echo htmlspecialchars($bus['bus_number']); ?></span>
                                <span class="bus-name"><?php echo htmlspecialchars($bus['driver_name']); ?></span>
                            </div>
                            <div><?php echo htmlspecialchars($bus['bus_name']); ?></div>
                            <div><?php echo htmlspecialchars($bus['vehicle_grade']); ?></div>
                            <div><?php echo $bus['total_seats']; ?> Seats</div>
                            <div>
                                <span class="status-badge status-<?php echo $bus['status']; ?>">
                                    <?php echo ucfirst($bus['status']); ?>
                                </span>
                            </div>
                            <div>
                                <a href="#" class="btn-action" onclick="editBus(<?php echo $bus['id']; ?>)"><i class="fas fa-edit"></i> Edit</a>
                                <a href="#" class="btn-action btn-delete" onclick="deleteBus(<?php echo $bus['id']; ?>)"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #888; text-align: center; padding: 50px 0;">
                        <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                        No buses added yet
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Bus Modal -->
    <div class="modal fade" id="addBusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none;">
                    <h5 class="modal-title">Add New Bus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="buses.php?action=add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Bus Number <span style="color: red;">*</span></label>
                            <input type="text" class="form-control" name="bus_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bus Name <span style="color: red;">*</span></label>
                            <input type="text" class="form-control" name="bus_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vehicle Grade <span style="color: red;">*</span></label>
                            <select class="form-select" name="vehicle_grade" required>
                                <option value="">Select Grade</option>
                                <option value="Standard">Standard</option>
                                <option value="Business">Business</option>
                                <option value="VIP">VIP</option>
                                <option value="Luxury">Luxury</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Seats <span style="color: red;">*</span></label>
                            <input type="number" class="form-control" name="total_seats" value="20" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Driver Name <span style="color: red;">*</span></label>
                            <input type="text" class="form-control" name="driver_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Driver Phone <span style="color: red;">*</span></label>
                            <input type="tel" class="form-control" name="driver_phone" placeholder="09XXXXXXXX" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" style="background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none;">
                            <i class="fas fa-check"></i> Add Bus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBus(busId) {
            // Implement edit functionality
            alert('Edit bus ' + busId);
        }

        function deleteBus(busId) {
            if (confirm('Are you sure you want to delete this bus?')) {
                // Implement delete functionality
                alert('Delete bus ' + busId);
            }
        }
    </script>
</body>
</html>
