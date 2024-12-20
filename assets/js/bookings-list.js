jQuery(function($) {
    'use strict';

    function loadBookings(filters = {}) {
        const $container = $('#bookings-list-content');
        
        // Mostrar indicador de carga
        $container.html('<tr><td colspan="6" class="text-center">Cargando reservas...</td></tr>');

        $.ajax({
            url: menphisBookings.ajax_url,
            type: 'POST',
            data: {
                action: 'get_user_bookings',
                nonce: menphisBookings.nonce,
                filters: filters
            },
            success: function(response) {
                if (response.success && response.data) {
                    if (response.data.length > 0) {
                        renderBookings(response.data);
                    } else {
                        $container.html('<tr><td colspan="6" class="text-center">No se encontraron reservas</td></tr>');
                    }
                } else {
                    $container.html('<tr><td colspan="6" class="text-center text-danger">Error al cargar las reservas</td></tr>');
                }
            },
            error: function() {
                $container.html('<tr><td colspan="6" class="text-center text-danger">Error de conexión</td></tr>');
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

    function showBookingDetails(bookingId) {
        const $modal = $('#booking-details-modal');
        const $modalBody = $modal.find('.modal-body');
        
        $modalBody.html('Cargando detalles...');
        $modal.modal('show');

        $.ajax({
            url: menphisBookings.ajax_url,
            type: 'POST',
            data: {
                action: 'get_booking_details',
                booking_id: bookingId,
                nonce: menphisBookings.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderBookingDetails(response.data, $modalBody);
                } else {
                    $modalBody.html('<div class="alert alert-danger">Error al cargar los detalles</div>');
                }
            },
            error: function() {
                $modalBody.html('<div class="alert alert-danger">Error de conexión</div>');
            }
        });
    }

    function cancelBooking(bookingId) {
        $.ajax({
            url: menphisBookings.ajax_url,
            type: 'POST',
            data: {
                action: 'cancel_booking',
                booking_id: bookingId,
                nonce: menphisBookings.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Recargar la lista de reservas
                    loadBookings({
                        date: $('#date-filter').val(),
                        status: $('#status-filter').val()
                    });
                } else {
                    alert('No se pudo cancelar la reserva');
                }
            },
            error: function() {
                alert('Error de conexión');
            }
        });
    }

    // Cargar reservas iniciales
    loadBookings();
}); 