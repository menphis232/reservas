<?php
if (!defined('ABSPATH')) {
    exit;
}

class Menphis_Employee_Dashboard {
    private $employee_id;

    public function __construct() {
        $this->employee_id = get_current_user_id();
        
        // Agregar menú para empleados
        add_action('admin_menu', array($this, 'add_employee_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_get_employee_bookings', array($this, 'ajax_get_employee_bookings'));
        add_action('wp_ajax_update_booking_status', array($this, 'ajax_update_booking_status'));
    }

    public function add_employee_menu() {
        if (current_user_can('menphis_employee')) {
            add_menu_page(
                __('Mis Reservas', 'menphis-reserva'),
                __('Mis Reservas', 'menphis-reserva'),
                'menphis_employee',
                'menphis-employee-dashboard',
                array($this, 'render_dashboard'),
                'dashicons-calendar-alt',
                30
            );
        }
    }

    public function render_dashboard() {
        // Obtener estadísticas
        $pending_bookings = $this->get_pending_bookings_count();
        $today_bookings = $this->get_today_bookings_count();
        $week_bookings = $this->get_week_bookings_count();

        // Incluir la vista
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/employee-dashboard.php';
    }

    private function get_pending_bookings_count() {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}menphis_bookings 
            WHERE staff_id = %d AND status = 'pending'",
            $this->employee_id
        ));
    }

    private function get_today_bookings_count() {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}menphis_bookings 
            WHERE staff_id = %d AND DATE(booking_date) = CURDATE()",
            $this->employee_id
        ));
    }

    private function get_week_bookings_count() {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}menphis_bookings 
            WHERE staff_id = %d 
            AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)",
            $this->employee_id
        ));
    }

    public function ajax_get_employee_bookings() {
        check_ajax_referer('menphis_employee_nonce', 'nonce');

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d');
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d', strtotime('+30 days'));

        global $wpdb;
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, c.first_name, c.last_name, s.post_title as service_name
            FROM {$wpdb->prefix}menphis_bookings b
            LEFT JOIN {$wpdb->prefix}menphis_customers c ON b.customer_id = c.id
            LEFT JOIN {$wpdb->posts} s ON b.service_id = s.ID
            WHERE b.staff_id = %d
            AND b.booking_date BETWEEN %s AND %s
            ORDER BY b.booking_date ASC, b.booking_time ASC",
            $this->employee_id,
            $start_date,
            $end_date
        ));

        wp_send_json_success($bookings);
    }

    public function ajax_update_booking_status() {
        check_ajax_referer('menphis_employee_nonce', 'nonce');

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (!$booking_id || !$status) {
            wp_send_json_error('Datos inválidos');
        }

        global $wpdb;
        $updated = $wpdb->update(
            $wpdb->prefix . 'menphis_bookings',
            array('status' => $status),
            array('id' => $booking_id, 'staff_id' => $this->employee_id),
            array('%s'),
            array('%d', '%d')
        );

        if ($updated) {
            wp_send_json_success('Estado actualizado correctamente');
        } else {
            wp_send_json_error('No se pudo actualizar el estado');
        }
    }
} 