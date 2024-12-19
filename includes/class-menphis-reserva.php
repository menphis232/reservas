<?php

class Menphis_Reserva {
    public function __construct() {
        error_log('=== CONSTRUCTOR MENPHIS RESERVA ===');
        
        // Agregar scripts y estilos de WooCommerce
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Hooks para la pestaña y campos del producto
        add_action('woocommerce_product_data_tabs', array($this, 'add_booking_product_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_booking_product_data'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_custom_fields'));
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('wc-admin-meta-boxes');
    }

    public function add_booking_product_tab($tabs) {
        $tabs['booking'] = array(
            'label'    => __('Reserva', 'menphis-reserva'),
            'target'   => 'booking_product_data',
            'class'    => array(),
            'priority' => 21
        );
        return $tabs;
    }

    public function add_booking_product_data() {
        global $post;
        ?>
        <div id="booking_product_data" class="panel woocommerce_options_panel">
            <?php
            woocommerce_wp_checkbox(array(
                'id' => 'menphis_booking_product',
                'label' => '¿Es un servicio con reserva?',
                'description' => 'Marcar si este producto requiere reserva de horario'
            ));

            woocommerce_wp_text_input(array(
                'id' => 'menphis_booking_duration',
                'label' => 'Duración del Servicio (minutos)',
                'type' => 'number',
                'custom_attributes' => array(
                    'min' => '1',
                    'step' => '1'
                ),
                'description' => 'Duración del servicio en minutos'
            ));
            ?>
        </div>
        <?php
    }

    public function save_product_custom_fields($post_id) {
        $is_booking_product = isset($_POST['menphis_booking_product']) ? 'yes' : 'no';
        update_post_meta($post_id, 'menphis_booking_product', $is_booking_product);

        if (isset($_POST['menphis_booking_duration'])) {
            $booking_duration = intval($_POST['menphis_booking_duration']);
            update_post_meta($post_id, 'menphis_booking_duration', $booking_duration);
        }
    }
} 