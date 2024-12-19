<?php
if (!defined('ABSPATH')) exit;
?>

<div class="booking-step-3">
    <h3><?php _e('Finalizar Reserva', 'menphis-reserva'); ?></h3>

    <?php if (!is_user_logged_in()): ?>
        <div class="user-registration-form">
            <div class="form-group">
                <h4><?php _e('Información de Usuario', 'menphis-reserva'); ?></h4>
                <p class="description">
                    <?php _e('Para completar tu reserva, necesitamos crear una cuenta.', 'menphis-reserva'); ?>
                </p>
            </div>

            <div class="form-group">
                <label for="booking_email"><?php _e('Email *', 'menphis-reserva'); ?></label>
                <input type="email" id="booking_email" name="booking_email" required 
                       class="form-control" placeholder="tu@email.com">
            </div>

            <div class="form-group">
                <label for="booking_password"><?php _e('Contraseña *', 'menphis-reserva'); ?></label>
                <input type="password" id="booking_password" name="booking_password" 
                       required class="form-control">
            </div>

            <div class="form-group">
                <label for="booking_password_confirm">
                    <?php _e('Confirmar Contraseña *', 'menphis-reserva'); ?>
                </label>
                <input type="password" id="booking_password_confirm" 
                       name="booking_password_confirm" required class="form-control">
            </div>
        </div>
    <?php endif; ?>

    <!-- Resto del formulario de reserva -->
    <div class="booking-summary">
        <!-- Resumen de la reserva -->
    </div>

    <div class="booking-actions">
        <button type="button" class="btn btn-secondary" data-step="2">
            <?php _e('Anterior', 'menphis-reserva'); ?>
        </button>
        <button type="button" class="btn btn-primary" id="complete-booking">
            <?php _e('Completar Reserva', 'menphis-reserva'); ?>
        </button>
    </div>
</div>