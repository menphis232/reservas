<?php if (!defined('ABSPATH')) exit; ?>

<div class="menphis-my-bookings">
    <?php if ($atts['show_filters'] === 'yes'): ?>
        <div class="bookings-filters">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="date-filter"><?php _e('Filtrar por fecha', 'menphis-reserva'); ?></label>
                        <select id="date-filter" class="form-control">
                            <option value="all"><?php _e('Todas', 'menphis-reserva'); ?></option>
                            <option value="upcoming"><?php _e('Próximas', 'menphis-reserva'); ?></option>
                            <option value="past"><?php _e('Pasadas', 'menphis-reserva'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="status-filter"><?php _e('Estado', 'menphis-reserva'); ?></label>
                        <select id="status-filter" class="form-control">
                            <option value="all"><?php _e('Todos', 'menphis-reserva'); ?></option>
                            <option value="pending"><?php _e('Pendiente', 'menphis-reserva'); ?></option>
                            <option value="confirmed"><?php _e('Confirmada', 'menphis-reserva'); ?></option>
                            <option value="completed"><?php _e('Completada', 'menphis-reserva'); ?></option>
                            <option value="cancelled"><?php _e('Cancelada', 'menphis-reserva'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bookings-list">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><?php _e('Fecha', 'menphis-reserva'); ?></th>
                        <th><?php _e('Hora', 'menphis-reserva'); ?></th>
                        <th><?php _e('Servicio', 'menphis-reserva'); ?></th>
                        <th><?php _e('Ubicación', 'menphis-reserva'); ?></th>
                        <th><?php _e('Estado', 'menphis-reserva'); ?></th>
                        <th><?php _e('Acciones', 'menphis-reserva'); ?></th>
                    </tr>
                </thead>
                <tbody id="bookings-list-content">
                    <!-- Se llenará vía AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal" id="booking-details-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php _e('Detalles de la Reserva', 'menphis-reserva'); ?></h5>
                    <button type="button" class="close" onclick="closeModal()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Se llenará dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <?php _e('Volver', 'menphis-reserva'); ?>
                    </button>
                    <button type="button" class="btn btn-danger btn-cancel-booking" style="display: none;">
                        <?php _e('Cancelar Reserva', 'menphis-reserva'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div> 