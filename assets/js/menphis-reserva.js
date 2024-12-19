jQuery(document).ready(function($) {
    let currentStep = 1;
    
    // Manejo de pasos
    function showStep(step) {
        $('.step-' + currentStep).hide();
        $('.step-' + step).show();
        currentStep = step;
    }

    // Cuando se selecciona un servicio
    $('.menphis-service-item').click(function() {
        const serviceId = $(this).data('service-id');
        
        // Cargar ubicaciones disponibles
        $.ajax({
            url: menphis_ajax.ajax_url,
            data: {
                action: 'menphis_get_available_locations',
                service_id: serviceId
            },
            success: function(response) {
                $('#menphis-locations').html(response);
                showStep(2); // Mostrar selección de ubicación
            }
        });
    });

    // Cuando se selecciona una ubicación
    $('#menphis-locations').on('change', function() {
        const locationId = $(this).val();
        const serviceId = $('.menphis-service-item.selected').data('service-id');
        
        // Cargar calendario con disponibilidad
        loadAvailableDates(serviceId, locationId);
        showStep(3);
    });

    function loadAvailableDates(serviceId, locationId) {
        $('#menphis-calendar').datepicker({
            beforeShowDay: function(date) {
                // Verificar disponibilidad para esta fecha
                return checkDateAvailability(date, serviceId, locationId);
            },
            onSelect: function(date) {
                loadAvailableTimes(date, serviceId, locationId);
            }
        });
    }

    function loadAvailableTimes(date, serviceId, locationId) {
        $.ajax({
            url: menphis_ajax.ajax_url,
            data: {
                action: 'menphis_get_available_times',
                date: date,
                service_id: serviceId,
                location_id: locationId
            },
            success: function(response) {
                $('#menphis-time-slots').html(response);
            }
        });
    }
}); 