<?php
if (!defined('ABSPATH')) exit;
global $menphis_reserva;
?>

<div class="menphis-admin">
    <nav class="white z-depth-1">
        <div class="nav-wrapper">
            <h1 class="brand-logo black-text">Configuración</h1>
        </div>
    </nav>

    <div class="section">
        <div class="row">
            <!-- Tabs de navegación -->
            <div class="col s12">
                <ul class="tabs">
                    <li class="tab col s3"><a class="active" href="#general">General</a></li>
                    <li class="tab col s3"><a href="#form-builder">Constructor de Formulario</a></li>
                    <li class="tab col s3"><a href="#shortcodes">Shortcodes</a></li>
                    <li class="tab col s3"><a href="#notifications">Notificaciones</a></li>
                </ul>
            </div>

            <!-- Configuración General -->
            <div id="general" class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Configuración General</span>
                        <form id="general-settings-form">
                            <div class="row">
                                <div class="input-field col s12 m6">
                                    <input type="text" id="business_name" name="business_name" value="<?php echo esc_attr(get_option('menphis_business_name')); ?>">
                                    <label for="business_name">Nombre del Negocio</label>
                                </div>
                                <div class="input-field col s12 m6">
                                    <input type="email" id="business_email" name="business_email" value="<?php echo esc_attr(get_option('menphis_business_email')); ?>">
                                    <label for="business_email">Email del Negocio</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s12 m6">
                                    <select id="time_slot" name="time_slot">
                                        <option value="15" <?php selected(get_option('menphis_time_slot'), '15'); ?>>15 minutos</option>
                                        <option value="30" <?php selected(get_option('menphis_time_slot'), '30'); ?>>30 minutos</option>
                                        <option value="60" <?php selected(get_option('menphis_time_slot'), '60'); ?>>1 hora</option>
                                    </select>
                                    <label>Intervalo de Tiempo</label>
                                </div>
                                <div class="input-field col s12 m6">
                                    <select id="calendar_view" name="calendar_view">
                                        <option value="day">Diario</option>
                                        <option value="week">Semanal</option>
                                        <option value="month">Mensual</option>
                                    </select>
                                    <label>Vista de Calendario por Defecto</label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Constructor de Formulario -->
            <div id="form-builder" class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Constructor de Formulario Step by Step</span>
                        <div class="row">
                            <div class="col s12">
                                <ul class="collapsible form-steps">
                                    <li class="active">
                                        <div class="collapsible-header">
                                            <i class="material-icons">looks_one</i>
                                            Paso 1: Selección de Servicios
                                            <div class="switch right">
                                                <label>
                                                    Off
                                                    <input type="checkbox" checked>
                                                    <span class="lever"></span>
                                                    On
                                                </label>
                                            </div>
                                        </div>
                                        <div class="collapsible-body">
                                            <div class="row">
                                                <div class="input-field col s12">
                                                    <input type="text" id="step1_title" value="Selecciona tus servicios">
                                                    <label for="step1_title">Título del Paso</label>
                                                </div>
                                                <div class="col s12">
                                                    <p>Campos a mostrar:</p>
                                                    <p>
                                                        <label>
                                                            <input type="checkbox" class="filled-in" checked />
                                                            <span>Categorías de Servicios</span>
                                                        </label>
                                                    </p>
                                                    <p>
                                                        <label>
                                                            <input type="checkbox" class="filled-in" checked />
                                                            <span>Lista de Servicios</span>
                                                        </label>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- Más pasos aquí... -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shortcodes -->
            <div id="shortcodes" class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Shortcodes Disponibles</span>
                        <div class="row">
                            <div class="col s12">
                                <table class="highlight">
                                    <thead>
                                        <tr>
                                            <th>Shortcode</th>
                                            <th>Descripción</th>
                                            <th>Opciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>[menphis_booking_form]</code></td>
                                            <td>Formulario de reservas completo</td>
                                            <td>
                                                <code>style="default|modern"</code><br>
                                                <code>steps="3|4|5"</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><code>[menphis_services]</code></td>
                                            <td>Lista de servicios</td>
                                            <td>
                                                <code>category="id"</code><br>
                                                <code>layout="grid|list"</code>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notificaciones -->
            <div id="notifications" class="col s12">
                <!-- Contenido de notificaciones -->
            </div>

            <!-- Mantenimiento -->
            <div id="mantenimiento" class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Mantenimiento</span>
                        <form method="post" action="">
                            <?php wp_nonce_field('menphis_recreate_tables', 'menphis_nonce'); ?>
                            <button type="submit" name="menphis_recreate_tables" class="waves-effect waves-light btn red">
                                Recrear Tablas
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para la página de configuración */
.tabs .tab a {
    color: rgba(38, 166, 154, 0.7);
}

.tabs .tab a:hover,
.tabs .tab a.active {
    color: #26a69a;
}

.tabs .indicator {
    background-color: #26a69a;
}

.card .card-content .card-title {
    margin-bottom: 20px;
}

code {
    background: #f5f5f5;
    padding: 2px 5px;
    border-radius: 3px;
    color: #e91e63;
}

.form-steps .collapsible-header {
    display: flex;
    align-items: center;
}

.form-steps .collapsible-header i {
    margin-right: 1rem;
}

.form-steps .switch {
    margin-left: auto;
}

.form-steps .collapsible-body {
    padding: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Inicializar componentes de Materialize
    $('.tabs').tabs();
    $('.collapsible').collapsible();
    $('select').formSelect();
    
    // Guardar configuración
    $('#general-settings-form').on('submit', function(e) {
        e.preventDefault();
        // Aquí irá la lógica de guardar
    });
});
</script> 