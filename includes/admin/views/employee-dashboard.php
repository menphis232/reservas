<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap menphis-employee-dashboard">
    <?php if (current_user_can('administrator')): ?>
        <div class="notice notice-info">
            <p>
                <?php 
                if ($this->employee_id) {
                    $employee = get_userdata($this->employee_id);
                    printf(
                        __('Viendo reservas de: %s', 'menphis-reserva'),
                        esc_html($employee->display_name)
                    );
                } else {
                    _e('Viendo todas las reservas', 'menphis-reserva');
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <h1><?php _e('Mi Panel de Reservas', 'menphis-reserva'); ?></h1>

    <div class="employee-stats card">
        <div class="card-content">
            <h2><?php _e('Resumen', 'menphis-reserva'); ?></h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($pending_bookings); ?></span>
                    <span class="stat-label"><?php _e('Reservas Pendientes', 'menphis-reserva'); ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($today_bookings); ?></span>
                    <span class="stat-label"><?php _e('Reservas Hoy', 'menphis-reserva'); ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($week_bookings); ?></span>
                    <span class="stat-label"><?php _e('Reservas Esta Semana', 'menphis-reserva'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="employee-calendar card">
        <div class="card-content">
            <h2><?php _e('Mi Calendario', 'menphis-reserva'); ?></h2>
            <div id="employee-calendar"></div>
        </div>
    </div>

    <div class="employee-bookings card">
        <div class="card-content">
            <h2><?php _e('Mis Próximas Reservas', 'menphis-reserva'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Fecha', 'menphis-reserva'); ?></th>
                        <th><?php _e('Hora', 'menphis-reserva'); ?></th>
                        <th><?php _e('Cliente', 'menphis-reserva'); ?></th>
                        <th><?php _e('Servicio', 'menphis-reserva'); ?></th>
                        <th><?php _e('Estado', 'menphis-reserva'); ?></th>
                        <th><?php _e('Acciones', 'menphis-reserva'); ?></th>
                    </tr>
                </thead>
                <tbody id="employee-bookings-list">
                    <!-- Los datos se cargarán vía AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div> 