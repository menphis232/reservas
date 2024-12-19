jQuery(document).ready(function($) {
    'use strict';

    console.log('Menphis Staff JS iniciando...');

    function initComponents() {
        // Inicializar modales
        var modals = document.querySelectorAll('.modal');
        if (modals.length > 0) {
            M.Modal.init(modals, {
                dismissible: true,
                opacity: 0.5,
                inDuration: 250,
                outDuration: 250,
                onOpenStart: function(modal) {
                    // Si es el modal de horarios, inicializar sus componentes
                    if (modal.id === 'modal-schedule') {
                        initScheduleForm();
                    }
                }
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
        });

        // Inicializar otros componentes
        M.updateTextFields();
    }

    // Manejar el envío del formulario
    $('#staff-form').on('submit', function(e) {
        e.preventDefault();
        
        var staffId = $('#staff_id').val();
        var isEdit = staffId !== '';
        
        var formData = new FormData();
        formData.append('action', isEdit ? 'update_staff' : 'add_staff');
        formData.append('nonce', menphisStaff.nonce);
        
        if (isEdit) {
            formData.append('id', staffId);
        }
        
        formData.append('name', $('#staff_name').val());
        formData.append('email', $('#staff_email').val());
        formData.append('phone', $('#staff_phone').val());
        
        var services = $('#staff_services').val();
        if (services) {
            services.forEach(function(service) {
                formData.append('services[]', service);
            });
        }
        
        var locations = $('#staff_locations').val();
        if (locations) {
            locations.forEach(function(location) {
                formData.append('locations[]', location);
            });
        }

        $.ajax({
            url: menphisStaff.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    M.toast({html: response.data.message || 'Empleado guardado correctamente'});
                    var modal = M.Modal.getInstance($('#modal-new-staff'));
                    modal.close();
                    loadStaffTable();
                    
                    // Limpiar formulario
                    $('#staff-form')[0].reset();
                    $('#staff_id').val('');
                    $('#staff_services').val(null).trigger('change');
                    $('#staff_locations').val(null).trigger('change');
                    $('#modal-new-staff h4').text('Nuevo Empleado');
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

    // Función para cargar la tabla de personal
    function loadStaffTable() {
        $.ajax({
            url: menphisStaff.ajax_url,
            type: 'POST',
            data: {
                action: 'get_staff_list',
                nonce: menphisStaff.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStaffTable(response.data);
                } else {
                    M.toast({html: 'Error al cargar personal: ' + response.data});
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar personal:', error);
                M.toast({html: 'Error al cargar personal'});
            }
        });
    }

    // Función para actualizar la tabla
    function updateStaffTable(staffList) {
        var tbody = $('#staff-table');
        tbody.empty();

        if (!staffList || staffList.length === 0) {
            tbody.append('<tr><td colspan="7" class="center-align">No hay personal registrado</td></tr>');
            return;
        }

        staffList.forEach(function(staff) {
            var row = $('<tr>');
            row.append('<td>' + staff.id + '</td>');
            row.append('<td>' + staff.name + '</td>');
            row.append('<td>' + staff.email + '</td>');
            row.append('<td>' + (staff.phone || '-') + '</td>');
            row.append('<td>' + (staff.services || '-') + '</td>');
            row.append('<td>' + staff.status + '</td>');
            row.append('<td>' + staff.actions + '</td>');
            tbody.append(row);
        });
    }

    // Limpiar formulario al abrir modal
    $(document).on('click', '[href="#modal-new-staff"]', function() {
        $('#staff-form')[0].reset();
        $('#staff_services').val(null).trigger('change');
        $('#staff_locations').val(null).trigger('change');
        M.updateTextFields();
    });

    // Manejar clic en botón editar
    $(document).on('click', '.edit-staff', function(e) {
        e.preventDefault();
        console.log('Clic en editar staff:', $(this).data('id'));
        var staffId = $(this).data('id');
        
        $.ajax({
            url: menphisStaff.ajax_url,
            type: 'POST',
            data: {
                action: 'get_staff',
                nonce: menphisStaff.nonce,
                id: staffId
            },
            success: function(response) {
                console.log('Respuesta get_staff:', response); // Debug
                if (response.success) {
                    var staff = response.data;
                    
                    // Llenar el formulario con los datos
                    $('#staff_id').val(staff.id);
                    $('#staff_name').val(staff.name);
                    $('#staff_email').val(staff.email);
                    $('#staff_phone').val(staff.phone);
                    
                    // Actualizar selects múltiples
                    if (staff.services) {
                        $('#staff_services').val(staff.services).trigger('change');
                    }
                    if (staff.locations) {
                        $('#staff_locations').val(staff.locations).trigger('change');
                    }
                    
                    // Actualizar labels
                    M.updateTextFields();
                    
                    // Cambiar título del modal
                    $('#modal-new-staff h4').text('Editar Empleado');
                    
                    // Abrir modal
                    var modal = M.Modal.getInstance($('#modal-new-staff'));
                    modal.open();
                } else {
                    M.toast({html: 'Error: ' + (response.data || 'Error al cargar empleado')});
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar empleado:', {xhr, status, error});
                M.toast({html: 'Error al cargar empleado: ' + error});
            }
        });
    });

    // Manejar clic en botón eliminar
    $(document).on('click', '.delete-staff', function(e) {
        e.preventDefault();
        console.log('Clic en eliminar staff:', $(this).data('id'));
        var staffId = $(this).data('id');
        
        if (confirm('¿Estás seguro de que deseas eliminar este empleado?')) {
            $.ajax({
                url: menphisStaff.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_staff',
                    nonce: menphisStaff.nonce,
                    id: staffId
                },
                success: function(response) {
                    if (response.success) {
                        M.toast({html: 'Empleado eliminado correctamente'});
                        loadStaffTable();
                    } else {
                        M.toast({html: 'Error: ' + (response.data || 'Error al eliminar empleado')});
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al eliminar empleado:', error);
                    M.toast({html: 'Error al eliminar empleado'});
                }
            });
        }
    });

    // Manejar clic en botón de horario
    $(document).on('click', '.schedule-staff', function(e) {
        e.preventDefault();
        var staffId = $(this).data('id');
        
        // Limpiar formulario y establecer staff_id
        $('#schedule-form')[0].reset();
        $('#schedule_staff_id').val(staffId);
        
        // Reinicializar timepickers
        initScheduleForm();
        
        // Cargar horarios existentes
        $.ajax({
            url: menphisStaff.ajax_url,
            type: 'POST',
            data: {
                action: 'get_staff_schedule',
                nonce: menphisStaff.nonce,
                staff_id: staffId
            },
            success: function(response) {
                if (response.success) {
                    console.log('Horarios recibidos:', response.data);
                    var schedules = response.data;
                    var schedulesByDay = {};

                    // Primero, organizar los horarios por día
                    schedules.forEach(function(schedule) {
                        if (!schedulesByDay[schedule.day_of_week]) {
                            schedulesByDay[schedule.day_of_week] = {
                                regular: null,
                                break: null
                            };
                        }
                        
                        if (schedule.is_break == 1) {
                            schedulesByDay[schedule.day_of_week].break = schedule;
                        } else {
                            schedulesByDay[schedule.day_of_week].regular = schedule;
                        }
                    });

                    console.log('Horarios organizados:', schedulesByDay);

                    // Luego, aplicar los horarios al formulario
                    Object.keys(schedulesByDay).forEach(function(day) {
                        var daySchedules = schedulesByDay[day];
                        var $container = $('.day-schedule[data-day="' + day + '"]');
                        
                        if (daySchedules.regular) {
                            // Activar el día
                            $container.find('.day-enabled').prop('checked', true).trigger('change');
                            
                            // Establecer horario regular
                            $container.find('.start-time').val(daySchedules.regular.start_time.slice(0, 5));
                            $container.find('.end-time').val(daySchedules.regular.end_time.slice(0, 5));
                            
                            // Si hay horario de descanso, establecerlo
                            if (daySchedules.break) {
                                $container.find('.break-enabled').prop('checked', true).trigger('change');
                                $container.find('.break-start').val(daySchedules.break.start_time.slice(0, 5));
                                $container.find('.break-end').val(daySchedules.break.end_time.slice(0, 5));
                            }
                        }
                    });
                    
                    // Actualizar campos materialize
                    M.updateTextFields();
                    
                    // Abrir modal
                    var modal = M.Modal.getInstance($('#modal-schedule'));
                    modal.open();
                } else {
                    M.toast({html: 'Error: ' + (response.data || 'Error al cargar horario')});
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar horario:', error);
                M.toast({html: 'Error al cargar horario'});
            }
        });
    });

    // Inicializar componentes
    initComponents();
    
    // Cargar tabla inicial
    loadStaffTable();

    function initScheduleForm() {
        // Inicializar timepickers
        var timepickers = document.querySelectorAll('.timepicker');
        M.Timepicker.init(timepickers, {
            twelveHour: false,
            i18n: {
                cancel: 'Cancelar',
                done: 'Aceptar'
            }
        });

        // Habilitar/deshabilitar campos según checkbox del día
        $('.day-enabled').off('change').on('change', function() {
            var $container = $(this).closest('.day-schedule');
            var enabled = $(this).prop('checked');
            
            $container.find('.start-time, .end-time').prop('disabled', !enabled);
            $container.find('.break-enabled').prop('disabled', !enabled);
            
            if (!enabled) {
                $container.find('.break-enabled').prop('checked', false).trigger('change');
            }
        });

        // Habilitar/deshabilitar campos de descanso
        $('.break-enabled').off('change').on('change', function() {
            var $container = $(this).closest('.day-schedule');
            var enabled = $(this).prop('checked');
            
            $container.find('.break-times').toggle(enabled);
            $container.find('.break-start, .break-end').prop('disabled', !enabled);
        });

        // Manejar envío del formulario
        $('#schedule-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'save_staff_schedule');
            formData.append('nonce', menphisStaff.nonce);

            // Debug - Ver qué datos se están enviando
            var formDataObj = {};
            formData.forEach((value, key) => {
                formDataObj[key] = value;
            });
            console.log('Enviando datos de horario:', formDataObj);

            $.ajax({
                url: menphisStaff.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Respuesta del servidor:', response);
                    if (response.success) {
                        M.toast({html: 'Horario guardado correctamente'});
                        var modal = M.Modal.getInstance($('#modal-schedule'));
                        modal.close();
                    } else {
                        M.toast({html: 'Error: ' + response.data});
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al guardar horario:', {xhr, status, error});
                    M.toast({html: 'Error al guardar horario'});
                }
            });
        });
    }
}); 