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
        /* Root Spacing System */
        :root {
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-xxl: 4rem;
            --primary-color: #0066cc;
            --secondary-color: #0052a3;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-dark: linear-gradient(135deg, #0066cc, #0052a3);
        }

        /* Hero Section */
        .hero-section {
            background: var(--gradient-primary);
            color: white;
            padding: var(--spacing-xxl) var(--spacing-sm);
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-section h1 {
            font-size: clamp(2rem, 8vw, 3.5rem);
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: var(--spacing-lg);
            line-height: 1.2;
        }

        .hero-section .lead {
            font-size: clamp(1rem, 2.5vw, 1.25rem);
            margin-bottom: var(--spacing-lg);
            line-height: 1.6;
            opacity: 0.95;
        }

        /* Features Section */
        .features-section {
            background: #f5f7fa;
            padding: var(--spacing-xxl) var(--spacing-sm);
        }

        .features-section h2 {
            font-size: clamp(1.75rem, 5vw, 2.5rem);
            font-weight: 700;
            text-align: center;
            margin-bottom: var(--spacing-xxl);
            color: #1a1a1a;
            letter-spacing: -0.3px;
        }

        /* Feature Cards */
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: var(--spacing-lg);
            text-align: center;
            height: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        .feature-card:hover {
            box-shadow: 0 8px 24px rgba(0, 102, 204, 0.12);
            transform: translateY(-4px);
            border-color: var(--primary-color);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: var(--spacing-md);
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 102, 204, 0.1);
            border-radius: 12px;
        }

        .feature-card h5 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: var(--spacing-sm);
            line-height: 1.4;
        }

        .feature-card p {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 0;
            flex-grow: 1;
        }

        /* CTA Section */
        .cta-section {
            background: var(--gradient-dark);
            color: white;
            padding: var(--spacing-xxl) var(--spacing-sm);
            text-align: center;
        }

        .cta-section h2 {
            font-size: clamp(1.75rem, 5vw, 2.5rem);
            font-weight: 700;
            margin-bottom: var(--spacing-lg);
            letter-spacing: -0.3px;
        }

        .cta-section .lead {
            font-size: clamp(1rem, 2.5vw, 1.125rem);
            margin-bottom: var(--spacing-lg);
            line-height: 1.6;
            opacity: 0.95;
        }

        /* Footer */
        footer {
            background: #1a1a1a;
            color: white;
            padding: var(--spacing-xxl) var(--spacing-sm);
        }

        footer h5 {
            font-weight: 600;
            margin-bottom: var(--spacing-md);
            font-size: 1.125rem;
            letter-spacing: -0.3px;
        }

        footer p {
            font-size: 0.95rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.75);
        }

        footer a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        footer a:hover {
            color: white;
        }

        footer ul li {
            margin-bottom: var(--spacing-sm);
            font-size: 0.95rem;
        }

        footer .contact-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        footer .contact-info i {
            margin-right: var(--spacing-sm);
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }

        /* Navbar */
        .navbar-custom {
            background: var(--gradient-dark) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: var(--spacing-md) var(--spacing-sm);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -0.3px;
        }

        .navbar-brand i {
            margin-right: var(--spacing-xs);
        }

        .navbar-nav .nav-link {
            font-weight: 500;
            margin-left: var(--spacing-md);
            transition: opacity 0.3s ease;
            font-size: 0.95rem;
        }

        .navbar-nav .nav-link:hover {
            opacity: 0.8;
        }

        /* Buttons */
        .btn-primary-custom {
            background: white;
            color: var(--primary-color);
            border: none;
            padding: var(--spacing-md) var(--spacing-lg);
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-primary-custom:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .feature-card {
                margin-bottom: var(--spacing-lg);
            }

            footer .row {
                gap: var(--spacing-lg);
            }

            footer .col-md-4 {
                text-align: center;
            }
        }

        /* HR styling */
        hr {
            border-color: rgba(255, 255, 255, 0.1);
            margin: var(--spacing-xl) 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-lg">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-bus"></i> SafeWay Transport
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
    <section class="hero-section">
        <div class="container-lg">
            <div class="hero-content">
                <h1 class="fw-bold">Travel Safe, Travel Smart</h1>
                <p class="lead">Book your bus tickets online with SafeWay Transport. Comfortable seats, affordable fares, and reliable service.</p>
                <a href="customer/booking.php" class="btn btn-primary-custom btn-lg">
                    <i class="fas fa-ticket-alt"></i> Book Now
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container-lg">
            <h2 class="mb-5">Why Choose SafeWay?</h2>
            <div class="row g-4">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h5>Easy Booking</h5>
                        <p>Simple and fast online booking process</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5>Secure Payment</h5>
                        <p>Safe and secure payment options</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h5>Best Prices</h5>
                        <p>Affordable fares across all routes</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
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
    <section class="cta-section">
        <div class="container-lg">
            <h2 class="fw-bold">Ready to Book Your Journey?</h2>
            <p class="lead">Join thousands of satisfied passengers using SafeWay Transport</p>
            <a href="customer/booking.php" class="btn btn-light btn-lg fw-600">
                <i class="fas fa-arrow-right"></i> Get Started
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="container-lg">
            <div class="row g-4">
                <div class="col-12 col-md-4">
                    <h5><i class="fas fa-bus"></i> SafeWay Transport</h5>
                    <p>Your trusted transportation partner since 2024</p>
                </div>
                <div class="col-12 col-md-4">
                    <h5><i class="fas fa-link"></i> Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50">About Us</a></li>
                        <li><a href="#" class="text-white-50">Contact Us</a></li>
                        <li><a href="#" class="text-white-50">Terms & Conditions</a></li>
                    </ul>
                </div>
                <div class="col-12 col-md-4">
                    <h5><i class="fas fa-phone"></i> Contact Us</h5>
                    <div class="contact-info">
                        <div><i class="fas fa-phone"></i> <span>+251 911 234 567</span></div>
                        <div><i class="fas fa-envelope"></i> <span>info@safeway.com</span></div>
                        <div><i class="fas fa-map-marker-alt"></i> <span>Addis Ababa, Ethiopia</span></div>
                    </div>
                </div>
            </div>
            <hr>
            <p class="text-center text-white-50 mb-0">&copy; 2024 SafeWay Transport. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>