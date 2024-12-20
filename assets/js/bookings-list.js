jQuery(function($) {
    'use strict';

    function loadBookings(filters = {}) {
        $.ajax({
            url: menphisBookings.ajax_url,
            type: 'POST',
            data: {
                action: 'get_user_bookings',
                nonce: menphisBookings.nonce,
                filters: filters
            },
            success: function(response) {
                if (response.success) {
                    renderBookings(response.data);
                }
            }
        });
    }

    function renderBookings(bookings) {
        const $container = $('#bookings-list-content');
        $container.empty();

        bookings.forEach(booking => {
            const date = new Date(booking.booking_date);
            const formattedDate = date.toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const row = `
                <tr>
                    <td>${formattedDate}</td>
                    <td>${booking.booking_time}</td>
                    <td>${booking.service_name}</td>
                    <td>${booking.location_name}</td>
                    <td><span class="status-badge status-${booking.status}">${getStatusText(booking.status)}</span></td>
                    <td class="action-buttons">
                        <button class="btn-view" data-booking-id="${booking.id}">Ver detalles</button>
                        ${booking.status === 'pending' ? `
                            <button class="btn-cancel" data-booking-id="${booking.id}">Cancelar</button>
                        ` : ''}
                    </td>
                </tr>
            `;
            $container.append(row);
        });
    }

    function getStatusText(status) {
        const statusTexts = {
            'pending': 'Pendiente',
            'confirmed': 'Confirmada',
            'completed': 'Completada',
            'cancelled': 'Cancelada'
        };
        return statusTexts[status] || status;
    }

    // Event Listeners
    $('#date-filter, #status-filter').on('change', function() {
        const filters = {
            date: $('#date-filter').val(),
            status: $('#status-filter').val()
        };
        loadBookings(filters);
    });

    $(document).on('click', '.btn-view', function() {
        const bookingId = $(this).data('booking-id');
        showBookingDetails(bookingId);
    });

    $(document).on('click', '.btn-cancel', function() {
        const bookingId = $(this).data('booking-id');
        if (confirm('¿Está seguro de que desea cancelar esta reserva?')) {
            cancelBooking(bookingId);
        }
    });

    // Cargar reservas iniciales
    loadBookings();
}); 