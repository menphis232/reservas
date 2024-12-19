<?php
if (!defined('ABSPATH')) exit;

class MenphisCustomers {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;

        // Agregar los hooks de AJAX
        add_action('wp_ajax_add_customer', array($this, 'ajax_add_customer'));
        add_action('wp_ajax_update_customer', array($this, 'ajax_update_customer'));
        add_action('wp_ajax_get_customer', array($this, 'ajax_get_customer'));
        add_action('wp_ajax_delete_customer', array($this, 'ajax_delete_customer'));
        add_action('wp_ajax_get_customers_list', array($this, 'ajax_get_customers_list'));
    }

    public function get_customers_list() {
        return $this->db->get_results("SELECT * FROM {$this->db->prefix}menphis_customers ORDER BY created_at DESC");
    }

    public function get_customer($id) {
        return $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->db->prefix}menphis_customers WHERE id = %d",
            $id
        ));
    }

    public function add_customer($data) {
        $result = $this->db->insert(
            $this->db->prefix . 'menphis_customers',
            array(
                'wp_user_id' => $data['wp_user_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'notes' => $data['notes'],
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            throw new Exception('Error al crear el cliente');
        }

        return $this->db->insert_id;
    }

    public function update_customer($id, $data) {
        $update_data = array();
        $format = array();

        $fields = array('first_name', 'last_name', 'email', 'phone', 'notes');
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
                $format[] = '%s';
            }
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $this->db->update(
            $this->db->prefix . 'menphis_customers',
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );

        if ($result === false) {
            throw new Exception('Error al actualizar el cliente');
        }

        return true;
    }

    public function delete_customer($id) {
        $result = $this->db->delete(
            $this->db->prefix . 'menphis_customers',
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            throw new Exception('Error al eliminar el cliente');
        }

        return true;
    }

    // Métodos AJAX
    public function ajax_add_customer() {
        try {
            check_ajax_referer('menphis_customers_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            // Debug - Datos recibidos
            error_log('Datos de cliente recibidos: ' . print_r($_POST, true));

            // Validar datos requeridos
            $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
            $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
            $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
            $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

            // Validaciones
            if (empty($first_name) || empty($email)) {
                wp_send_json_error('Nombre y email son requeridos');
                return;
            }

            // Verificar que la tabla existe
            $table_name = $this->db->prefix . 'menphis_customers';
            $table_exists = $this->db->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            if (!$table_exists) {
                throw new Exception('La tabla de clientes no existe');
            }

            // Debug - SQL que se ejecutará
            $insert_data = array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'notes' => $notes,
                'status' => 'active',
                'created_at' => current_time('mysql')
            );
            error_log('Datos a insertar: ' . print_r($insert_data, true));

            // Insertar el cliente
            $result = $this->db->insert(
                $this->db->prefix . 'menphis_customers',
                $insert_data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );

            if ($result === false) {
                error_log('Error SQL: ' . $this->db->last_error);
                throw new Exception('Error al insertar en la base de datos: ' . $this->db->last_error);
            }

            $customer_id = $this->db->insert_id;
            error_log('Cliente creado con ID: ' . $customer_id);

            wp_send_json_success(array(
                'message' => 'Cliente creado correctamente',
                'customer_id' => $customer_id
            ));

        } catch (Exception $e) {
            error_log('Error en ajax_add_customer: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            wp_send_json_error('Error al crear el cliente: ' . $e->getMessage());
        }
    }

    public function ajax_update_customer() {
        try {
            check_ajax_referer('menphis_customers_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
            $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
            $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
            $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

            if (!$id || empty($first_name) || empty($email)) {
                wp_send_json_error('Datos incompletos');
                return;
            }

            // Obtener el cliente actual
            $customer = $this->get_customer($id);
            if (!$customer) {
                wp_send_json_error('Cliente no encontrado');
                return;
            }

            // Actualizar usuario de WordPress
            $user_data = array(
                'ID' => $customer->wp_user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'user_email' => $email,
                'display_name' => $first_name . ' ' . $last_name
            );

            $user_id = wp_update_user($user_data);
            if (is_wp_error($user_id)) {
                wp_send_json_error($user_id->get_error_message());
                return;
            }

            // Actualizar datos en la tabla de clientes
            $result = $this->update_customer($id, array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'notes' => $notes
            ));

            if ($result) {
                wp_send_json_success(array(
                    'message' => 'Cliente actualizado correctamente',
                    'customer_id' => $id
                ));
            } else {
                wp_send_json_error('Error al actualizar el cliente');
            }

        } catch (Exception $e) {
            wp_send_json_error('Error al actualizar el cliente: ' . $e->getMessage());
        }
    }

    public function ajax_get_customer() {
        try {
            check_ajax_referer('menphis_customers_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                wp_send_json_error('ID de cliente inválido');
                return;
            }

            $customer = $this->get_customer($id);
            if (!$customer) {
                wp_send_json_error('Cliente no encontrado');
                return;
            }

            wp_send_json_success($customer);

        } catch (Exception $e) {
            wp_send_json_error('Error al obtener datos del cliente: ' . $e->getMessage());
        }
    }

    public function ajax_delete_customer() {
        try {
            check_ajax_referer('menphis_customers_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                wp_send_json_error('ID de cliente inválido');
                return;
            }

            $customer = $this->get_customer($id);
            if (!$customer) {
                wp_send_json_error('Cliente no encontrado');
                return;
            }

            // Eliminar el usuario de WordPress
            if ($customer->wp_user_id) {
                wp_delete_user($customer->wp_user_id);
            }

            // Eliminar el registro del cliente
            $result = $this->delete_customer($id);

            if ($result) {
                wp_send_json_success('Cliente eliminado correctamente');
            } else {
                wp_send_json_error('Error al eliminar el cliente');
            }

        } catch (Exception $e) {
            wp_send_json_error('Error al eliminar el cliente: ' . $e->getMessage());
        }
    }

    public function ajax_get_customers_list() {
        try {
            check_ajax_referer('menphis_customers_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción');
                return;
            }

            $customers = $this->get_customers_list();
            
            // Formatear la lista para la tabla
            $formatted_list = array_map(function($customer) {
                return array(
                    'id' => $customer->id,
                    'name' => $customer->first_name . ' ' . $customer->last_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'notes' => $customer->notes,
                    'created_at' => date('d/m/Y', strtotime($customer->created_at)),
                    'actions' => $this->get_customer_actions_html($customer)
                );
            }, $customers);

            wp_send_json_success($formatted_list);

        } catch (Exception $e) {
            wp_send_json_error('Error al obtener la lista de clientes: ' . $e->getMessage());
        }
    }

    private function get_customer_actions_html($customer) {
        $actions = '<div class="action-buttons">';
        $actions .= sprintf(
            '<button class="btn-floating btn-small waves-effect waves-light blue edit-customer" data-id="%d" title="Editar"><i class="material-icons">edit</i></button>',
            $customer->id
        );
        $actions .= '&nbsp;';
        $actions .= sprintf(
            '<button class="btn-floating btn-small waves-effect waves-light red delete-customer" data-id="%d" title="Eliminar"><i class="material-icons">delete</i></button>',
            $customer->id
        );
        $actions .= '</div>';
        return $actions;
    }

    public function render_customers_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/customers.php';
    }
} 