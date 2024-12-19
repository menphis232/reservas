jQuery(document).ready(function($) {
    // Inicializar componentes de Materialize
    M.AutoInit();

    // Manejar envío del formulario
    $('#employee-form').on('submit', function(e) {
        e.preventDefault();
        
        var employeeData = {
            id: $('#employee-id').val(),
            name: $('#employee-name').val(),
            email: $('#employee-email').val(),
            phone: $('#employee-phone').val(),
            status: $('#employee-status').val(),
            services: [],
            notes: $('#employee-notes').val()
        };

        // Recoger servicios seleccionados
        $('.service-checkbox:checked').each(function() {
            employeeData.services.push($(this).val());
        });

        $.ajax({
            url: menphisAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'menphis_save_employee',
                employee_data: employeeData,
                _nonce: menphisAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    M.toast({html: response.data.message});
                    $('#modal-employee').modal('close');
                    location.reload(); // Recargar para ver los cambios
                } else {
                    M.toast({html: 'Error: ' + response.data.message});
                }
            },
            error: function() {
                M.toast({html: 'Error al guardar el empleado'});
            }
        });
    });

    // Cargar datos del empleado al editar
    $('.edit-employee').on('click', function() {
        var employeeId = $(this).data('id');
        
        // Resetear formulario
        $('#employee-form')[0].reset();
        $('.service-checkbox').prop('checked', false);
        
        // Actualizar título del modal
        $('#employee-modal-title').text('Editar Empleado');
        
        $.ajax({
            url: menphisAjax.ajaxurl,
            data: {
                action: 'menphis_get_employee',
                employee_id: employeeId,
                _nonce: menphisAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    $('#employee-id').val(data.id);
                    $('#employee-name').val(data.name);
                    $('#employee-email').val(data.email);
                    $('#employee-phone').val(data.phone);
                    $('#employee-status').val(data.status);
                    $('#employee-notes').val(data.notes);
                    
                    // Marcar servicios
                    if (data.services) {
                        data.services.forEach(function(serviceId) {
                            $(`.service-checkbox[value="${serviceId}"]`).prop('checked', true);
                        });
                    }
                    
                    // Actualizar campos de Materialize
                    M.updateTextFields();
                    M.FormSelect.init(document.querySelector('#employee-status'));
                    M.textareaAutoResize($('#employee-notes'));
                    
                    // Abrir modal
                    $('#modal-employee').modal('open');
                }
            }
        });
    });

    // Nuevo empleado
    $('.add-employee').on('click', function() {
        $('#employee-form')[0].reset();
        $('#employee-id').val('');
        $('#employee-modal-title').text('Nuevo Empleado');
        $('.service-checkbox').prop('checked', false);
        
        M.updateTextFields();
        $('#modal-employee').modal('open');
    });
}); 