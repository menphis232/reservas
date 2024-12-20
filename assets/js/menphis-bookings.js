jQuery(document).ready(function($) {
    'use strict';

    // Variable para controlar el estado de la tabla
    let tableState = {
        isLoading: false,
        currentRequest: null
    };

    // Debug para verificar que menphisBookings está disponible
    console.log('menphisBookings:', typeof menphisBookings !== 'undefined' ? menphisBookings : 'no definido');

    // Verificar que tenemos todo lo necesario
    if (typeof menphisBookings === 'undefined') {
        console.error('Error: menphisBookings no está definido');
        return;
    }

    if (typeof M === 'undefined') {
        console.error('Error: Materialize no está definido');
        return;
    }

    console.log('Menphis Bookings JS loaded');

    function initComponents() {
        // Inicializar modales
        var modals = document.querySelectorAll('.modal');
        if (modals.length > 0) {
            M.Modal.init(modals, {
                dismissible: true,
                opacity: 0.5,
                inDuration: 250,
                outDuration: 250
            });
        }

        // Inicializar Select2
        $('.select2').select2({
            width: '100%',
            language: {
                noResults: function() {
                    return "No se encontraron resultados";
                },
                searching: function() {
                    return "Buscando...";
                }
            }
        }).on('select2:open', function() {
            // Debug
            console.log('Select abierto:', $(this).attr('id'));
            console.log('Valor actual:', $(this).val());
        }).on('select2:select', function(e) {
            // Debug
            console.log('Selección realizada:', {
                element: $(this).attr('id'),
                value: e.params.data
            });
        });

        // Inicializar Datepicker
        var datepickers = document.querySelectorAll('.datepicker');
        if (datepickers.length > 0) {
            M.Datepicker.init(datepickers, {
                format: 'dd/mm/yyyy',
                firstDay: 1,
                i18n: {
                    months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                    monthsShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    weekdays: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
                    weekdaysShort: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
                    weekdaysAbbrev: ['D', 'L', 'M', 'M', 'J', 'V', 'S']
                }
            });
        }

        // Inicializar Timepicker
        var timepickers = document.querySelectorAll('.timepicker');
        if (timepickers.length > 0) {
            M.Timepicker.init(timepickers, {
                twelveHour: false,
                i18n: {
                    cancel: 'Cancelar',
                    done: 'Aceptar'
                }
            });
        }
    }

    // Manejar el envío del formulario
    $('#booking-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData();
        formData.append('action', 'add_booking');
        formData.append('nonce', menphisBookings.nonce);
        formData.append('customer', $('#customer').val());
        formData.append('location', $('#location').val());
        formData.append('staff', $('#staff').val());
        
        var services = $('#services').val();
        if (services) {
            services.forEach(function(service) {
                formData.append('services[]', service);
            });
        }
        
        formData.append('booking_date', $('#booking_date').val());
        formData.append('booking_time', $('#booking_time').val());
        formData.append('notes', $('#notes').val());

        // Debug
        console.log('Enviando datos:', {
            customer: $('#customer').val(),
            location: $('#location').val(),
            staff: $('#staff').val(),
            services: $('#services').val(),
            booking_date: $('#booking_date').val(),
            booking_time: $('#booking_time').val(),
            notes: $('#notes').val()
        });

        $.ajax({
            url: menphisBookings.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    M.toast({html: response.data.message || 'Reserva guardada correctamente'});
                    var modal = M.Modal.getInstance($('#modal-new-booking'));
                    modal.close();
                    refreshBookingsTable();
                    
                    // Limpiar formulario
                    $('#booking-form')[0].reset();
                    $('#services').val(null).trigger('change');
                    $('#location').val('').trigger('change');
                    $('#customer').val('').trigger('change');
                    M.updateTextFields();
                } else {
                    M.toast({html: 'Error: ' + (response.data || 'Error desconocido')});
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición:', {xhr, status, error});
                M.toast({html: 'Error al guardar: ' + error});
            }
        });
    });

    // Función para cargar la tabla de reservas
    function loadBookingsTable(useFilters = false) {
        // Si hay una petición en curso, la cancelamos
        if (tableState.currentRequest) {
            tableState.currentRequest.abort();
        }

        // Si está cargando, no hacer nada
        if (tableState.isLoading) {
            console.log('La tabla está cargando, esperando...');
            return;
        }

        console.log('=== INICIO LOAD BOOKINGS ===');
        tableState.isLoading = true;

        // Datos base para la petición
        var data = {
            action: 'get_bookings_list',
            nonce: menphisBookings.nonce
        };

        // Solo agregar filtros si useFilters es true
        if (useFilters) {
            var filters = {
                booking_id: $('#booking_id').val() || '',
                date_from: $('#date_from').val() || '',
                date_to: $('#date_to').val() || '',
                status: $('#status').val() || ''
            };

            // Solo agregar filtros si al menos uno tiene valor
            if (Object.values(filters).some(value => value !== '')) {
                data.filters = filters;
            }
        }

        tableState.currentRequest = $.ajax({
            url: menphisBookings.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    var bookings = response.data;
                    var html = '';
                    
                    $('table tbody').empty();
                    
                    bookings.forEach(function(booking) {
                        html += `
                            <tr>
                                <td>${booking.ID}</td>
                                <td>${booking.customer_name}</td>
                                <td>${booking.location_name}</td>
                                <td>${booking.booking_date}</td>
                                <td>${booking.booking_time}</td>
                                <td>${booking.staff_name}</td>
                                <td>
                                    <button class="btn-small waves-effect waves-light blue view-booking" data-id="${booking.ID}">
                                        <i class="material-icons">visibility</i>
                                    </button>
                                    <button class="btn-small waves-effect waves-light green edit-booking" data-id="${booking.ID}">
                                        <i class="material-icons">edit</i>
                                    </button>
                                    <button class="btn-small waves-effect waves-light red delete-booking" data-id="${booking.ID}">
                                        <i class="material-icons">delete</i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    $('table tbody').html(html);
                }
            },
            complete: function() {
                tableState.isLoading = false;
                tableState.currentRequest = null;
            }
        });
    }

    // Inicializar componentes
    initComponents();
    
    // NO cargar la tabla automáticamente al inicio
    // Eliminar la llamada automática a loadBookingsTable()

    // Manejar el filtrado - Ahora será el punto principal para cargar la tabla
    $('#filtrar-reservas').on('click', function() {
        loadBookingsTable(true);
    });

    // Limpiar filtros
    $('#clear_filters').on('click', function() {
        $('#booking_id').val('');
        $('#date_from').val('');
        $('#date_to').val('');
        $('#status').val('').formSelect();
        M.updateTextFields();
        loadBookingsTable(false);
    });

    // Actualizar después de agregar/editar/eliminar
    function refreshBookingsTable() {
        loadBookingsTable(false);
    }

    // Manejar clic en botón editar
    $(document).on('click', '.edit-booking', function(e) {
        e.preventDefault();
        const bookingId = $(this).data('id');
        
        $.ajax({
            url: menphisBookings.ajax_url,
            type: 'POST',
            data: {
                action: 'edit_booking',
                booking_id: bookingId,
                nonce: menphisBookings.nonce
            },
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                if (response.success) {
                    fillBookingForm(response.data);
                } else {
                    M.toast({html: response.data || 'Error al cargar los datos'});
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                M.toast({html: 'Error al cargar los datos'});
            }
        });
    });

    // Manejar clic en botón eliminar
    $(document).on('click', '.delete-booking', function(e) {
        e.preventDefault();
        var bookingId = $(this).data('id');
        
        if (confirm('¿Estás seguro de que deseas eliminar esta reserva?')) {
            $.ajax({
                url: menphisBookings.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_booking',
                    nonce: menphisBookings.nonce,
                    id: bookingId
                },
                success: function(response) {
                    if (response.success) {
                        M.toast({html: 'Reserva eliminada correctamente'});
                        refreshBookingsTable();
                    } else {
                        M.toast({html: 'Error: ' + (response.data || 'Error al eliminar reserva')});
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al eliminar reserva:', error);
                    M.toast({html: 'Error al eliminar reserva'});
                }
            });
        }
    });

    // Manejar filtros
    $('#apply_filters').on('click', function() {
        loadBookingsTable();
    });

    $('#clear_filters').on('click', function() {
        $('#booking_id').val('');
        $('#date_from').val('');
        $('#date_to').val('');
        $('#status').val('').formSelect();
        M.updateTextFields();
        loadBookingsTable();
    });

    // Limpiar formulario al abrir modal para nueva reserva
    $(document).on('click', '[href="#modal-new-booking"]', function() {
        $('#booking-form')[0].reset();
        $('#booking_id').val('');
        $('#services').val(null).trigger('change');
        $('#modal-new-booking h4').text('Nueva Reserva');
        M.updateTextFields();
    });

    // Ver detalles de reserva
    $(document).on('click', '.view-booking', function() {
        var bookingId = $(this).data('id');
        viewBooking(bookingId);
    });

    // Eliminar reserva
    $(document).on('click', '.delete-booking', function() {
        var bookingId = $(this).data('id');
        deleteBooking(bookingId);
    });

    function viewBooking(bookingId) {
        // Debug para verificar el nonce
        console.log('Nonce:', menphisBookings.nonce);

        $.ajax({
            url: menphisBookings.ajax_url,
            type: 'POST',
            data: {
                action: 'get_booking_data',
                booking_id: bookingId,
                nonce: menphisBookings.nonce
            },
            success: function(response) {
                if (response.success) {
                    var booking = response.data;
                    var html = `
                        <div class="booking-details">
                            <h5>Detalles de la Reserva #${booking.id}</h5>
                            <div class="row">
                                <div class="col s12 m6">
                                    <p><strong>Cliente:</strong> ${booking.customer_name || 'No especificado'}</p>
                                    <p><strong>Fecha:</strong> ${booking.booking_date || 'No especificada'}</p>
                                    <p><strong>Hora:</strong> ${booking.booking_time || 'No especificada'}</p>
                                    <p><strong>Ubicación:</strong> ${booking.location_name || 'No especificada'}</p>
                                    <p><strong>Empleado:</strong> ${booking.staff_name || 'No especificado'}</p>
                                    <p><strong>Estado:</strong> ${booking.status || 'No especificado'}</p>
                                </div>
                                <div class="col s12 m6">
                                    <p><strong>Servicios:</strong></p>
                                    <ul class="services-list">
                                        ${(booking.services || []).map(service => `
                                            <li>${service.service_name} </li>
                                        `).join('')}
                                    </ul>
                                </div>
                            </div>
                            ${booking.notes ? `
                                <div class="row">
                                    <div class="col s12">
                                        <p><strong>Notas:</strong></p>
                                        <p>${booking.notes}</p>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    `;

                    // Actualizar el contenido del modal
                    $('#modal-view-booking .modal-content').html(html);
                    
                    var modalElem = document.getElementById('modal-view-booking');
                    var modalInstance = M.Modal.getInstance(modalElem);
                    if (!modalInstance) {
                        modalInstance = M.Modal.init(modalElem);
                    }
                    modalInstance.open();
                } else {
                    M.toast({html: 'Error: ' + (response.data || 'Error al cargar los detalles de la reserva')});
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar detalles de la reserva:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                M.toast({html: 'Error al cargar los detalles de la reserva'});
            }
        });
    }

    function deleteBooking(bookingId) {
        if (confirm('¿Estás seguro de que quieres eliminar esta reserva?')) {
            $.ajax({
                url: menphisBookings.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_booking',
                    booking_id: bookingId,
                    nonce: menphisBookings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        M.toast({html: 'Reserva eliminada correctamente'});
                        refreshBookingsTable();
                    } else {
                        M.toast({html: response.data || 'Error al eliminar la reserva'});
                    }
                }
            });
        }
    }

    // Asegurarnos de que los modales se inicializan cuando el documento está listo
    $(document).ready(function() {
        // Inicializar todos los modales
        var modals = document.querySelectorAll('.modal');
        M.Modal.init(modals);
    });

    function fillBookingForm(booking) {
        console.log('Llenando formulario con datos:', booking);

        // Llenar campos ocultos
        $('#booking_id').val(booking.id);

        // Seleccionar cliente
        $('#customer').val(booking.customer_id).trigger('change');
        
        // Seleccionar ubicación
        $('#location').val(booking.location_id).trigger('change');
        
        // Seleccionar empleado si existe
        if (booking.staff_id && booking.staff_id !== '0') {
            $('#staff').val(booking.staff_id).trigger('change');
        } else {
            $('#staff').val('').trigger('change');
        }

        // Seleccionar servicios
        if (booking.services && booking.services.length > 0) {
            const serviceIds = booking.services.map(service => service.service_id);
            $('#services').val(serviceIds).trigger('change');
        } else {
            $('#services').val(null).trigger('change');
        }

        // Llenar fecha y hora
        $('#booking_date').val(booking.booking_date);
        $('#booking_time').val(booking.booking_time);

        // Llenar notas si existen
        $('#notes').val(booking.notes || '');

        // Actualizar campos de Materialize
        M.updateTextFields();
        
        // Reinicializar selects de Materialize
        var elems = document.querySelectorAll('select');
        M.FormSelect.init(elems);

        // Cambiar título del modal
        $('#modal-new-booking h4').text('Editar Reserva');

        // Abrir el modal
        var modal = M.Modal.getInstance(document.querySelector('#modal-new-booking'));
        if (modal) {
            modal.open();
        } else {
            modal = M.Modal.init(document.querySelector('#modal-new-booking'));
            modal.open();
        }
    }
}); 