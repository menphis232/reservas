<?php
class MenphisNotifications {
    public function __construct() {
        add_action('menphis_booking_created', array($this, 'send_booking_confirmation'));
        add_action('menphis_booking_updated', array($this, 'send_booking_update'));
        add_action('menphis_booking_cancelled', array($this, 'send_booking_cancellation'));
        add_action('menphis_booking_created', array($this, 'notify_staff'));
    }

    public function send_booking_confirmation($booking_id) {
        $booking = $this->get_booking($booking_id);
        $customer = $this->get_customer($booking->customer_id);
        
        $subject = 'Confirmación de tu reserva';
        $message = $this->get_email_template('booking-confirmation', array(
            'booking' => $booking,
            'customer' => $customer
        ));
        
        $this->send_email($customer->email, $subject, $message);
    }

    public function notify_staff($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, s.wp_user_id, l.name as location_name, sv.post_title as service_name
            FROM {$wpdb->prefix}menphis_bookings b
            INNER JOIN {$wpdb->prefix}menphis_staff s ON s.id = b.staff_id
            INNER JOIN {$wpdb->prefix}menphis_locations l ON l.id = b.location_id
            INNER JOIN {$wpdb->posts} sv ON sv.ID = b.service_id
            WHERE b.id = %d
        ", $booking_id));

        if (!$booking) {
            return;
        }

        // Obtener email del staff
        $staff_user = get_userdata($booking->wp_user_id);
        if (!$staff_user) {
            return;
        }

        $subject = 'Nueva cita asignada';
        $message = $this->get_email_template('staff-booking-notification', array(
            'booking' => $booking,
            'staff_name' => $staff_user->display_name
        ));

        // Enviar email
        $this->send_email($staff_user->user_email, $subject, $message);

        // Opcional: Enviar SMS o notificación push
        $this->send_sms_notification($booking);
    }

    private function get_email_template($template, $data) {
        ob_start();
        include MENPHIS_RESERVA_PLUGIN_DIR . 'templates/emails/' . $template . '.php';
        return ob_get_clean();
    }

    private function send_sms_notification($booking) {
        // Implementar integración con servicio SMS
    }
} 