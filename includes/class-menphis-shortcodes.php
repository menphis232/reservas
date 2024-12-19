<?php
if (!defined('ABSPATH')) exit;

class Menphis_Shortcodes {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        // Registrar shortcodes
        add_shortcode('menphis_booking_form', array($this, 'render_booking_form'));
        
        // Registrar endpoints AJAX
        add_action('wp_ajax_get_available_timeslots', array($this, 'ajax_get_available_timeslots'));
        add_action('wp_ajax_load_booking_step', array($this, 'ajax_load_booking_step'));
        add_action('wp_ajax_save_booking_to_cart', array($this, 'ajax_save_booking_to_cart'));
        
        // Hooks de WooCommerce
        add_action('woocommerce_checkout_create_order', array($this, 'save_booking_data_to_order'), 10, 2);
        add_action('woocommerce_thankyou', array($this, 'display_booking_confirmation'), 10);
    }

    public function render_booking_form($atts) {
        try {
            // Verificar si WooCommerce está activo
            if (!class_exists('WooCommerce')) {
                error_log('Menphis Reserva: WooCommerce no está activo');
                return '<div class="menphis-booking-error">WooCommerce debe estar instalado y activado.</div>';
            }

            // Verificar si WC está inicializado
            if (!function_exists('WC')) {
                error_log('Menphis Reserva: WC() no está disponible');
                return '<div class="menphis-booking-error">Error al inicializar WooCommerce.</div>';
            }

            // Verificar el carrito
            if (!isset(WC()->cart)) {
                error_log('Menphis Reserva: WC()->cart no está disponible');
                return '<div class="menphis-booking-error">Error al acceder al carrito.</div>';
            }

            // Verificar si hay productos en el carrito
            if (WC()->cart->is_empty()) {
                return '<div class="menphis-booking-error">
                    <p>Debe agregar servicios al carrito antes de hacer una reserva.</p>
                    <a href="' . esc_url(get_permalink(wc_get_page_id('shop'))) . '" class="button">Ver servicios</a>
                </div>';
            }

            // Obtener ubicaciones
            $locations = $this->get_locations();
            error_log('Menphis Reserva: Ubicaciones encontradas - ' . print_r($locations, true));

            // Verificar si estamos en el editor de Elementor
            if (isset($_GET['elementor-preview'])) {
                return '<div class="menphis-booking-preview">
                    <h4>Formulario de Reserva</h4>
                    <p>Este es un marcador de posición para el formulario de reserva. El formulario real aparecerá en el frontend.</p>
                </div>';
            }

            // Iniciar buffer de salida
            ob_start();

            // Incluir CSS y JS necesarios
            wp_enqueue_style('materialize-css', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css', array(), '1.0.0');
            wp_enqueue_style('material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons', array(), null);
            wp_enqueue_style('menphis-booking-style', MENPHIS_RESERVA_PLUGIN_URL . 'assets/css/booking-form.css', array(), MENPHIS_RESERVA_VERSION);
            
            wp_enqueue_script('materialize-js', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js', array('jquery'), '1.0.0', true);
            wp_enqueue_script('menphis-booking-script', MENPHIS_RESERVA_PLUGIN_URL . 'assets/js/booking-form.js', array('jquery', 'materialize-js'), MENPHIS_RESERVA_VERSION, true);

            // Pasar variables a JavaScript
            wp_localize_script('menphis-booking-script', 'menphisBooking', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('menphis_booking_nonce'),
                'debug' => WP_DEBUG
            ));

            // Renderizar el formulario
            ?>
            <div class="menphis-booking-container">
                <div class="menphis-booking-form">
                    <div class="row">
                        <div class="col s12">
                            <h4 class="center-align">Reserva tu cita</h4>
                            
                            <!-- Stepper -->
                            <ul class="stepper horizontal">
                                <li class="step active">
                                    <div class="step-title waves-effect">Servicios</div>
                                </li>
                                <li class="step">
                                    <div class="step-title waves-effect">Fecha y Hora</div>
                                </li>
                                <li class="step">
                                    <div class="step-title waves-effect">Confirmación</div>
                                </li>
                            </ul>

                            <!-- Contenido del paso 1 -->
                            <div class="step-content">
                                <!-- Servicios seleccionados -->
                                <div class="section">
                                    <h5>Servicios seleccionados</h5>
                                    <div class="collection">
                                        <?php 
                                        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                                            $product = $cart_item['data'];
                                            if ($product) {
                                                $duration = get_post_meta($product->get_id(), '_service_duration', true);
                                                ?>
                                                <div class="collection-item">
                                                    <div class="row mb-0">
                                                        <div class="col s12 m6">
                                                            <span class="title"><?php echo esc_html($product->get_name()); ?></span>
                                                            <?php if ($duration): ?>
                                                            <p class="grey-text">
                                                                <i class="material-icons tiny">schedule</i> 
                                                                <?php echo esc_html($duration); ?> min
                                                            </p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col s12 m3">
                                                            <span class="grey-text">
                                                                Cantidad: <?php echo esc_html($cart_item['quantity']); ?>
                                                            </span>
                                                        </div>
                                                        <div class="col s12 m3 right-align">
                                                            <span class="price">
                                                                <?php echo wc_price($cart_item['line_total']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>

                                <!-- Selección de ubicación -->
                                <div class="section">
                                    <h5>Ubicación</h5>
                                    <div class="input-field">
                                        <select id="booking_location" name="location" required>
                                            <option value="" disabled selected>Seleccionar ubicación</option>
                                            <?php foreach ($locations as $location): ?>
                                                <option value="<?php echo esc_attr($location->id); ?>">
                                                    <?php echo esc_html($location->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label>Seleccione una ubicación</label>
                                    </div>
                                </div>

                                <!-- Botones de navegación -->
                                <div class="section">
                                    <div class="row">
                                        <div class="col s12 right-align">
                                            <button class="btn waves-effect waves-light next-step" id="goto-step-2">
                                                Siguiente <i class="material-icons right">arrow_forward</i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php

            return ob_get_clean();

        } catch (Exception $e) {
            error_log('Error en Menphis Reserva shortcode: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return '<div class="menphis-booking-error">Ha ocurrido un error al cargar el formulario de reserva.</div>';
        }
    }

    private function get_locations() {
        try {
            $log_file = WP_CONTENT_DIR . '/menphis-debug.log';
            $log = function($message) use ($log_file) {
                file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
            };

            // Verificar si la tabla existe
            $table_name = $this->db->prefix . 'menphis_locations';
            $table_exists = $this->db->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            $log("Verificando tabla de ubicaciones...");
            $log("Tabla: " . $table_name);
            $log("¿Existe tabla?: " . ($table_exists ? 'Sí' : 'No'));
            
            if (!$table_exists) {
                $log("ERROR: La tabla de ubicaciones no existe");
                return array();
            }

            // Obtener ubicaciones
            $query = "SELECT * FROM {$this->db->prefix}menphis_locations WHERE status = 'active'";
            $log("Query: " . $query);
            
            $locations = $this->db->get_results($query);
            
            $log("Ubicaciones encontradas: " . ($locations ? count($locations) : 0));
            if ($locations) {
                foreach ($locations as $location) {
                    $log("- ID: {$location->id}, Nombre: {$location->name}, Estado: {$location->status}");
                }
            }
            
            return $locations ?: array();

        } catch (Exception $e) {
            file_put_contents(
                WP_CONTENT_DIR . '/menphis-debug.log',
                date('[Y-m-d H:i:s] ') . "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n",
                FILE_APPEND
            );
            return array();
        }
    }

    // Método para obtener horarios disponibles
    public function ajax_get_available_timeslots() {
        try {
            check_ajax_referer('menphis_booking_nonce', 'nonce');
            
            $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
            $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
            
            if (!$location_id || !$date) {
                wp_send_json_error('Datos incompletos');
                return;
            }

            $timeslots = $this->get_available_timeslots($location_id, $date);
            wp_send_json_success($timeslots);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    // Método para cargar los pasos del formulario
    public function ajax_load_booking_step() {
        try {
            check_ajax_referer('menphis_booking_nonce', 'nonce');
            
            $step = isset($_POST['step']) ? intval($_POST['step']) : 1;
            $template = '';
            
            switch ($step) {
                case 1:
                    ob_start();
                    include MENPHIS_RESERVA_PLUGIN_DIR . 'templates/booking-step-1.php';
                    $template = ob_get_clean();
                    break;
                    
                case 2:
                    ob_start();
                    include MENPHIS_RESERVA_PLUGIN_DIR . 'templates/booking-step-2.php';
                    $template = ob_get_clean();
                    break;
                    
                case 3:
                    $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
                    $booking_date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
                    $booking_time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';

                    error_log("=== INICIO ASIGNACIÓN DE STAFF ===");
                    error_log("Location: $location_id, Date: $booking_date, Time: $booking_time");

                    // Obtener staff disponible
                    $available_staff = $this->get_available_staff($location_id, $booking_date, $booking_time);
                    
                    if (empty($available_staff)) {
                        error_log("No se encontró staff disponible");
                        wp_send_json_error('No hay personal disponible para el horario seleccionado');
                        return;
                    }

                    // Asignar el staff menos ocupado
                    $assigned_staff = $this->get_least_busy_staff($available_staff, $booking_date);
                    
                    if (!$assigned_staff) {
                        error_log("No se pudo asignar un staff");
                        wp_send_json_error('No se pudo asignar un empleado');
                        return;
                    }

                    // Guardar en sesión
                    WC()->session->set('booking_staff_id', $assigned_staff->id);
                    WC()->session->set('booking_location_id', $location_id);
                    WC()->session->set('booking_date', $booking_date);
                    WC()->session->set('booking_time', $booking_time);

                    error_log("Staff asignado ID: " . $assigned_staff->id);
                    error_log("Datos guardados en sesión: " . print_r(array(
                        'staff_id' => WC()->session->get('booking_staff_id'),
                        'location_id' => WC()->session->get('booking_location_id'),
                        'date' => WC()->session->get('booking_date'),
                        'time' => WC()->session->get('booking_time')
                    ), true));

                    $formatted_date = date('d/m/Y', strtotime($booking_date));
                    $formatted_time = date('H:i', strtotime($booking_time));
                    
                    ob_start();
                    include MENPHIS_RESERVA_PLUGIN_DIR . 'templates/booking-step-3.php';
                    $template = ob_get_clean();
                    break;
            }

            wp_send_json_success($template);

        } catch (Exception $e) {
            error_log('Error in ajax_load_booking_step: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    private function get_available_staff($location_id, $date, $time) {
        global $wpdb;
        
        error_log("Buscando staff para - Location: $location_id, Date: $date, Time: $time");
        
        // Obtener la duración del servicio desde el carrito
        $cart_items = WC()->cart->get_cart();
        $booking_duration = 0;
        
        foreach ($cart_items as $item) {
            $product_id = $item['product_id'];
            $is_booking_product = get_post_meta($product_id, '_menphis_booking_product', true);
            
            if ($is_booking_product === 'yes') {
                $duration = intval(get_post_meta($product_id, '_menphis_booking_duration', true));
                $booking_duration = max($booking_duration, $duration);
            }
        }
        
        error_log("Duración de la reserva: " . $booking_duration . " minutos");
        
        // Calcular la hora de fin basada en la duración
        $end_time = date('H:i:s', strtotime($time) + $booking_duration * 60);
        
        $staff = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT s.id
            FROM {$wpdb->prefix}menphis_staff s
            INNER JOIN {$wpdb->prefix}menphis_staff_locations sl ON s.id = sl.staff_id
            INNER JOIN {$wpdb->prefix}menphis_staff_schedules ss ON s.id = ss.staff_id
            WHERE sl.location_id = %d 
            AND ss.day_of_week = %d
            AND %s BETWEEN ss.start_time AND ss.end_time
            AND %s <= ss.end_time
            AND s.status = 'active'
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->prefix}menphis_bookings b
                WHERE b.staff_id = s.id
                AND b.booking_date = %s
                AND (
                    (b.booking_time <= %s AND DATE_ADD(b.booking_time, INTERVAL b.duration MINUTE) > %s)
                    OR 
                    (b.booking_time >= %s AND b.booking_time < %s)
                )
            )",
            $location_id,
            date('w', strtotime($date)),
            $time,
            $end_time,
            $date,
            $time,
            $time,
            $time,
            $end_time
        ));
        
        error_log("SQL Query: " . $wpdb->last_query);
        error_log("Staff encontrado: " . print_r($staff, true));
        
        return $staff;
    }

    private function get_least_busy_staff($available_staff, $date) {
        global $wpdb;
        
        $least_busy_staff = null;
        $min_bookings = PHP_INT_MAX;
        
        foreach ($available_staff as $staff) {
            // Contar reservas para este staff en la fecha dada
            $bookings_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) 
                FROM {$wpdb->prefix}menphis_bookings 
                WHERE staff_id = %d 
                AND booking_date = %s",
                $staff->id,
                $date
            ));
            
            if ($bookings_count < $min_bookings) {
                $min_bookings = $bookings_count;
                $least_busy_staff = $staff->id;
            }
        }
        
        return $least_busy_staff;
    }

    // Método para guardar datos de reserva en el carrito
    public function ajax_save_booking_to_cart() {
        try {
            check_ajax_referer('menphis_booking_nonce', 'nonce');
            
            $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
            $booking_date = isset($_POST['booking_date']) ? sanitize_text_field($_POST['booking_date']) : '';
            $booking_time = isset($_POST['booking_time']) ? sanitize_text_field($_POST['booking_time']) : '';
            
            error_log("=== INICIO SAVE_BOOKING_TO_CART ===");
            error_log("Datos recibidos: " . print_r($_POST, true));
            
            if (!$location_id || !$booking_date || !$booking_time) {
                wp_send_json_error('Datos incompletos');
                return;
            }

            // Obtener y asignar staff aquí
            $available_staff = $this->get_available_staff($location_id, $booking_date, $booking_time);
            error_log("Staff disponible: " . print_r($available_staff, true));
            
            if (!empty($available_staff)) {
                $assigned_staff = $this->get_least_busy_staff($available_staff, $booking_date);
                if ($assigned_staff) {
                    // Guardar el staff_id en la sesión
                    WC()->session->set('booking_staff_id', $assigned_staff->id);
                    error_log("Staff asignado ID: " . $assigned_staff->id);
                }
            }

            // Guardar datos en la sesión del carrito
            WC()->session->set('booking_location_id', $location_id);
            WC()->session->set('booking_date', $booking_date);
            WC()->session->set('booking_time', $booking_time);
            
            error_log("Datos guardados en sesión: " . print_r(array(
                'staff_id' => WC()->session->get('booking_staff_id'),
                'location_id' => $location_id,
                'date' => $booking_date,
                'time' => $booking_time
            ), true));
            
            wp_send_json_success(array(
                'checkout_url' => wc_get_checkout_url()
            ));

        } catch (Exception $e) {
            error_log("Error en save_booking_to_cart: " . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    // Método para guardar datos de reserva en la orden
    public function save_booking_data_to_order($order, $data) {
        $location_id = WC()->session->get('booking_location_id');
        $booking_date = WC()->session->get('booking_date');
        $booking_time = WC()->session->get('booking_time');
        
        if ($location_id && $booking_date && $booking_time) {
            $order->update_meta_data('_booking_location_id', $location_id);
            $order->update_meta_data('_booking_date', $booking_date);
            $order->update_meta_data('_booking_time', $booking_time);
            
            // Guardar también los datos formateados para mostrar
            $order->update_meta_data('_booking_date_formatted', date('d/m/Y', strtotime($booking_date)));
            $order->update_meta_data('_booking_time_formatted', date('H:i', strtotime($booking_time)));
            
            // Obtener y guardar el nombre de la ubicación
            $location = $this->get_location_details($location_id);
            if ($location) {
                $order->update_meta_data('_booking_location_name', $location->name);
            }
        }
    }

    // Mostrar confirmación de la reserva en la página de agradecimiento
    public function display_booking_confirmation($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $booking_id = $order->get_meta('_booking_id');
        if (!$booking_id) return;

        global $wpdb;
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}menphis_bookings WHERE id = %d",
            $booking_id
        ));

        if (!$booking) return;

        $location_id = $booking->location_id;
        $booking_date = date('d/m/Y', strtotime($booking->booking_date));
        $booking_time = date('H:i', strtotime($booking->booking_time));

        $location = $this->get_location_details($location_id);
        $location_name = $location ? $location->name : '';

        ?>
        <div class="booking-confirmation">
            <h2>Detalles de tu reserva</h2>
            <div class="booking-details">
                <p><strong>Número de reserva:</strong> #<?php echo esc_html($booking_id); ?></p>
                <p><strong>Ubicación:</strong> <?php echo esc_html($location_name); ?></p>
                <p><strong>Fecha:</strong> <?php echo esc_html($booking_date); ?></p>
                <p><strong>Hora:</strong> <?php echo esc_html($booking_time); ?></p>
            </div>
            <p class="booking-thank-you">¡Gracias por tu reserva! Te esperamos.</p>
        </div>
        <?php
    }

    private function get_location_details($location_id) {
        return $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->db->prefix}menphis_locations WHERE id = %d",
            $location_id
        ));
    }

    private function get_available_staff_for_services($services, $location_id, $booking_date, $booking_time) {
        // Implementa este método para obtener el empleado disponible
        // basado en los servicios y la ubicación
        // Devuelve el ID del empleado disponible
    }

    private function get_available_timeslots($location_id, $date) {
        try {
            $day_of_week = date('w', strtotime($date));
            $date_ymd = date('Y-m-d', strtotime($date));

            // Obtener horarios de trabajo de los empleados en esta ubicación
            $schedules = $this->db->get_results($this->db->prepare(
                "SELECT DISTINCT ss.start_time, ss.end_time
                FROM {$this->db->prefix}menphis_staff_schedules ss
                JOIN {$this->db->prefix}menphis_staff s ON ss.staff_id = s.id
                WHERE s.locations LIKE %s
                AND ss.day_of_week = %d
                AND ss.is_break = 0
                AND s.status = 'active'
                ORDER BY ss.start_time ASC",
                '%"' . $location_id . '"%',
                $day_of_week
            ));

            if (empty($schedules)) {
                return array();
            }

            // Generar slots de 30 minutos
            $timeslots = array();
            foreach ($schedules as $schedule) {
                $start = strtotime($schedule->start_time);
                $end = strtotime($schedule->end_time);
                
                // Generar slots cada 30 minutos
                for ($time = $start; $time < $end; $time += 1800) {
                    $slot_time = date('H:i:s', $time);
                    
                    // Verificar si ya existe este horario
                    if (!isset($timeslots[$slot_time])) {
                        $timeslots[$slot_time] = array(
                            'time' => date('H:i', $time),
                            'available' => $this->is_timeslot_available($location_id, $date_ymd, $slot_time)
                        );
                    }
                }
            }

            // Ordenar por hora
            ksort($timeslots);
            
            return array_values($timeslots);

        } catch (Exception $e) {
            error_log('Error al obtener horarios disponibles: ' . $e->getMessage());
            return array();
        }
    }

    private function is_timeslot_available($location_id, $date, $time) {
        try {
            // Verificar reservas existentes
            $existing_bookings = $this->db->get_var($this->db->prepare(
                "SELECT COUNT(*) 
                FROM {$this->db->prefix}menphis_bookings 
                WHERE location_id = %d 
                AND booking_date = %s 
                AND booking_time = %s",
                $location_id,
                $date,
                $time
            ));

            if ($existing_bookings > 0) {
                return false;
            }

            // Verificar empleados disponibles
            $available_staff = $this->db->get_results($this->db->prepare(
                "SELECT s.id
                FROM {$this->db->prefix}menphis_staff s
                JOIN {$this->db->prefix}menphis_staff_schedules ss ON s.id = ss.staff_id
                WHERE s.locations LIKE %s
                AND s.status = 'active'
                AND ss.day_of_week = %d
                AND ss.is_break = 0
                AND %s BETWEEN ss.start_time AND ss.end_time
                AND NOT EXISTS (
                    SELECT 1 
                    FROM {$this->db->prefix}menphis_staff_schedules break_s
                    WHERE break_s.staff_id = s.id
                    AND break_s.day_of_week = %d
                    AND break_s.is_break = 1
                    AND %s BETWEEN break_s.start_time AND break_s.end_time
                )",
                '%"' . $location_id . '"%',
                date('w', strtotime($date)),
                $time,
                date('w', strtotime($date)),
                $time
            ));

            return !empty($available_staff);

        } catch (Exception $e) {
            error_log('Error al verificar disponibilidad: ' . $e->getMessage());
            return false;
        }
    }

    public function save_booking_data($order_id) {
        try {
            error_log('=== INICIO SAVE_BOOKING_DATA ===');
            global $wpdb;
            
            $order = wc_get_order($order_id);
            
            // Obtener datos de la sesión
            $staff_id = WC()->session->get('booking_staff_id');
            $location_id = WC()->session->get('booking_location_id');
            $booking_date = WC()->session->get('booking_date');
            $booking_time = WC()->session->get('booking_time');
            
            error_log("Datos de la reserva: " . print_r([
                'staff_id' => $staff_id,
                'location_id' => $location_id,
                'date' => $booking_date,
                'time' => $booking_time
            ], true));

            // Obtener la duración del servicio desde el carrito
            $cart_items = WC()->cart->get_cart();
            $booking_duration = 0;
            
            foreach ($cart_items as $item) {
                $product_id = $item['product_id'];
                $is_booking_product = get_post_meta($product_id, '_menphis_booking_product', true);
                
                if ($is_booking_product === 'yes') {
                    $duration = intval(get_post_meta($product_id, '_menphis_booking_duration', true));
                    $booking_duration = max($booking_duration, $duration);
                }
            }

            // Insertar en la tabla wp_menphis_bookings
            $result = $wpdb->insert(
                $wpdb->prefix . 'menphis_bookings',
                array(
                    'customer_id' => $order->get_customer_id(),
                    'staff_id' => $staff_id,
                    'location_id' => $location_id,
                    'booking_date' => $booking_date,
                    'booking_time' => $booking_time,
                    'status' => 'pending',
                    'created_at' => current_time('mysql'),
                    'order_id' => $order_id,
                    'duration' => $booking_duration,
                ),
                array(
                    '%d', // customer_id
                    '%d', // staff_id
                    '%d', // location_id
                    '%s', // booking_date
                    '%s', // booking_time
                    '%s', // status
                    '%s', // created_at
                    '%d', // order_id
                    '%d', // duration
                )
            );

            if ($result === false) {
                error_log("Error al insertar en la tabla: " . $wpdb->last_error);
                throw new Exception('Error al crear la reserva');
            }

            $booking_id = $wpdb->insert_id;
            error_log("Reserva creada con ID: " . $booking_id);

            // Actualizar la orden con el ID de la reserva
            $order->update_meta_data('_booking_id', $booking_id);
            $order->save();

            // Actualizar el estado de la reserva si la orden está completada
            if ($order->get_status() === 'completed') {
                $wpdb->update(
                    $wpdb->prefix . 'menphis_bookings',
                    array('status' => 'confirmed'),
                    array('id' => $booking_id),
                    array('%s'),
                    array('%d')
                );
            }

            // Limpiar la sesión
            WC()->session->__unset('booking_staff_id');
            WC()->session->__unset('booking_location_id');
            WC()->session->__unset('booking_date');
            WC()->session->__unset('booking_time');

        } catch (Exception $e) {
            error_log("Error en save_booking_data: " . $e->getMessage());
        }
    }
} 