<?php if (!defined('ABSPATH')) exit; ?>

<div class="customer-selector">
    <form method="get">
        <input type="hidden" name="page" value="menphis-bookings-dashboard">
        <select name="customer_id" onchange="this.form.submit()">
            <option value="0"><?php _e('Todos los clientes', 'menphis-reserva'); ?></option>
            <?php foreach ($customers as $customer): ?>
                <option value="<?php echo esc_attr($customer->ID); ?>" 
                        <?php selected(isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0, $customer->ID); ?>>
                    <?php echo esc_html($customer->display_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div> 