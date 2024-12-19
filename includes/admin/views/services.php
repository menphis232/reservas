<?php if (!defined('ABSPATH')) exit; 
global $menphis_reserva;
?>

<div class="menphis-admin">
    <nav class="white z-depth-1">
        <div class="nav-wrapper">
            <h1 class="brand-logo black-text">Servicios</h1>
        </div>
    </nav>

    <div class="section">
        <!-- Lista de servicios -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="card-title">
                            Servicios Disponibles
                            <a href="#modal-service" class="btn waves-effect waves-light blue right modal-trigger">
                                <i class="material-icons left">add</i>Nuevo Servicio
                            </a>
                        </div>
                        <table class="striped highlight">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Duración</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $services = $menphis_reserva->get_services()->get_active_services();
                                if (empty($services)): ?>
                                    <tr>
                                        <td colspan="5" class="center-align">No hay servicios disponibles</td>
                                    </tr>
                                <?php else:
                                    foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo esc_html($service->name); ?></td>
                                        <td><?php echo $menphis_reserva->get_helpers()->format_duration($service->duration); ?></td>
                                        <td><?php echo $menphis_reserva->get_helpers()->format_price($service->price); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $service->active ? 'active' : 'inactive'; ?>">
                                                <?php echo $service->active ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="#!" class="edit-service tooltipped" data-id="<?php echo $service->id; ?>"
                                               data-position="top" data-tooltip="Editar">
                                                <i class="material-icons">edit</i>
                                            </a>
                                            <a href="#!" class="delete-service tooltipped" data-id="<?php echo $service->id; ?>"
                                               data-position="top" data-tooltip="Eliminar">
                                                <i class="material-icons red-text">delete</i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de servicio -->
<?php include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/modals/service-form.php'; ?> 