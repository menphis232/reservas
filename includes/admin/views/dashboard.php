<?php
if (!defined('ABSPATH')) exit;

// Obtener estadísticas
$total_bookings = $this->get_total_bookings();
$pending_bookings = $this->get_total_bookings('pending');
$completed_bookings = $this->get_total_bookings('completed');
$total_revenue = $this->get_total_revenue();
$today_bookings = $this->get_today_bookings();
$recent_bookings = $this->get_recent_bookings(5);
?>

<div class="wrap menphis-dashboard">
    <!-- Encabezado -->
    <div class="row">
        <div class="col s12">
            <div class="card-panel blue darken-2 white-text z-depth-2">
                <h4 class="center-align">Panel de Control - Menphis Reserva</h4>
            </div>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="row stats-row">
        <div class="col s12 m6 l3">
            <div class="card hoverable">
                <div class="card-content blue-text text-darken-2">
                    <span class="card-title center-align">Total Reservas</span>
                    <h3 class="center-align" id="total-bookings"><?php echo $total_bookings; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col s12 m6 l3">
            <div class="card hoverable">
                <div class="card-content orange-text text-darken-2">
                    <span class="card-title center-align">Reservas Pendientes</span>
                    <h3 class="center-align" id="pending-bookings"><?php echo $pending_bookings; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col s12 m6 l3">
            <div class="card hoverable">
                <div class="card-content green-text text-darken-2">
                    <span class="card-title center-align">Reservas Completadas</span>
                    <h3 class="center-align" id="completed-bookings"><?php echo $completed_bookings; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col s12 m6 l3">
            <div class="card hoverable">
                <div class="card-content purple-text text-darken-2">
                    <span class="card-title center-align">Ingresos Totales</span>
                    <h3 class="center-align" id="total-revenue"><?php echo wc_price($total_revenue); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="row">
        <!-- Reservas de Hoy -->
        <div class="col s12 l6">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Reservas de Hoy</span>
                    <?php if (!empty($today_bookings)): ?>
                        <table class="striped highlight responsive-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Servicio</th>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo esc_html($booking->customer_name); ?></td>
                                        <td><?php echo esc_html($booking->service_name); ?></td>
                                        <td><?php echo esc_html(date('H:i', strtotime($booking->booking_time))); ?></td>
                                        <td>
                                            <span class="chip <?php echo esc_attr($booking->status); ?>">
                                                <?php echo esc_html($booking->status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="center-align">No hay reservas programadas para hoy.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Últimas Reservas -->
        <div class="col s12 l6">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Últimas Reservas</span>
                    <?php if (!empty($recent_bookings)): ?>
                        <table class="striped highlight responsive-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo esc_html(date('d/m/Y', strtotime($booking->booking_date))); ?></td>
                                        <td><?php echo esc_html($booking->customer_name); ?></td>
                                        <td>
                                            <span class="chip <?php echo esc_attr($booking->status); ?>">
                                                <?php echo esc_html($booking->status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo wc_price($booking->total_amount); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="center-align">No hay reservas recientes.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.menphis-dashboard {
    padding: 20px;
}

.stats-row {
    margin-bottom: 0;
}

.stats-row .card {
    margin-top: 0;
}

.card {
    border-radius: 8px !important;
    margin: 0.5rem 0 1rem 0;
}

.card .card-content {
    padding: 24px;
}

.card .card-title {
    font-size: 1.2rem;
    font-weight: 500;
    margin-bottom: 20px;
}

.card h3 {
    margin: 20px 0;
    font-size: 2.5rem;
}

.chip {
    border-radius: 16px;
    padding: 5px 12px;
}

.chip.pending {
    background-color: #ffd54f !important;
    color: #000;
}

.chip.completed {
    background-color: #81c784 !important;
    color: #fff;
}

.chip.cancelled {
    background-color: #e57373 !important;
    color: #fff;
}

@media only screen and (max-width: 992px) {
    .card h3 {
        font-size: 2rem;
    }
    
    .stats-row .col {
        margin-bottom: 20px;
    }
}
</style> 