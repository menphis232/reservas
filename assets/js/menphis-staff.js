jQuery(document).ready(function($) {
    'use strict';

    console.log('Menphis Staff JS iniciando...');

    // Función para inicializar los selects (mover fuera del DOMContentLoaded)
    function initializeSelects() {
        var serviceSelect = document.getElementById('employeeServices');
        var locationSelect = document.getElementById('employeeLocations');
        
        if (serviceSelect) {
            M.FormSelect.init(serviceSelect, {
                dropdownOptions: {
                    container: document.body
                }
            });
        }
        
        if (locationSelect) {
            M.FormSelect.init(locationSelect, {
                dropdownOptions: {
                    container: document.body
                }
            });
        }
    }

    // Inicializar componentes
    function initComponents() {
        // Inicializar modal
        var modal = document.getElementById('employeeModal');
        var modalInstance = M.Modal.init(modal, {
            dismissible: true,
            onOpenStart: function() {
                // Reinicializar los selects cuando se abre el modal
                initializeSelects();
            }
        });

        // Inicializar selects
        initializeSelects();

        // Manejar el botón de nuevo empleado
        document.querySelector('[href="#employeeModal"]').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('modalTitle').textContent = 'Nuevo Empleado';
            document.getElementById('employeeForm').reset();
            document.getElementById('employee_id').value = '';
            initializeSelects();
            modalInstance.open();
        });
    }

    // Manejar clic en botón editar
    $(document).on('click', '.edit-staff', function(e) {
        e.preventDefault();
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
                if (response.success) {
                    var staff = response.data;
                    
                    // Llenar el formulario con los datos
                    $('#employee_id').val(staff.id);
                    $('#employee_name').val(staff.name);
                    $('#employee_email').val(staff.email);
                    $('#employee_phone').val(staff.phone);
                    
                    // Actualizar selects múltiples
                    if (staff.services) {
                        $('#employeeServices').val(staff.services);
                    }
                    if (staff.locations) {
                        $('#employeeLocations').val(staff.locations);
                    }
                    
                    // Actualizar labels y reinicializar selects
                    M.updateTextFields();
                    initializeSelects();
                    
                    // Cambiar título del modal y abrirlo
                    $('#modalTitle').text('Editar Empleado');
                    var modal = M.Modal.getInstance(document.getElementById('employeeModal'));
                    if (modal) {
                        modal.open();
                    } else {
                        console.error('No se pudo obtener la instancia del modal');
                    }
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

    // Actualizar el manejador del formulario
    $('#employeeForm').on('submit', function(e) {
        e.preventDefault();
        
        var employeeId = $('#employee_id').val();
        var isEdit = employeeId !== '';
        
        var formData = new FormData(this);
        formData.append('action', isEdit ? 'update_staff' : 'add_staff');
        formData.append('nonce', menphisStaff.nonce);
        
        if (isEdit) {
            formData.append('id', employeeId);
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
                    var modal = M.Modal.getInstance(document.getElementById('employeeModal'));
                    modal.close();
                    loadStaffTable();
                    
                    // Limpiar formulario
                    $('#employeeForm')[0].reset();
                    $('#employee_id').val('');
                    $('#employeeServices').val(null);
                    $('#employeeLocations').val(null);
                    initializeSelects();
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

    // Inicializar componentes cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initComponents();
        loadStaffTable();
    });

    // Resto del código...
}); 