<?php
if (!defined('ABSPATH')) exit;

class Menphis_Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Eliminar tablas existentes para forzar recreación
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menphis_bookings");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menphis_booking_services");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menphis_customers");

        // Tabla de reservas
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_bookings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            staff_id bigint(20) NOT NULL,
            location_id bigint(20) NOT NULL,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            notes text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY staff_id (staff_id),
            KEY location_id (location_id)
        ) $charset_collate;";

        // Tabla de servicios de reserva
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_booking_services (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            service_id bigint(20) NOT NULL,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY service_id (service_id)
        ) $charset_collate;";

        // Tabla de clientes
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_customers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            first_name varchar(255) NOT NULL,
            last_name varchar(255),
            email varchar(255) NOT NULL,
            phone varchar(50),
            notes text,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY status (status)
        ) $charset_collate;";

        // Tabla de ubicaciones
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_locations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address text,
            phone varchar(50),
            email varchar(255),
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset_collate;";

        // Agregar esta tabla en el método activate()
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_staff_locations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            staff_id bigint(20) NOT NULL,
            location_id bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY staff_location (staff_id, location_id)
        ) $charset_collate;";

        // Tabla de empleados
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_staff (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) NOT NULL,
            phone varchar(50),
            services text,
            locations text,
            working_hours text,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wp_user_id (wp_user_id),
            KEY status (status)
        ) $charset_collate;";

        // Tabla de horarios de empleados
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_staff_schedules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            staff_id bigint(20) NOT NULL,
            day_of_week tinyint(1) NOT NULL, -- 0 = domingo, 1 = lunes, ..., 6 = sábado
            start_time time NOT NULL,
            end_time time NOT NULL,
            is_break tinyint(1) DEFAULT 0, -- 0 = horario normal, 1 = horario de descanso
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY staff_id (staff_id),
            KEY day_of_week (day_of_week)
        ) $charset_collate;";

        // Tabla de días libres
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_staff_time_off (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            staff_id bigint(20) NOT NULL,
            date_from date NOT NULL,
            date_to date NOT NULL,
            reason text,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY staff_id (staff_id),
            KEY date_from (date_from),
            KEY date_to (date_to)
        ) $charset_collate;";

        // Ejecutar las consultas
        foreach ($sql as $query) {
            dbDelta($query);
        }

        // Debug - Verificar estructura de las tablas
        $tables = array(
            'bookings' => $wpdb->prefix . 'menphis_bookings',
            'booking_services' => $wpdb->prefix . 'menphis_booking_services',
            'customers' => $wpdb->prefix . 'menphis_customers'
        );

        foreach ($tables as $name => $table) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
            error_log("Estructura de la tabla $name: " . print_r($columns, true));
        }
    }
} 