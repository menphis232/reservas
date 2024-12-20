<?php
if (!defined('ABSPATH')) exit;

class Menphis_Bookings {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;

        // Crear las tablas necesarias
        $this->create_tables();

        // Agregar endpoints AJAX
        add_action('wp_ajax_get_bookings_list', array($this, 'ajax_get_bookings_list'));
        add_action('wp_ajax_add_booking', array($this, 'ajax_add_booking'));
        add_action('wp_ajax_delete_booking', array($this, 'ajax_delete_booking'));
        add_action('wp_ajax_save_booking_data', array($this, 'ajax_save_booking_data'));
        add_action('wp_ajax_nopriv_save_booking_data', array($this, 'ajax_save_booking_data'));

        // Hooks para la creación de reservas
        add_action('woocommerce_payment_complete', array($this, 'create_booking_from_order'));
        add_action('woocommerce_order_status_processing', array($this, 'create_booking_from_order'));
        add_action('woocommerce_order_status_completed', array($this, 'create_booking_from_order'));
        // Para pagos contra reembolso
        add_action('woocommerce_order_status_on-hold', array($this, 'create_booking_from_order'));

        // Guardar los datos de la reserva en los metadatos de la orden
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_booking_data'));

        // Agregar endpoint para obtener horarios disponibles
        add_action('wp_ajax_get_available_time_slots', array($this, 'ajax_get_available_time_slots'));
        add_action('wp_ajax_nopriv_get_available_time_slots', array($this, 'ajax_get_available_time_slots'));

        // Agregar datos para el JavaScript
        add_action('wp_enqueue_scripts', array($this, 'enqueue_booking_scripts'));

        // Agregar el endpoint AJAX para obtener detalles de reserva
        add_action('wp_ajax_get_booking_data', array($this, 'get_booking_data'));
        add_action('wp_ajax_nopriv_get_booking_data', array($this, 'get_booking_data'));

        // Agregar scripts y datos para el JavaScript
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Modificar los hooks de WooCommerce para el proceso de checkout
        add_action('woocommerce_checkout_process', array($this, 'validate_booking_data'));
        add_action('woocommerce_checkout_order_processed', array($this, 'save_booking_data'), 10, 3);
        add_action('woocommerce_payment_complete', array($this, 'create_booking_from_order'));
        
        // Para pagos contra reembolso y otros métodos
        add_action('woocommerce_order_status_processing', array($this, 'create_booking_from_order'));
        add_action('woocommerce_order_status_completed', array($this, 'create_booking_from_order'));
        add_action('woocommerce_order_status_on-hold', array($this, 'create_booking_from_order'));

        // Actualizar estado de la reserva cuando cambia el estado de la orden
        add_action('woocommerce_order_status_changed', array($this, 'update_booking_status'), 10, 3);

        // Agregar endpoint para editar reserva
        add_action('wp_ajax_edit_booking', array($this, 'ajax_edit_booking'));

        // Agregar endpoint AJAX para obtener reservas del usuario
        add_action('wp_ajax_get_user_bookings', array($this, 'ajax_get_user_bookings'));
        add_action('wp_ajax_nopriv_get_user_bookings', array($this, 'ajax_get_user_bookings'));

