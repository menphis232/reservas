<?php if (!defined('ABSPATH')) exit; ?>
<div id="menphis-new-booking-modal" class="menphis-modal">
    <div class="menphis-modal-content">
        <div class="menphis-modal-header">
            <h2>Nueva cita</h2>
            <button class="menphis-modal-close">&times;</button>
        </div>
        <div class="menphis-modal-body">
            <form id="menphis-new-booking-form" class="menphis-booking-form">
                <!-- Proveedor -->
                <div class="menphis-form-group">
                    <label for="provider">Proveedor</label>
                    <select id="provider" name="provider" class="menphis-select">
                        <option value="">-- Selecciona un proveedor --</option>
                        <?php echo $this->get_providers_options(); ?>
                    </select>
                </div>

                <!-- Servicio -->
                <div class="menphis-form-group">
                    <label for="service">Servicio</label>
                    <select id="service" name="service" class="menphis-select">
                        <option value="">-- Selecciona un servicio --</option>
                        <?php echo $this->get_services_options(); ?>
                    </select>
                </div>

                <!-- Ubicación -->
                <div class="menphis-form-group">
                    <label for="location">Ubicación</label>
                    <select id="location" name="location" class="menphis-select">
                        <option value="">-- Selecciona una ubicación --</option>
                        <?php echo $this->get_locations_options(); ?>
                    </select>
                </div>

                <!-- Fecha y Hora -->
                <div class="menphis-form-row">
                    <div class="menphis-form-group">
                        <label for="date">Fecha</label>
                        <input type="text" id="date" name="date" class="menphis-datepicker">
                    </div>
                    <div class="menphis-form-group period-group">
                        <label>Periodo</label>
                        <div class="period-inputs">
                            <select id="start_time" name="start_time" class="menphis-select time-select">
                                <?php echo $this->get_time_options(); ?>
                            </select>
                            <span class="period-separator">a</span>
                            <select id="end_time" name="end_time" class="menphis-select time-select">
                                <?php echo $this->get_time_options(); ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Repetir cita -->
                <div class="menphis-form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="repeat_booking" name="repeat_booking">
                        Repetir esta cita
                    </label>
                </div>

                <!-- Clientes -->
                <div class="menphis-form-group">
                    <label for="customer">Clientes</label>
                    <div class="customer-search-group">
                        <input type="text" id="customer_search" class="menphis-input" placeholder="-- Buscar clientes --">
                        <button type="button" class="button button-primary new-customer-btn">
                            <i class="dashicons dashicons-plus-alt2"></i> Nuevo cliente
                        </button>
                    </div>
                </div>

                <!-- Nota interna -->
                <div class="menphis-form-group">
                    <label for="internal_note">Nota interna</label>
                    <textarea id="internal_note" name="internal_note" class="menphis-textarea" rows="4"></textarea>
                    <p class="field-description">Este texto se puede insertar en las notificaciones con el código {internal_note}</p>
                </div>

                <!-- Enviar notificaciones -->
                <div class="menphis-form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="send_notifications" name="send_notifications" checked>
                        Enviar notificaciones
                    </label>
                </div>
            </form>
        </div>
        <div class="menphis-modal-footer">
            <button type="button" class="button modal-close">Cerrar</button>
            <button type="button" class="button button-primary save-booking">Guardar</button>
        </div>
    </div>
</div> 