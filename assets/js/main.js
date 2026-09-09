// Main JavaScript for Bus Ticketing System

// Initialize tooltips and popovers
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Form validation
    initFormValidation();
});

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Seat Selection Handler
function selectSeat(seatId) {
    const seat = document.getElementById('seat-' + seatId);
    if (!seat) return;

    // Check if seat is available
    if (seat.classList.contains('occupied') || seat.classList.contains('paid')) {
        alert('This seat is not available');
        return;
    }

    // Remove previous selection
    document.querySelectorAll('.seat.selected').forEach(s => {
        s.classList.remove('selected');
    });

    // Add selection to clicked seat
    seat.classList.add('selected');

    // Update hidden input
    const seatInput = document.getElementById('selected_seat');
    if (seatInput) {
        seatInput.value = seatId;
    }
}

// Price Calculator
function calculatePrice() {
    const baseFare = parseFloat(document.getElementById('base_fare')?.value) || 0;
    const additionalCharges = parseFloat(document.getElementById('additional_charges')?.value) || 0;
    const total = baseFare + additionalCharges;

    const totalElement = document.getElementById('total_price');
    if (totalElement) {
        totalElement.textContent = 'ETB ' + total.toFixed(2);
    }
}

// Print Ticket
function printTicket() {
    window.print();
}

// Export to PDF
function exportToPDF() {
    const element = document.getElementById('ticket');
    const opt = {
        margin: 10,
        filename: 'ticket-' + new Date().getTime() + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
    };
    // html2pdf().set(opt).save(element);
    alert('PDF export requires html2pdf library');
}

// Date Picker
function initDatePicker() {
    const dateInput = document.getElementById('travel_date');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
}

// Load available buses based on route
function loadBuses() {
    const from = document.getElementById('from_destination')?.value;
    const to = document.getElementById('to_destination')?.value;
    const date = document.getElementById('travel_date')?.value;

    if (!from || !to || !date) return;

    fetch('api/get-buses.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            from: from,
            to: to,
            date: date
        })
    })
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('buses-container');
        if (container) {
            container.innerHTML = '';
            if (data.success && data.buses.length > 0) {
                data.buses.forEach(bus => {
                    container.innerHTML += `
                        <div class="col-md-6 mb-3">
                            <div class="card bus-card">
                                <div class="card-body">
                                    <h5 class="card-title">${bus.bus_name}</h5>
                                    <p class="card-text">
                                        <small>
                                            <strong>Bus No:</strong> ${bus.bus_number}<br>
                                            <strong>Grade:</strong> ${bus.vehicle_grade}<br>
                                            <strong>Departure:</strong> ${bus.departure_time}<br>
                                            <strong>Fare:</strong> ETB ${bus.fare}
                                        </small>
                                    </p>
                                    <button class="btn btn-primary btn-sm" onclick="selectBus(${bus.trip_id})">
                                        Select Bus
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                container.innerHTML = '<div class="alert alert-info">No buses available for this route</div>';
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Select Bus
function selectBus(tripId) {
    window.location.href = 'select-seats.php?trip_id=' + tripId;
}

// Format phone number
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 10) value = value.slice(0, 10);
    input.value = value;
}

// Show loading spinner
function showLoading() {
    document.body.innerHTML += `
        <div class="spinner-overlay">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
}

// Hide loading spinner
function hideLoading() {
    const overlay = document.querySelector('.spinner-overlay');
    if (overlay) overlay.remove();
}

// Close alert
function closeAlert(element) {
    element.closest('.alert').remove();
}
