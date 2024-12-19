<?php if (!defined('ABSPATH')) exit; ?>

<div id="modal-booking" class="modal">
    <div class="modal-content">
        <h4>Nueva Reserva</h4>
        <form id="booking-form" autocomplete="off">
            <input type="hidden" id="booking-id">
            
            <!-- Cliente -->
            <div class="row">
                <div class="col s12">
                    <div class="input-field">
                        <select id="booking-client" required>
                            <option value="" disabled selected>Seleccionar cliente</option>
                            <?php
                            $clients = get_users(array('role' => 'customer'));
                            foreach ($clients as $client): ?>
                                <option value="<?php echo $client->ID; ?>">
                                    <?php echo esc_html($client->display_name); ?> 
                                    (<?php echo $client->user_email; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Cliente</label>
                    </div>
                </div>
            </div>

            <!-- Empleado y Fecha -->
            <div class="row">
                <div class="col s12 m6">
                    <div class="input-field">
                        <select id="booking-employee" required>
                            <option value="" disabled selected>Seleccionar empleado</option>
                            <?php
                            $employees = get_users(array('role' => 'menphis_employee'));
                            foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee->ID; ?>">
                                    <?php echo esc_html($employee->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Empleado</label>
                    </div>
                </div>
                <div class="col s12 m6">
                    <div class="input-field">
                        <input type="text" id="booking-date" class="datepicker" required>
                        <label for="booking-date">Fecha</label>
                    </div>
                </div>
            </div>

            <!-- Servicios -->
            <div class="row">
                <div class="col s12">
                    <div class="input-field">
                        <select id="booking-services" multiple="multiple" required>
                            <?php
                            $services = $this->bookings->get_services();
                            foreach ($services as $service): ?>
                                <option value="<?php echo $service->id; ?>" 
                                        data-duration="<?php echo $service->duration; ?>"
                                        data-price="<?php echo $service->price; ?>">
                                    <?php echo esc_html($service->name); ?> 
                                    (<?php echo $this->helpers->format_duration($service->duration); ?> - 
                                    <?php echo $this->helpers->format_price($service->price); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Servicios</label>
                    </div>
                </div>
            </div>

            <!-- Hora -->
            <div class="row">
                <div class="col s12">
                    <div class="input-field">
                        <select id="booking-time" required disabled>
                            <option value="" disabled selected>Seleccionar hora</option>
                        </select>
                        <label>Hora</label>
                        <span class="helper-text">Seleccione empleado, fecha y servicios para ver horarios disponibles</span>
                    </div>
                </div>
            </div>

            <!-- Resumen -->
            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Resumen de la reserva</span>
                            <div id="booking-summary">
                                <p>Duración total: <span id="total-duration">0 min</span></p>
                                <p>Precio total: <span id="total-price">€0.00</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notas -->
            <div class="row">
                <div class="col s12">
                    <div class="input-field">
                        <textarea id="booking-notes" class="materialize-textarea"></textarea>
                        <label for="booking-notes">Notas adicionales</label>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="modal-close waves-effect waves-light btn-flat">Cancelar</button>
        <button type="submit" form="booking-form" class="waves-effect waves-light btn green">
            <i class="material-icons left">save</i>Guardar
        </button>
    </div>
</div>

<style>
/* Estilos específicos para el formulario de reserva */
.modal .input-field {
    margin-top: 1rem;
    margin-bottom: 1rem;
}

.modal .card {
    margin: 0.5rem 0;
    box-shadow: none;
    border: 1px solid #e0e0e0;
}

.modal .card-content {
    padding: 12px;
}

.modal .card-title {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
}

#booking-summary p {
    margin: 5px 0;
    font-size: 1rem;
}

.helper-text {
    color: #9e9e9e;
    font-size: 0.8rem;
    margin-top: 5px;
}

/* Ajustes para Select2 */
.select2-container {
    width: 100% !important;
    margin-top: 0.5rem;
}

.select2-container .select2-selection--multiple {
    min-height: 45px;
}
</style> 