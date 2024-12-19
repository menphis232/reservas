<?php
if (!defined('ABSPATH')) exit;

class Menphis_Services {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function get_services() {
        // Obtener productos marcados como servicios
        $sql = "
            SELECT p.*, pm.meta_value as duration 
            FROM {$this->db->posts} p
            INNER JOIN {$this->db->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_is_service'
            AND pm.meta_value = 'yes'
            ORDER BY p.post_title ASC
        ";

        $services = $this->db->get_results($sql);

        if (!$services) {
            return array();
        }

        // Obtener información adicional para cada servicio
        foreach ($services as $service) {
            $service->duration = get_post_meta($service->ID, '_service_duration', true);
            $service->price = get_post_meta($service->ID, '_price', true);
            $service->staff = get_post_meta($service->ID, '_service_staff', true);
        }

        return $services;
    }

    public function get_active_services() {
        return $this->get_services();
    }
} 