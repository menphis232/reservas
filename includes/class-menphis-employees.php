<?php
if (!defined('ABSPATH')) exit;

class Menphis_Employees {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        // Registrar endpoints AJAX
        add_action('wp_ajax_menphis_save_employee', array($this, 'save_employee'));
        add_action('wp_ajax_menphis_get_employee', array($this, 'get_employee'));
        add_action('wp_ajax_menphis_delete_employee', array($this, 'delete_employee'));
    }

    public function save_employee() {
        check_ajax_referer('menphis_nonce', '_nonce');
        
        if (!current_user_can('edit_users')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }

        $employee_data = $_POST['employee_data'];
        $employee_id = isset($employee_data['id']) ? intval($employee_data['id']) : 0;

        try {
            if ($employee_id) {
                // Actualizar empleado existente
                $user_data = array(
                    'ID' => $employee_id,
                    'display_name' => sanitize_text_field($employee_data['name']),
                    'user_email' => sanitize_email($employee_data['email'])
                );
                
                $result = wp_update_user($user_data);
            } else {
                // Crear nuevo empleado
                $username = $this->generate_username($employee_data['name']);
                $password = wp_generate_password();
                
                $user_data = array(
                    'user_login' => $username,
                    'user_pass' => $password,
                    'user_email' => sanitize_email($employee_data['email']),
                    'display_name' => sanitize_text_field($employee_data['name']),
                    'role' => 'menphis_employee'
                );
                
                $result = wp_insert_user($user_data);
                
                if (!is_wp_error($result)) {
                    // Enviar email con credenciales
                    $this->send_welcome_email($result, $username, $password);
                }
            }

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            $employee_id = $result;

            // Actualizar meta datos
            update_user_meta($employee_id, 'phone', sanitize_text_field($employee_data['phone']));
            update_user_meta($employee_id, 'status', sanitize_text_field($employee_data['status']));
            update_user_meta($employee_id, 'services', array_map('intval', $employee_data['services']));
            update_user_meta($employee_id, 'notes', sanitize_textarea_field($employee_data['notes']));

            wp_send_json_success(array(
                'message' => 'Empleado guardado correctamente',
                'employee_id' => $employee_id
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    public function get_employee() {
        check_ajax_referer('menphis_nonce', '_nonce');
        
        $employee_id = intval($_GET['employee_id']);
        $employee = get_userdata($employee_id);

        if (!$employee) {
            wp_send_json_error(array('message' => 'Empleado no encontrado'));
        }

        $data = array(
            'id' => $employee->ID,
            'name' => $employee->display_name,
            'email' => $employee->user_email,
            'phone' => get_user_meta($employee_id, 'phone', true),
            'status' => get_user_meta($employee_id, 'status', true),
            'services' => get_user_meta($employee_id, 'services', true),
            'notes' => get_user_meta($employee_id, 'notes', true)
        );

        wp_send_json_success($data);
    }

    public function delete_employee() {
        check_ajax_referer('menphis_nonce', '_nonce');
        
        if (!current_user_can('delete_users')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }

        $employee_id = intval($_POST['employee_id']);
        
        // Verificar si tiene citas pendientes
        $has_bookings = $this->db->get_var($this->db->prepare(
            "SELECT COUNT(*) FROM {$this->db->prefix}menphis_bookings 
            WHERE employee_id = %d AND status = 'pending'",
            $employee_id
        ));

        if ($has_bookings > 0) {
            wp_send_json_error(array(
                'message' => 'No se puede eliminar el empleado porque tiene citas pendientes'
            ));
        }

        // Eliminar usuario
        $result = wp_delete_user($employee_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Empleado eliminado correctamente'));
        } else {
            wp_send_json_error(array('message' => 'Error al eliminar el empleado'));
        }
    }

    private function generate_username($name) {
        $base = sanitize_user(strtolower(str_replace(' ', '', $name)));
        $username = $base;
        $i = 1;
        
        while (username_exists($username)) {
            $username = $base . $i;
            $i++;
        }
        
        return $username;
    }

    private function send_welcome_email($user_id, $username, $password) {
        $user = get_userdata($user_id);
        $to = $user->user_email;
        $subject = 'Bienvenido a ' . get_bloginfo('name');
        
        $message = sprintf(
            'Hola %s,\n\n' .
            'Tu cuenta ha sido creada en %s.\n\n' .
            'Usuario: %s\n' .
            'Contraseña: %s\n\n' .
            'Por favor, cambia tu contraseña después de iniciar sesión.\n\n' .
            'Accede aquí: %s',
            $user->display_name,
            get_bloginfo('name'),
            $username,
            $password,
            wp_login_url()
        );
        
        wp_mail($to, $subject, $message);
    }
} 