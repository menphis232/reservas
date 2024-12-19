<?php

class Menphis_Locations {
    public function save_location() {
        check_ajax_referer('menphis_nonce', '_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'No tienes permisos para realizar esta acción']);
        }
        
        $location_data = [
            'name' => sanitize_text_field($_POST['name']),
            'info' => sanitize_textarea_field($_POST['info']),
            'staff' => array_map('intval', (array)$_POST['staff'])
        ];
        
        $result = $this->db->insert(
            $this->db->prefix . 'menphis_locations',
            $location_data,
            ['%s', '%s', '%s']
        );
        
        if ($result) {
            wp_send_json_success(['message' => 'Ubicación guardada correctamente']);
        } else {
            wp_send_json_error(['message' => 'Error al guardar la ubicación']);
        }
    }
} 