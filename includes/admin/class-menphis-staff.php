<?php
if (!defined('ABSPATH')) exit;

class MenphisStaff {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;

        // Agregar los hooks de AJAX
        add_action('wp_ajax_add_staff', array($this, 'ajax_add_staff'));
        add_action('wp_ajax_update_staff', array($this, 'ajax_update_staff'));
        add_action('wp_ajax_get_staff', array($this, 'ajax_get_staff'));
        add_action('wp_ajax_delete_staff', array($this, 'ajax_delete_staff'));
        add_action('wp_ajax_get_staff_list', array($this, 'ajax_get_staff_list'));
        add_action('wp_ajax_save_staff_schedule', array($this, 'ajax_save_staff_schedule'));
        add_action('wp_ajax_get_staff_schedule', array($this, 'ajax_get_staff_schedule'));
    }

    public function get_staff_list() {
        $sql = "SELECT * FROM {$this->db->prefix}menphis_staff ORDER BY id DESC";
        return $this->db->get_results($sql);
    }

    public function get_staff($id) {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->db->prefix}menphis_staff WHERE id = %d",
            $id
        );
        return $this->db->get_row($sql);
    }

    public function add_staff($data) {
        $result = $this->db->insert(
            $this->db->prefix . 'menphis_staff',
            array(
                'wp_user_id' => $data['wp_user_id'],
                'phone' => $data['phone'],
                'services' => json_encode($data['services'] ?: []),
                'working_hours' => json_encode($data['working_hours'] ?: []),
                'locations' => json_encode($data['locations'] ?: []),
                'status' => 'active'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            throw new Exception('Error al crear el empleado');
        }

        return $this->db->insert_id;
    }

    public function update_staff($id, $data) {
        try {
            $update_data = array();
            $format = array();

            if (isset($data['phone'])) {
                $update_data['phone'] = $data['phone'];
                $format[] = '%s';
            }

            if (isset($data['services'])) {
                // Asegurarnos de que services es un array
                $services = is_array($data['services']) ? $data['services'] : [];
                $update_data['services'] = json_encode(array_map('intval', $services));
                error_log('Guardando servicios: ' . $update_data['services']);
                $format[] = '%s';
            }

            if (isset($data['locations'])) {
                // Asegurarnos de que locations es un array
                $locations = is_array($data['locations']) ? $data['locations'] : [];
                $update_data['locations'] = json_encode(array_map('intval', $locations));
                error_log('Guardando ubicaciones: ' . $update_data['locations']);
                $format[] = '%s';
            }

            error_log('Actualizando staff con datos: ' . print_r($update_data, true));

            if (empty($update_data)) {
                return false;
            }

            $result = $this->db->update(
                $this->db->prefix . 'menphis_staff',
                $update_data,
                array('id' => $id),
                $format,
                array('%d')
            );

            if ($result === false) {
                error_log('Error al actualizar: ' . $this->db->last_error);
                throw new Exception('Error al actualizar el empleado en la base de datos');
            }

            // Verificar los datos guardados
            $staff = $this->get_staff($id);
            error_log('Datos después de actualizar: ' . print_r($staff, true));

            return true;
        } catch (Exception $e) {
            error_log('Error en update_staff: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete_staff($id) {
        $result = $this->db->delete(
            $this->db->prefix . 'menphis_staff',
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            throw new Exception('Error al eliminar el empleado');
        }

        return true;
    }

    public function get_services() {
        // Obtener servicios de WooCommerce con meta _is_service = 'yes'
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_is_service',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        );
        return get_posts($args);
    }

    public function get_locations() {
        return $this->db->get_results("SELECT * FROM {$this->db->prefix}menphis_locations WHERE status = 'active'");
    }

    // Métodos AJAX
    public function ajax_add_staff() {
        try {
            check_ajax_referer('menphis_staff_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
            $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
            $services = isset($_POST['services']) ? array_map('intval', $_POST['services']) : array();
            $locations = isset($_POST['locations']) ? array_map('intval', $_POST['locations']) : array();

            if (empty($name) || empty($email)) {
                wp_send_json_error('Nombre y email son requeridos');
                return;
            }

            // Crear usuario de WordPress
            $user_id = wp_create_user($email, wp_generate_password(), $email);
            if (is_wp_error($user_id)) {
                wp_send_json_error($user_id->get_error_message());
                return;
            }

            // Asignar rol de empleado
            $user = new WP_User($user_id);
            $user->set_role('menphis_employee');

            // Actualizar nombre
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $name,
                'first_name' => $name
            ));

            // Crear registro de empleado
            $staff_id = $this->add_staff(array(
                'wp_user_id' => $user_id,
                'phone' => $phone,
                'services' => $services,
                'working_hours' => array(),
                'locations' => $locations
            ));

            wp_send_json_success(array(
                'message' => 'Empleado creado correctamente',
                'staff_id' => $staff_id
            ));

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function render_staff_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }

        // Obtener servicios y ubicaciones antes de cargar la vista
        $services = $this->get_services();
        $locations = $this->get_locations();
        
        // Incluir la vista pasando las variables necesarias
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/staff.php';
    }

    public function ajax_get_staff_list() {
        try {
            check_ajax_referer('menphis_staff_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $staff_list = $this->get_staff_list();
            
            // Debug
            error_log('Staff list raw: ' . print_r($staff_list, true));
            
            // Formatear la lista para la tabla
            $formatted_list = array_map(function($staff) {
                $services = json_decode($staff->services, true);
                $locations = json_decode($staff->locations, true);
                
                error_log('Staff ID ' . $staff->id . ' services: ' . print_r($services, true));
                
                return array(
                    'id' => $staff->id,
                    'name' => get_userdata($staff->wp_user_id)->display_name,
                    'email' => get_userdata($staff->wp_user_id)->user_email,
                    'phone' => $staff->phone,
                    'services' => $this->format_services($services ?: []),
                    'locations' => $this->format_locations($locations ?: []),
                    'status' => $staff->status,
                    'actions' => $this->get_staff_actions_html($staff)
                );
            }, $staff_list);

            wp_send_json_success($formatted_list);

        } catch (Exception $e) {
            error_log('Error al obtener la lista de personal: ' . $e->getMessage());
            wp_send_json_error('Error al obtener la lista de personal: ' . $e->getMessage());
        }
    }

    private function format_services($services) {
        if (empty($services)) return '-';
        
        $service_names = array();
        foreach ($services as $service_id) {
            $service = get_post($service_id);
            if ($service) {
                $service_names[] = $service->post_title;
            }
        }
        
        error_log('Service names: ' . print_r($service_names, true));
        return !empty($service_names) ? implode(', ', $service_names) : '-';
    }

    private function format_locations($locations) {
        if (empty($locations)) return '-';
        
        $location_names = array();
        foreach ($locations as $location_id) {
            $location = $this->db->get_row($this->db->prepare(
                "SELECT name FROM {$this->db->prefix}menphis_locations WHERE id = %d",
                $location_id
            ));
            if ($location) {
                $location_names[] = $location->name;
            }
        }
        
        return !empty($location_names) ? implode(', ', $location_names) : '-';
    }

    private function get_staff_actions_html($staff) {
        $actions = '<div class="action-buttons">';
        
        // Botón de horario
        $actions .= sprintf(
            '<button class="btn-floating btn-small waves-effect waves-light green schedule-staff" data-id="%d" title="Horario"><i class="material-icons">schedule</i></button>',
            $staff->id
        );
        $actions .= '&nbsp;';
        
        // Botón editar
        $actions .= sprintf(
            '<button class="btn-floating btn-small waves-effect waves-light blue edit-staff" data-id="%d" title="Editar"><i class="material-icons">edit</i></button>',
            $staff->id
        );
        $actions .= '&nbsp;';
        
        // Botón eliminar
        $actions .= sprintf(
            '<button class="btn-floating btn-small waves-effect waves-light red delete-staff" data-id="%d" title="Eliminar"><i class="material-icons">delete</i></button>',
            $staff->id
        );
        
        $actions .= '</div>';
        return $actions;
    }

    public function ajax_get_staff() {
        try {
            check_ajax_referer('menphis_staff_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                wp_send_json_error('ID de empleado inválido');
                return;
            }

            $staff = $this->get_staff($id);
            if (!$staff) {
                wp_send_json_error('Empleado no encontrado');
                return;
            }

            // Obtener datos del usuario de WordPress
            $user_data = get_userdata($staff->wp_user_id);
            if (!$user_data) {
                wp_send_json_error('Usuario no encontrado');
                return;
            }

            // Formatear la respuesta
            $response = array(
                'id' => $staff->id,
                'name' => $user_data->display_name,
                'email' => $user_data->user_email,
                'phone' => $staff->phone,
                'services' => maybe_unserialize($staff->services),
                'locations' => maybe_unserialize($staff->locations),
                'status' => $staff->status
            );

            wp_send_json_success($response);

        } catch (Exception $e) {
            error_log('Error en ajax_get_staff: ' . $e->getMessage());
            wp_send_json_error('Error al obtener datos del empleado: ' . $e->getMessage());
        }
    }

    public function ajax_update_staff() {
        try {
            check_ajax_referer('menphis_staff_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            // Validar datos requeridos
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
            $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
            $services = isset($_POST['services']) ? array_map('intval', (array)$_POST['services']) : array();
            $locations = isset($_POST['locations']) ? array_map('intval', (array)$_POST['locations']) : array();

            if (!$id || empty($name) || empty($email)) {
                wp_send_json_error('Datos incompletos');
                return;
            }

            // Obtener el staff actual
            $staff = $this->get_staff($id);
            if (!$staff) {
                wp_send_json_error('Empleado no encontrado');
                return;
            }

            // Actualizar datos del usuario de WordPress
            $user_data = array(
                'ID' => $staff->wp_user_id,
                'display_name' => $name,
                'user_email' => $email
            );

            $user_id = wp_update_user($user_data);
            if (is_wp_error($user_id)) {
                wp_send_json_error($user_id->get_error_message());
                return;
            }

            // Actualizar datos en la tabla de staff
            $result = $this->update_staff($id, array(
                'phone' => $phone,
                'services' => $services,
                'locations' => $locations
            ));

            if ($result) {
                wp_send_json_success(array(
                    'message' => 'Empleado actualizado correctamente',
                    'staff_id' => $id
                ));
            } else {
                wp_send_json_error('Error al actualizar el empleado');
            }

        } catch (Exception $e) {
            error_log('Error en ajax_update_staff: ' . $e->getMessage());
            wp_send_json_error('Error al actualizar el empleado: ' . $e->getMessage());
        }
    }

    public function ajax_save_staff_schedule() {
        try {
            check_ajax_referer('menphis_staff_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
            $days = isset($_POST['days']) ? $_POST['days'] : array();

            error_log('Guardando horarios para staff_id: ' . $staff_id);
            error_log('Datos recibidos: ' . print_r($days, true));

            if (!$staff_id || empty($days)) {
                wp_send_json_error('Datos incompletos');
                return;
            }

            // Eliminar horarios existentes
            $this->db->delete(
                $this->db->prefix . 'menphis_staff_schedules',
                array('staff_id' => $staff_id),
                array('%d')
            );

            // Insertar nuevos horarios
            foreach ($days as $day_num => $schedule) {
                if (!isset($schedule['enabled']) || $schedule['enabled'] !== 'on') {
                    continue;
                }

                // Validar que las horas no estén vacías
                if (empty($schedule['start_time']) || empty($schedule['end_time'])) {
                    wp_send_json_error('Debe especificar hora de inicio y fin para los días seleccionados');
                    return;
                }

                // Convertir a formato 24h si es necesario
                $start_time = date('H:i:s', strtotime($schedule['start_time']));
                $end_time = date('H:i:s', strtotime($schedule['end_time']));

                error_log("Procesando día $day_num: " . print_r([
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'break_enabled' => isset($schedule['break_enabled']) ? 'sí' : 'no'
                ], true));

                // Insertar horario principal
                $result = $this->db->insert(
                    $this->db->prefix . 'menphis_staff_schedules',
                    array(
                        'staff_id' => $staff_id,
                        'day_of_week' => $day_num,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'is_break' => 0,
                        'status' => 'active'
                    ),
                    array('%d', '%d', '%s', '%s', '%d', '%s')
                );

                if ($result === false) {
                    error_log("Error al insertar horario principal: " . $this->db->last_error);
                    throw new Exception('Error al guardar horario principal');
                }

                // Insertar horario de descanso solo si está habilitado y tiene horas válidas
                if (isset($schedule['break_enabled']) && $schedule['break_enabled'] === 'on') {
                    if (!empty($schedule['break_start']) && !empty($schedule['break_end'])) {
                        $break_start = date('H:i:s', strtotime($schedule['break_start']));
                        $break_end = date('H:i:s', strtotime($schedule['break_end']));

                        error_log("Insertando descanso para día $day_num: " . print_r([
                            'break_start' => $break_start,
                            'break_end' => $break_end
                        ], true));

                        $result = $this->db->insert(
                            $this->db->prefix . 'menphis_staff_schedules',
                            array(
                                'staff_id' => $staff_id,
                                'day_of_week' => $day_num,
                                'start_time' => $break_start,
                                'end_time' => $break_end,
                                'is_break' => 1,
                                'status' => 'active'
                            ),
                            array('%d', '%d', '%s', '%s', '%d', '%s')
                        );

                        if ($result === false) {
                            error_log("Error al insertar horario de descanso: " . $this->db->last_error);
                            throw new Exception('Error al guardar horario de descanso');
                        }
                    }
                }
            }

            // Verificar los horarios guardados
            $saved_schedules = $this->db->get_results($this->db->prepare(
                "SELECT * FROM {$this->db->prefix}menphis_staff_schedules WHERE staff_id = %d",
                $staff_id
            ));
            error_log("Horarios guardados: " . print_r($saved_schedules, true));

            wp_send_json_success('Horario guardado correctamente');

        } catch (Exception $e) {
            error_log('Error al guardar horario: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            wp_send_json_error('Error al guardar horario: ' . $e->getMessage());
        }
    }

    public function ajax_get_staff_schedule() {
        try {
            check_ajax_referer('menphis_staff_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
            if (!$staff_id) {
                wp_send_json_error('ID de empleado inválido');
                return;
            }

            // Obtener horarios
            $schedules = $this->db->get_results($this->db->prepare(
                "SELECT * FROM {$this->db->prefix}menphis_staff_schedules 
                WHERE staff_id = %d AND status = 'active'
                ORDER BY day_of_week ASC, is_break ASC",
                $staff_id
            ));

            wp_send_json_success($schedules);

        } catch (Exception $e) {
            wp_send_json_error('Error al obtener horario: ' . $e->getMessage());
        }
    }
} 