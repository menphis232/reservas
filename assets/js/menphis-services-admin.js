jQuery(document).ready(function($) {
    // Inicializar componentes de Materialize
    M.AutoInit();

    // Manejar envío del formulario de servicio
    $('#service-form').on('submit', function(e) {
        e.preventDefault();
        
        var serviceData = {
            id: $('#service-id').val(),
            name: $('#service-name').val(),
            description: $('#service-description').val(),
            duration: $('#service-duration').val(),
            price: $('#service-price').val(),
            category_id: $('#service-category').val(),
            active: $('#service-active').prop('checked') ? 1 : 0
        };

        $.ajax({
            url: menphisAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'menphis_save_service',
                service_data: serviceData,
                _nonce: menphisAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    M.toast({html: response.data.message});
                    $('#modal-service').modal('close');
                    location.reload();
                } else {
                    M.toast({html: 'Error: ' + response.data.message});
                }
            }
        });
    });

    // Cargar datos del servicio al editar
    $('.edit-service').on('click', function() {
        var serviceId = $(this).data('id');
        
        $('#service-form')[0].reset();
        $('#service-modal-title').text('Editar Servicio');
        
        $.ajax({
            url: menphisAjax.ajaxurl,
            data: {
                action: 'menphis_get_service',
                service_id: serviceId,
                _nonce: menphisAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    $('#service-id').val(data.id);
                    $('#service-name').val(data.name);
                    $('#service-description').val(data.description);
                    $('#service-duration').val(data.duration);
                    $('#service-price').val(data.price);
                    $('#service-category').val(data.category_id);
                    $('#service-active').prop('checked', data.active == 1);
                    
                    M.updateTextFields();
                    M.FormSelect.init(document.querySelector('#service-category'));
                    M.textareaAutoResize($('#service-description'));
                    
                    $('#modal-service').modal('open');
                }
            }
        });
    });

    // Eliminar servicio
    $('.delete-service').on('click', function() {
        if (!confirm('¿Estás seguro de que deseas eliminar este servicio?')) {
            return;
        }

        var serviceId = $(this).data('id');
        
        $.ajax({
            url: menphisAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'menphis_delete_service',
                service_id: serviceId,
                _nonce: menphisAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    M.toast({html: response.data.message});
                    location.reload();
                } else {
                    M.toast({html: 'Error: ' + response.data.message});
                }
            }
        });
    });

    // Manejar categorías
    $('#category-form').on('submit', function(e) {
        e.preventDefault();
        
        var categoryName = $('#category-name').val();
        
        $.ajax({
            url: menphisAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'menphis_save_category',
                name: categoryName,
                _nonce: menphisAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    M.toast({html: response.data.message});
                    location.reload();
                } else {
                    M.toast({html: 'Error: ' + response.data.message});
                }
            }
        });
    });

    // Editar categoría inline
    $('.edit-category').on('click', function() {
        var $item = $(this).closest('.collection-item');
        var $name = $item.find('.category-name');
        var currentName = $name.text();
        
        $name.html(`
            <input type="text" class="category-edit-input" value="${currentName}">
            <button class="btn-small waves-effect waves-light green save-category-edit">
                <i class="material-icons">check</i>
            </button>
            <button class="btn-small waves-effect waves-light red cancel-category-edit">
                <i class="material-icons">close</i>
            </button>
        `);
    });

    $(document).on('click', '.save-category-edit', function() {
        var $item = $(this).closest('.collection-item');
        var categoryId = $item.data('id');
        var newName = $item.find('.category-edit-input').val();
        
        $.ajax({
            url: menphisAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'menphis_update_category',
                category_id: categoryId,
                name: newName,
                _nonce: menphisAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $item.find('.category-name').text(newName);
                    M.toast({html: response.data.message});
                } else {
                    M.toast({html: 'Error: ' + response.data.message});
                }
            }
        });
    });

    $(document).on('click', '.cancel-category-edit', function() {
        var $item = $(this).closest('.collection-item');
        var currentName = $item.find('.category-edit-input').val();
        $item.find('.category-name').text(currentName);
    });
}); 