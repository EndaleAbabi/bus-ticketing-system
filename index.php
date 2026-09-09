<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeWay Transport - Book Your Ticket Now</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
        }
        .feature-card {
            text-align: center;
            padding: 30px;
        }
        .feature-icon {
            font-size: 40px;
            color: #0066cc;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #0066cc, #0052a3);">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-bus"></i> SafeWay Transport
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo ($_SESSION['role'] === 'ADMIN') ? 'admin/dashboard.php' : ($_SESSION['role'] === 'CASHIER' ? 'cashier/dashboard.php' : 'customer/booking.php'); ?>">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Sign In
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Travel Safe, Travel Smart</h1>
            <p class="lead mb-4">Book your bus tickets online with SafeWay Transport. Comfortable seats, affordable fares, and reliable service.</p>
            <a href="customer/booking.php" class="btn btn-light btn-lg" style="color: #0066cc;">
                <i class="fas fa-ticket-alt"></i> Book Now
            </a>
        </div>
    </div>

    <!-- Features Section -->
    <section id="features" class="py-5" style="background: #f5f7fa;">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose SafeWay?</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h5>Easy Booking</h5>
                        <p>Simple and fast online booking process</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5>Secure Payment</h5>
                        <p>Safe and secure payment options</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h5>Best Prices</h5>
                        <p>Affordable fares across all routes</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h5>Quality Service</h5>
                        <p>Professional drivers and comfortable buses</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section style="background: linear-gradient(135deg, #0066cc, #0052a3); color: white; padding: 60px 20px; text-align: center;">
        <div class="container">
            <h2 class="mb-4">Ready to Book Your Journey?</h2>
            <p class="lead mb-4">Join thousands of satisfied passengers using SafeWay Transport</p>
            <a href="customer/booking.php" class="btn btn-light btn-lg">
                <i class="fas fa-arrow-right"></i> Get Started
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: #1a1a1a; color: white; padding: 40px 20px;">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>SafeWay Transport</h5>
                    <p>Your trusted transportation partner since 2024</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Contact Us</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Terms & Conditions</a></li>
                    </ul>
                </div>
                <div class="col-md-4" id="contact">
                    <h5>Contact Us</h5>
                    <p>
                        <i class="fas fa-phone"></i> +251 911 234 567<br>
                        <i class="fas fa-envelope"></i> info@safeway.com<br>
                        <i class="fas fa-map-marker-alt"></i> Addis Ababa, Ethiopia
                    </p>
                </div>
            </div>
            <hr class="bg-white-50">
            <p class="text-center text-white-50 mb-0">&copy; 2024 SafeWay Transport. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
