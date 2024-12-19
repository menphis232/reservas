jQuery(document).ready(function($) {
    'use strict';

    // Debug
    console.log('Menphis Admin JS cargado en:', menphisAdmin.currentPage);

    // Inicializar componentes de Materialize
    M.AutoInit();

    // Inicializar elementos específicos
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    var tooltips = document.querySelectorAll('.tooltipped');
    M.Tooltip.init(tooltips);

    // Solo para el dashboard
    if (menphisAdmin.currentPage.includes('menphis-reserva')) {
        // Cargar datos de reservas
        function loadBookingData() {
            $.ajax({
                url: menphisAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_booking_data',
                    nonce: menphisAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateDashboard(response.data);
                    } else {
                        M.toast({html: 'Error al cargar los datos'});
                    }
                },
                error: function() {
                    M.toast({html: 'Error de conexión'});
                }
            });
        }

        // Actualizar dashboard con los datos
        function updateDashboard(data) {
            if (data.total_bookings) $('#total-bookings').text(data.total_bookings);
            if (data.pending_bookings) $('#pending-bookings').text(data.pending_bookings);
            if (data.completed_bookings) $('#completed-bookings').text(data.completed_bookings);
            if (data.total_revenue) $('#total-revenue').text(data.total_revenue);
        }

        loadBookingData();
    }
}); 