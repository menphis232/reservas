<?php
if (!defined('ABSPATH')) exit;

class Menphis_Shortcodes {
    public function __construct() {
        add_shortcode('menphis_employee_bookings', array($this, 'render_employee_bookings'));
        add_shortcode('menphis_my_bookings', array($this, 'render_my_bookings'));

        // Agregar scripts necesarios para el modal
        add_action('wp_enqueue_scripts', array($this, 'enqueue_modal_scripts'));
    }

    public function enqueue_modal_scripts() {
        // Bootstrap JS (si no está ya incluido en tu tema)
        wp_enqueue_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '4.6.0',
            true
        );

        // Bootstrap CSS (si no está ya incluido en tu tema)
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css'
        );
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

    public function render_my_bookings($atts) {
        // Verificar si el usuario está logueado
        if (!is_user_logged_in()) {
            return '<p class="alert alert-warning">' . 
                   __('Debe iniciar sesión para ver sus reservas.', 'menphis-reserva') . 
                   '</p>';
        }

        // Procesar atributos
        $atts = shortcode_atts(array(
            'limit' => 10,
            'show_filters' => 'yes',
            'view' => 'list' // list o calendar
        ), $atts);

        // Cargar scripts y estilos necesarios
        wp_enqueue_style('menphis-bookings-style', MENPHIS_RESERVA_PLUGIN_URL . 'assets/css/bookings-list.css');
        wp_enqueue_script('menphis-bookings-script', MENPHIS_RESERVA_PLUGIN_URL . 'assets/js/bookings-list.js', array('jquery'), null, true);
        
        // Pasar datos al JavaScript
        wp_localize_script('menphis-bookings-script', 'menphisBookings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('menphis_bookings_nonce'),
            'messages' => array(
                'confirm_cancel' => __('¿Está seguro de que desea cancelar esta reserva?', 'menphis-reserva'),
                'error_loading' => __('Error al cargar los detalles', 'menphis-reserva'),
                'error_connection' => __('Error de conexión', 'menphis-reserva')
            )
        ));

        // Iniciar buffer de salida
        ob_start();

        // Incluir template
        include MENPHIS_RESERVA_PLUGIN_DIR . 'templates/my-bookings.php';

        return ob_get_clean();
    }
} 