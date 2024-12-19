<?php
if (!defined('ABSPATH')) exit;

class MenphisLocations {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;

        // Agregar los hooks de AJAX
        add_action('wp_ajax_add_location', array($this, 'ajax_add_location'));
        add_action('wp_ajax_update_location', array($this, 'ajax_update_location'));
        add_action('wp_ajax_get_locations', array($this, 'ajax_get_locations'));
        add_action('wp_ajax_get_location', array($this, 'ajax_get_location'));
        add_action('wp_ajax_delete_location', array($this, 'ajax_delete_location'));
    }

    public function get_locations() {
        $sql = "SELECT * FROM {$this->db->prefix}menphis_locations ORDER BY name ASC";
        return $this->db->get_results($sql);
    }

    public function get_location($id) {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->db->prefix}menphis_locations WHERE id = %d",
            $id
        );
        return $this->db->get_row($sql);
    }

    public function add_location($data) {
        $result = $this->db->insert(
            $this->db->prefix . 'menphis_locations',
            array(
                'name' => $data['name'],
                'address' => $data['address'],
                'phone' => $data['phone'],
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            throw new Exception('Error al crear la ubicación');
        }

        return $this->db->insert_id;
    }

    public function update_location($id, $data) {
        $result = $this->db->update(
            $this->db->prefix . 'menphis_locations',
            array(
                'name' => $data['name'],
                'address' => $data['address'],
                'phone' => $data['phone']
            ),
            array('id' => $id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            throw new Exception('Error al actualizar la ubicación');
        }

        return true;
    }

    public function delete_location($id) {
        $result = $this->db->delete(
            $this->db->prefix . 'menphis_locations',
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            throw new Exception('Error al eliminar la ubicación');
        }

        return true;
    }

    public function render_locations_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/locations.php';
    }

    // Métodos AJAX
    public function ajax_add_location() {
        try {
            error_log('=== Inicio de ajax_add_location ===');
            error_log('POST recibido: ' . print_r($_POST, true));
            error_log('Nonce recibido: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'no hay nonce'));
            
            // Verificar nonce
            check_ajax_referer('menphis_locations_nonce', 'nonce');
            error_log('Nonce verificado correctamente');

            // Verificar permisos
            if (!current_user_can('manage_options')) {
                error_log('Usuario sin permisos suficientes');
                wp_send_json_error('No tienes permisos para realizar esta acción', 403);
                return;
            }

            $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
            $address = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

            error_log('Datos procesados: ' . print_r(array(
                'name' => $name,
                'address' => $address,
                'phone' => $phone
            ), true));

            if (empty($name)) {
                error_log('Error: Nombre vacío');
                wp_send_json_error('El nombre es requerido');
                return;
            }

            $location_id = $this->add_location(array(
                'name' => $name,
                'address' => $address,
                'phone' => $phone
            ));

            error_log('Ubicación creada con ID: ' . $location_id);
            wp_send_json_success(array(
                'message' => 'Ubicación guardada correctamente',
                'location_id' => $location_id
            ));

        } catch (Exception $e) {
            error_log('Error en ajax_add_location: ' . $e->getMessage());
            wp_send_json_error('Error al guardar la ubicación: ' . $e->getMessage());
        }
    }

    public function ajax_update_location() {
        try {
            check_ajax_referer('menphis_locations_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
            $address = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

            if (!$id || empty($name)) {
                wp_send_json_error('Datos inválidos');
                return;
            }

            $this->update_location($id, array(
                'name' => $name,
                'address' => $address,
                'phone' => $phone
            ));

            wp_send_json_success(array(
                'message' => 'Ubicación actualizada correctamente',
                'location_id' => $id
            ));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_get_locations() {
        try {
            check_ajax_referer('menphis_locations_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción', 403);
                return;
            }

            $locations = $this->get_locations();
            
            $formatted_locations = array_map(function($location) {
                return array(
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->address,
                    'phone' => $location->phone,
                    'status' => $location->status,
                    'actions' => $this->get_location_actions_html($location)
                );
            }, $locations);

            wp_send_json_success(array(
                'data' => $formatted_locations,
                'message' => 'Ubicaciones obtenidas correctamente'
            ));

        } catch (Exception $e) {
            error_log('Error en ajax_get_locations: ' . $e->getMessage());
            wp_send_json_error('Error al obtener las ubicaciones: ' . $e->getMessage());
        }
    }

    private function get_location_actions_html($location) {
        $actions = '<div class="action-buttons">';
        $actions .= sprintf(
            '<a href="#" class="btn-floating btn-small waves-effect waves-light blue edit-location" data-id="%d" title="Editar"><i class="material-icons">edit</i></a>',
            $location->id
        );
        $actions .= sprintf(
            '<a href="#" class="btn-floating btn-small waves-effect waves-light red delete-location" data-id="%d" title="Eliminar"><i class="material-icons">delete</i></a>',
            $location->id
        );
        $actions .= '</div>';
        return $actions;
    }

    public function ajax_get_location() {
        try {
            check_ajax_referer('menphis_locations_nonce', 'nonce');
            
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                wp_send_json_error('ID de ubicación inválido');
                return;
            }

            $location = $this->get_location($id);
            if (!$location) {
                wp_send_json_error('Ubicación no encontrada');
                return;
            }

            wp_send_json_success($location);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_delete_location() {
        try {
            check_ajax_referer('menphis_locations_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                wp_send_json_error('ID de ubicación inválido');
                return;
            }

            $this->delete_location($id);
            wp_send_json_success('Ubicación eliminada correctamente');
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
} 