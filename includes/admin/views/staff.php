<?php if (!defined('ABSPATH')) exit; ?>

<!-- Obtener instancia de la clase de personal -->
<?php
global $menphis_reserva;
$staff = $menphis_reserva->staff;

// Las variables $services y $locations ya están disponibles desde el controlador

// Debug logs
error_log('DEBUG - Servicios disponibles: ' . print_r($services, true));
error_log('DEBUG - Ubicaciones disponibles: ' . print_r($locations, true));
?>

<!-- Definir variables JavaScript -->
<script>
var menphisStaff = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('menphis_staff_nonce'); ?>'
};
</script>

<div class="menphis-admin">
    <div class="row">
        <div class="col s12">
            <div class="card-panel blue darken-2 white-text">
                <h4>Gestión de Personal</h4>
            </div>
        </div>
    </div>

    <div class="section">
        <!-- Filtros -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="row">
                            <div class="col s12">
                                <a class="btn waves-effect waves-light blue right modal-trigger" href="#employeeModal">
                                    <i class="material-icons left">person_add</i>
                                    Nuevo Empleado
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Personal -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Listado de Personal</span>
                        <table class="striped highlight centered responsive-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Servicios</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="staff-table">
                                <!-- Contenido dinámico -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Empleado -->
<div id="employeeModal" class="modal">
    <div class="modal-content">
        <h4 id="modalTitle">Nuevo Empleado</h4>
        <div class="row">
            <form class="col s12" id="employeeForm">
                <input type="hidden" id="employee_id" name="employee_id" value="">
                
                <!-- Campos de información básica -->
                <div class="row">
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">person</i>
                        <input id="employee_name" name="name" type="text" class="validate" required>
                        <label for="employee_name">Nombre Completo</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">email</i>
                        <input id="employee_email" name="email" type="email" class="validate" required>
                        <label for="employee_email">Email</label>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">phone</i>
                        <input id="employee_phone" name="phone" type="tel" class="validate">
                        <label for="employee_phone">Teléfono</label>
                    </div>
                </div>

                <!-- Servicios y Ubicaciones -->
                <div class="row">
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">content_cut</i>
                        <select id="employeeServices" name="services[]" multiple>
                            <option value="" disabled>Seleccionar servicios</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo esc_attr($service->ID); ?>">
                                    <?php echo esc_html($service->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Servicios</label>
                    </div>

                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">place</i>
                        <select id="employeeLocations" name="locations[]" multiple>
                            <option value="" disabled>Seleccionar ubicaciones</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo esc_attr($location->id); ?>">
                                    <?php echo esc_html($location->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Ubicaciones</label>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
        <button type="submit" form="employeeForm" class="waves-effect waves-green btn blue">
            <i class="material-icons left">save</i>
            Guardar
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar modal
    var modal = document.getElementById('employeeModal');
    var modalInstance = M.Modal.init(modal);
    
    // Inicializar selects solo una vez
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

    // Función para abrir modal de crear
    window.openCreateModal = function() {
        document.getElementById('modalTitle').textContent = 'Crear Empleado';
        document.getElementById('employeeForm').reset();
        // Reinicializar los selects después de resetear
        M.FormSelect.init(document.getElementById('employeeServices'));
        M.FormSelect.init(document.getElementById('employeeLocations'));
        modalInstance.open();
    };

    // Función para abrir modal de editar
    window.openEditModal = function(employeeData) {
        document.getElementById('modalTitle').textContent = 'Editar Empleado';
        // Rellenar datos del empleado
        // ...
        modalInstance.open();
    };
});
</script>

<!-- Actualizar el estilo de la modal -->
<style>
.modal {
    max-width: 66% !important;
    width: 95% !important;
    max-height: 95% !important;
    height: auto !important;
    border-radius: 8px;
    background-color: white;
}

.card {
    width: 100% !important;
    max-width: 100% !important;
}

.input-field {
    margin-top: 1rem;
    margin-bottom: 1rem;
}

.input-field .prefix {
    font-size: 1.5rem;
    line-height: 3rem;
}
.btn i {
    line-height: inherit;
}

.responsive-table {
    margin-bottom: 0;
}

/* Ajustes para Select2 */
.select2-container {
    width: 100% !important;
    margin-top: 0.5rem;
}

.select2-container--default .select2-selection--multiple {
    border: 1px solid #9e9e9e;
    border-radius: 4px;
}

.select-wrapper input.select-dropdown {
    margin-bottom: 0;
}

.modal .modal-footer {
    padding: 4px 24px;
}

.btn i {
    line-height: inherit;
}

.modal .select-wrapper {
    position: relative;
    z-index: 1000;
}

.modal .dropdown-content {
    z-index: 1001 !important;
}

.select-wrapper input.select-dropdown {
    position: relative;
    z-index: 1;
    background-color: transparent;
}
</style>

<?php include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/schedule-form.php'; ?> 