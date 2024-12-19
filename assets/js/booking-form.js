jQuery(document).ready(function($) {
    'use strict';

    // No inicializar si estamos en el editor de Elementor
    if (window.elementorFrontend && window.elementorFrontend.isEditMode()) {
        return;
    }

    // Función para actualizar el resumen
    function updateBookingSummary() {
        var location = $('#booking_location option:selected').text();
        var date = $('#booking-calendar').datepicker('getDate');
        var time = $('input[name="booking_time"]:checked').val();

        // Formatear la fecha
        if (date) {
            var formattedDate = date.toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            $('.date-display').text(formattedDate);
        }

        $('.location-display').text(location || '');
        $('.time-display').text(time || '');
    }

    // Inicializar calendario
    $('#booking-calendar').datepicker({
        inline: true,
        minDate: 0,
        maxDate: '+2M',
        dateFormat: 'yy-mm-dd',
        firstDay: 1,
        showOtherMonths: true,
        selectOtherMonths: true,
        beforeShowDay: function(date) {
            var day = date.getDay();
            return [day != 0, '']; // Deshabilita domingos
        },
        onSelect: function(dateText) {
            updateBookingSummary();
            updateAvailableTimeSlots(dateText);
        }
    });

    // Actualizar resumen cuando cambian los valores
    $('#booking_location').on('change', updateBookingSummary);
    $('input[name="booking_time"]').on('change', updateBookingSummary);

    // Función para actualizar horarios disponibles
    function updateAvailableTimeSlots(date) {
        var locationId = $('#booking_location').val();
        if (!locationId || !date) return;

        // Obtener los servicios del carrito
        $.ajax({
            url: menphisBooking.ajax_url,
            type: 'POST',
            data: {
                action: 'get_available_time_slots',
                location_id: locationId,
                date: date,
                nonce: menphisBooking.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Primero deshabilitamos todos los slots
                    $('.time-slot input').prop('disabled', true);
                    $('.time-slot label').addClass('disabled');

                    // Habilitamos solo los horarios disponibles
                    response.data.forEach(function(timeSlot) {
                        $('#time_' + timeSlot.time.replace(':', '')).prop('disabled', false);
                        $('label[for="time_' + timeSlot.time.replace(':', '') + '"]').removeClass('disabled');
                    });

                    // Si hay un horario seleccionado que ya no está disponible, lo deseleccionamos
                    var selectedTime = $('input[name="booking_time"]:checked');
                    if (selectedTime.length && selectedTime.prop('disabled')) {
                        selectedTime.prop('checked', false);
                        updateBookingSummary();
                    }
                } else {
                    M.toast({html: 'Error al obtener horarios disponibles'});
                }
            }
        });
    }

    // Manejar navegación del stepper
    $('.next-step').on('click', function(e) {
        e.preventDefault();
        var currentStep = $('.step-content.active');
        var currentStepNum = currentStep.data('step');
        var nextStepNum = currentStepNum + 1;
        
        // Validar campos requeridos antes de avanzar
        var isValid = true;
        currentStep.find('input[required], select[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) return;

        // Actualizar contenido
        currentStep.removeClass('active');
        $('.step-content[data-step="' + nextStepNum + '"]').addClass('active');
        
        // Actualizar header
        $('.step[data-step="' + currentStepNum + '"]').addClass('done');
        $('.step[data-step="' + nextStepNum + '"]').addClass('active');
    });

    $('.previous-step').on('click', function(e) {
        e.preventDefault();
        var currentStep = $('.step-content.active');
        var currentStepNum = currentStep.data('step');
        var prevStepNum = currentStepNum - 1;
        
        // Actualizar contenido
        currentStep.removeClass('active');
        $('.step-content[data-step="' + prevStepNum + '"]').addClass('active');
        
        // Actualizar header
        $('.step[data-step="' + currentStepNum + '"]').removeClass('active');
        $('.step[data-step="' + prevStepNum + '"]').removeClass('done').addClass('active');
    });

    // Manejar eliminación de servicios
    $('.remove-service').on('click', function(e) {
        e.preventDefault();
        var cartKey = $(this).data('cart-key');
        var $serviceItem = $(this).closest('.service-item');

        $.ajax({
            url: menphisBooking.ajax_url,
            type: 'POST',
            data: {
                action: 'remove_service_from_cart',
                cart_key: cartKey,
                nonce: menphisBooking.nonce
            },
            success: function(response) {
                if (response.success) {
                    $serviceItem.fadeOut(function() {
                        $(this).remove();
                        if ($('.service-item').length === 0) {
                            location.reload();
                        }
                    });
                }
            }
        });
    });

    // Manejar cambios en los campos requeridos
    $('input[required], select[required]').on('change', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
        }
    });

    // En la función que maneja el botón de "Finalizar Reserva"
    $('.finish-booking').on('click', function(e) {
        e.preventDefault();
        
        // Obtener datos de la reserva
        var bookingData = {
            location: $('#booking_location').val(),
            date: $('#booking-calendar').datepicker('getDate').toISOString().split('T')[0],
            time: $('input[name="booking_time"]:checked').val()
        };
        
        console.log('Datos a enviar:', bookingData);
        
        // Validar que todos los datos estén presentes
        if (!bookingData.location || !bookingData.date || !bookingData.time) {
            alert('Por favor completa todos los campos de la reserva');
            return;
        }

        // Guardar datos en sesión antes de enviar al checkout
        $.ajax({
            url: menphisBooking.ajax_url,
            type: 'POST',
            data: {
                action: 'save_booking_data',
                booking_data: bookingData,
                nonce: menphisBooking.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Guardar datos en input oculto
                    $('#booking_data').val(JSON.stringify(bookingData));
                    // Redirigir al checkout
                    window.location.href = menphisBooking.checkout_url;
                } else {
                    alert('Hubo un error al guardar los datos de la reserva. Por favor intenta de nuevo.');
                }
            },
            error: function() {
                alert('Hubo un error al procesar la reserva. Por favor intenta de nuevo.');
            }
        });
    });

    $('#complete-booking').on('click', function(e) {
        e.preventDefault();
        
        // Validar formulario
        if (!validateBookingForm()) {
            return;
        }

        var bookingData = {
            // ... otros datos de la reserva ...
            email: $('#booking_email').val(),
            password: $('#booking_password').val(),
            password_confirm: $('#booking_password_confirm').val()
        };

        // Validar contraseñas si el usuario no está logueado
        if (!isUserLoggedIn && bookingData.password !== bookingData.password_confirm) {
            showError('Las contraseñas no coinciden');
            return;
        }

        // Enviar datos al servidor
        $.ajax({
            url: menphisBooking.ajax_url,
            type: 'POST',
            data: {
                action: 'process_booking',
                nonce: menphisBooking.nonce,
                booking_data: bookingData
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Reserva creada exitosamente');
                    // Redirigir a la página de confirmación
                    window.location.href = menphisBooking.confirmation_url + '?booking_id=' + response.data.booking_id;
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError('Error al procesar la reserva');
            }
        });
    });

    function validateBookingForm() {
        var isValid = true;
        
        // Validar campos requeridos
        $('.booking-step-3 input[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            showError('Por favor complete todos los campos requeridos');
        }

        return isValid;
    }

    function showError(message) {
        // Implementar mostrar mensaje de error
    }

    function showSuccess(message) {
        // Implementar mostrar mensaje de éxito
    }
});