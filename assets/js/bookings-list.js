jQuery(function($) {
    'use strict';

    function closeModal() {
        const modal = document.getElementById('booking-details-modal');
        modal.style.display = 'none';
        
        // Remover el backdrop si existe
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        
        // Remover la clase modal-open del body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

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
        const $cancelButton = $modal.find('.btn-cancel-booking');
        
        $modalBody.html('<div class="loading">Cargando detalles...</div>');
        
        // Mostrar el modal y agregar clases necesarias
        $modal.css('display', 'block');
        document.body.classList.add('modal-open');
        
        // Agregar backdrop si no existe
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }

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
                    renderBookingDetails(response.data);
                    
                    // Mostrar botón de cancelar solo si la reserva está pendiente
                    if (response.data.status === 'pending') {
                        $cancelButton.show().data('booking-id', bookingId);
                    } else {
                        $cancelButton.hide();
                    }
                } else {
                    $modalBody.html('<div class="alert alert-danger">Error al cargar los detalles</div>');
                }
            },
            error: function() {
                $modalBody.html('<div class="alert alert-danger">Error de conexión</div>');
            }
        });
    }

    function renderBookingDetails(booking) {
        const date = new Date(booking.booking_date);
        const formattedDate = date.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        const html = `
            <div class="booking-details">
                <div class="booking-detail-item">
                    <span class="booking-detail-label">Fecha:</span>
                    <span class="booking-detail-value">${formattedDate}</span>
                </div>
                <div class="booking-detail-item">
                    <span class="booking-detail-label">Hora:</span>
                    <span class="booking-detail-value">${booking.booking_time}</span>
                </div>
                <div class="booking-detail-item">
                    <span class="booking-detail-label">Servicio:</span>
                    <span class="booking-detail-value">${booking.service_name}</span>
                </div>
                <div class="booking-detail-item">
                    <span class="booking-detail-label">Ubicación:</span>
                    <span class="booking-detail-value">${booking.location_name}</span>
                </div>
                <div class="booking-detail-item">
                    <span class="booking-detail-label">Estado:</span>
                    <span class="status-badge status-${booking.status}">${getStatusText(booking.status)}</span>
                </div>
            </div>
        `;

        $('#booking-details-modal .modal-body').html(html);
    }

    $(document).on('click', '.btn-cancel-booking', function() {
        const bookingId = $(this).data('booking-id');
        if (confirm('¿Está seguro de que desea cancelar esta reserva?')) {
            cancelBooking(bookingId);
            $('#booking-details-modal').modal('hide');
        }
    });

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
                    // Mostrar mensaje de éxito
                    showMessage('Reserva cancelada exitosamente', 'success');
                } else {
                    showMessage('No se pudo cancelar la reserva', 'error');
                }
            },
            error: function() {
                showMessage('Error de conexión', 'error');
            }
        });
    }

    function showMessage(message, type = 'info') {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 
                          'alert-info';
        
        const $alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `);

        $('.menphis-my-bookings').prepend($alert);
        
        // Remover la alerta después de 5 segundos
        setTimeout(() => {
            $alert.alert('close');
        }, 5000);
    }

    // Agregar manejador para cerrar con la tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    // Agregar manejador para cerrar al hacer clic fuera del modal
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('booking-details-modal');
        if (event.target === modal) {
            closeModal();
        }
    });

    // Cargar reservas iniciales
    loadBookings();
}); 