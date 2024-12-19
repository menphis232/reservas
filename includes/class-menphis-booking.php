<?php
class MenphisBooking {
    public function __construct() {
        add_shortcode('menphis_booking_form', array($this, 'render_booking_form'));
        add_action('wp_ajax_menphis_get_available_times', array($this, 'get_available_times'));
        add_action('wp_ajax_nopriv_menphis_get_available_times', array($this, 'get_available_times'));
        add_action('wp_ajax_menphis_create_booking', array($this, 'create_booking'));
        add_action('wp_ajax_nopriv_menphis_create_booking', array($this, 'create_booking'));
    }

    public function render_booking_form() {
        ob_start();
        ?>
        <div class="menphis-booking-form">
            <!-- Paso 1: Selección de servicio y productos -->
            <div class="step-1">
                <h3>Selecciona el servicio</h3>
                <?php $this->render_services_list(); ?>
            </div>

            <!-- Paso 2: Selección de fecha y hora -->
            <div class="step-2" style="display: none;">
                <h3>Selecciona fecha y hora</h3>
                <div id="menphis-calendar"></div>
                <div id="menphis-time-slots"></div>
            </div>

            <!-- Paso 3: Detalles del cliente -->
            <div class="step-3" style="display: none;">
                <h3>Tus datos</h3>
                <form id="menphis-customer-details">
                    <!-- Campos del cliente -->
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_services_list() {
        $services = get_posts(array(
            'post_type' => 'menphis_service',
            'posts_per_page' => -1
        ));

        foreach ($services as $service) {
            $products = get_post_meta($service->ID, '_menphis_service_products', true);
            ?>
            <div class="menphis-service-item">
                <h4><?php echo $service->post_title; ?></h4>
                <div class="service-products">
                    <?php $this->render_service_products($products); ?>
                </div>
            </div>
            <?php
        }
    }

    public function create_booking() {
        $location_id = intval($_POST['location_id']);
        $service_id = intval($_POST['service_id']);
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        
        // Obtener personal disponible
        $staff = new MenphisStaff();
        $available_staff = $staff->get_available_staff($location_id, $service_id, $date);
        
        if (empty($available_staff)) {
            wp_send_json_error('No hay personal disponible para esta fecha');
            return;
        }
        
        // Asignar al primer personal disponible
        // Aquí podrías implementar una lógica más compleja de asignación
        $assigned_staff = $available_staff[0];
        
        $booking_data = array(
            'service_id' => $service_id,
            'staff_id' => $assigned_staff->id,
            'customer_id' => get_current_user_id(),
            'location_id' => $location_id,
            'start_date' => $date . ' ' . $time,
            'end_date' => $this->calculate_end_time($date, $time, $service_id),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'menphis_bookings', $booking_data);
        
        do_action('menphis_booking_created', $wpdb->insert_id);
        
        wp_send_json_success();
    }
} 