<?php if (!defined('ABSPATH')) exit; ?>

<!-- Obtener instancia de la clase de personal -->
global $menphis_reserva;
$staff = $menphis_reserva->staff;

// Las variables $services y $locations ya están disponibles desde el controlador
?>

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
                                <a class="btn waves-effect waves-light blue right modal-trigger" href="#modal-new-staff">
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
<div id="modal-new-staff" class="modal">
    <div class="modal-content">
        <h4>Nuevo Empleado</h4>
        <div class="row">
            <form class="col s12" id="staff-form">
                <input type="hidden" id="staff_id" name="staff_id" value="">
                <div class="row">
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">person</i>
                        <input id="staff_name" name="staff_name" type="text" class="validate" required>
                        <label for="staff_name">Nombre Completo</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">email</i>
                        <input id="staff_email" name="staff_email" type="email" class="validate" required>
                        <label for="staff_email">Email</label>
                    </div>
                </div>
                <div class="row">
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">phone</i>
                        <input id="staff_phone" name="staff_phone" type="tel" class="validate">
                        <label for="staff_phone">Teléfono</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">content_cut</i>
                        <select id="staff_services" name="staff_services[]" multiple class="select2" data-placeholder="Seleccionar servicios">
                            <option value="">Seleccionar servicios</option>
                            <?php
                            if (!empty($services)) {
                                foreach($services as $service) {
                                    echo '<option value="'.esc_attr($service->ID).'">'.esc_html($service->post_title).'</option>';
                                }
                            }
                            ?>
                        </select>
                        <label>Servicios</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">place</i>
                        <select id="staff_locations" name="staff_locations[]" multiple class="select2" data-placeholder="Seleccionar ubicaciones">
                            <option value="">Seleccionar ubicaciones</option>
                            <option value="all">Todas las ubicaciones</option>
                            <?php
                            if (!empty($locations)) {
                                foreach($locations as $location) {
                                    echo '<option value="'.esc_attr($location->id).'">'.esc_html($location->name).'</option>';
                                }
                            }
                            ?>
                        </select>
                        <label>Ubicaciones</label>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
        <button type="submit" form="staff-form" class="waves-effect waves-green btn blue">
            <i class="material-icons left">save</i>
            Guardar
        </button>
    </div>
</div>

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
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
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
</style>

<?php include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/schedule-form.php'; ?> 