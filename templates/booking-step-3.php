<div class="menphis-booking-form container">
    <div class="row">
        <div class="col s12">
            <h4 class="center-align">Reserva tu cita</h4>
            
            <!-- Stepper -->
            <ul class="stepper horizontal">
                <li class="step completed">
                    <div class="step-title waves-effect">Servicios</div>
                </li>
                <li class="step completed">
                    <div class="step-title waves-effect">Fecha y Hora</div>
                </li>
                <li class="step active">
                    <div class="step-title waves-effect">Confirmación</div>
                </li>
            </ul>

            <div class="step-content">
                <div class="card">
                    <div class="card-content">
                        <!-- Detalles de la ubicación -->
                        <div class="section">
                            <div class="row">
                                <div class="col s12">
                                    <h5 class="header">
                                        <i class="material-icons left">location_on</i>
                                        Ubicación
                                    </h5>
                                    <?php 
                                    $location = $this->get_location_details($location_id);
                                    if ($location): ?>
                                        <p class="flow-text"><?php echo esc_html($location->name); ?></p>
                                        <?php if (!empty($location->address)): ?>
                                            <p class="grey-text"><?php echo esc_html($location->address); ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <!-- Detalles de fecha y hora -->
                        <div class="section">
                            <div class="row">
                                <div class="col s12">
                                    <h5 class="header">
                                        <i class="material-icons left">event</i>
                                        Fecha y Hora
                                    </h5>
                                    <p class="flow-text">
                                        <?php echo date('d/m/Y', strtotime($booking_date)); ?>
                                        <span class="grey-text">a las</span>
                                        <?php echo date('H:i', strtotime($booking_time)); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <!-- Servicios seleccionados -->
                        <div class="section">
                            <div class="row">
                                <div class="col s12">
                                    <h5 class="header">
                                        <i class="material-icons left">spa</i>
                                        Servicios
                                    </h5>
                                    <ul class="collection">
                                        <?php 
                                        $total = 0;
                                        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item):
                                            $product = $cart_item['data'];
                                            $price = $cart_item['line_total'];
                                            $total += $price;
                                            $duration = get_post_meta($product->get_id(), '_service_duration', true);
                                        ?>
                                            <li class="collection-item">
                                                <div class="row mb-0">
                                                    <div class="col s12 m6">
                                                        <span class="title"><?php echo esc_html($product->get_name()); ?></span>
                                                        <p class="grey-text">
                                                            <i class="material-icons tiny">schedule</i> 
                                                            <?php echo esc_html($duration); ?> min
                                                        </p>
                                                    </div>
                                                    <div class="col s6 m3">
                                                        <span class="grey-text">
                                                            Cantidad: <?php echo esc_html($cart_item['quantity']); ?>
                                                        </span>
                                                    </div>
                                                    <div class="col s6 m3 right-align">
                                                        <span class="blue-text text-darken-1">
                                                            <?php echo wc_price($price); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="section blue lighten-5">
                            <div class="row">
                                <div class="col s12">
                                    <h5 class="right-align">
                                        Total: <span class="blue-text"><?php echo wc_price($total); ?></span>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de navegación -->
                <div class="section">
                    <div class="row">
                        <div class="col s12 m6">
                            <button class="btn waves-effect waves-light grey" id="back-to-step-2">
                                <i class="material-icons left">arrow_back</i>
                                Anterior
                            </button>
                        </div>
                        <div class="col s12 m6 right-align">
                            <button class="btn waves-effect waves-light blue" id="proceed-to-checkout">
                                Proceder al Pago
                                <i class="material-icons right">payment</i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 

<?php
$staff_id = WC()->session->get('booking_staff_id');
if ($staff_id) {
    $staff = get_staff_details($staff_id); // Implementa esta función
    echo '<p><strong>Profesional asignado:</strong> ' . esc_html($staff->display_name) . '</p>';
}
?>