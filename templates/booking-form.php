<?php if (!defined('ABSPATH')) exit; ?>

<div class="menphis-booking-wrapper">
    <div class="bs-stepper">
        <!-- Header del Stepper -->
        <div class="bs-stepper-header">
            <div class="step active" data-step="1">
                <button type="button" class="step-trigger">
                    <span class="bs-stepper-circle">
                        <i class="bi bi-cart-check"></i>
                    </span>
                    <span class="bs-stepper-label">Servicios</span>
                </button>
            </div>
            
            <div class="bs-stepper-line"></div>
            
            <div class="step" data-step="2">
                <button type="button" class="step-trigger">
                    <span class="bs-stepper-circle">
                        <i class="bi bi-calendar-date"></i>
                    </span>
                    <span class="bs-stepper-label">Fecha y Hora</span>
                </button>
            </div>
            
            <div class="bs-stepper-line"></div>
            
            <div class="step" data-step="3">
                <button type="button" class="step-trigger">
                    <span class="bs-stepper-circle">
                        <i class="bi bi-check-lg"></i>
                    </span>
                    <span class="bs-stepper-label">Confirmación</span>
                </button>
            </div>
        </div>

        <!-- Contenido del Stepper -->
        <div class="bs-stepper-content mt-4">
            <!-- Paso 1: Servicios -->
            <div class="step-content active" data-step="1">
                <div class="services-list">
                    <?php 
                    $has_services = false;
                    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                        $product_id = $cart_item['product_id'];
                        if ($this->is_product_service($product_id)) {
                            $has_services = true;
                            $product = wc_get_product($product_id);
                            ?>
                            <div class="card service-item mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title mb-3"><?php echo esc_html($product->get_name()); ?></h5>
                                            <div class="service-meta">
                                                <span class="me-3">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php 
                                                    $duration = get_post_meta($product_id, '_service_duration', true);
                                                    echo esc_html($duration) . ' min';
                                                    ?>
                                                </span>
                                                <span class="price">
                                                    <?php echo $product->get_price_html(); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <button class="btn btn-outline-danger btn-sm remove-service" 
                                                data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    
                    if (!$has_services) {
                        ?>
                        <div class="alert alert-info">
                            <p class="text-center mb-3">No hay servicios en tu carrito.</p>
                            <div class="text-center">
                                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" 
                                   class="btn btn-primary">
                                    Ver servicios disponibles
                                </a>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="form-group mb-4">
                            <label for="booking_location" class="form-label">Ubicación</label>
                            <select id="booking_location" class="form-select" required>
                                <option value="" selected disabled>Selecciona una ubicación</option>
                                <?php foreach ($locations as $location) : ?>
                                    <option value="<?php echo esc_attr($location->id); ?>">
                                        <?php echo esc_html($location->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor selecciona una ubicación
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary next-step">
                                Continuar <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <!-- Paso 2: Fecha y Hora -->
            <div class="step-content" data-step="2">
                <div class="row g-4">
                    <div class="col-md-7">
                        <div class="form-group">
                            <label class="form-label">Selecciona una fecha</label>
                            <div id="booking-calendar" class="calendar-wrapper"></div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">Horarios disponibles</label>
                            <div class="time-slots">
                                <?php
                                // Horarios disponibles cada 30 minutos
                                $start = strtotime('09:00');
                                $end = strtotime('20:00');
                                
                                for ($time = $start; $time <= $end; $time += (30 * 60)) {
                                    printf(
                                        '<div class="time-slot">
                                            <input type="radio" class="btn-check" name="booking_time" 
                                                id="time_%s" value="%s" required>
                                            <label class="btn btn-outline-primary" for="time_%s">
                                                %s
                                            </label>
                                        </div>',
                                        date('Hi', $time),
                                        date('H:i', $time),
                                        date('Hi', $time),
                                        date('H:i', $time)
                                    );
                                }
                                ?>
                            </div>
                            <div class="invalid-feedback">
                                Por favor selecciona una hora
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button class="btn btn-outline-secondary btn-lg previous-step">
                        <i class="bi bi-arrow-left me-2"></i> Atrás
                    </button>
                    <button class="btn btn-primary btn-lg next-step">
                        Continuar <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- Paso 3: Confirmación -->
            <div class="step-content" data-step="3">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Resumen de tu Reserva</h5>
                        
                        <div class="booking-summary">
                            <?php
                            $total = 0;
                            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                                $product = wc_get_product($cart_item['product_id']);
                                if ($this->is_product_service($cart_item['product_id'])) {
                                    $total += $cart_item['line_total'];
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="mb-1"><?php echo esc_html($product->get_name()); ?></h6>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php 
                                                $duration = get_post_meta($cart_item['product_id'], '_service_duration', true);
                                                echo esc_html($duration) . ' min';
                                                ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="fw-bold">
                                                <?php echo wc_price($cart_item['line_total']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                            
                            <hr class="my-4">
                            
                            <div class="booking-details mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <i class="bi bi-geo-alt me-2"></i>
                                            <strong>Ubicación:</strong>
                                            <span class="location-display"></span>
                                        </p>
                                        <p class="mb-2">
                                            <i class="bi bi-calendar me-2"></i>
                                            <strong>Fecha:</strong>
                                            <span class="date-display"></span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <i class="bi bi-clock me-2"></i>
                                            <strong>Hora:</strong>
                                            <span class="time-display"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="total-section">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Total</h5>
                                    <h4 class="mb-0"><?php echo wc_price($total); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button class="btn btn-outline-secondary btn-lg previous-step">
                        <i class="bi bi-arrow-left me-2"></i> Atrás
                    </button>
                    <button type="button" class="btn btn-success btn-lg finish-booking">
                        Finalizar Reserva <i class="bi bi-check-lg ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="booking-form" method="post">
    <?php wp_nonce_field('booking_form', 'booking_form_nonce'); ?>
    <input type="hidden" name="booking_data" id="booking_data" />
    <input type="hidden" name="action" value="process_booking" />
    
    <!-- ... resto del formulario ... -->

    <div class="d-flex justify-content-between">
        <button class="btn btn-outline-secondary btn-lg previous-step">
            <i class="bi bi-arrow-left me-2"></i> Atrás
        </button>
        <button type="button" class="btn btn-success btn-lg finish-booking">
            Finalizar Reserva <i class="bi bi-check-lg ms-2"></i>
        </button>
    </div>
</form> 