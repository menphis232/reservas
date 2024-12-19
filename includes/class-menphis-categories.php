<?php
if (!defined('ABSPATH')) exit;

class Menphis_Categories {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        // Registrar endpoints AJAX
        add_action('wp_ajax_menphis_save_category', array($this, 'save_category'));
        add_action('wp_ajax_menphis_update_category', array($this, 'update_category'));
        add_action('wp_ajax_menphis_delete_category', array($this, 'delete_category'));
        
        // Registrar taxonomía al inicializar WordPress
        add_action('init', array($this, 'register_taxonomy'));
    }

    public function register_taxonomy() {
        $labels = array(
            'name' => 'Categorías de Servicios',
            'singular_name' => 'Categoría de Servicio',
            'menu_name' => 'Categorías',
            'all_items' => 'Todas las Categorías',
            'edit_item' => 'Editar Categoría',
            'view_item' => 'Ver Categoría',
            'update_item' => 'Actualizar Categoría',
            'add_new_item' => 'Añadir Nueva Categoría',
            'new_item_name' => 'Nombre de Nueva Categoría',
            'search_items' => 'Buscar Categorías',
            'not_found' => 'No se encontraron categorías'
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => false,
            'rewrite' => array('slug' => 'service-category')
        );

        register_taxonomy('service_category', null, $args);
    }

    public function save_category() {
        check_ajax_referer('menphis_nonce', '_nonce');
        
        if (!current_user_can('manage_categories')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }

        $name = sanitize_text_field($_POST['name']);
        
        if (empty($name)) {
            wp_send_json_error(array('message' => 'El nombre de la categoría es requerido'));
        }

        $result = wp_insert_term($name, 'service_category');

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'Categoría creada correctamente',
            'category_id' => $result['term_id']
        ));
    }

    public function update_category() {
        check_ajax_referer('menphis_nonce', '_nonce');
        
        if (!current_user_can('manage_categories')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }

        $category_id = intval($_POST['category_id']);
        $name = sanitize_text_field($_POST['name']);
        
        if (empty($name)) {
            wp_send_json_error(array('message' => 'El nombre de la categoría es requerido'));
        }

        $result = wp_update_term($category_id, 'service_category', array(
            'name' => $name
        ));

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'Categoría actualizada correctamente'
        ));
    }

    public function delete_category() {
        check_ajax_referer('menphis_nonce', '_nonce');
        
        if (!current_user_can('manage_categories')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }

        $category_id = intval($_POST['category_id']);
        
        // Verificar si hay servicios en esta categoría
        $services = $this->db->get_var($this->db->prepare(
            "SELECT COUNT(*) FROM {$this->db->prefix}menphis_services 
            WHERE category_id = %d AND active = 1",
            $category_id
        ));

        if ($services > 0) {
            wp_send_json_error(array(
                'message' => 'No se puede eliminar la categoría porque tiene servicios asociados'
            ));
        }

        $result = wp_delete_term($category_id, 'service_category');

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'Categoría eliminada correctamente'
        ));
    }

    public function get_categories() {
        return get_terms(array(
            'taxonomy' => 'service_category',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
    }
} 