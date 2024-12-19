<?php
if (!defined('ABSPATH')) exit;

class Menphis_Shortcodes {
    public function __construct() {
        add_shortcode('menphis_employee_bookings', array($this, 'render_employee_bookings'));
    }

    public function render_employee_bookings($atts) {
        // Verificar si el usuario está logueado y es empleado
        if (!is_user_logged_in() || !current_user_can('menphis_employee')) {
            return '<p>' . __('Debe iniciar sesión como empleado para ver sus reservas.', 'menphis-reserva') . '</p>';
        }

        // Procesar atributos
        $atts = shortcode_atts(array(
            'days' => 7,
            'view' => 'list' // list o calendar
        ), $atts);

        // Cargar scripts y estilos necesarios
        wp_enqueue_style('menphis-employee-bookings');
        wp_enqueue_script('menphis-employee-bookings');

        // Iniciar buffer de salida
        ob_start();

        // Incluir template según la vista seleccionada
        if ($atts['view'] === 'calendar') {
            include MENPHIS_RESERVA_PLUGIN_DIR . 'templates/employee-bookings-calendar.php';
        } else {
            include MENPHIS_RESERVA_PLUGIN_DIR . 'templates/employee-bookings-list.php';
        }

        return ob_get_clean();
    }
} 