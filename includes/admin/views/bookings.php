<?php if (!defined('ABSPATH')) exit; ?>

<div class="menphis-admin">
    <div class="row">
        <div class="col s12">
            <div class="card-panel blue darken-2 white-text">
                <h4>Gestión de Reservas</h4>
            </div>
        </div>
    </div>

    <div class="section">
        <!-- Filtros -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Filtros de búsqueda</span>
                        <div class="row mb-0">
                            <div class="input-field col s12 m3">
                                <i class="material-icons prefix">search</i>
                                <input id="booking_id" type="text" class="validate">
                                <label for="booking_id">ID Reserva</label>
                            </div>
                            <div class="input-field col s12 m3">
                                <i class="material-icons prefix">date_range</i>
                                <input type="text" class="datepicker" id="date_from">
                                <label for="date_from">Fecha desde</label>
                            </div>
                            <div class="input-field col s12 m3">
                                <i class="material-icons prefix">date_range</i>
                                <input type="text" class="datepicker" id="date_to">
                                <label for="date_to">Fecha hasta</label>
                            </div>
                            <div class="input-field col s12 m3">
                                <i class="material-icons prefix">filter_list</i>
                                <select id="status">
                                    <option value="" selected>Todos los estados</option>
                                    <option value="pending">Pendiente</option>
                                    <option value="confirmed">Confirmada</option>
                                    <option value="completed">Completada</option>
                                    <option value="cancelled">Cancelada</option>
                                </select>
                                <label>Estado</label>
                            </div>
                        </div>
                        <div class="row" style="margin-bottom: 0;">
                            <div class="col s12">
                                <button class="btn waves-effect waves-light" type="button" id="apply_filters">
                                    <i class="material-icons left">search</i>
                                    Buscar
                                </button>
                                <button class="btn waves-effect waves-light red lighten-1" type="button" id="clear_filters">
                                    <i class="material-icons left">clear</i>
                                    Limpiar
                                </button>
                                <a class="btn waves-effect waves-light blue right modal-trigger" href="#modal-new-booking">
                                    <i class="material-icons left">add</i>
                                    Nueva Reserva
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Reservas -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Listado de Reservas</span>
                        <table class="striped highlight centered responsive-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Ubicación</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Empleado</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)) : ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No hay reservas registradas</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($bookings as $booking) : ?>
                                        <tr>
                                            <td><?php echo esc_html($booking->id); ?></td>
                                            <td><?php echo esc_html($booking->customer_name); ?></td>
                                            <td><?php echo esc_html($booking->location_name); ?></td>
                                            <td><?php echo esc_html(date('d/m/Y', strtotime($booking->booking_date))); ?></td>
                                            <td><?php echo esc_html(date('H:i', strtotime($booking->booking_time))); ?></td>
                                            <td><?php echo esc_html($booking->staff_name ?: 'Sin asignar'); ?></td>
                                            <td><?php echo esc_html($booking->status); ?></td>
                                            <td>
                                                <?php echo $this->get_booking_actions_html($booking->id); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Reserva -->
<div id="modal-new-booking" class="modal">
    <div class="modal-content">
        <h4>Nueva Reserva</h4>
        <div class="row">
            <form class="col s12" id="booking-form" onsubmit="return false;">
                <input type="hidden" id="booking_id" name="booking_id" value="">
                
                <div class="row">
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">person</i>
                        <select id="customer" name="customer" class="select2" required>
                            <option value="" disabled selected>Seleccionar cliente</option>
                            <?php foreach($customers as $customer): ?>
                                <option value="<?php echo esc_attr($customer->id); ?>">
                                    <?php echo esc_html($customer->first_name . ' ' . $customer->last_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Cliente</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">location_on</i>
                        <select id="location" name="location" class="select2" required>
                            <option value="" disabled selected>Seleccionar ubicación</option>
                            <?php if (!empty($locations)): ?>
                                <?php foreach($locations as $location): ?>
                                    <option value="<?php echo esc_attr($location->id); ?>">
                                        <?php echo esc_html($location->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <label>Ubicación</label>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">content_cut</i>
                        <select id="services" name="services[]" multiple class="select2" required>
                            <?php foreach($services as $service): ?>
                                <option value="<?php echo esc_attr($service->ID); ?>">
                                    <?php echo esc_html($service->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Servicios</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">person</i>
                        <select id="staff" name="staff" class="select2">
                            <option value="" selected>Asignar automáticamente</option>
                            <?php foreach($staff_members as $staff): ?>
                                <option value="<?php echo esc_attr($staff->id); ?>">
                                    <?php echo esc_html($staff->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Empleado (opcional)</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">event</i>
                        <input id="booking_date" name="booking_date" type="text" class="datepicker" required>
                        <label for="booking_date">Fecha</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">access_time</i>
                        <input id="booking_time" name="booking_time" type="text" class="timepicker" required>
                        <label for="booking_time">Hora</label>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field col s12">
                        <i class="material-icons prefix">note</i>
                        <textarea id="notes" name="notes" class="materialize-textarea"></textarea>
                        <label for="notes">Notas</label>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
        <button type="submit" form="booking-form" class="waves-effect waves-green btn blue">
            <i class="material-icons left">save</i>
            Guardar
        </button>
    </div>
</div>

<!-- Modal de Detalles -->
<div id="booking-details-modal" class="modal">
    <div class="modal-content">
        <div id="booking-details"></div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cerrar</a>
    </div>
</div>

<!-- Modal para ver detalles de reserva -->
<div id="modal-view-booking" class="modal">
    <div class="modal-content">
        <!-- El contenido se insertará dinámicamente -->
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cerrar</a>
    </div>
</div>

<style>
.modal {
    max-width: 66% !important;
    width: 95% !important;
    max-height: 95% !important;
    height: auto !important;
    border-radius: 8px;
    background-color: white;
}
.modal .modal-content {
    padding: 24px;
}
.booking-detail-item {
    margin-bottom: 12px;
}
.booking-detail-item i {
    vertical-align: middle;
    margin-right: 8px;
    color: #0d6efd;
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

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 8px;
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
?> 