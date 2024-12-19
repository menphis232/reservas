add_action('woocommerce_after_order_notes', 'add_booking_fields_to_checkout');
add_action('woocommerce_review_order_before_payment', 'add_booking_summary_to_checkout');

function add_booking_fields_to_checkout($checkout) {
    echo '<div id="menphis_booking_fields" style="display:none;">';
    
    woocommerce_form_field('booking_location', array(
        'type' => 'hidden'
    ), $checkout->get_value('booking_location'));
    
    woocommerce_form_field('booking_date', array(
        'type' => 'hidden'
    ), $checkout->get_value('booking_date'));
    
    woocommerce_form_field('booking_time', array(
        'type' => 'hidden'
    ), $checkout->get_value('booking_time'));
    
    echo '</div>';
    
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Recuperar datos de la reserva
            var bookingData = JSON.parse(sessionStorage.getItem('menphis_booking_data'));
            if (bookingData) {
                $('#booking_location').val(bookingData.location);
                $('#booking_date').val(bookingData.date);
                $('#booking_time').val(bookingData.time);
            }
        });
    </script>
    <?php
}

function add_booking_summary_to_checkout() {
    ?>
    <div class="booking-summary-checkout" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
        <h3 style="margin-bottom: 15px;">Detalles de tu Reserva</h3>
        <div id="booking-summary-content"></div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            function updateBookingSummary() {
                var bookingData = JSON.parse(sessionStorage.getItem('menphis_booking_data'));
                if (bookingData) {
                    var date = new Date(bookingData.date);
                    var formattedDate = date.toLocaleDateString('es-ES', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    var summaryHtml = `
                        <div class="booking-detail-item">
                            <i class="bi bi-geo-alt"></i>
                            <span class="booking-detail-label">Ubicación:</span>
                            <span class="booking-detail-value">${bookingData.location_name}</span>
                        </div>
                        <div class="booking-detail-item">
                            <i class="bi bi-calendar"></i>
                            <span class="booking-detail-label">Fecha:</span>
                            <span class="booking-detail-value">${formattedDate}</span>
                        </div>
                        <div class="booking-detail-item">
                            <i class="bi bi-clock"></i>
                            <span class="booking-detail-label">Hora:</span>
                            <span class="booking-detail-value">${bookingData.time}</span>
                        </div>
                    `;

                    $('#booking-summary-content').html(summaryHtml);
                }
            }

            updateBookingSummary();
        });
    </script>
    <?php
}

// Asegurarnos de que los datos de la reserva se guarden en la orden
add_action('woocommerce_checkout_update_order_meta', 'save_booking_data_to_order', 10, 1);
function save_booking_data_to_order($order_id) {
    $booking_data = isset($_POST['booking_data']) ? $_POST['booking_data'] : '';
    if (empty($booking_data)) {
        // Intentar obtener datos del sessionStorage via JavaScript
        ?>
        <script>
            var bookingData = sessionStorage.getItem('menphis_booking_data');
            if (bookingData) {
                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'save_booking_data',
                        order_id: <?php echo $order_id; ?>,
                        booking_data: bookingData,
                        nonce: '<?php echo wp_create_nonce('save_booking_data'); ?>'
                    }
                });
            }
        </script>
        <?php
    }
}

// Agregar endpoint AJAX para guardar datos
add_action('wp_ajax_save_booking_data', 'ajax_save_booking_data');
add_action('wp_ajax_nopriv_save_booking_data', 'ajax_save_booking_data');

function ajax_save_booking_data() {
    check_ajax_referer('save_booking_data', 'nonce');
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $booking_data = isset($_POST['booking_data']) ? $_POST['booking_data'] : '';
    
    if ($order_id && $booking_data) {
        $data = json_decode(stripslashes($booking_data), true);
        
        $order = wc_get_order($order_id);
        if ($order) {
            $order->update_meta_data('_booking_location', sanitize_text_field($data['location']));
            $order->update_meta_data('_booking_date', sanitize_text_field($data['date']));
            $order->update_meta_data('_booking_time', sanitize_text_field($data['time']));
            $order->save();
        }
    }
    wp_die();
}

// Opcional: Agregar los detalles de la reserva a la descripción del producto
add_filter('woocommerce_order_item_name', 'add_booking_details_to_order_item', 10, 2);
function add_booking_details_to_order_item($item_name, $item) {
    if (is_checkout()) {
        $bookingData = '<div class="booking-details-item" style="font-size: 0.9em; color: #666; margin-top: 0.5em;"></div>';
        return $item_name . $bookingData;
    }
    return $item_name;
}

// Agregar el resumen en la página de orden recibida
add_action('woocommerce_thankyou', 'add_booking_details_to_thankyou', 10, 1);
function add_booking_details_to_thankyou($order_id) {
    $booking_id = WC()->session->get('booking_id');
    if (!$booking_id) return;

    $booking = get_post($booking_id);
    if (!$booking) return;

    $location_id = get_post_meta($booking_id, '_location_id', true);
    $booking_date = get_post_meta($booking_id, '_booking_date', true);
    $booking_time = get_post_meta($booking_id, '_booking_time', true);

    // Obtener el nombre de la ubicación
    $location = get_post($location_id);
    $location_name = $location ? $location->post_title : '';

    // Formatear la fecha
    $date = new DateTime($booking_date);
    $formatted_date = $date->format('l j \d\e F \d\e Y');

    ?>
    <div class="booking-summary-thankyou">
        <h2>Detalles de tu Reserva</h2>
        <ul>
            <li><strong>Nº de Reserva:</strong> <?php echo $booking->ID; ?></li>
            <li><strong>Ubicación:</strong> <?php echo esc_html($location_name); ?></li>
            <li><strong>Fecha:</strong> <?php echo esc_html($formatted_date); ?></li>
            <li><strong>Hora:</strong> <?php echo esc_html($booking_time); ?></li>
        </ul>
    </div>
    <?php

    // Limpiar el ID de la sesión
    WC()->session->__unset('booking_id');
}

<?php if (!defined('ABSPATH')) exit; ?>

<?php if (!is_user_logged_in()): ?>
    <div class="woocommerce-account-fields">
        <h3><?php _e('Información de Cuenta', 'menphis-reserva'); ?></h3>
        <p class="form-row form-row-wide create-account">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                <input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" 
                       id="createaccount" type="checkbox" name="createaccount" value="1" checked="checked" />
                <span><?php _e('¿Crear una cuenta?', 'menphis-reserva'); ?></span>
            </label>
        </p>

        <div class="create-account-fields" style="display: block;">
            <p class="form-row form-row-wide">
                <label for="account_password">
                    <?php _e('Contraseña', 'menphis-reserva'); ?> <span class="required">*</span>
                </label>
                <input type="password" class="input-text" name="account_password" id="account_password" />
            </p>
            <p class="form-row form-row-wide">
                <label for="account_password_confirm">
                    <?php _e('Confirmar contraseña', 'menphis-reserva'); ?> <span class="required">*</span>
                </label>
                <input type="password" class="input-text" name="account_password_confirm" id="account_password_confirm" />
            </p>
        </div>
    </div>
<?php endif; ?> 