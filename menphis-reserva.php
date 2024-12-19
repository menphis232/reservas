<?php
/*
Plugin Name: Menphis Reserva
Plugin URI: https://menphis.com/menphis-reserva
Description: Sistema avanzado de reservas con productos asociados a servicios, gestión de personal y ubicaciones múltiples
Version: 1.0.0
Requires at least: 5.8
Requires PHP: 7.4
Author: Menphis
Author URI: https://menphis.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: menphis-reserva
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('MENPHIS_RESERVA_VERSION', '1.0.1');
define('MENPHIS_RESERVA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MENPHIS_RESERVA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Agregar después de las definiciones de constantes
if (!function_exists('menphis_debug')) {
    function menphis_debug($message, $data = null) {
        if (WP_DEBUG) {
            if (is_null($data)) {
                error_log('[Menphis Debug] ' . print_r($message, true));
            } else {
                error_log('[Menphis Debug] ' . $message . ': ' . print_r($data, true));
            }
        }
    }
}

// Al inicio del plugin, después de las definiciones existentes
if (!defined('MENPHIS_RESERVA_PLUGIN_URL')) {
    define('MENPHIS_RESERVA_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Clase principal del plugin
class MenphisReserva {
    public function __construct() {
        // Remover cualquier menú existente primero
        remove_all_actions('admin_menu');
        
        // Registrar nuestro menú con prioridad muy alta
        add_action('admin_menu', array($this, 'admin_menu'), 0);
        
        // Registrar el hook de activación antes de cualquier otra cosa
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Agregar el hook de init con prioridad baja para asegurar que WP está listo
        add_action('init', array($this, 'init'), 20);
        
        // Cargar clases principales
        require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/class-menphis-activator.php';
        require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/class-menphis-bookings.php';
        require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/class-menphis-helpers.php';
        require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/class-menphis-categories.php';
        require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/class-menphis-services.php';
        require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/class-menphis-admin.php';
        require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/class-menphis-staff.php';
        require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/class-menphis-locations.php';
        require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/class-menphis-reports.php';
        
        // Inicializar clases principales
        $this->bookings = new Menphis_Bookings();
        $this->helpers = new Menphis_Helpers();
        $this->categories = new Menphis_Categories();
        $this->services = new Menphis_Services();
        
        // Inicializar clases admin
        if (is_admin()) {
            $this->admin = new MenphisAdmin();
            $this->staff = new MenphisStaff();
            $this->locations = new MenphisLocations();
            $this->reports = new MenphisReports();
        }

        // Agregar endpoint AJAX para eventos del calendario
        add_action('wp_ajax_get_calendar_events', array($this, 'ajax_get_calendar_events'));
        add_action('wp_ajax_nopriv_get_calendar_events', array($this, 'ajax_get_calendar_events'));

        // Registrar shortcode
        add_shortcode('menphis_booking_form', array($this, 'render_booking_form_shortcode'));
        
        // Agregar scripts y estilos en el frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // Agregar endpoint para eliminar servicios del carrito
        add_action('wp_ajax_remove_service_from_cart', array($this, 'ajax_remove_service_from_cart'));
        add_action('wp_ajax_nopriv_remove_service_from_cart', array($this, 'ajax_remove_service_from_cart'));

        // Agregar script de verificación en el head
        add_action('wp_head', array($this, 'add_materialize_check'));

        // Cargar clase del panel de empleados
        require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/class-menphis-employee-dashboard.php';
        require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/class-menphis-shortcodes.php';
        
        // Inicializar clases
        new Menphis_Employee_Dashboard();
        new Menphis_Shortcodes();
    }

    public function init() {
        try {
            $this->check_dependencies();
            $this->load_dependencies();
            
            // Registrar post types después de cargar las dependencias
            if (is_admin()) {
                $this->register_post_types();
            }
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="error"><p>Error en Menphis Reserva: ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
        
        // Agregar endpoints AJAX
        add_action('wp_ajax_get_location_services', array($this, 'ajax_get_location_services'));
        add_action('wp_ajax_nopriv_get_location_services', array($this, 'ajax_get_location_services'));
        
        add_action('wp_ajax_add_service_to_cart', array($this, 'ajax_add_service_to_cart'));
        add_action('wp_ajax_nopriv_add_service_to_cart', array($this, 'ajax_add_service_to_cart'));

        // Agregar endpoints AJAX para ubicaciones
        add_action('wp_ajax_add_location', array($this->locations, 'ajax_add_location'));
        add_action('wp_ajax_update_location', array($this->locations, 'ajax_update_location'));

        // Agregar nonce para ubicaciones
        add_action('admin_enqueue_scripts', function($hook) {
            if (strpos($hook, 'menphis-locations') !== false) {
                wp_localize_script('menphis-locations-js', 'menphisLocations', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('menphis_locations_nonce')
                ));
            }
        });
    }

    private function check_dependencies() {
        // Verificar que WooCommerce está instalado y activado
        if (!class_exists('WooCommerce')) {
            throw new Exception('Este plugin requiere WooCommerce para funcionar.');
        }

        // Verificar que las carpetas necesarias existen
        $required_dirs = array(
            'includes',
            'includes/admin',
            'includes/admin/views',
            'includes/gateways',
            'assets/css',
            'assets/js',
            'templates/emails'
        );

        foreach ($required_dirs as $dir) {
            $full_path = MENPHIS_RESERVA_PLUGIN_DIR . $dir;
            if (!is_dir($full_path)) {
                // Intentar crear el directorio si no existe
                if (!wp_mkdir_p($full_path)) {
                    throw new Exception("No se pudo crear o acceder al directorio: {$dir}");
                }
            }
            
            // Verificar permisos de escritura
            if (!is_writable($full_path)) {
                throw new Exception("El directorio {$dir} no tiene permisos de escritura");
            }
        }
    }

    public function activate() {
        try {
            // 1. Primero crear todas las carpetas necesarias
            $directories = array(
                'assets/css',
                'assets/js',
                'assets/images',
                'includes',
                'includes/admin',
                'includes/admin/views',
                'includes/gateways',
                'templates/emails'
            );

            foreach ($directories as $dir) {
                $full_path = MENPHIS_RESERVA_PLUGIN_DIR . $dir;
                if (!file_exists($full_path)) {
                    if (!wp_mkdir_p($full_path)) {
                        throw new Exception("No se pudo crear el directorio: {$dir}");
                    }
                }
            }

            // 2. Crear los archivos de vistas básicos
            $views = array(
                'includes/admin/views/dashboard.php' => '<?php if (!defined("ABSPATH")) exit; ?>
<div class="wrap">
    <h1>Panel de Control - Menphis Reserva</h1>
    <!-- Contenido del dashboard -->
</div>',
                'includes/admin/views/staff.php' => '<?php if (!defined("ABSPATH")) exit; ?>
<div class="wrap">
    <h1>Gestión de Personal</h1>
    <!-- Contenido de gestión de personal -->
</div>',
                'includes/admin/views/calendar.php' => '<?php if (!defined("ABSPATH")) exit; ?>
<div class="wrap">
    <h1>Calendario</h1>
    <!-- Contenido del calendario -->
</div>',
                'includes/admin/views/bookings.php' => '<?php if (!defined("ABSPATH")) exit; ?>
<div class="wrap">
    <h1>Reservas</h1>
    <!-- Contenido de reservas -->
</div>',
                'includes/admin/views/settings.php' => '<?php if (!defined("ABSPATH")) exit; ?>
<div class="wrap">
    <h1>Configuración</h1>
    <!-- Contenido de configuración -->
</div>'
            );

            foreach ($views as $file => $content) {
                $file_path = MENPHIS_RESERVA_PLUGIN_DIR . $file;
                if (!file_exists($file_path)) {
                    if (file_put_contents($file_path, $content) === false) {
                        throw new Exception("No se pudo crear el archivo: {$file}");
                    }
                }
            }

            // 3. Crear los archivos de gateway
            $gateways = array(
                'includes/gateways/class-menphis-stripe-gateway.php' => '<?php
if (!defined("ABSPATH")) exit;

class MenphisStripeGateway {
    public function __construct() {
        // Inicialización del gateway de Stripe
    }
}',
                'includes/gateways/class-menphis-paypal-gateway.php' => '<?php
if (!defined("ABSPATH")) exit;

class MenphisPayPalGateway {
    public function __construct() {
        // Inicialización del gateway de PayPal
    }
}'
            );

            foreach ($gateways as $file => $content) {
                $file_path = MENPHIS_RESERVA_PLUGIN_DIR . $file;
                if (!file_exists($file_path)) {
                    if (file_put_contents($file_path, $content) === false) {
                        throw new Exception("No se pudo crear el archivo: {$file}");
                    }
                }
            }

            // 4. Crear otros archivos requeridos
            $this->create_required_files();
            
            // 5. Verificar que todo se creó correctamente
            $this->check_dependencies();
            
            // 6. Crear las tablas de la base de datos
            $this->create_tables();
            
            flush_rewrite_rules();

            // Crear rol de empleado si no existe
            if (!get_role('menphis_employee')) {
                add_role('menphis_employee', 'Empleado Menphis', array(
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                    'upload_files' => true
                ));
            }
        } catch (Exception $e) {
            wp_die('Error activando Menphis Reserva: ' . $e->getMessage());
        }
    }

    private function create_required_files() {
        // Lista de archivos requeridos y su contenido por defecto
        $files = array(
            'assets/css/menphis-reserva.css' => '/* Menphis Reserva Styles */',
            'assets/js/menphis-reserva.js' => 'jQuery(document).ready(function($) {});',
            'templates/emails/staff-booking-notification.php' => '<?php /* Template de email */ ?>'
        );

        foreach ($files as $file => $content) {
            $file_path = MENPHIS_RESERVA_PLUGIN_DIR . $file;
            if (!file_exists($file_path)) {
                file_put_contents($file_path, $content);
            }
        }
    }

    private function load_dependencies() {
        $required_files = array(
            'includes/class-menphis-service.php',
            'includes/class-menphis-booking.php',
            'includes/admin/class-menphis-admin.php',
            'includes/admin/class-menphis-staff.php',
            'includes/admin/class-menphis-locations.php',
            'includes/class-menphis-payments.php',
            'includes/class-menphis-notifications.php',
            'includes/admin/class-menphis-reports.php'
        );

        foreach ($required_files as $file) {
            $file_path = MENPHIS_RESERVA_PLUGIN_DIR . $file;
            if (!file_exists($file_path)) {
                throw new Exception("Archivo requerido no encontrado: {$file}");
            }
            require_once $file_path;
        }

        // Solo inicializar clases si todos los archivos existen
        try {
            if (is_admin()) {
                new MenphisAdmin();
                new MenphisStaff();
                new MenphisLocations();
                new MenphisReports();
            }
            
            new MenphisPayments();
            new MenphisNotifications();
        } catch (Exception $e) {
            throw new Exception("Error inicializando clases: " . $e->getMessage());
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('menphis-reserva', MENPHIS_RESERVA_PLUGIN_URL . 'assets/css/menphis-reserva.css');
        wp_enqueue_script('menphis-reserva', MENPHIS_RESERVA_PLUGIN_URL . 'assets/js/menphis-reserva.js', array('jquery'), MENPHIS_RESERVA_VERSION, true);
    }

    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        // Tabla de reservas
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_bookings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_id bigint(20) NOT NULL,
            staff_id bigint(20) NOT NULL,
            customer_id bigint(20) NOT NULL,
            location_id bigint(20) NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'pending',
            payment_status varchar(30) NOT NULL DEFAULT 'pending',
            total_amount decimal(10,2) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Tabla de personal
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_staff (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) NOT NULL,
            phone varchar(30),
            services text,
            working_hours text,
            locations text,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Tabla de ubicaciones
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_locations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address text,
            phone varchar(30),
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Tabla de clientes
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_customers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20),
            first_name varchar(255),
            last_name varchar(255),
            email varchar(255),
            phone varchar(30),
            notes text,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Tabla de pagos
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method varchar(50),
            transaction_id varchar(255),
            status varchar(30),
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Agregar en la función create_tables()
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menphis_staff_leaves (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            staff_id bigint(20) NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            type varchar(20) NOT NULL, -- vacation/break/sick
            status varchar(20) DEFAULT 'approved',
            notes text,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach($sql as $query) {
            dbDelta($query);
        }
    }

    private function register_post_types() {
        // Registrar el tipo de post para servicios
        $args = array(
            'public' => true,
            'label'  => 'Servicios',
            'labels' => array(
                'name'               => 'Servicios',
                'singular_name'      => 'Servicio',
                'menu_name'          => 'Servicios',
                'add_new'           => 'Agregar Nuevo',
                'add_new_item'      => 'Agregar Nuevo Servicio',
                'edit_item'         => 'Editar Servicio',
                'new_item'          => 'Nuevo Servicio',
                'view_item'         => 'Ver Servicio',
                'search_items'      => 'Buscar Servicios',
                'not_found'         => 'No se encontraron servicios',
                'not_found_in_trash'=> 'No se encontraron servicios en la papelera'
            ),
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-calendar-alt',
            'has_archive' => true,
            'rewrite' => array('slug' => 'servicios'),
            'show_in_menu' => 'menphis-bookings',
            'show_in_admin_bar' => false
        );
        
        register_post_type('menphis_service', $args);

        // Registrar el tipo de post para reservas
        $args = array(
            'public' => false,
            'label'  => 'Reservas',
            'labels' => array(
                'name'               => 'Reservas',
                'singular_name'      => 'Reserva',
                'menu_name'          => 'Reservas',
                'add_new'           => 'Nueva Reserva',
                'add_new_item'      => 'Nueva Reserva',
                'edit_item'         => 'Editar Reserva',
                'new_item'          => 'Nueva Reserva',
                'view_item'         => 'Ver Reserva',
                'search_items'      => 'Buscar Reservas',
                'not_found'         => 'No se encontraron reservas',
                'not_found_in_trash'=> 'No se encontraron reservas en la papelera'
            ),
            'supports' => array('title'),
            'show_in_menu' => false,
            'show_in_admin_bar' => false
        );
        
        register_post_type('menphis_booking', $args);

        // Registrar taxonomías si son necesarias
        $taxonomy_args = array(
            'hierarchical'      => true,
            'labels'           => array(
                'name'              => 'Categorías de Servicio',
                'singular_name'     => 'Categoría de Servicio',
                'search_items'      => 'Buscar Categorías',
                'all_items'         => 'Todas las Categorías',
                'parent_item'       => 'Categoría Padre',
                'parent_item_colon' => 'Categoría Padre:',
                'edit_item'         => 'Editar Categoría',
                'update_item'       => 'Actualizar Categoría',
                'add_new_item'      => 'Agregar Nueva Categoría',
                'new_item_name'     => 'Nombre de Nueva Categoría',
                'menu_name'         => 'Categorías'
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'categoria-servicio'),
        );

        register_taxonomy('service_category', array('menphis_service'), $taxonomy_args);
    }

    public function admin_scripts($hook) {
        // Solo cargar en las páginas de nuestro plugin
        if (strpos($hook, 'menphis-bookings') === false && 
            strpos($hook, 'menphis-employees') === false && 
            strpos($hook, 'menphis-services') === false && 
            strpos($hook, 'menphis-settings') === false) {
            return;
        }

        try {
            // CSS
            wp_enqueue_style('material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons');
            wp_enqueue_style('materialize-css', MENPHIS_RESERVA_PLUGIN_URL . 'assets/css/materialize.min.css');
            wp_enqueue_style('menphis-admin-css', MENPHIS_RESERVA_PLUGIN_URL . 'assets/css/menphis-reserva-admin.css', array(), time());

            // JavaScript
            wp_enqueue_script('jquery');
            wp_enqueue_script('materialize-js', MENPHIS_RESERVA_PLUGIN_URL . 'assets/js/materialize.min.js', array('jquery'), null, true);
            wp_enqueue_script('menphis-bookings-admin-js', MENPHIS_RESERVA_PLUGIN_URL . 'assets/js/menphis-bookings-admin.js', array('jquery', 'materialize-js'), time(), true);

            // Localización para JavaScript
            wp_localize_script('menphis-bookings-admin-js', 'menphisAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('menphis_nonce'),
                'debug' => WP_DEBUG,
                'hook' => $hook,
                'pluginUrl' => MENPHIS_RESERVA_PLUGIN_URL
            ));

            wp_localize_script('menphis-calendar-js', 'menphisAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'calendarNonce' => wp_create_nonce('menphis_calendar_nonce'),
                'debug' => WP_DEBUG
            ));

        } catch (Exception $e) {
            error_log('Error cargando scripts de Menphis Reserva: ' . $e->getMessage());
        }
    }

    public function admin_menu() {
        // Remover cualquier menú existente de Menphis
        global $menu;
        if (is_array($menu)) {
            foreach ($menu as $key => $item) {
                if (isset($item[2]) && $item[2] === 'menphis-reserva') {
                    unset($menu[$key]);
                }
            }
        }
        
        // Menú principal
        add_menu_page(
            'Menphis Reserva',
            'Menphis Reserva',
            'manage_options',
            'menphis-reserva',
            array($this, 'render_admin_page'),
            'dashicons-calendar-alt',
            30
        );

        // Submenús
        $submenus = array(
            array(
                'title' => 'Calendario',
                'menu_title' => 'Calendario',
                'capability' => 'manage_options',
                'menu_slug' => 'menphis-calendar',
                'callback' => array($this, 'render_calendar_page')
            ),
            array(
                'title' => 'Reservas',
                'menu_title' => 'Reservas',
                'capability' => 'manage_options',
                'menu_slug' => 'menphis-bookings',
                'callback' => array($this, 'render_bookings_page')
            ),
            array(
                'title' => 'Personal',
                'menu_title' => 'Personal',
                'capability' => 'manage_options',
                'menu_slug' => 'menphis-staff',
                'callback' => array($this, 'render_staff_page')
            ),
            array(
                'title' => 'Ubicaciones',
                'menu_title' => 'Ubicaciones',
                'capability' => 'manage_options',
                'menu_slug' => 'menphis-locations',
                'callback' => array($this, 'render_locations_page')
            ),
            array(
                'title' => 'Configuración',
                'menu_title' => 'Configuración',
                'capability' => 'manage_options',
                'menu_slug' => 'menphis-settings',
                'callback' => array($this, 'render_settings_page')
            ),
            array(
                'title' => 'Logs',
                'menu_title' => 'Logs',
                'capability' => 'manage_options',
                'menu_slug' => 'menphis-logs',
                'callback' => array($this, 'render_logs_page')
            )
        );

        foreach ($submenus as $submenu) {
            add_submenu_page(
                'menphis-reserva',
                $submenu['title'],
                $submenu['menu_title'],
                $submenu['capability'],
                $submenu['menu_slug'],
                $submenu['callback']
            );
        }

        // Remover el submenú duplicado que se crea automáticamente
        remove_submenu_page('menphis-reserva', 'menphis-reserva');
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        // Cambiar esto para mostrar el dashboard en lugar de las reservas
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/dashboard.php';
    }

    public function render_calendar_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/calendar.php';
    }

    public function render_bookings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }

        try {
            if (!isset($this->bookings)) {
                $this->bookings = new Menphis_Bookings();
            }
            $this->bookings->render_bookings_page();
        } catch (Exception $e) {
            error_log('Error al renderizar página de reservas: ' . $e->getMessage());
            echo '<div class="error"><p>Error al cargar la página de reservas</p></div>';
        }
    }

    public function render_staff_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        
        try {
            if (!isset($this->staff)) {
                require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/class-menphis-staff.php';
                $this->staff = new MenphisStaff();
            }
            include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/staff.php';
        } catch (Exception $e) {
            error_log('Error al renderizar página de personal: ' . $e->getMessage());
            echo '<div class="error"><p>Error al cargar la página de personal</p></div>';
        }
    }

    public function render_services_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/services.php';
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }

        // Manejar la recreación de tablas
        if (isset($_POST['menphis_recreate_tables']) && check_admin_referer('menphis_recreate_tables', 'menphis_nonce')) {
            require_once MENPHIS_RESERVA_PLUGIN_DIR . 'includes/class-menphis-activator.php';
            Menphis_Activator::activate();
            echo '<div class="updated"><p>Tablas recreadas correctamente</p></div>';
        }

        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/settings.php';
    }

    public function render_locations_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/locations.php';
    }

    public function render_customers_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        
        try {
            $this->customers->render_customers_page();
        } catch (Exception $e) {
            error_log('Error al renderizar página de clientes: ' . $e->getMessage());
            echo '<div class="error"><p>Error al cargar la página de clientes</p></div>';
        }
    }

    // Agregar método para obtener instancia de helpers
    public function get_helpers() {
        return $this->helpers;
    }

    // Agregar método para obtener instancia de categories
    public function get_categories() {
        return $this->categories;
    }

    // Agregar método getter para servicios
    public function get_services() {
        return $this->services;
    }

    public function booking_form_shortcode($atts) {
        try {
            // Debug
            error_log('Iniciando shortcode de reserva...');
            
            global $menphis_reserva;
            
            // Si no está disponible la instancia global, usar $this
            if (!isset($menphis_reserva)) {
                $menphis_reserva = $this;
            }

            $atts = shortcode_atts(array(
                'style' => 'default',
                'steps' => '4'
            ), $atts);

            // Encolar estilos básicos solamente
            wp_enqueue_style('menphis-booking-css', MENPHIS_RESERVA_PLUGIN_URL . 'assets/css/menphis-reserva.css', array(), time());

            // Pasar la instancia del plugin al template
            set_query_var('menphis_plugin', $menphis_reserva);

            ob_start();
            include MENPHIS_RESERVA_PLUGIN_DIR . 'templates/booking-form.php';
            $content = ob_get_clean();
            
            error_log('Shortcode completado exitosamente');
            
            return $content;
            
        } catch (Exception $e) {
            error_log('Error en shortcode de reserva: ' . $e->getMessage());
            return '<div class="error">Error al cargar el formulario de reserva</div>';
        }
    }

    public function services_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'layout' => 'grid'
        ), $atts);

        ob_start();
        include MENPHIS_RESERVA_PLUGIN_DIR . 'templates/services-list.php';
        return ob_get_clean();
    }

    // Agregar campos en la pestaña general del producto
    public function add_service_fields() {
        global $post;
        
        echo '<div class="options_group show_if_simple show_if_variable">';
        
        // Checkbox para marcar como servicio
        woocommerce_wp_checkbox(array(
            'id' => '_is_service',
            'wrapper_class' => 'show_if_simple show_if_variable',
            'label' => 'Es un servicio reservable',
            'description' => 'Marcar si este producto es un servicio que se puede reservar'
        ));
        
        // Campo para duración
        woocommerce_wp_text_input(array(
            'id' => '_service_duration',
            'wrapper_class' => 'show_if_is_service',
            'label' => 'Duración (minutos)',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '15',
                'min' => '15'
            ),
            'desc_tip' => true,
            'description' => 'Duración del servicio en minutos'
        ));
        
        // Selector de empleados
        $staff_options = $this->get_staff_options();
        woocommerce_wp_select(array(
            'id' => '_service_staff',
            'wrapper_class' => 'show_if_is_service',
            'label' => 'Empleados disponibles',
            'options' => $staff_options,
            'custom_attributes' => array(
                'multiple' => 'multiple'
            ),
            'description' => 'Selecciona los empleados que pueden realizar este servicio'
        ));

        echo '</div>';

        // JavaScript para mostrar/ocultar campos
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function toggleServiceFields() {
                var isService = $('#_is_service').is(':checked');
                if (isService) {
                    $('.show_if_is_service').show();
                } else {
                    $('.show_if_is_service').hide();
                }
            }

            $('#_is_service').on('change', toggleServiceFields);
            toggleServiceFields(); // Ejecutar al cargar
            
            // Debug
            $('#_is_service').on('change', function() {
                console.log('Checkbox cambiado:', $(this).is(':checked'));
            });
            
            // Asegurarse de que el formulario se envíe correctamente
            $('form#post').on('submit', function() {
                console.log('Formulario enviado');
                return true;
            });
        });
        </script>
        <?php
    }

    // Guardar campos personalizados
    public function save_service_fields($post_id) {
        try {
            // Verificar el nonce y permisos
            if (!current_user_can('edit_product', $post_id)) {
                return;
            }

            // Guardar checkbox de servicio
            $is_service = isset($_POST['_is_service']) ? 'yes' : 'no';
            update_post_meta($post_id, '_is_service', $is_service);
            
            // Solo guardar los otros campos si es un servicio
            if ($is_service === 'yes') {
                // Guardar duración
                if (isset($_POST['_service_duration'])) {
                    $duration = absint($_POST['_service_duration']);
                    if ($duration < 15) $duration = 15; // Mínimo 15 minutos
                    update_post_meta($post_id, '_service_duration', $duration);
                }
                
                // Guardar empleados asignados
                if (isset($_POST['_service_staff']) && is_array($_POST['_service_staff'])) {
                    $staff = array_map('absint', $_POST['_service_staff']);
                    update_post_meta($post_id, '_service_staff', $staff);
                } else {
                    update_post_meta($post_id, '_service_staff', array());
                }
            }

            error_log('Campos de servicio guardados correctamente para el producto ' . $post_id);

        } catch (Exception $e) {
            error_log('Error al guardar campos de servicio: ' . $e->getMessage());
        }
    }

    // Obtener lista de empleados para el selector
    private function get_staff_options() {
        $options = array('' => 'Seleccionar empleados');
        $employees = get_users(array(
            'role' => 'menphis_employee',
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        foreach ($employees as $employee) {
            $options[$employee->ID] = $employee->display_name;
        }
        
        return $options;
    }

    // Función helper para verificar si un producto es un servicio
    public function is_product_service($product_id) {
        return get_post_meta($product_id, '_is_service', true) === 'yes';
    }

    // Función para obtener la duración de un servicio
    public function get_service_duration($product_id) {
        return get_post_meta($product_id, '_service_duration', true);
    }

    // Función para obtener los empleados asignados a un servicio
    public function get_service_staff($product_id) {
        return get_post_meta($product_id, '_service_staff', true);
    }

    // Agregar los métodos AJAX
    public function ajax_get_location_services() {
        check_ajax_referer('menphis_nonce', 'nonce');
        
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        
        if (!$location_id) {
            wp_send_json_error('Ubicación no válida');
        }
        
        $services = $this->get_services()->get_location_services($location_id);
        wp_send_json_success($services);
    }

    public function ajax_add_service_to_cart() {
        check_ajax_referer('menphis_nonce', 'nonce');
        
        $service = isset($_POST['service']) ? $_POST['service'] : array();
        
        if (empty($service)) {
            wp_send_json_error('Datos del servicio no válidos');
        }
        
        // Agregar al carrito de WooCommerce
        $cart_item_key = WC()->cart->add_to_cart(
            $service['service'],
            $service['quantity'],
            0,
            array(),
            array(
                'booking_location' => $service['location'],
                'booking_duration' => $service['duration']
            )
        );
        
        if ($cart_item_key) {
            wp_send_json_success(array(
                'cart_item_key' => $cart_item_key,
                'message' => 'Servicio agregado al carrito'
            ));
        } else {
            wp_send_json_error('No se pudo agregar al carrito');
        }
    }

    public function ajax_get_calendar_events() {
        try {
            check_ajax_referer('menphis_calendar_nonce', '_nonce');

            $start = isset($_GET['start']) ? sanitize_text_field($_GET['start']) : '';
            $end = isset($_GET['end']) ? sanitize_text_field($_GET['end']) : '';

            if (empty($start) || empty($end)) {
                wp_send_json_error('Fechas no válidas');
                return;
            }

            global $wpdb;
            $events = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    b.id,
                    b.booking_date as start,
                    b.booking_time,
                    CONCAT(c.first_name, ' ', c.last_name) as title,
                    l.name as location,
                    b.status
                FROM {$wpdb->prefix}menphis_bookings b
                LEFT JOIN {$wpdb->prefix}menphis_customers c ON b.customer_id = c.id
                LEFT JOIN {$wpdb->prefix}menphis_locations l ON b.location_id = l.id
                WHERE b.booking_date BETWEEN %s AND %s
                ORDER BY b.booking_date, b.booking_time",
                date('Y-m-d', strtotime($start)),
                date('Y-m-d', strtotime($end))
            ));

            $calendar_events = array();
            foreach ($events as $event) {
                $calendar_events[] = array(
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start . 'T' . $event->booking_time,
                    'end' => $event->start . 'T' . date('H:i:s', strtotime($event->booking_time . ' +1 hour')),
                    'location' => $event->location,
                    'className' => 'event-' . $event->status
                );
            }

            wp_send_json_success($calendar_events);

        } catch (Exception $e) {
            error_log('Error en get_calendar_events: ' . $e->getMessage());
            wp_send_json_error('Error al obtener eventos del calendario');
        }
    }

    public function render_logs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }

        ?>
        <div class="wrap">
            <h1>Registros del Sistema</h1>
            
            <?php
            $log_file = WP_CONTENT_DIR . '/menphis-debug.log';
            
            if (file_exists($log_file)) {
                $logs = file_get_contents($log_file);
                ?>
                <div class="card" style="padding: 20px; margin-top: 20px;">
                    <div style="margin-bottom: 20px;">
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=menphis-logs&action=clear'), 'clear_logs'); ?>" 
                           class="button button-secondary">
                            Limpiar logs
                        </a>
                    </div>
                    
                    <div style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                        <pre style="margin: 0; overflow: auto; max-height: 500px; white-space: pre-wrap;">
                            <?php echo esc_html($logs); ?>
                        </pre>
                    </div>
                </div>
                <?php
            } else {
                ?>
                <div class="notice notice-info">
                    <p>No hay logs disponibles en este momento.</p>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    public function enqueue_frontend_assets() {
        // Asegurarse de que los estilos se cargan también en el checkout
        if (is_checkout()) {
            wp_enqueue_style(
                'bootstrap-icons',
                'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css'
            );
        }
        
        // jQuery UI
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
        wp_enqueue_script('jquery-ui-datepicker');
        
        // Bootstrap CSS
        wp_enqueue_style(
            'bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'
        );
        
        // Bootstrap Icons
        wp_enqueue_style(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css'
        );
        
        // jQuery
        wp_enqueue_script('jquery');
        
        // Bootstrap JS
        wp_enqueue_script(
            'bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.2',
            true
        );
        
        // Nuestros estilos
        wp_enqueue_style(
            'menphis-booking-css',
            MENPHIS_RESERVA_PLUGIN_URL . 'assets/css/booking-form.css',
            array('bootstrap-css'),
            time()
        );
        
        // Nuestro script
        wp_enqueue_script(
            'menphis-booking-js',
            MENPHIS_RESERVA_PLUGIN_URL . 'assets/js/booking-form.js',
            array('jquery', 'bootstrap-js'),
            time(),
            true
        );

        // Localizar script para AJAX y datos adicionales
        wp_localize_script('menphis-booking-js', 'menphisBooking', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('menphis_booking_nonce'),
            'checkout_url' => wc_get_checkout_url()
        ));
    }

    public function render_booking_form_shortcode($atts) {
        // Iniciar buffer de salida
        ob_start();

        try {
            // Verificar si WooCommerce está activo
            if (!class_exists('WooCommerce')) {
                throw new Exception(__('WooCommerce debe estar instalado y activado.', 'menphis-reserva'));
            }

            // Obtener servicios disponibles
            $services = $this->get_active_services();
            
            // Obtener ubicaciones disponibles
            $locations = $this->get_active_locations();

            // Incluir la plantilla del formulario
            include MENPHIS_RESERVA_PLUGIN_DIR . 'templates/booking-form.php';

        } catch (Exception $e) {
            echo '<div class="menphis-booking-error">';
            echo esc_html($e->getMessage());
            echo '</div>';
        }

        // Retornar el contenido del buffer
        return ob_get_clean();
    }

    private function get_active_services() {
        if (!isset($this->services)) {
            $this->services = new Menphis_Services();
        }
        return $this->services->get_active_services();
    }

    private function get_active_locations() {
        if (!isset($this->locations)) {
            $this->locations = new MenphisLocations();
        }
        return $this->locations->get_locations();
    }

    public function ajax_remove_service_from_cart() {
        check_ajax_referer('menphis_booking_nonce', 'nonce');

        $cart_key = isset($_POST['cart_key']) ? sanitize_text_field($_POST['cart_key']) : '';
        
        if (empty($cart_key)) {
            wp_send_json_error(array('message' => 'Cart key no válido'));
            return;
        }

        // Eliminar item del carrito
        WC()->cart->remove_cart_item($cart_key);

        wp_send_json_success(array(
            'message' => 'Servicio eliminado correctamente',
            'cart_count' => WC()->cart->get_cart_contents_count()
        ));
    }

    public function add_materialize_check() {
        ?>
        <script>
            window.materializeLoaded = false;
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof M !== 'undefined') {
                    window.materializeLoaded = true;
                }
            });
        </script>
        <?php
    }
}

// Variable global para mantener una única instancia
global $menphis_reserva;

// Función para obtener la instancia única del plugin
function menphis_reserva() {
    global $menphis_reserva;
    
    if (!isset($menphis_reserva)) {
        $menphis_reserva = new MenphisReserva();
        $menphis_reserva->bookings->init(); // Llamar al método init
    }
    
    return $menphis_reserva;
}

// Inicializar solo una vez
add_action('plugins_loaded', 'menphis_reserva'); 