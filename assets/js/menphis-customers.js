jQuery(document).ready(function($) {
    'use strict';

    console.log('Menphis Customers JS iniciando...');

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
                    console.log('Abriendo modal de cliente');
                }
            });
        }

        // Inicializar otros componentes
        M.updateTextFields();
        
        var textareas = document.querySelectorAll('.materialize-textarea');
        if (textareas.length > 0) {
            textareas.forEach(function(textarea) {
                M.textareaAutoResize($(textarea));
            });
        }
    }

    // Manejar el envío del formulario
    $('#customer-form').on('submit', function(e) {
        e.preventDefault();
        
        var customerId = $('#customer_id').val();
        var isEdit = customerId !== '';
        
        var formData = new FormData();
        formData.append('action', isEdit ? 'update_customer' : 'add_customer');
        formData.append('nonce', menphisCustomers.nonce);
        
        if (isEdit) {
            formData.append('id', customerId);
        }
        
        formData.append('first_name', $('#first_name').val());
        formData.append('last_name', $('#last_name').val());
        formData.append('email', $('#email').val());
        formData.append('phone', $('#phone').val());
        formData.append('notes', $('#notes').val());

        $.ajax({
            url: menphisCustomers.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Respuesta:', response);
                if (response.success) {
                    M.toast({html: response.data.message || 'Cliente guardado correctamente'});
                    var modal = M.Modal.getInstance($('#modal-new-customer'));
                    modal.close();
                    loadCustomersTable();
                    
                    // Limpiar formulario
                    $('#customer-form')[0].reset();
                    $('#customer_id').val('');
                    $('#modal-new-customer h4').text('Nuevo Cliente');
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

    // Función para cargar la tabla de clientes
    function loadCustomersTable() {
        $.ajax({
            url: menphisCustomers.ajax_url,
            type: 'POST',
            data: {
                action: 'get_customers_list',
                nonce: menphisCustomers.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateCustomersTable(response.data);
                } else {
                    M.toast({html: 'Error al cargar clientes: ' + response.data});
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar clientes:', error);
                M.toast({html: 'Error al cargar clientes'});
            }
        });
    }

    // Función para actualizar la tabla
    function updateCustomersTable(customersList) {
        var tbody = $('#customers-table');
        tbody.empty();

        if (!customersList || customersList.length === 0) {
            tbody.append('<tr><td colspan="7" class="center-align">No hay clientes registrados</td></tr>');
            return;
        }

        customersList.forEach(function(customer) {
            var row = $('<tr>');
            row.append('<td>' + customer.id + '</td>');
            row.append('<td>' + customer.name + '</td>');
            row.append('<td>' + customer.email + '</td>');
            row.append('<td>' + (customer.phone || '-') + '</td>');
            row.append('<td>' + (customer.notes || '-') + '</td>');
            row.append('<td>' + customer.created_at + '</td>');
            row.append('<td>' + customer.actions + '</td>');
            tbody.append(row);
        });
    }

    // Manejar clic en botón editar
    $(document).on('click', '.edit-customer', function(e) {
        e.preventDefault();
        var customerId = $(this).data('id');
        
        $.ajax({
            url: menphisCustomers.ajax_url,
            type: 'POST',
            data: {
                action: 'get_customer',
                nonce: menphisCustomers.nonce,
                id: customerId
            },
            success: function(response) {
                if (response.success) {
                    var customer = response.data;
                    
                    // Llenar el formulario con los datos
                    $('#customer_id').val(customer.id);
                    $('#first_name').val(customer.first_name);
                    $('#last_name').val(customer.last_name);
                    $('#email').val(customer.email);
                    $('#phone').val(customer.phone);
                    $('#notes').val(customer.notes);
                    
                    // Actualizar labels y textarea
                    M.updateTextFields();
                    M.textareaAutoResize($('#notes'));
                    
                    // Cambiar título del modal
                    $('#modal-new-customer h4').text('Editar Cliente');
                    
                    // Abrir modal
                    var modal = M.Modal.getInstance($('#modal-new-customer'));
                    modal.open();
                } else {
                    M.toast({html: 'Error: ' + (response.data || 'Error al cargar cliente')});
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar cliente:', error);
                M.toast({html: 'Error al cargar cliente'});
            }
        });
    });

    // Manejar clic en botón eliminar
    $(document).on('click', '.delete-customer', function(e) {
        e.preventDefault();
        var customerId = $(this).data('id');
        
        if (confirm('¿Estás seguro de que deseas eliminar este cliente?')) {
            $.ajax({
                url: menphisCustomers.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_customer',
                    nonce: menphisCustomers.nonce,
                    id: customerId
                },
                success: function(response) {
                    if (response.success) {
                        M.toast({html: 'Cliente eliminado correctamente'});
                        loadCustomersTable();
                    } else {
                        M.toast({html: 'Error: ' + (response.data || 'Error al eliminar cliente')});
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al eliminar cliente:', error);
                    M.toast({html: 'Error al eliminar cliente'});
                }
            });
        }
    });

    // Búsqueda en tiempo real
    var searchTimeout;
    $('#search_customers').on('keyup', function() {
        clearTimeout(searchTimeout);
        var searchTerm = $(this).val();
        
        searchTimeout = setTimeout(function() {
            filterCustomers(searchTerm);
        }, 300);
    });

    function filterCustomers(term) {
        term = term.toLowerCase();
        $('#customers-table tr').each(function() {
            var row = $(this);
            var text = row.text().toLowerCase();
            row.toggle(text.indexOf(term) > -1);
        });
    }

    // Limpiar formulario al abrir modal para nuevo cliente
    $(document).on('click', '[href="#modal-new-customer"]', function() {
        $('#customer-form')[0].reset();
        $('#customer_id').val('');
        $('#modal-new-customer h4').text('Nuevo Cliente');
        M.updateTextFields();
    });

    // Inicializar componentes
    initComponents();
    
    // Cargar tabla inicial
    loadCustomersTable();
}); 