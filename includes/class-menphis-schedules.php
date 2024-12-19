<?php
if (!defined('ABSPATH')) exit;

class Menphis_Schedules {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        // Registrar endpoints AJAX
        add_action('wp_ajax_menphis_save_schedule', array($this, 'save_schedule'));
        add_action('wp_ajax_menphis_get_employee_schedule', array($this, 'get_employee_schedule'));
    }

    public function save_schedule() {
        check_ajax_referer('menphis_nonce', '_nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }

        $employee_id = intval($_POST['employee_id']);
        $schedules = $_POST['schedules'];

        $this->db->query('START TRANSACTION');

        try {
            // Primero desactivamos todos los horarios existentes
            $this->db->update(
                $this->db->prefix . 'menphis_schedules',
                array('active' => 0),
                array('employee_id' => $employee_id),
                array('%d'),
                array('%d')
            );

            // Insertamos los nuevos horarios
            foreach ($schedules as $day => $schedule) {
                if (!empty($schedule['enabled'])) {
                    $this->db->insert(
                        $this->db->prefix . 'menphis_schedules',
                        array(
                            'employee_id' => $employee_id,
                            'day_of_week' => $day,
                            'start_time' => $schedule['start'],
                            'end_time' => $schedule['end'],
                            'break_start' => !empty($schedule['break_start']) ? $schedule['break_start'] : null,
                            'break_end' => !empty($schedule['break_end']) ? $schedule['break_end'] : null,
                            'active' => 1
                        ),
                        array('%d', '%d', '%s', '%s', '%s', '%s', '%d')
                    );
                }
            }

            $this->db->query('COMMIT');
            wp_send_json_success(array('message' => 'Horario guardado correctamente'));

        } catch (Exception $e) {
            $this->db->query('ROLLBACK');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    public function get_employee_schedule() {
        check_ajax_referer('menphis_nonce', '_nonce');
        
        $employee_id = intval($_GET['employee_id']);
        
        $schedules = $this->db->get_results($this->db->prepare(
            "SELECT * FROM {$this->db->prefix}menphis_schedules 
            WHERE employee_id = %d AND active = 1",
            $employee_id
        ));

        $formatted_schedules = array();
        foreach ($schedules as $schedule) {
            $formatted_schedules[$schedule->day_of_week] = array(
                'enabled' => true,
                'start' => $schedule->start_time,
                'end' => $schedule->end_time,
                'break_start' => $schedule->break_start,
                'break_end' => $schedule->break_end
            );
        }

        wp_send_json_success($formatted_schedules);
    }
} 