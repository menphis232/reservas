<?php
if (!defined('ABSPATH')) {
    exit;
}

class Menphis_Employee_Dashboard {
    private $user_id;
    private $is_admin;

    public function __construct() {
        $this->user_id = get_current_user_id();
        $this->is_admin = current_user_can('administrator');
        
        // Agregar menú para clientes y administradores
        add_action('admin_menu', array($this, 'add_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_get_user_bookings', array($this, 'ajax_get_user_bookings'));
        add_action('wp_ajax_update_booking_status', array($this, 'ajax_update_booking_status'));
    }

    public function add_menu() {
        // Mostrar menú para administradores y clientes
        if ($this->is_admin || current_user_can('customer')) {
            add_menu_page(
                __('Mis Reservas', 'menphis-reserva'),
                __('Mis Reservas', 'menphis-reserva'),
                'read', // Permiso mínimo para ver el menú
                'menphis-bookings-dashboard',
                array($this, 'render_dashboard'),
                'dashicons-calendar-alt',
                30
            );
        }
    }

    public function render_dashboard() {
        // Si es admin, mostrar selector de clientes
        if ($this->is_admin) {
            $customers = get_users(array('role' => 'customer'));
            include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/customer-selector.php';
        }

        // Obtener estadísticas
        $pending_bookings = $this->get_pending_bookings_count();
        $today_bookings = $this->get_today_bookings_count();
        $week_bookings = $this->get_week_bookings_count();

        // Incluir la vista
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/bookings-dashboard.php';
    }

    private function get_pending_bookings_count() {
        global $wpdb;
        $where = array("status = 'pending'");
        
        // Si no es admin, filtrar por usuario actual
        if (!$this->is_admin) {
            $where[] = $wpdb->prepare("customer_id = %d", $this->user_id);
        }

        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}menphis_bookings WHERE " . implode(' AND ', $where);
        return $wpdb->get_var($query);
    }

    private function get_today_bookings_count() {
        global $wpdb;
        $where = array("DATE(booking_date) = CURDATE()");
        
        if (!$this->is_admin) {
            $where[] = $wpdb->prepare("customer_id = %d", $this->user_id);
        }

        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}menphis_bookings WHERE " . implode(' AND ', $where);
        return $wpdb->get_var($query);
    }

    private function get_week_bookings_count() {
        global $wpdb;
        $where = array("YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)");
        
        if (!$this->is_admin) {
            $where[] = $wpdb->prepare("customer_id = %d", $this->user_id);
        }

        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}menphis_bookings WHERE " . implode(' AND ', $where);
        return $wpdb->get_var($query);
    }

    public function ajax_get_user_bookings() {
        check_ajax_referer('menphis_bookings_nonce', 'nonce');

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d');
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d', strtotime('+30 days'));

        global $wpdb;
        
        $where = array(
            $wpdb->prepare("b.booking_date BETWEEN %s AND %s", $start_date, $end_date)
        );

        // Si no es admin, filtrar por usuario actual
        if (!$this->is_admin) {
            $where[] = $wpdb->prepare("b.customer_id = %d", $this->user_id);
        }

        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                b.*,
                c.first_name, 
                c.last_name,
                s.post_title as service_name,
                l.name as location_name
            FROM {$wpdb->prefix}menphis_bookings b
            LEFT JOIN {$wpdb->prefix}menphis_customers c ON b.customer_id = c.wp_user_id
            LEFT JOIN {$wpdb->posts} s ON b.service_id = s.ID
            LEFT JOIN {$wpdb->prefix}menphis_locations l ON b.location_id = l.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY b.booking_date ASC, b.booking_time ASC"
        ));

        wp_send_json_success($bookings);
    }

    public function ajax_update_booking_status() {
        check_ajax_referer('menphis_bookings_nonce', 'nonce');

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (!$booking_id || !$status) {
            wp_send_json_error('Datos inválidos');
            return;
        }

        global $wpdb;
        
        // Verificar que el usuario puede actualizar esta reserva
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}menphis_bookings WHERE id = %d",
            $booking_id
        ));

        if (!$booking || (!$this->is_admin && $booking->customer_id != $this->user_id)) {
            wp_send_json_error('No tienes permiso para actualizar esta reserva');
            return;
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'menphis_bookings',
            array('status' => $status),
            array('id' => $booking_id),
            array('%s'),
            array('%d')
        );

        if ($updated) {
            wp_send_json_success('Estado actualizado correctamente');
        } else {
            wp_send_json_error('No se pudo actualizar el estado');
        }
    }
} 