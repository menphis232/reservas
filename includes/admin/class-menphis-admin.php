<?php
class MenphisAdmin {
    private $db;
    private $bookings;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->bookings = new Menphis_Bookings();

        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Agregar columnas de reserva en pedidos
        add_filter('manage_edit-shop_order_columns', array($this, 'add_order_booking_columns'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'render_order_booking_columns'), 10, 2);
        
        // Agregar metabox de reserva en pedidos
        add_action('add_meta_boxes', array($this, 'add_order_booking_metabox'));
    }

    public function enqueue_admin_scripts($hook) {
        // Solo cargar en las páginas de nuestro plugin
        if (strpos($hook, 'menphis') === false) {
            return;
        }

        // Debug
        error_log('Menphis Admin: Cargando scripts para página: ' . $hook);

        // Material Icons
        wp_enqueue_style(
            'material-icons',
            'https://fonts.googleapis.com/icon?family=Material+Icons'
        );
        
        // Materialize CSS
        wp_enqueue_style(
            'materialize-css',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/materialize.min.css',
            array(),
            '1.0.0'
        );

        // FullCalendar CSS (solo en la página de calendario)
        if (strpos($hook, 'menphis-calendar') !== false) {
            wp_enqueue_style(
                'fullcalendar-bundle',
                'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css'
            );

            wp_enqueue_script(
                'fullcalendar-bundle',
                'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js',
                array('jquery'),
                '5.11.3',
                true
            );

            // Locales de FullCalendar
            wp_enqueue_script(
                'fullcalendar-locales',
                'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js',
                array('fullcalendar-bundle'),
                '5.11.3',
                true
            );
        }

        // Plugin CSS
        wp_enqueue_style(
            'menphis-admin-style',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/menphis-reserva-admin.css',
            array('materialize-css'),
            MENPHIS_RESERVA_VERSION
        );

        // jQuery
        wp_enqueue_script('jquery');

        // Materialize JS
        wp_enqueue_script(
            'materialize-js',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/materialize.min.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Plugin JS
        wp_enqueue_script(
            'menphis-admin-script',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/menphis-reserva-admin.js',
            array('jquery', 'materialize-js'),
            MENPHIS_RESERVA_VERSION,
            true
        );

        // Scripts específicos según la página
        if (strpos($hook, 'menphis-calendar') !== false) {
            wp_enqueue_script(
                'menphis-calendar-js',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/menphis-calendar.js',
                array('jquery', 'materialize-js', 'fullcalendar-bundle', 'fullcalendar-locales'),
                MENPHIS_RESERVA_VERSION,
                true
            );
        } else if (strpos($hook, 'menphis-bookings') !== false) {
            // Debug
            error_log('Cargando scripts de reservas en: ' . $hook);
            
            // Asegurarnos de que jQuery y Materialize están cargados
            wp_enqueue_script('jquery');
            wp_enqueue_script('materialize-js');
            wp_enqueue_script('select2-js');
            
            wp_enqueue_script(
                'menphis-bookings-js',
                MENPHIS_RESERVA_PLUGIN_URL . 'assets/js/menphis-bookings.js',
                array('jquery', 'materialize-js', 'select2-js'),
                time(),
                true
            );

            wp_localize_script('menphis-bookings-js', 'menphisBookings', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('menphis_bookings_nonce'),
                'debug' => WP_DEBUG
            ));
        } else if (strpos($hook, 'menphis-staff') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('materialize-js');
            
            wp_enqueue_script(
                'menphis-staff-js',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/menphis-staff.js',
                array('jquery', 'materialize-js', 'select2-js'),
                MENPHIS_RESERVA_VERSION . '.' . time(),
                true
            );

            wp_localize_script('menphis-staff-js', 'menphisStaff', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('menphis_staff_nonce'),
                'debug' => WP_DEBUG
            ));
        } else if (strpos($hook, 'menphis-locations') !== false) {
            $locations_nonce = wp_create_nonce('menphis_locations_nonce');
            error_log('Nonce generado en admin: ' . $locations_nonce);

            wp_enqueue_script(
                'menphis-locations-js',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/menphis-locations.js',
                array('jquery', 'materialize-js'),
                MENPHIS_RESERVA_VERSION . '.' . time(),
                true
            );

            wp_localize_script('menphis-locations-js', 'menphisLocations', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => $locations_nonce,
                'debug' => WP_DEBUG,
                'hook' => $hook
            ));
        } else if (strpos($hook, 'menphis-customers') !== false) {
            wp_enqueue_script(
                'menphis-customers-js',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/menphis-customers.js',
                array('jquery', 'materialize-js'),
                MENPHIS_RESERVA_VERSION . '.' . time(),
                true
            );

            // Localización para el script de clientes
            wp_localize_script('menphis-customers-js', 'menphisCustomers', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('menphis_customers_nonce'),
                'debug' => WP_DEBUG
            ));
        }

        // Localización para JavaScript
        wp_localize_script('menphis-admin-script', 'menphisAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('menphis_admin_nonce'),
            'currentPage' => $hook,
            'pluginUrl' => plugin_dir_url(dirname(dirname(__FILE__)))
        ));

        // En la función enqueue_admin_scripts, agrega:
        wp_enqueue_style(
            'select2-css',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            array(),
            '4.1.0'
        );

        wp_enqueue_script(
            'select2-js',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            array('jquery'),
            '4.1.0',
            true
        );
    }

    public function render_admin_page() {
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/dashboard.php';
    }

    public function render_calendar_page() {
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/calendar.php';
    }

    public function render_bookings_page() {
        error_log("=== ANTES DE LLAMAR A RENDER_BOOKINGS_PAGE ===");
        $bookings = new Menphis_Bookings();
        $bookings->render_bookings_page();
        error_log("=== DESPUÉS DE LLAMAR A RENDER_BOOKINGS_PAGE ===");
    }

    public function render_settings_page() {
        include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/settings.php';
    }

    public function get_total_bookings($status = '') {
        $sql = "SELECT COUNT(*) FROM {$this->db->prefix}menphis_bookings";
        
        if ($status) {
            $sql .= $this->db->prepare(" WHERE status = %s", $status);
        }
        
        return (int) $this->db->get_var($sql);
    }

    public function get_recent_bookings($limit = 5) {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->db->prefix}menphis_bookings 
            ORDER BY created_at DESC LIMIT %d",
            $limit
        );
        
        return $this->db->get_results($sql);
    }

    public function get_bookings_by_status($status) {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->db->prefix}menphis_bookings 
            WHERE status = %s 
            ORDER BY created_at DESC",
            $status
        );
        
        return $this->db->get_results($sql);
    }

    public function get_total_revenue() {
        $sql = "SELECT SUM(total_amount) FROM {$this->db->prefix}menphis_bookings 
                WHERE status = 'completed'";
        
        return (float) $this->db->get_var($sql);
    }

    public function get_today_bookings() {
        $today = date('Y-m-d');
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->db->prefix}menphis_bookings 
            WHERE DATE(booking_date) = %s",
            $today
        );
        
        return $this->db->get_results($sql);
    }

    public function add_order_booking_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            if ($key === 'order_status') {
                $new_columns['booking_info'] = 'Reserva';
            }
        }
        
        return $new_columns;
    }

    public function render_order_booking_columns($column, $post_id) {
        if ($column === 'booking_info') {
            $order = wc_get_order($post_id);
            $booking_date = $order->get_meta('_booking_date');
            $booking_time = $order->get_meta('_booking_time');
            $location_id = $order->get_meta('_booking_location_id');
            
            if ($booking_date && $booking_time && $location_id) {
                $location = $this->get_location_details($location_id);
                echo sprintf(
                    '<strong>Fecha:</strong> %s<br><strong>Hora:</strong> %s<br><strong>Ubicación:</strong> %s',
                    date('d/m/Y', strtotime($booking_date)),
                    date('H:i', strtotime($booking_time)),
                    $location ? $location->name : 'N/A'
                );
            } else {
                echo '-';
            }
        }
    }

    public function add_order_booking_metabox() {
        add_meta_box(
            'menphis_booking_details',
            'Detalles de la Reserva',
            array($this, 'render_order_booking_metabox'),
            'shop_order',
            'side',
            'high'
        );
    }

    public function render_order_booking_metabox($post) {
        $order = wc_get_order($post->ID);
        $booking_date = $order->get_meta('_booking_date');
        $booking_time = $order->get_meta('_booking_time');
        $location_id = $order->get_meta('_booking_location_id');
        
        if ($booking_date && $booking_time && $location_id) {
            $location = $this->get_location_details($location_id);
            ?>
            <div class="booking-details">
                <p>
                    <strong>Fecha:</strong><br>
                    <?php echo date('d/m/Y', strtotime($booking_date)); ?>
                </p>
                <p>
                    <strong>Hora:</strong><br>
                    <?php echo date('H:i', strtotime($booking_time)); ?>
                </p>
                <p>
                    <strong>Ubicación:</strong><br>
                    <?php echo $location ? esc_html($location->name) : 'N/A'; ?>
                </p>
                <?php if ($location && $location->address): ?>
                <p>
                    <strong>Dirección:</strong><br>
                    <?php echo esc_html($location->address); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php
        } else {
            echo '<p>No hay información de reserva para este pedido.</p>';
        }
    }

    public function render_logs_page() {
        ?>
        <div class="wrap">
            <h1>Logs del Sistema</h1>
            
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

    public function init() {
        // Manejar la acción de limpiar logs
        if (isset($_GET['page']) && $_GET['page'] === 'menphis-logs' && 
            isset($_GET['action']) && $_GET['action'] === 'clear' && 
            check_admin_referer('clear_logs')) {
            
            $log_file = WP_CONTENT_DIR . '/menphis-debug.log';
            if (file_exists($log_file)) {
                unlink($log_file);
                add_action('admin_notices', function() {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p>Los logs han sido limpiados correctamente.</p>
                    </div>
                    <?php
                });
            }
            
            wp_redirect(admin_url('admin.php?page=menphis-logs'));
            exit;
        }
    }
} 