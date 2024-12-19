<?php

class Menphis_Admin {
    public function enqueue_admin_scripts() {
        // Materialize CSS y JS
        wp_enqueue_style('materialize-css', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css', array(), '1.0.0');
        wp_enqueue_style('material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons');
        wp_enqueue_script('materialize-js', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js', array('jquery'), '1.0.0', true);
        
        // DatePicker y otros plugins necesarios
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        
        // Nuestros estilos y scripts
        wp_enqueue_style('menphis-admin-css', MENPHIS_RESERVA_PLUGIN_URL . 'assets/css/menphis-reserva-admin.css', array('materialize-css'), '1.0.0');
        wp_enqueue_script('menphis-admin-js', MENPHIS_RESERVA_PLUGIN_URL . 'assets/js/menphis-reserva-admin.js', array('jquery', 'materialize-js'), '1.0.0', true);
    }
} 