<div class="menphis-booking-form">
    <!-- Progreso -->
    <div class="booking-progress">
        <div class="step active">1. Servicios y Ubicación</div>
        <div class="step">2. Fecha y Hora</div>
        <div class="step">3. Confirmación</div>
    </div>

    <!-- Contenido -->
    <div class="booking-content">
        <h3>Por favor seleccione servicio</h3>
        
        <!-- Servicios en el carrito -->
        <div class="cart-services">
            <?php 
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                ?>
                <div class="service-item">
                    <div class="service-info">
                        <h4><?php echo esc_html($product->get_name()); ?></h4>
                        <div class="service-meta">
                            <span class="duration">
                                <?php 
                                $duration = get_post_meta($product->get_id(), '_service_duration', true);
                                echo esc_html($duration) . ' min';
                                ?>
                            </span>
                            <span class="price">
                                <?php echo wc_price($cart_item['line_total']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="service-quantity">
                        Cantidad: <?php echo esc_html($cart_item['quantity']); ?>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>

        <!-- Selección de ubicación -->
        <div class="location-selection">
            <div class="form-row">
                <label for="booking_location">Ubicación</label>
                <select id="booking_location" name="location" required>
                    <option value="">Seleccionar ubicación</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo esc_attr($location->id); ?>">
                            <?php echo esc_html($location->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Botón siguiente -->
        <div class="form-actions">
            <button type="button" class="button next-step" id="goto-step-2">Siguiente</button>
        </div>
    </div>
</div> 