jQuery(document).ready(function($) {
    'use strict';

    console.log('Menphis Locations JS iniciando...');

    // Inicializar componentes de Materialize
    function initComponents() {
        // Inicializar modales
        var modals = document.querySelectorAll('.modal');
        if (modals.length > 0) {
            M.Modal.init(modals, {
                dismissible: true,
                opacity: 0.5,
                inDuration: 250,
                outDuration: 250,
                onOpenStart: function() {
                    console.log('Abriendo modal de ubicación');
                }
            });
        }

        // Inicializar otros componentes
        var textareas = document.querySelectorAll('.materialize-textarea');
        if (textareas.length > 0) {
            textareas.forEach(function(textarea) {
                M.textareaAutoResize($(textarea));
            });
        }
    }

    // Manejar el formulario de ubicación
    $('#location-form').on('submit', function(e) {
        e.preventDefault();
        
        var locationId = $('#location_id').val();
        var isEdit = locationId !== '';
        
        var formData = new FormData();
        formData.append('action', isEdit ? 'update_location' : 'add_location');
        formData.append('nonce', menphisLocations.nonce);
        if (isEdit) {
            formData.append('id', locationId);
        }
        formData.append('name', $('#location_name').val());
        formData.append('address', $('#location_address').val());
        formData.append('phone', $('#location_phone').val());

        $.ajax({
            url: menphisLocations.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                if (response.success) {
                    M.toast({html: response.data.message || 'Ubicación guardada correctamente'});
                    var modal = M.Modal.getInstance($('#modal-new-location'));
                    modal.close();
                    loadLocations(); // Recargar la tabla en lugar de recargar la página
                } else {
                    console.error('Error en la respuesta:', response);
                    M.toast({html: 'Error: ' + (response.data || 'Error desconocido')});
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                M.toast({html: 'Error al guardar la ubicación: ' + error});
            }
        });
    });

    // Limpiar el formulario cuando se abre el modal para nueva ubicación
    $(document).on('click', '[href="#modal-new-location"]', function() {
        $('#location-form')[0].reset();
        $('#location_id').val('');
        M.updateTextFields();
    });

    // Función para cargar las ubicaciones
    function loadLocations() {
        $.ajax({
            url: menphisLocations.ajax_url,
            type: 'POST',
            data: {
                action: 'get_locations',
                nonce: menphisLocations.nonce
            },
            success: function(response) {
                console.log('Respuesta de ubicaciones:', response);
                if (response.success) {
                    updateLocationsTable(response.data.data);
                } else {
                    M.toast({html: 'Error: ' + (response.data || 'Error al cargar ubicaciones')});
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar ubicaciones:', {xhr, status, error});
                M.toast({html: 'Error al cargar ubicaciones: ' + error});
            }
        });
    }

    // Función para actualizar la tabla
    function updateLocationsTable(locations) {
        var tbody = $('#locations-table');
        tbody.empty();

        if (locations.length === 0) {
            tbody.append('<tr><td colspan="6" class="center-align">No hay ubicaciones registradas</td></tr>');
            return;
        }

        locations.forEach(function(location) {
            var row = $('<tr>');
            row.append('<td>' + location.id + '</td>');
            row.append('<td>' + location.name + '</td>');
            row.append('<td>' + (location.address || '-') + '</td>');
            row.append('<td>' + (location.phone || '-') + '</td>');
            row.append('<td>' + location.status + '</td>');
            row.append('<td>' + location.actions + '</td>');
            tbody.append(row);
        });
    }

    // Cargar ubicaciones al iniciar
    loadLocations();

    // Inicializar componentes
    initComponents();

    // Después de loadLocations()

    // Manejar clic en botón editar
    $(document).on('click', '.edit-location', function(e) {
        e.preventDefault();
        var locationId = $(this).data('id');
        
        $.ajax({
            url: menphisLocations.ajax_url,
            type: 'POST',
            data: {
                action: 'get_location',
                nonce: menphisLocations.nonce,
                id: locationId
            },
            success: function(response) {
                if (response.success) {
                    var location = response.data;
                    $('#location_id').val(location.id);
                    $('#location_name').val(location.name);
                    $('#location_address').val(location.address);
                    $('#location_phone').val(location.phone);
                    
                    // Actualizar labels de Materialize
                    M.updateTextFields();
                    
                    // Actualizar textarea si existe
                    var textarea = $('#location_address');
                    if (textarea.length) {
                        M.textareaAutoResize(textarea);
                    }
                    
                    // Abrir modal
                    var modal = M.Modal.getInstance($('#modal-new-location'));
                    modal.open();
                } else {
                    M.toast({html: 'Error: ' + (response.data || 'Error al cargar ubicación')});
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar ubicación:', error);
                M.toast({html: 'Error al cargar ubicación'});
            }
        });
    });

    // Manejar clic en botón eliminar
    $(document).on('click', '.delete-location', function(e) {
        e.preventDefault();
        var locationId = $(this).data('id');
        
        if (confirm('¿Estás seguro de que deseas eliminar esta ubicación?')) {
            $.ajax({
                url: menphisLocations.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_location',
                    nonce: menphisLocations.nonce,
                    id: locationId
                },
                success: function(response) {
                    if (response.success) {
                        M.toast({html: 'Ubicación eliminada correctamente'});
                        loadLocations(); // Recargar la tabla
                    } else {
                        M.toast({html: 'Error: ' + (response.data || 'Error al eliminar ubicación')});
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al eliminar ubicación:', error);
                    M.toast({html: 'Error al eliminar ubicación'});
                }
            });
        }
    });
}); 