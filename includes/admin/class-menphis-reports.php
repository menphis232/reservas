<?php
class MenphisReports {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_reports_menu'));
    }

    public function add_reports_menu() {
        add_submenu_page(
            'menphis-reserva',
            'Informes',
            'Informes',
            'manage_options',
            'menphis-reports',
            array($this, 'render_reports_page')
        );
    }

    public function get_bookings_stats() {
        global $wpdb;
        return $wpdb->get_results("
            SELECT 
                COUNT(*) as total_bookings,
                SUM(total_amount) as total_revenue,
                DATE(created_at) as booking_date
            FROM {$wpdb->prefix}menphis_bookings
            GROUP BY DATE(created_at)
            ORDER BY booking_date DESC
            LIMIT 30
        ");
    }
} 