        // Agregar endpoint para obtener detalles de la reserva
        add_action('wp_ajax_get_booking_details', array($this, 'ajax_get_booking_details'));
        add_action('wp_ajax_nopriv_get_booking_details', array($this, 'ajax_get_booking_details'));
    }

    public function enqueue_booking_scripts() {
        wp_localize_script('menphis-booking', 'menphisBooking', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'checkout_url' => wc_get_checkout_url(),
            'nonce' => wp_create_nonce('menphis_booking_nonce')
        ));
    }

    public function render_bookings_page() {
        try {
            global $wpdb;

            error_log('Ejecutando consulta de reservas...');
            $query = "
                SELECT 
                    b.*,
                    CONCAT(mc.first_name, ' ', mc.last_name) as customer_name,
                    l.name as location_name,
                    CONCAT(us.display_name) as staff_name
                FROM {$wpdb->prefix}menphis_bookings b
                LEFT JOIN {$wpdb->prefix}menphis_customers mc ON b.customer_id = mc.id
                LEFT JOIN {$wpdb->prefix}menphis_locations l ON b.location_id = l.id
                LEFT JOIN {$wpdb->prefix}menphis_staff s ON b.staff_id = s.id
                LEFT JOIN {$wpdb->users} us ON s.wp_user_id = us.ID
                ORDER BY b.booking_date DESC, b.booking_time DESC";

            $bookings = $wpdb->get_results($query);
            error_log('Resultados de la consulta: ' . print_r($bookings, true));

            // Obtener datos necesarios para los filtros
            $customers = $this->get_customers_list();
            $services = $this->get_services_list();
            $staff_members = $this->get_staff_list();
            $locations = $this->get_locations_list();

            // Incluir la vista
            include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/bookings.php';

        } catch (Exception $e) {
            error_log('Error en render_bookings_page: ' . $e->getMessage());
            error_log($e->getTraceAsString());
            echo '<div class="error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    public function ajax_get_bookings_list() {
        try {
            check_ajax_referer('menphis_bookings_nonce', 'nonce');

            // Obtener filtros
            $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
            
            // Construir la consulta base
            $query = "
                SELECT 
                    b.*,
                    CONCAT(mc.first_name, ' ', mc.last_name) as customer_name,
                    l.name as location_name,
                    CONCAT(us.display_name) as staff_name
                FROM {$wpdb->prefix}menphis_bookings b
                LEFT JOIN {$wpdb->prefix}menphis_customers mc ON b.customer_id = mc.id
                LEFT JOIN {$wpdb->prefix}menphis_locations l ON b.location_id = l.id
                LEFT JOIN {$wpdb->prefix}menphis_staff s ON b.staff_id = s.id
                LEFT JOIN {$wpdb->users} us ON s.wp_user_id = us.ID
                WHERE 1=1";

            $where = array();
            $values = array();

            // Aplicar filtros
            if (!empty($filters['booking_id'])) {
                $where[] = "b.id = %d";
                $values[] = intval($filters['booking_id']);
            }

            if (!empty($filters['date_from'])) {
                $where[] = "b.booking_date >= %s";
                $values[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "b.booking_date <= %s";
                $values[] = $filters['date_to'];
            }

            if (!empty($filters['status'])) {
                $where[] = "b.status = %s";
                $values[] = $filters['status'];
            }

            // Agregar condiciones WHERE si existen
            if (!empty($where)) {
                $query .= " AND " . implode(" AND ", $where);
            }

            // Ordenar resultados
            $query .= " ORDER BY b.booking_date DESC, b.booking_time DESC";

            // Preparar la consulta si hay valores
            if (!empty($values)) {
                $query = $this->db->prepare($query, $values);
            }

            $bookings = $this->db->get_results($query);

            wp_send_json_success($bookings);

        } catch (Exception $e) {
            error_log('Error en ajax_get_bookings_list: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    public function get_bookings($filters = array()) {
        global $wpdb;
        
        $where = array("1=1");
        $values = array();
        
        if (!empty($filters['booking_id'])) {
            $where[] = "id = %d";
            $values[] = $filters['booking_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "booking_date >= %s";
            $values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "booking_date <= %s";
            $values[] = $filters['date_to'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = %s";
            $values[] = $filters['status'];
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}menphis_bookings WHERE " . implode(" AND ", $where) . " ORDER BY booking_date DESC, booking_time DESC";
        
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        
        return $wpdb->get_results($query);
    }

    public function get_customers_list() {
        static $call_count = 0;
        $call_count++;
        error_log("=== INICIO GET_CUSTOMERS_LIST (llamada #$call_count) ===");
        
        try {
            $sql = "SELECT * FROM {$this->db->prefix}menphis_customers";
            $customers = $this->db->get_results($sql);
            
            // Debug
            error_log('SQL Clientes: ' . $sql);
            error_log('Clientes encontrados: ' . ($customers ? count($customers) : 0));
            
            return $customers ?: array();
        } catch (Exception $e) {
            error_log('Error al obtener lista de clientes: ' . $e->getMessage());
            return array();
        }
    }

    public function get_services_list() {
        static $call_count = 0;
        $call_count++;
        error_log("=== INICIO GET_SERVICES_LIST (llamada #$call_count) ===");
        
        try {
            return get_posts(array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_is_service',
                        'value' => 'yes',
                        'compare' => '='
                    )
                )
            )) ?: array();
        } catch (Exception $e) {
            error_log('Error al obtener lista de servicios: ' . $e->getMessage());
            return array();
        }
    }

    public function ajax_add_booking() {
        try {
            check_ajax_referer('menphis_bookings_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            error_log('Datos recibidos para nueva reserva: ' . print_r($_POST, true));

            $location_id = sanitize_text_field($_POST['location']);
            $booking_date = sanitize_text_field($_POST['booking_date']);
            $booking_time = sanitize_text_field($_POST['booking_time']);
            $services = isset($_POST['services']) ? array_map('sanitize_text_field', $_POST['services']) : array();

            // Si no se seleccionó un empleado específico, buscar uno disponible
            $assigned_staff = array();
            if (empty($_POST['staff'])) {
                error_log('Buscando empleado disponible automáticamente');
                
                // Obtener personal disponible
                $available_staff = $this->get_available_staff($location_id, $booking_date, $booking_time);
                error_log('Personal disponible: ' . print_r($available_staff, true));

                if (!empty($available_staff)) {
                    // Asignar el primer empleado disponible
                    $assigned_staff = array($available_staff[0]['id']);
                    error_log('Empleado asignado automáticamente: ' . $assigned_staff[0]);
                } else {
                    throw new Exception('No hay personal disponible para esta fecha y hora');
                }
            } else {
                $assigned_staff = array(sanitize_text_field($_POST['staff']));
            }

            // Crear la reserva
            $booking_args = array(
                'post_title'  => 'Reserva Manual',
                'post_type'   => 'menphis_booking',
                'post_status' => 'publish',
                'post_content' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '',
                'meta_input'  => array(
                    '_location_id'    => $location_id,
                    '_booking_date'   => $booking_date,
                    '_booking_time'   => $booking_time,
                    '_customer_id'    => sanitize_text_field($_POST['customer']),
                    '_assigned_staff' => $assigned_staff,
                    '_services'       => $services
                )
            );

            error_log('Intentando crear reserva con datos: ' . print_r($booking_args, true));

            // Insertar la reserva
            $booking_id = wp_insert_post($booking_args);

            if (is_wp_error($booking_id)) {
                throw new Exception($booking_id->get_error_message());
            }

            error_log('Reserva creada con ID: ' . $booking_id);
            wp_send_json_success(array(
                'message' => 'Reserva creada exitosamente',
                'booking_id' => $booking_id
            ));

        } catch (Exception $e) {
            error_log('Error al crear reserva: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    // Método helper para validar fecha y hora
    private function validate_datetime($date, $time) {
        $datetime = date('Y-m-d H:i:s', strtotime("$date $time"));
        return $datetime && $datetime !== '1970-01-01 00:00:00';
    }

    // Método helper para formatear fecha
    private function format_date($date) {
        return date('Y-m-d', strtotime(str_replace('/', '-', $date)));
    }

    // Método helper para formatear hora
    private function format_time($time) {
        return date('H:i:s', strtotime($time));
    }

    private function get_customer_name($customer_id) {
        $customer = $this->db->get_row($this->db->prepare(
            "SELECT first_name, last_name FROM {$this->db->prefix}menphis_customers WHERE id = %d",
            $customer_id
        ));
        return $customer ? trim($customer->first_name . ' ' . $customer->last_name) : 'N/A';
    }

    private function get_booking_services($booking_id) {
        $services = $this->db->get_results($this->db->prepare(
            "SELECT s.post_title 
            FROM {$this->db->prefix}menphis_booking_services bs
            JOIN {$this->db->prefix}posts s ON bs.service_id = s.ID
            WHERE bs.booking_id = %d",
            $booking_id
        ));
        return $services ? implode(', ', array_column($services, 'post_title')) : 'N/A';
    }

    private function get_booking_actions_html($booking_id) {
        $actions = array(
            'view' => array(
                'class' => 'blue view-booking',
                'icon' => 'visibility',
                'title' => 'Ver'
            ),
            'edit' => array(
                'class' => 'green edit-booking',
                'icon' => 'edit',
                'title' => 'Editar'
            ),
            'delete' => array(
                'class' => 'red delete-booking',
                'icon' => 'delete',
                'title' => 'Eliminar'
            )
        );

        $html = '';
        foreach ($actions as $action => $data) {
            $html .= sprintf(
                '<button class="btn-small waves-effect waves-light %s" data-id="%d" title="%s">
                    <i class="material-icons">%s</i>
                </button> ',
                esc_attr($data['class']),
                esc_attr($booking_id),
                esc_attr($data['title']),
                esc_html($data['icon'])
            );
        }

        return $html;
    }

    public function ajax_get_booking() {
        check_ajax_referer('menphis_bookings_nonce', 'nonce');

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

        if (!$booking_id) {
            wp_send_json_error('ID de reserva no válido');
            return;
        }

        $booking = get_post($booking_id);

        if (!$booking || $booking->post_type !== 'menphis_booking') {
            wp_send_json_error('Reserva no encontrada');
            return;
        }

        $assigned_staff = get_post_meta($booking->ID, '_assigned_staff', true);
        $staff_id = $assigned_staff ? $assigned_staff[0] : '';

        $booking_data = array(
            'id' => $booking->ID,
            'customer_id' => get_post_meta($booking->ID, '_customer_id', true),
            'customer_name' => get_post_meta($booking->ID, '_customer_name', true),
            'location_id' => get_post_meta($booking->ID, '_location_id', true),
            'location_name' => get_the_title(get_post_meta($booking->ID, '_location_id', true)),
            'booking_date' => get_post_meta($booking->ID, '_booking_date', true),
            'booking_time' => get_post_meta($booking->ID, '_booking_time', true),
            'staff_id' => $staff_id,
            'staff_name' => $staff_id ? get_userdata($staff_id)->display_name : '',
            'services' => get_post_meta($booking->ID, '_services', true),
            'notes' => $booking->post_content
        );

        wp_send_json_success($booking_data);
    }

    public function ajax_delete_booking() {
        try {
            check_ajax_referer('menphis_bookings_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                wp_send_json_error('ID de reserva inválido');
                return;
            }

            // Verificar que la reserva existe
            $booking = get_post($id);
            if (!$booking || $booking->post_type !== 'menphis_booking') {
                wp_send_json_error('Reserva no encontrada');
                return;
            }

            // Eliminar la reserva
            $result = wp_delete_post($id, true);

            if (!$result) {
                throw new Exception('Error al eliminar la reserva');
            }

            wp_send_json_success('Reserva eliminada correctamente');

        } catch (Exception $e) {
            error_log('Error en ajax_delete_booking: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    public function get_staff_list() {
        static $call_count = 0;
        $call_count++;
        error_log("=== INICIO GET_STAFF_LIST (llamada #$call_count) ===");
        
        try {
            global $wpdb;
            
            // Obtener solo el personal activo de la tabla menphis_staff
            $staff_query = "
                SELECT ms.id, ms.wp_user_id, u.display_name 
                FROM {$wpdb->prefix}menphis_staff ms
                JOIN {$wpdb->users} u ON ms.wp_user_id = u.ID
                WHERE ms.status = 'active'
                ORDER BY u.display_name ASC
            ";
            
            error_log('Query lista de personal: ' . $staff_query);
            $staff = $wpdb->get_results($staff_query);
            error_log('Personal encontrado: ' . print_r($staff, true));
            
            return $staff;
            
        } catch (Exception $e) {
            error_log('Error al obtener lista de personal: ' . $e->getMessage());
            return array();
        }
    }

    private function get_staff_name($staff_id) {
        $staff = get_userdata($staff_id);
        return $staff ? $staff->display_name : 'N/A';
    }

    // Método para obtener un empleado disponible para los servicios
    private function get_available_staff_for_services($service_ids, $location_id, $booking_date, $booking_time) {
        try {
            $log_file = WP_CONTENT_DIR . '/menphis-debug.log';
            $log = function($message) use ($log_file) {
                file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
            };

            $day_of_week = date('w', strtotime($booking_date));
            $booking_time = date('H:i:s', strtotime($booking_time));
            
            $log("=== INICIO BÚSQUEDA DE EMPLEADO ===");
            $log("Parámetros de búsqueda:");
            $log("- Servicios: " . print_r($service_ids, true));
            $log("- Ubicación: $location_id");
            $log("- Fecha: $booking_date");
            $log("- Hora: $booking_time");
            $log("- Día semana: $day_of_week");

            // 1. Obtener empleados activos
            $all_staff = $this->db->get_results(
                "SELECT * FROM {$this->db->prefix}menphis_staff WHERE status = 'active'"
            );
            $log("1. Empleados activos encontrados: " . count($all_staff));

            // 2. Verificar horarios
            $staff_with_schedule = [];
            foreach ($all_staff as $staff) {
                $schedule_query = $this->db->prepare(
                    "SELECT * FROM {$this->db->prefix}menphis_staff_schedules 
                    WHERE staff_id = %d 
                    AND day_of_week = %d 
                    AND is_break = 0 
                    AND %s BETWEEN start_time AND end_time",
                    $staff->id,
                    $day_of_week,
                    $booking_time
                );
                $log("Query horario para staff {$staff->id}: " . $schedule_query);
                
                $schedule = $this->db->get_row($schedule_query);
                if ($schedule) {
                    $log("- Empleado {$staff->id} tiene horario disponible");
                    $staff_with_schedule[] = $staff;
                } else {
                    $log("- Empleado {$staff->id} NO tiene horario disponible");
                }
            }

            if (empty($staff_with_schedule)) {
                $log("No hay empleados con horario disponible");
                return 0;
            }

            // 3. Verificar ubicación y servicios
            $available_staff = [];
            foreach ($staff_with_schedule as $staff) {
                $log("\nVerificando empleado {$staff->id}:");
                
                // Verificar ubicación
                $staff_locations = json_decode($staff->locations, true);
                $log("- Ubicaciones del empleado: " . print_r($staff_locations, true));
                
                if (!in_array($location_id, $staff_locations)) {
                    $log("- No trabaja en la ubicación $location_id");
                    continue;
                }
                
                // Verificar servicios
                $staff_services = json_decode($staff->services, true);
                $log("- Servicios del empleado: " . print_r($staff_services, true));
                
                $can_do_services = true;
                foreach ($service_ids as $service_id) {
                    if (!in_array($service_id, $staff_services)) {
                        $log("- No puede realizar el servicio $service_id");
                        $can_do_services = false;
                        break;
                    }
                }

                if (!$can_do_services) {
                    continue;
                }

                $log("- Puede realizar todos los servicios");
                $available_staff[] = $staff->wp_user_id;
            }

            $log("\nEmpleados disponibles final: " . print_r($available_staff, true));

            if (empty($available_staff)) {
                $log("No se encontró ningún empleado disponible");
                return 0;
            }

            $selected_staff = intval($available_staff[0]);
            $log("Empleado seleccionado: $selected_staff");
            return $selected_staff;

        } catch (Exception $e) {
            file_put_contents(
                WP_CONTENT_DIR . '/menphis-debug.log', 
                date('[Y-m-d H:i:s] ') . "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", 
                FILE_APPEND
            );
            return 0;
        }
    }

    public function get_locations_list() {
        try {
            return $this->db->get_results(
                "SELECT * FROM {$this->db->prefix}menphis_locations WHERE status = 'active'"
            );
        } catch (Exception $e) {
            error_log('Error al obtener lista de ubicaciones: ' . $e->getMessage());
            return array();
        }
    }

    private function get_location_name($location_id) {
        $location = $this->db->get_row($this->db->prepare(
            "SELECT name FROM {$this->db->prefix}menphis_locations WHERE id = %d",
            $location_id
        ));
        return $location ? $location->name : 'N/A';
    }

    private function debug_staff_locations($location_id) {
        $assignments = $this->db->get_results($this->db->prepare(
            "SELECT sl.*, u.display_name, l.name as location_name
            FROM {$this->db->prefix}menphis_staff_locations sl
            JOIN {$this->db->users} u ON sl.staff_id = u.ID
            JOIN {$this->db->prefix}menphis_locations l ON sl.location_id = l.id
            WHERE sl.location_id = %d",
            $location_id
        ));

        error_log('Asignaciones de empleados para ubicación ' . $location_id . ': ' . print_r($assignments, true));
        return $assignments;
    }

    private function check_staff_availability($staff_id, $date, $time) {
        $day_of_week = date('w', strtotime($date));
        
        error_log(sprintf(
            'Verificando disponibilidad - Staff ID: %d, Fecha: %s, Hora: %s, Día: %d',
            $staff_id,
            $date,
            $time,
            $day_of_week
        ));

        // 1. Verificar horario normal
        $schedule_query = $this->db->prepare(
            "SELECT * FROM {$this->db->prefix}menphis_staff_schedules
            WHERE staff_id = %d
            AND day_of_week = %d
            AND is_break = 0
            AND status = 'active'
            AND TIME(%s) BETWEEN TIME(start_time) AND TIME(end_time)",
            $staff_id,
            $day_of_week,
            $time
        );

        error_log('Query horario: ' . $schedule_query);
        $schedule = $this->db->get_row($schedule_query);

        if (!$schedule) {
            error_log("Staff $staff_id no tiene horario para este día/hora");
            return false;
        }

        // 2. Verificar que no esté en descanso
        $break_query = $this->db->prepare(
            "SELECT * FROM {$this->db->prefix}menphis_staff_schedules
            WHERE staff_id = %d
            AND day_of_week = %d
            AND is_break = 1
            AND status = 'active'
            AND TIME(%s) BETWEEN TIME(start_time) AND TIME(end_time)",
            $staff_id,
            $day_of_week,
            $time
        );

        $break = $this->db->get_row($break_query);

        // 3. Verificar reservas existentes (solo si no está en descanso)
        $has_booking = false;
        if (!$break) {
            $booking_query = $this->db->prepare(
                "SELECT p.* FROM {$this->db->posts} p
                INNER JOIN {$this->db->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_assigned_staff'
                INNER JOIN {$this->db->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_booking_date'
                INNER JOIN {$this->db->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_booking_time'
                WHERE p.post_type = 'menphis_booking'
                AND p.post_status = 'publish'
                AND pm1.meta_value LIKE %s
                AND pm2.meta_value = %s
                AND pm3.meta_value = %s",
                '%' . $staff_id . '%',
                $date,
                $time
            );

            error_log('Query reservas: ' . $booking_query);
            $has_booking = (bool) $this->db->get_row($booking_query);
        }

        $is_available = $schedule && !$break && !$has_booking;

        error_log(sprintf(
            'Staff %d disponibilidad: schedule=%s, break=%s, has_booking=%s, final=%s',
            $staff_id,
            $schedule ? 'true' : 'false',
            $break ? 'true' : 'false',
            $has_booking ? 'true' : 'false',
            $is_available ? 'true' : 'false'
        ));

        return $is_available;
    }

    /**
     * Guarda los datos de la reserva en la orden
     */
    public function save_booking_data($order_id) {
        try {
            error_log('=== INICIO SAVE_BOOKING_DATA ===');
            error_log('Order ID: ' . $order_id);
            
            $booking_data = WC()->session->get('booking_data');
            if (empty($booking_data)) {
                error_log('No hay datos de reserva en la sesión');
                return;
            }

            // Guardar datos en la orden
            update_post_meta($order_id, '_booking_location', $booking_data['location']);
            update_post_meta($order_id, '_booking_date', $booking_data['date']);
            update_post_meta($order_id, '_booking_time', $booking_data['time']);
            
            error_log('Datos guardados en la orden: ' . print_r(get_post_meta($order_id), true));
            error_log('=== FIN SAVE_BOOKING_DATA ===');

        } catch (Exception $e) {
            error_log('Error en save_booking_data: ' . $e->getMessage());
            error_log($e->getTraceAsString());
        }
    }

    /**
     * Crea la reserva cuando se completa el pago
     */
    private function create_or_update_customer($order) {
        global $wpdb;
        
        error_log('=== INICIO CREATE_OR_UPDATE_CUSTOMER ===');
        
        // Crear o obtener usuario WordPress
        $user_id = $this->create_wordpress_user($order);
        if (!$user_id) {
            throw new Exception('Error al crear usuario WordPress');
        }

        $email = $order->get_billing_email();
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $phone = $order->get_billing_phone();

        // Verificar si el cliente ya existe en nuestra tabla
        $existing_customer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}menphis_customers WHERE email = %s",
            $email
        ));
        
        // Preparar datos del cliente
        $customer_data = array(
            'wp_user_id'  => $user_id,
            'first_name'  => $first_name,
            'last_name'   => $last_name,
            'email'       => $email,
            'phone'       => $phone,
            'status'      => 'active',
            'created_at'  => current_time('mysql')
        );
        
        if ($existing_customer) {
            // Actualizar cliente existente
            $wpdb->update(
                $wpdb->prefix . 'menphis_customers',
                $customer_data,
                array('id' => $existing_customer->id),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            error_log('Cliente actualizado - ID: ' . $existing_customer->id);
            return $existing_customer->id;
        } else {
            // Crear nuevo cliente
            $wpdb->insert(
                $wpdb->prefix . 'menphis_customers',
                $customer_data,
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            $customer_id = $wpdb->insert_id;
            error_log('Nuevo cliente creado - ID: ' . $customer_id);
            return $customer_id;
        }
    }

    public function create_booking_from_order($order_id) {
        try {
            error_log('=== INICIO CREATE_BOOKING_FROM_ORDER ===');
            error_log('Order ID: ' . $order_id);
            
            global $wpdb;
            
            // Verificar si ya existe una reserva
            $existing_booking = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}menphis_bookings WHERE order_id = %d",
                $order_id
            ));

            if ($existing_booking) {
                error_log('Ya existe una reserva para esta orden');
                return;
            }
            
            $order = wc_get_order($order_id);
            if (!$order) {
                error_log('Orden no encontrada');
                return;
            }

            // Crear o actualizar cliente
            $customer_id = $this->create_or_update_customer($order);
            
            $booking_data = WC()->session->get('booking_data');
            if (empty($booking_data)) {
                error_log('No hay datos de reserva en la sesión');
                return;
            }

            // Obtener servicios del carrito/orden
            $services = array();
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                error_log('Analizando producto del carrito - ID: ' . $product_id);
                error_log('Nombre del producto: ' . $item->get_name());
                
                if ($this->is_product_service($product_id)) {
                    error_log('Servicio encontrado: ' . $product_id);
                    // Obtener el ID del servicio vinculado o usar el ID del producto si no hay vinculación
                    $service_id = get_post_meta($product_id, '_linked_service', true);
                    $service_id_to_use = $service_id ? $service_id : $product_id;
                    error_log('ID del servicio a usar: ' . $service_id_to_use);
                    $services[] = $service_id_to_use;
                } else {
                    error_log('El producto no es un servicio');
                }
            }

            error_log('Total de servicios encontrados: ' . count($services));
            error_log('Lista de servicios: ' . print_r($services, true));

            // Insertar la reserva principal
            $data_to_insert = array(
                'customer_id' => $customer_id, // Usar el ID del cliente de nuestra tabla
                'staff_id' => 0,
                'location_id' => $booking_data['location'],
                'booking_date' => $booking_data['date'],
                'booking_time' => $booking_data['time'],
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'order_id' => $order_id
            );

            $result = $wpdb->insert(
                $wpdb->prefix . 'menphis_bookings',
                $data_to_insert,
                array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d')
            );

            if ($result === false) {
                error_log('Error al insertar la reserva: ' . $wpdb->last_error);
                return;
            }

            $booking_id = $wpdb->insert_id;
            error_log('Reserva creada con ID: ' . $booking_id);

            // Insertar los servicios en la tabla intermedia
            if (!empty($services)) {
                error_log('=== INICIO INSERCIÓN DE SERVICIOS ===');
                error_log('Booking ID: ' . $booking_id);
                
                foreach ($services as $service_id) {
                    error_log('Insertando servicio en tabla intermedia:');
                    error_log('- Booking ID: ' . $booking_id);
                    error_log('- Service ID: ' . $service_id);
                    
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'menphis_booking_services',
                        array(
                            'booking_id' => $booking_id,
                            'service_id' => $service_id
                        ),
                        array('%d', '%d')
                    );
                    
                    if ($result === false) {
                        error_log('ERROR al insertar servicio:');
                        error_log('- Error MySQL: ' . $wpdb->last_error);
                        error_log('- Query ejecutada: ' . $wpdb->last_query);
                    } else {
                        error_log('Servicio insertado correctamente - ID: ' . $wpdb->insert_id);
                    }
                }
                
                // Verificar los servicios insertados
                $inserted_services = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}menphis_booking_services WHERE booking_id = %d",
                    $booking_id
                ));
                error_log('Servicios insertados en la tabla intermedia: ' . print_r($inserted_services, true));
                error_log('=== FIN INSERCIÓN DE SERVICIOS ===');
            } else {
                error_log('No hay servicios para insertar en la tabla intermedia');
            }

            // Limpiar datos de sesión
            WC()->session->set('booking_data', null);

            // Agregar nota a la orden con los servicios
            $service_names = array();
            foreach ($services as $service_id) {
                $service = get_post($service_id);
                if ($service) {
                    $service_names[] = $service->post_title;
                }
            }

            $order->add_order_note(sprintf(
                'Reserva creada exitosamente (ID: %s). Servicios: %s',
                $booking_id,
                !empty($service_names) ? implode(', ', $service_names) : 'Ninguno'
            ));
            $order->save();

        } catch (Exception $e) {
            error_log('ERROR en create_booking_from_order: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Obtiene el personal disponible para una fecha y hora
     */
    private function get_available_staff($location_id, $date, $time) {
        global $wpdb;
        
        error_log('Buscando personal disponible para: ' . print_r([
            'location_id' => $location_id,
            'date' => $date,
            'time' => $time
        ], true));

        // Obtener personal que trabaja en esta ubicación con su conteo de reservas
        $staff_query = $wpdb->prepare(
            "SELECT 
                ms.id as staff_id, 
                ms.wp_user_id, 
                u.display_name,
                COUNT(DISTINCT p.ID) as booking_count
            FROM {$wpdb->prefix}menphis_staff ms
            JOIN {$wpdb->users} u ON ms.wp_user_id = u.ID
            JOIN {$wpdb->prefix}menphis_staff_schedules ss ON ms.id = ss.staff_id
            LEFT JOIN {$wpdb->posts} p ON p.post_type = 'menphis_booking'
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id 
                AND pm1.meta_key = '_assigned_staff'
                AND pm1.meta_value LIKE CONCAT('%%', ms.id, '%%')
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id 
                AND pm2.meta_key = '_booking_date'
                AND pm2.meta_value = %s
            WHERE ms.status = 'active'
            AND JSON_CONTAINS(ms.locations, %s)
            AND ss.day_of_week = %d
            AND ss.is_break = 0
            AND ss.status = 'active'
            AND TIME(%s) BETWEEN TIME(ss.start_time) AND TIME(ss.end_time)
            GROUP BY ms.id, ms.wp_user_id, u.display_name
            ORDER BY booking_count ASC",
            $date,
            $location_id,
            date('w', strtotime($date)),
            $time
        );

        error_log('Query personal: ' . $staff_query);
        $available_staff = $wpdb->get_results($staff_query);
        error_log('Personal encontrado en ubicación: ' . print_r($available_staff, true));

        // Filtrar personal que ya tiene reserva en este horario específico
        $available_staff_filtered = array();
        foreach ($available_staff as $staff) {
            $booking_query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_assigned_staff'
                INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_booking_date'
                INNER JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_booking_time'
                WHERE p.post_type = 'menphis_booking'
                AND p.post_status = 'publish'
                AND pm1.meta_value LIKE %s
                AND pm2.meta_value = %s
                AND pm3.meta_value = %s",
                '%' . $staff->staff_id . '%',
                $date,
                $time
            );
            
            $has_booking = (int) $wpdb->get_var($booking_query) > 0;
            
            if (!$has_booking) {
                $available_staff_filtered[] = array(
                    'id' => $staff->staff_id,
                    'wp_user_id' => $staff->wp_user_id,
                    'name' => $staff->display_name,
                    'booking_count' => (int)$staff->booking_count
                );
            }
        }

        // Ordenar por cantidad de reservas (menos reservas primero)
        usort($available_staff_filtered, function($a, $b) {
            return $a['booking_count'] - $b['booking_count'];
        });

        error_log('Personal disponible final (ordenado por carga de trabajo): ' . print_r($available_staff_filtered, true));
        return $available_staff_filtered;
    }

    /**
     * Asigna personal aleatoriamente a los servicios
     */
    private function assign_random_staff($available_staff, $services_count) {
        $assigned_staff = array();
        
        // Asegurarse de que hay suficiente personal disponible
        if (count($available_staff) < $services_count) {
            return false;
        }
        
        // Asignar aleatoriamente
        shuffle($available_staff);
        for ($i = 0; $i < $services_count; $i++) {
            $assigned_staff[] = $available_staff[$i];
        }
        
        return $assigned_staff;
    }

    private function is_product_service($product_id) {
        error_log('Verificando si el producto ' . $product_id . ' es un servicio');
        
        $product = wc_get_product($product_id);
        if (!$product) {
            error_log('Producto no encontrado');
            return false;
        }

        // 1. Verificar por meta _linked_service
        $service_id = get_post_meta($product_id, '_linked_service', true);
        error_log('Service ID vinculado: ' . $service_id);
        if ($service_id) {
            return true;
        }

        // 2. Verificar por categoría
        $terms = wp_get_post_terms($product_id, 'product_cat');
        foreach ($terms as $term) {
            if ($term->slug === 'servicios' || $term->slug === 'services') {
                error_log('Producto encontrado en categoría de servicios');
                return true;
            }
        }

        // 3. Verificar por título (palabras clave)
        $title = strtolower($product->get_name());
        $service_keywords = array('manicura', 'pedicura', 'uñas', 'nails', 'acrigel', 'rusa');
        
        foreach ($service_keywords as $keyword) {
            if (strpos($title, $keyword) !== false) {
                error_log('Producto identificado como servicio por título: ' . $keyword);
                return true;
            }
        }

        // 4. Verificar por SKU (opcional)
        $sku = $product->get_sku();
        if (strpos($sku, 'SRV-') === 0) {
            error_log('Producto identificado como servicio por SKU');
            return true;
        }

        error_log('Producto no es un servicio');
        return false;
    }

    public function process_booking_form() {
        error_log('=== INICIO PROCESS_BOOKING_FORM ===');
        error_log('POST data: ' . print_r($_POST, true));

        if (!isset($_POST['booking_form_nonce'])) {
            error_log('No se encontró el nonce del formulario');
            return;
        }

        if (!wp_verify_nonce($_POST['booking_form_nonce'], 'booking_form')) {
            error_log('Verificación de nonce falló');
            return;
        }

        if (!isset($_POST['booking_data'])) {
            error_log('No hay datos de reserva en POST');
            return;
        }

        $booking_data = json_decode(stripslashes($_POST['booking_data']), true);
        error_log('Datos de reserva decodificados: ' . print_r($booking_data, true));

        try {
            // Validar datos requeridos
            if (empty($booking_data['location'])) {
                throw new Exception('Falta la ubicación');
            }
            if (empty($booking_data['date'])) {
                throw new Exception('Falta la fecha');
            }
            if (empty($booking_data['time'])) {
                throw new Exception('Falta la hora');
            }

            // Obtener datos de la reserva
            $location_id = intval($booking_data['location']);
            $booking_date = sanitize_text_field($booking_data['date']);
            $booking_time = sanitize_text_field($booking_data['time']);

            error_log('Datos procesados:');
            error_log('- Location ID: ' . $location_id);
            error_log('- Fecha: ' . $booking_date);
            error_log('- Hora: ' . $booking_time);

            // Obtener servicios del carrito
            $services = array();
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product_id = $cart_item['product_id'];
                if ($this->is_product_service($product_id)) {
                    $services[] = array(
                        'service_id' => $product_id,
                        'duration' => get_post_meta($product_id, '_service_duration', true)
                    );
                }
            }
            error_log('Servicios encontrados: ' . print_r($services, true));

            // Obtener personal disponible
            $available_staff = $this->get_available_staff($location_id, $booking_date, $booking_time);
            error_log('Personal disponible: ' . print_r($available_staff, true));

            if (empty($available_staff)) {
                throw new Exception('No hay personal disponible para esta fecha y hora');
            }

            // Guardar datos en la sesión
            $session_data = array(
                'location_id' => $location_id,
                'booking_date' => $booking_date,
                'booking_time' => $booking_time,
                'services' => $services,
                'available_staff' => $available_staff
            );

            WC()->session->set('booking_data', $session_data);
            error_log('Datos guardados en sesión: ' . print_r($session_data, true));
            error_log('=== FIN PROCESS_BOOKING_FORM ===');

        } catch (Exception $e) {
            error_log('ERROR en process_booking_form: ' . $e->getMessage());
            error_log($e->getTraceAsString());
            wc_add_notice($e->getMessage(), 'error');
        }
    }

    public function init() {
        // Agregar el hook para procesar el formulario
        add_action('template_redirect', array($this, 'process_booking_form'));
    }

    public function ajax_save_booking_data() {
        check_ajax_referer('menphis_booking_nonce', 'nonce');

        $booking_data = isset($_POST['booking_data']) ? $_POST['booking_data'] : array();

        error_log('Guardando datos de reserva en sesión: ' . print_r($booking_data, true));

        // Validar datos
        if (empty($booking_data['location']) || empty($booking_data['date']) || empty($booking_data['time'])) {
            wp_send_json_error('Faltan datos de la reserva');
            return;
        }

        // Guardar datos en la sesión
        WC()->session->set('booking_data', $booking_data);
        error_log('Datos guardados en sesión');

        wp_send_json_success();
    }

    public function ajax_get_available_time_slots() {
        check_ajax_referer('menphis_booking_nonce', 'nonce');

        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $day_of_week = date('w', strtotime($date));

        error_log('Solicitando horarios disponibles para: ' . print_r([
            'location_id' => $location_id,
            'date' => $date,
            'day_of_week' => $day_of_week
        ], true));

        if (!$location_id || !$date) {
            wp_send_json_error('Faltan datos necesarios');
            return;
        }

        try {
            global $wpdb;

            // Obtener personal disponible para esta ubicación
            $staff_query = $wpdb->prepare(
                "SELECT DISTINCT 
                    ms.id as staff_id,
                    ms.wp_user_id,
                    u.display_name,
                    ss.start_time,
                    ss.end_time
                FROM {$wpdb->prefix}menphis_staff ms
                JOIN {$wpdb->users} u ON ms.wp_user_id = u.ID
                JOIN {$wpdb->prefix}menphis_staff_schedules ss ON ms.id = ss.staff_id
                WHERE ms.status = 'active'
                AND JSON_CONTAINS(ms.locations, %s)
                AND ss.day_of_week = %d
                AND ss.is_break = 0
                AND ss.status = 'active'",
                $location_id,
                $day_of_week
            );

            error_log('Query personal: ' . $staff_query);
            $available_staff = $wpdb->get_results($staff_query);
            error_log('Personal encontrado: ' . print_r($available_staff, true));

            // Obtener todos los horarios posibles
            $start_time = strtotime('09:00');
            $end_time = strtotime('20:00');
            $available_slots = array();

            // Verificar cada horario
            for ($time = $start_time; $time <= $end_time; $time += (30 * 60)) {
                $current_time = date('H:i', $time);
                $staff_for_slot = array();

                foreach ($available_staff as $staff) {
                    // Verificar si el horario está dentro del rango del empleado
                    $staff_start = strtotime($staff->start_time);
                    $staff_end = strtotime($staff->end_time);
                    $current = strtotime($current_time);

                    if ($current >= $staff_start && $current <= $staff_end) {
                        // Verificar si ya tiene una reserva para este horario
                        $has_booking = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->posts} p
                            JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_assigned_staff'
                            JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_booking_date'
                            JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_booking_time'
                            WHERE p.post_type = 'menphis_booking'
                            AND p.post_status = 'publish'
                            AND pm1.meta_value LIKE %s
                            AND pm2.meta_value = %s
                            AND pm3.meta_value = %s",
                            '%' . $staff->staff_id . '%',
                            $date,
                            $current_time
                        ));

                        if (!$has_booking) {
                            $staff_for_slot[] = array(
                                'id' => $staff->staff_id,
                                'wp_user_id' => $staff->wp_user_id,
                                'name' => $staff->display_name
                            );
                        }
                    }
                }

                // Si hay personal disponible para este horario, agregarlo
                if (!empty($staff_for_slot)) {
                    $available_slots[] = array(
                        'time' => $current_time,
                        'staff' => $staff_for_slot
                    );
                }
            }

            error_log('Horarios disponibles: ' . print_r($available_slots, true));
            wp_send_json_success($available_slots);

        } catch (Exception $e) {
            error_log('Error al obtener horarios disponibles: ' . $e->getMessage());
            wp_send_json_error('Error al obtener horarios disponibles');
        }
    }

    // Método para obtener los detalles de una reserva
    public function get_booking_data() {
        try {
            // Verificar nonce
            if (!check_ajax_referer('menphis_bookings_nonce', 'nonce', false)) {
                error_log('Nonce verification failed');
                wp_send_json_error('Error de seguridad');
                return;
            }

            // Verificar permisos
            if (!current_user_can('manage_options')) {
                error_log('User does not have permission');
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
            
            error_log('=== INICIO GET_BOOKING_DATA ===');
            error_log('Booking ID recibido: ' . $booking_id);
            
            if (!$booking_id) {
                wp_send_json_error('ID de reserva no válido');
                return;
            }

            global $wpdb;
            
            // Obtener datos de la reserva
            $query = $wpdb->prepare("
                SELECT 
                    b.*,
                    u.display_name as customer_name,
                    l.name as location_name,
                    CONCAT(us.display_name) as staff_name
                FROM {$wpdb->prefix}menphis_bookings b
                LEFT JOIN {$wpdb->users} u ON b.customer_id = u.ID
                LEFT JOIN {$wpdb->prefix}menphis_locations l ON b.location_id = l.id
                LEFT JOIN {$wpdb->prefix}menphis_staff s ON b.staff_id = s.id
                LEFT JOIN {$wpdb->users} us ON s.wp_user_id = us.ID
                WHERE b.id = %d
            ", $booking_id);

            $booking = $wpdb->get_row($query);
            
            if ($booking) {
                // Formatear fechas para mostrar
                $booking->booking_date = date('Y-m-d', strtotime($booking->booking_date));
                $booking->booking_time = date('H:i', strtotime($booking->booking_time));

                // Obtener servicios asociados
                $services_query = $wpdb->prepare("
                    SELECT 
                        s.*,
                        p.post_title as service_name
                    FROM {$wpdb->prefix}menphis_booking_services s
                    LEFT JOIN {$wpdb->posts} p ON s.service_id = p.ID
                    WHERE s.booking_id = %d
                ", $booking_id);

                $booking->services = $wpdb->get_results($services_query);
                
                error_log('Servicios encontrados: ' . print_r($booking->services, true));

                wp_send_json_success($booking);
            } else {
                wp_send_json_error('No se encontró la reserva');
            }
            
        } catch (Exception $e) {
            error_log('Error en get_booking_data: ' . $e->getMessage());
            wp_send_json_error('Error al procesar la solicitud');
        }
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script(
            'menphis-bookings', 
            MENPHIS_RESERVA_PLUGIN_URL . 'assets/js/menphis-bookings.js', 
            array('jquery'), 
            MENPHIS_RESERVA_VERSION, 
            true
        );

        // Pasar datos al JavaScript
        wp_localize_script('menphis-bookings', 'menphisBookings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('menphis_bookings_nonce'), // Asegúrate de usar el mismo nombre en PHP y JS
            'messages' => array(
                'error' => __('Error al cargar los datos', 'menphis-reserva'),
                'success' => __('Operación exitosa', 'menphis-reserva')
            )
        ));
    }

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla principal de reservas
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_bookings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            staff_id bigint(20) NOT NULL,
            location_id bigint(20) NOT NULL,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            status varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            order_id bigint(20) DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Tabla intermedia para servicios
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_booking_services (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            service_id bigint(20) NOT NULL,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY service_id (service_id)
        ) $charset_collate;";

        // Tabla de clientes
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_customers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) NOT NULL,
            first_name varchar(255) NOT NULL,
            last_name varchar(255) DEFAULT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            notes text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY wp_user_id (wp_user_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Agregar validación antes del checkout
    public function validate_booking_data() {
        $booking_data = WC()->session->get('booking_data');
        
        if (empty($booking_data)) {
            return; // Si no hay datos de reserva, podría ser una orden normal
        }

        // Verificar datos mínimos necesarios
        if (empty($booking_data['location']) || empty($booking_data['date']) || empty($booking_data['time'])) {
            wc_add_notice('Por favor complete todos los datos de la reserva', 'error');
            return;
        }
    }

    public function update_booking_status($order_id, $old_status, $new_status) {
        try {
            global $wpdb;
            
            // Buscar la reserva asociada a esta orden
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}menphis_bookings WHERE order_id = %d",
                $order_id
            ));

            if (!$booking) {
                return;
            }

            // Determinar el nuevo estado de la reserva
            $booking_status = 'pending';
            if (in_array($new_status, array('completed', 'processing'))) {
                $booking_status = 'completed';
            } elseif ($new_status === 'cancelled') {
                $booking_status = 'cancelled';
            }

            // Actualizar el estado de la reserva
            $wpdb->update(
                $wpdb->prefix . 'menphis_bookings',
                array('status' => $booking_status),
                array('id' => $booking->id),
                array('%s'),
                array('%d')
            );

            // Agregar nota a la orden
            $order = wc_get_order($order_id);
            if ($order) {
                $order->add_order_note(sprintf(
                    'Estado de la reserva actualizado a: %s', 
                    $booking_status
                ));
            }

        } catch (Exception $e) {
            error_log('Error al actualizar estado de la reserva: ' . $e->getMessage());
        }
    }

    public function ajax_edit_booking() {
        try {
            // Verificar nonce
            if (!check_ajax_referer('menphis_bookings_nonce', 'nonce', false)) {
                error_log('Nonce verification failed');
                wp_send_json_error('Error de seguridad');
                return;
            }

            $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
            
            error_log('=== INICIO EDIT_BOOKING ===');
            error_log('Booking ID recibido: ' . $booking_id);
            error_log('POST data: ' . print_r($_POST, true));
            
            if (!$booking_id) {
                wp_send_json_error('ID de reserva no válido');
                return;
            }

            global $wpdb;
            
            // Obtener los datos de la reserva con los joins correctos
            $query = $wpdb->prepare("
                SELECT 
                    b.*,
                    CONCAT(mc.first_name, ' ', mc.last_name) as customer_name,
                    mc.email as customer_email,
                    mc.phone as customer_phone,
                    l.name as location_name,
                    CONCAT(us.display_name) as staff_name
                FROM {$wpdb->prefix}menphis_bookings b
                LEFT JOIN {$wpdb->prefix}menphis_customers mc ON b.customer_id = mc.id
                LEFT JOIN {$wpdb->prefix}menphis_locations l ON b.location_id = l.id
                LEFT JOIN {$wpdb->prefix}menphis_staff s ON b.staff_id = s.id
                LEFT JOIN {$wpdb->users} us ON s.wp_user_id = us.ID
                WHERE b.id = %d
            ", $booking_id);

            $booking = $wpdb->get_row($query);
            
            if (!$booking) {
                wp_send_json_error('No se encontró la reserva');
                return;
            }

            // Formatear fechas
            $booking->booking_date = date('Y-m-d', strtotime($booking->booking_date));
            $booking->booking_time = date('H:i', strtotime($booking->booking_time));

            // Obtener servicios asociados
            $services_query = $wpdb->prepare("
                SELECT 
                    bs.*,
                    p.post_title as service_name
                FROM {$wpdb->prefix}menphis_booking_services bs
                LEFT JOIN {$wpdb->posts} p ON bs.service_id = p.ID
                WHERE bs.booking_id = %d
            ", $booking_id);

            $booking->services = $wpdb->get_results($services_query);

            // Obtener listas necesarias para el formulario
            $booking->locations = $this->get_locations_list();
            $booking->staff = $this->get_staff_list();

            wp_send_json_success($booking);

        } catch (Exception $e) {
            error_log('Error en ajax_edit_booking: ' . $e->getMessage());
            wp_send_json_error('Error al procesar la solicitud');
        }
    }

    private function create_wordpress_user($order) {
        $email = $order->get_billing_email();
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        
        error_log('Creando usuario WordPress:');
        error_log("Email: $email");
        error_log("Nombre: $first_name $last_name");

        // Verificar si ya existe un usuario con este email
        $existing_user = get_user_by('email', $email);
        if ($existing_user) {
            error_log('Usuario ya existe con ID: ' . $existing_user->ID);
            return $existing_user->ID;
        }

        // Crear username desde el email
        $username = sanitize_user(current(explode('@', $email)));
        $counter = 1;
        while (username_exists($username)) {
            $username = $username . $counter;
            $counter++;
        }

        // Crear el usuario
        $random_password = wp_generate_password();
        $userdata = array(
            'user_login'    => $username,
            'user_email'    => $email,
            'user_pass'     => $random_password,
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'display_name'  => $first_name . ' ' . $last_name,
            'role'          => 'customer'
        );

        $user_id = wp_insert_user($userdata);

        if (is_wp_error($user_id)) {
            error_log('Error al crear usuario: ' . $user_id->get_error_message());
            return false;
        }

        // Enviar email con credenciales
        wp_new_user_notification($user_id, null, 'both');

        error_log('Usuario WordPress creado con ID: ' . $user_id);
        return $user_id;
    }

    public function ajax_get_user_bookings() {
        try {
            check_ajax_referer('menphis_bookings_nonce', 'nonce');

            // Verificar si el usuario está logueado
            if (!is_user_logged_in()) {
                wp_send_json_error('Usuario no autenticado');
                return;
            }

            $user_id = get_current_user_id();
            
            // Obtener el ID del cliente de nuestra tabla
            global $wpdb;
            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}menphis_customers WHERE wp_user_id = %d",
                $user_id
            ));

            if (!$customer) {
                wp_send_json_error('Cliente no encontrado');
                return;
            }

            // Obtener filtros
            $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
            
            // Construir la consulta
            $where = array(
                $wpdb->prepare("b.customer_id = %d", $customer->id)
            );

            // Aplicar filtros
            if (!empty($filters['date'])) {
                switch ($filters['date']) {
                    case 'upcoming':
                        $where[] = "b.booking_date >= CURDATE()";
                        break;
                    case 'past':
                        $where[] = "b.booking_date < CURDATE()";
                        break;
                }
            }

            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $where[] = $wpdb->prepare("b.status = %s", $filters['status']);
            }

            // Consulta principal
            $query = "
                SELECT 
                    b.*,
                    GROUP_CONCAT(p.post_title SEPARATOR ', ') as service_name,
                    l.name as location_name
                FROM {$wpdb->prefix}menphis_bookings b
                LEFT JOIN {$wpdb->prefix}menphis_booking_services bs ON b.id = bs.booking_id
                LEFT JOIN {$wpdb->posts} p ON bs.service_id = p.ID
                LEFT JOIN {$wpdb->prefix}menphis_locations l ON b.location_id = l.id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY b.id
                ORDER BY b.booking_date DESC, b.booking_time DESC";

            error_log('Query de reservas: ' . $query);
            
            $bookings = $wpdb->get_results($query);
            error_log('Reservas encontradas: ' . print_r($bookings, true));

            // Formatear fechas y datos adicionales
            foreach ($bookings as &$booking) {
                $booking->booking_date = date('Y-m-d', strtotime($booking->booking_date));
                $booking->booking_time = date('H:i', strtotime($booking->booking_time));
                
                // Agregar datos adicionales si es necesario
                $booking->can_cancel = $booking->status === 'pending' && strtotime($booking->booking_date) > time();
            }

            wp_send_json_success($bookings);

        } catch (Exception $e) {
            error_log('Error en ajax_get_user_bookings: ' . $e->getMessage());
            wp_send_json_error('Error al obtener las reservas');
        }
    }

    public function ajax_get_booking_details() {
        try {
            check_ajax_referer('menphis_bookings_nonce', 'nonce');

            $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
            
            if (!$booking_id) {
                wp_send_json_error('ID de reserva no válido');
                return;
            }

            global $wpdb;

            // Obtener detalles de la reserva con joins a todas las tablas relacionadas
            $booking = $wpdb->get_row($wpdb->prepare("
                SELECT 
                    b.*,
                    GROUP_CONCAT(p.post_title SEPARATOR ', ') as service_name,
                    l.name as location_name,
                    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                    c.email as customer_email,
                    c.phone as customer_phone
                FROM {$wpdb->prefix}menphis_bookings b
                LEFT JOIN {$wpdb->prefix}menphis_booking_services bs ON b.id = bs.booking_id
                LEFT JOIN {$wpdb->posts} p ON bs.service_id = p.ID
                LEFT JOIN {$wpdb->prefix}menphis_locations l ON b.location_id = l.id
                LEFT JOIN {$wpdb->prefix}menphis_customers c ON b.customer_id = c.id
                WHERE b.id = %d
                GROUP BY b.id
            ", $booking_id));

            if (!$booking) {
                wp_send_json_error('Reserva no encontrada');
                return;
            }

            // Verificar si el usuario actual tiene permiso para ver esta reserva
            if (!current_user_can('administrator')) {
                $customer = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}menphis_customers WHERE wp_user_id = %d",
                    get_current_user_id()
                ));

                if (!$customer || $customer->id != $booking->customer_id) {
                    wp_send_json_error('No tienes permiso para ver esta reserva');
                    return;
                }
            }

            // Formatear fechas
            $booking->booking_date = date('Y-m-d', strtotime($booking->booking_date));
            $booking->booking_time = date('H:i', strtotime($booking->booking_time));

            // Agregar datos adicionales que podrían ser útiles
            $booking->can_cancel = $booking->status === 'pending' && strtotime($booking->booking_date) > time();
            $booking->formatted_status = $this->get_status_text($booking->status);

            wp_send_json_success($booking);

        } catch (Exception $e) {
            error_log('Error en ajax_get_booking_details: ' . $e->getMessage());
            wp_send_json_error('Error al obtener los detalles de la reserva');
        }
    }

    private function get_status_text($status) {
        $status_texts = array(
            'pending' => __('Pendiente', 'menphis-reserva'),
            'confirmed' => __('Confirmada', 'menphis-reserva'),
            'completed' => __('Completada', 'menphis-reserva'),
            'cancelled' => __('Cancelada', 'menphis-reserva')
        );
        return isset($status_texts[$status]) ? $status_texts[$status] : $status;
    }
} 