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
        add_action('wp_ajax_get_booking', array($this, 'ajax_get_booking'));
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
    }

    public function enqueue_booking_scripts() {
        wp_localize_script('menphis-booking', 'menphisBooking', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'checkout_url' => wc_get_checkout_url(),
            'nonce' => wp_create_nonce('menphis_booking_nonce')
        ));
    }

    public function render_bookings_page() {
        static $call_count = 0;
        $call_count++;
        error_log("=== INICIO RENDER BOOKINGS PAGE (llamada #$call_count) ===");
        
        try {
            global $wpdb;

            error_log('Ejecutando consulta de reservas...');
            $query = "
                SELECT 
                    p.ID,
                    p.post_date,
                    p.post_status,
                    MAX(CASE WHEN pm.meta_key = '_customer_name' THEN pm.meta_value END) as customer_name,
                    MAX(CASE WHEN pm.meta_key = '_location_id' THEN pm.meta_value END) as location_id,
                    MAX(CASE WHEN pm.meta_key = '_booking_date' THEN pm.meta_value END) as booking_date,
                    MAX(CASE WHEN pm.meta_key = '_booking_time' THEN pm.meta_value END) as booking_time,
                    MAX(CASE WHEN pm.meta_key = '_assigned_staff' THEN pm.meta_value END) as assigned_staff
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'menphis_booking'
                AND p.post_status IN ('publish', 'pending')
                GROUP BY p.ID
                ORDER BY STR_TO_DATE(MAX(CASE WHEN pm.meta_key = '_booking_date' THEN pm.meta_value END), '%Y-%m-%d') DESC,
                         MAX(CASE WHEN pm.meta_key = '_booking_time' THEN pm.meta_value END) DESC";

            $results = $wpdb->get_results($query);
            error_log('Resultados de la consulta: ' . print_r($results, true));

            $bookings = array();
            foreach ($results as $result) {
                // Obtener nombre de la ubicación
                $location_name = '';
                if (!empty($result->location_id)) {
                    $location = $wpdb->get_row($wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}menphis_locations WHERE id = %d",
                        $result->location_id
                    ));
                    $location_name = $location ? $location->name : '';
                }

                // Obtener nombre del empleado
                $staff_name = 'Sin asignar';
                if (!empty($result->assigned_staff)) {
                    $assigned_staff = maybe_unserialize($result->assigned_staff);
                    
                    if (is_array($assigned_staff)) {
                        $staff_ids = array_map(function($staff) {
                            return is_array($staff) ? $staff['id'] : $staff;
                        }, $assigned_staff);
                        
                        $staff_query = $wpdb->prepare(
                            "SELECT GROUP_CONCAT(u.display_name SEPARATOR ', ') as names
                            FROM {$wpdb->prefix}menphis_staff ms
                            JOIN {$wpdb->users} u ON ms.wp_user_id = u.ID
                            WHERE ms.id IN (" . implode(',', array_fill(0, count($staff_ids), '%d')) . ")",
                            $staff_ids
                        );
                        
                        $staff_name = $wpdb->get_var($staff_query) ?: 'N/A';
                    } else {
                        error_log('El valor de assigned_staff no es un array: ' . print_r($assigned_staff, true));
                    }
                }

                // Formatear fecha y hora
                $booking_date = !empty($result->booking_date) ? date('d/m/Y', strtotime($result->booking_date)) : '';
                $booking_time = !empty($result->booking_time) ? date('H:i', strtotime($result->booking_time)) : '';

                $bookings[] = (object) array(
                    'ID' => $result->ID,
                    'customer_name' => $result->customer_name ?: 'Sin nombre',
                    'location_name' => $location_name ?: 'Sin ubicación',
                    'booking_date' => $booking_date,
                    'booking_time' => $booking_time,
                    'staff_name' => $staff_name
                );
            }

            error_log('Reservas procesadas: ' . print_r($bookings, true));

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
            
            // Construir argumentos de consulta
            $args = array(
                'post_type' => 'menphis_booking',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => array()
            );

            // Aplicar filtros
            if (!empty($filters['booking_id'])) {
                $args['p'] = intval($filters['booking_id']);
            }

            if (!empty($filters['date_from'])) {
                $args['meta_query'][] = array(
                    'key' => '_booking_date',
                    'value' => $filters['date_from'],
                    'compare' => '>='
                );
            }

            if (!empty($filters['date_to'])) {
                $args['meta_query'][] = array(
                    'key' => '_booking_date',
                    'value' => $filters['date_to'],
                    'compare' => '<='
                );
            }

            // Obtener reservas
            $bookings = get_posts($args);
            $formatted_bookings = array();

            foreach ($bookings as $booking) {
                // Obtener datos de la ubicación
                $location_id = get_post_meta($booking->ID, '_location_id', true);
                $location_name = '';
                
                // Obtener el nombre de la ubicación desde la tabla menphis_locations
                if ($location_id) {
                    global $wpdb;
                    $location_name = $wpdb->get_var($wpdb->prepare(
                        "SELECT name FROM {$wpdb->prefix}menphis_locations WHERE id = %d",
                        $location_id
                    ));
                }

                // Obtener datos del personal asignado
                $assigned_staff = get_post_meta($booking->ID, '_assigned_staff', true);
                $staff_name = '';
                if ($assigned_staff && !empty($assigned_staff[0])) {
                    $staff_user = get_userdata($assigned_staff[0]);
                    $staff_name = $staff_user ? $staff_user->display_name : 'N/A';
                }

                // Debug
                error_log('Datos de reserva ' . $booking->ID . ': ' . print_r([
                    'location_id' => $location_id,
                    'location_name' => $location_name,
                    'staff' => $assigned_staff,
                    'staff_name' => $staff_name
                ], true));

                $formatted_bookings[] = array(
                    'ID' => $booking->ID,
                    'customer_name' => get_post_meta($booking->ID, '_customer_name', true),
                    'location_name' => $location_name,
                    'booking_date' => get_post_meta($booking->ID, '_booking_date', true),
                    'booking_time' => get_post_meta($booking->ID, '_booking_time', true),
                    'staff_name' => $staff_name
                );
            }

            wp_send_json_success($formatted_bookings);

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

    private function get_status_text($status) {
        $status_texts = array(
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmada',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada'
        );
        return isset($status_texts[$status]) ? $status_texts[$status] : $status;
    }

    private function get_booking_actions_html($booking) {
        $actions = '<div class="action-buttons">';
        $actions .= sprintf(
            '<button class="btn-floating btn-small waves-effect waves-light blue edit-booking" data-id="%d" title="Editar"><i class="material-icons">edit</i></button>',
            $booking->id
        );
        $actions .= '&nbsp;';
        $actions .= sprintf(
            '<button class="btn-floating btn-small waves-effect waves-light red delete-booking" data-id="%d" title="Eliminar"><i class="material-icons">delete</i></button>',
            $booking->id
        );
        $actions .= '</div>';
        return $actions;
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
            
            // Obtener datos de la sesión
            $booking_data = WC()->session->get('booking_data');
            error_log('Datos de reserva en sesión: ' . print_r($booking_data, true));

            if (empty($booking_data)) {
                error_log('No hay datos de reserva en la sesión');
                return;
            }

            // Guardar datos en la orden
            update_post_meta($order_id, '_booking_location', $booking_data['location_id']);
            update_post_meta($order_id, '_booking_date', $booking_data['booking_date']);
            update_post_meta($order_id, '_booking_time', $booking_data['booking_time']);
            update_post_meta($order_id, '_services', $booking_data['services']);
            if (!empty($booking_data['available_staff'])) {
                update_post_meta($order_id, '_available_staff', $booking_data['available_staff']);
            }

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
    public function create_booking_from_order($order_id) {
        try {
            error_log('=== INICIO CREATE_BOOKING_FROM_ORDER ===');
            error_log('Order ID: ' . $order_id);
            
            $order = wc_get_order($order_id);
            if (!$order) {
                throw new Exception('Orden no encontrada');
            }

            // Obtener datos de la sesión
            $booking_data = WC()->session->get('booking_data');
            error_log('Datos de reserva en sesión: ' . print_r($booking_data, true));

            // Obtener datos de la orden
            $location_id = get_post_meta($order_id, '_booking_location', true);
            $booking_date = get_post_meta($order_id, '_booking_date', true);
            $booking_time = get_post_meta($order_id, '_booking_time', true);

            // Usar datos de la sesión si están disponibles, si no, usar los de la orden
            $location = !empty($booking_data['location']) ? $booking_data['location'] : $location_id;
            $date = !empty($booking_data['date']) ? $booking_data['date'] : $booking_date;
            $time = !empty($booking_data['time']) ? $booking_data['time'] : $booking_time;

            if (!$location || !$date || !$time) {
                throw new Exception('Faltan datos necesarios para la reserva');
            }

            // Crear la reserva
            $booking_args = array(
                'post_title'  => 'Reserva para orden #' . $order_id,
                'post_type'   => 'menphis_booking',
                'post_status' => 'publish',
                'meta_input'  => array(
                    '_order_id'       => $order_id,
                    '_location_id'    => $location,
                    '_booking_date'   => $date,
                    '_booking_time'   => $time,
                    '_customer_id'    => $order->get_customer_id(),
                    '_customer_name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    '_customer_email' => $order->get_billing_email(),
                    '_customer_phone' => $order->get_billing_phone(),
                    '_assigned_staff' => !empty($booking_data['available_staff']) ? $booking_data['available_staff'] : array()
                )
            );

            error_log('Intentando crear reserva con datos: ' . print_r($booking_args, true));

            // Insertar la reserva
            $booking_id = wp_insert_post($booking_args);

            if (is_wp_error($booking_id)) {
                throw new Exception('Error al crear la reserva: ' . $booking_id->get_error_message());
            }

            error_log('Reserva creada con ID: ' . $booking_id);

            // Limpiar datos de sesión
            WC()->session->set('booking_data', null);

            // Agregar nota a la orden
            $order->add_order_note(sprintf('Reserva creada exitosamente (ID: %s)', $booking_id));
            $order->save();

            error_log('=== FIN CREATE_BOOKING_FROM_ORDER ===');

        } catch (Exception $e) {
            error_log('ERROR en create_booking_from_order: ' . $e->getMessage());
            if (isset($order)) {
                $order->add_order_note('Error al crear la reserva: ' . $e->getMessage());
                $order->save();
            }
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
        $product = wc_get_product($product_id);
        if (!$product) return false;

        $service_id = $product->get_meta('_service_id');
        return !empty($service_id);
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
        check_ajax_referer('menphis_booking_nonce', 'nonce');

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        
        if (!$booking_id) {
            wp_send_json_error('ID de reserva no válido');
            return;
        }

        // Obtener los datos de la reserva
        $booking = $this->get_booking($booking_id);
        
        if ($booking) {
            wp_send_json_success($booking);
        } else {
            wp_send_json_error('No se encontró la reserva');
        }
    }

    public function enqueue_admin_scripts() {
        // ... otros scripts ...

        // Pasar datos al JavaScript
        wp_localize_script('menphis-bookings', 'menphisBookings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('menphis_booking_nonce') // Asegúrate de usar el mismo nombre de nonce
        ));
    }

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_bookings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            staff_id bigint(20) NOT NULL,
            location_id bigint(20) NOT NULL,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            duration int(11) NOT NULL,
            status varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            order_id bigint(20) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 