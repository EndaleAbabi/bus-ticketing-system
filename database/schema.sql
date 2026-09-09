-- Bus Ticketing System Database Schema
-- Drop existing database and create new one
DROP DATABASE IF EXISTS bus_ticketing_system;
CREATE DATABASE bus_ticketing_system;
USE bus_ticketing_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'CASHIER', 'CUSTOMER') NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Buses table
CREATE TABLE buses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_number VARCHAR(50) UNIQUE NOT NULL,
    bus_name VARCHAR(100) NOT NULL,
    total_seats INT NOT NULL,
    vehicle_grade ENUM('Level 1', 'Level 2', 'Level 3', 'VIP') DEFAULT 'Level 1',
    license_plate VARCHAR(50) UNIQUE,
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bus_number (bus_number),
    INDEX idx_status (status)
);

-- Destinations table
CREATE TABLE destinations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    destination_name VARCHAR(100) NOT NULL,
    destination_code VARCHAR(20) UNIQUE,
    country VARCHAR(50) DEFAULT 'Ethiopia',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_destination_name (destination_name),
    INDEX idx_status (status)
);

-- Routes table
CREATE TABLE routes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_destination_id INT NOT NULL,
    to_destination_id INT NOT NULL,
    route_code VARCHAR(50) UNIQUE,
    base_fare DECIMAL(10, 2) NOT NULL,
    distance_km INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_destination_id) REFERENCES destinations(id),
    FOREIGN KEY (to_destination_id) REFERENCES destinations(id),
    INDEX idx_from_to (from_destination_id, to_destination_id),
    INDEX idx_status (status)
);

-- Trips/Schedules table (flexible daily routing)
CREATE TABLE trips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_id INT NOT NULL,
    route_id INT NOT NULL,
    trip_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME,
    fare DECIMAL(10, 2) NOT NULL,
    assigned_cashier_id INT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id),
    FOREIGN KEY (route_id) REFERENCES routes(id),
    FOREIGN KEY (assigned_cashier_id) REFERENCES users(id),
    UNIQUE KEY unique_trip (bus_id, trip_date, departure_time),
    INDEX idx_trip_date (trip_date),
    INDEX idx_status (status),
    INDEX idx_bus_id (bus_id)
);

-- Seats table (seat configuration per bus)
CREATE TABLE seats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_id INT NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    row_number INT NOT NULL,
    column_number INT NOT NULL,
    seat_type ENUM('regular', 'disabled', 'vip') DEFAULT 'regular',
    status ENUM('available', 'occupied', 'reserved') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id),
    UNIQUE KEY unique_bus_seat (bus_id, seat_number),
    INDEX idx_bus_id (bus_id),
    INDEX idx_status (status)
);

-- Trip Seats table (seat status per trip)
CREATE TABLE trip_seats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    seat_id INT NOT NULL,
    status ENUM('available', 'booked', 'paid', 'cancelled') DEFAULT 'available',
    booking_reference VARCHAR(50),
    booked_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(id),
    UNIQUE KEY unique_trip_seat (trip_id, seat_id),
    INDEX idx_trip_id (trip_id),
    INDEX idx_status (status)
);

-- Bookings table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_reference VARCHAR(50) UNIQUE NOT NULL,
    trip_id INT NOT NULL,
    customer_id INT,
    customer_name VARCHAR(150) NOT NULL,
    customer_gender VARCHAR(20),
    customer_age INT,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(100),
    seat_id INT NOT NULL,
    fare DECIMAL(10, 2) NOT NULL,
    booking_type ENUM('online', 'cashier') DEFAULT 'online',
    payment_status ENUM('pending', 'paid', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    booking_status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    notes TEXT,
    booked_by_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (seat_id) REFERENCES seats(id),
    FOREIGN KEY (booked_by_user_id) REFERENCES users(id),
    INDEX idx_booking_reference (booking_reference),
    INDEX idx_trip_id (trip_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
);

-- Tickets table
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    booking_id INT NOT NULL,
    trip_id INT NOT NULL,
    qr_code TEXT,
    status ENUM('active', 'used', 'cancelled') DEFAULT 'active',
    printed_at TIMESTAMP,
    used_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    INDEX idx_ticket_number (ticket_number),
    INDEX idx_status (status)
);

-- Payments table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'card', 'mobile_money', 'bank_transfer') DEFAULT 'cash',
    transaction_id VARCHAR(100),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (processed_by) REFERENCES users(id),
    INDEX idx_booking_id (booking_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
);

-- Refunds table
CREATE TABLE refunds (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reason TEXT,
    refund_status ENUM('pending', 'approved', 'completed', 'rejected') DEFAULT 'pending',
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (processed_by) REFERENCES users(id),
    INDEX idx_booking_id (booking_id),
    INDEX idx_refund_status (refund_status)
);

-- Audit logs table
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
);

-- Reports table
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_type VARCHAR(50),
    report_date DATE,
    trip_id INT,
    bus_id INT,
    total_bookings INT DEFAULT 0,
    total_revenue DECIMAL(12, 2) DEFAULT 0.00,
    seats_filled INT DEFAULT 0,
    occupancy_rate DECIMAL(5, 2) DEFAULT 0.00,
    notes TEXT,
    generated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (bus_id) REFERENCES buses(id),
    FOREIGN KEY (generated_by) REFERENCES users(id),
    INDEX idx_report_date (report_date),
    INDEX idx_bus_id (bus_id)
);

-- Insert sample data
INSERT INTO users (username, email, password, role, full_name, phone) VALUES
('admin', 'admin@safeway.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.Gw7KO5zFm', 'ADMIN', 'Administrator', '0911111111'),
('cashier1', 'cashier1@safeway.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.Gw7KO5zFm', 'CASHIER', 'Cashier One', '0922222222'),
('cashier2', 'cashier2@safeway.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.Gw7KO5zFm', 'CASHIER', 'Cashier Two', '0933333333');

INSERT INTO destinations (destination_name, destination_code) VALUES
('Addis Ababa', 'AA'),
('Dessie', 'DE'),
('Mekelle', 'ME'),
('Bahir Dar', 'BD'),
('Adama', 'AD'),
('Hawassa', 'HA');

INSERT INTO buses (bus_number, bus_name, total_seats, vehicle_grade) VALUES
('1001', 'Selam Bus', 50, 'Level 1'),
('1002', 'Ethio Star', 48, 'Level 2'),
('1003', 'SafeWay Express', 42, 'Level 1'),
('1004', 'Premium Plus', 32, 'VIP');

-- Create default seats for Bus 1001 (50 seats - 5 rows x 10 columns)
-- This is a sample; adjust as needed for your bus layouts
