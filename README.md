# BusGo — Bus Ticketing & Seat Management System

BusGo is a PHP/MySQL bus-ticketing platform for Ethiopia with **online passenger booking, cashier/window booking, live seat inventory, admin operations and printable tickets**.

## Design
The public landing page has been redesigned around the supplied BusGo branding: deep navy, transport green and orange accents, rounded cards, responsive mobile layouts, route search and clear booking CTAs. The supplied logo artwork is represented in `assets/images/logo.svg`.

## Core modules already in the repository
- Public landing page and route search
- Customer booking flow with bus/trip/seat selection
- Passenger information and ticket generation
- Customer booking history
- Cashier dashboard and counter ticket workflow
- Admin dashboard
- Bus management
- Daily trips/routes and flexible bus assignment
- MySQL schema with buses, destinations, routes, trips, seats, trip seats, bookings, tickets and payments
- Email verification flow
- Responsive Bootstrap UI

## Database
Import `database/schema.sql` into MySQL, then configure `config/database.php` for your environment.

## Local setup
1. PHP 8+ and MySQL 8+.
2. Import `database/schema.sql`.
3. Configure database credentials.
4. Run Apache/Nginx with PHP and open `index.php`.

## Booking safety
The database uses a dedicated `trip_seats` inventory so the same physical seat can be reused on different trips while remaining independently available per trip. Production deployments should additionally enforce atomic reservation/payment workflows and use HTTPS.

## Production roadmap
- Telebirr / Chapa / card payment gateway integration
- OTP and email notifications
- QR ticket signing + public ticket verification
- Refund/cancellation policies
- Full admin CRUD for destinations/routes/trips/cashiers
- Reporting/export and audit logs
- Rate limiting and stronger session/security headers
