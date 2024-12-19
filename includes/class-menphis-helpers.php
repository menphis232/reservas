<?php
if (!defined('ABSPATH')) exit;

class Menphis_Helpers {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function get_employees_options() {
        $employees = get_users(array(
            'role' => 'menphis_employee',
            'orderby' => 'display_name'
        ));

        $options = '';
        foreach ($employees as $employee) {
            $options .= sprintf(
                '<option value="%d">%s</option>',
                $employee->ID,
                esc_html($employee->display_name)
            );
        }
        return $options;
    }

    public function get_services_options() {
        $services = $this->db->get_results(
            "SELECT id, name, duration, price 
            FROM {$this->db->prefix}menphis_services 
            WHERE active = 1 
            ORDER BY name ASC"
        );

        $options = '';
        foreach ($services as $service) {
            $options .= sprintf(
                '<option value="%d" data-duration="%d" data-price="%.2f">%s</option>',
                $service->id,
                $service->duration,
                $service->price,
                esc_html($service->name)
            );
        }
        return $options;
    }

    public function get_locations_options() {
        $locations = $this->db->get_results(
            "SELECT id, name 
            FROM {$this->db->prefix}menphis_locations 
            WHERE active = 1 
            ORDER BY name ASC"
        );

        $options = '';
        foreach ($locations as $location) {
            $options .= sprintf(
                '<option value="%d">%s</option>',
                $location->id,
                esc_html($location->name)
            );
        }
        return $options;
    }

    public function get_available_time_slots($date, $employee_id, $service_ids) {
        // Obtener duración total de los servicios
        $total_duration = $this->db->get_var($this->db->prepare(
            "SELECT SUM(duration) 
            FROM {$this->db->prefix}menphis_services 
            WHERE id IN (" . implode(',', array_map('intval', $service_ids)) . ")"
        ));

        // Obtener horario del empleado
        $schedule = $this->get_employee_schedule($employee_id, date('w', strtotime($date)));
        if (empty($schedule)) {
            return array();
        }

        // Obtener citas existentes
        $existing_bookings = $this->db->get_results($this->db->prepare(
            "SELECT time, duration 
            FROM {$this->db->prefix}menphis_bookings b
            JOIN {$this->db->prefix}menphis_booking_services bs ON b.id = bs.booking_id
            JOIN {$this->db->prefix}menphis_services s ON bs.service_id = s.id
            WHERE DATE(date) = %s AND employee_id = %d
            AND status != 'cancelled'",
            $date,
            $employee_id
        ));

        // Generar slots disponibles
        $available_slots = array();
        $current_time = strtotime($schedule->start_time);
        $end_time = strtotime($schedule->end_time);

        while ($current_time + ($total_duration * 60) <= $end_time) {
            $slot_available = true;
            foreach ($existing_bookings as $booking) {
                $booking_start = strtotime($booking->time);
                $booking_end = $booking_start + ($booking->duration * 60);
                
                if (
                    ($current_time >= $booking_start && $current_time < $booking_end) ||
                    ($current_time + ($total_duration * 60) > $booking_start && $current_time + ($total_duration * 60) <= $booking_end)
                ) {
                    $slot_available = false;
                    break;
                }
            }

            if ($slot_available) {
                $available_slots[] = date('H:i', $current_time);
            }
            
            $current_time += 900; // Incrementar en 15 minutos
        }

        return $available_slots;
    }

    private function get_employee_schedule($employee_id, $day_of_week) {
        return $this->db->get_row($this->db->prepare(
            "SELECT start_time, end_time 
            FROM {$this->db->prefix}menphis_schedules 
            WHERE employee_id = %d AND day_of_week = %d",
            $employee_id,
            $day_of_week
        ));
    }

    public function format_price($price) {
        return number_format($price, 2, '.', ',') . ' €';
    }

    public function format_duration($minutes) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return sprintf('%dh %02dmin', $hours, $mins);
        }
        return sprintf('%d min', $mins);
    }
} 