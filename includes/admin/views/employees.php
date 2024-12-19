<?php if (!defined('ABSPATH')) exit; ?>

<div class="menphis-admin">
    <nav class="white z-depth-1">
        <div class="nav-wrapper container">
            <h1 class="brand-logo black-text">Empleados</h1>
        </div>
    </nav>

    <div class="container">
        <!-- Barra de herramientas -->
        <div class="card">
            <div class="card-content">
                <div class="row mb-0">
                    <div class="col s12">
                        <a class="btn waves-effect waves-light modal-trigger" href="#modal-employee">
                            <i class="material-icons left">person_add</i>Nuevo empleado
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de empleados -->
        <div class="card">
            <div class="card-content">
                <table class="striped highlight responsive-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Servicios</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="employees-list">
                        <?php
                        $employees = get_users(array('role' => 'menphis_employee'));
                        foreach ($employees as $employee):
                            $phone = get_user_meta($employee->ID, 'phone', true);
                            $services = get_user_meta($employee->ID, 'services', true);
                            $status = get_user_meta($employee->ID, 'status', true);
                        ?>
                        <tr>
                            <td><?php echo $employee->ID; ?></td>
                            <td><?php echo $employee->display_name; ?></td>
                            <td><?php echo $employee->user_email; ?></td>
                            <td><?php echo $phone; ?></td>
                            <td><?php echo is_array($services) ? implode(', ', $services) : ''; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $status; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td>
                                <a href="#!" class="edit-employee" data-id="<?php echo $employee->ID; ?>">
                                    <i class="material-icons">edit</i>
                                </a>
                                <a href="#!" class="edit-schedule" data-employee-id="<?php echo $employee->ID; ?>">
                                    <i class="material-icons">schedule</i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de horario -->
<?php include MENPHIS_RESERVA_PLUGIN_DIR . 'includes/admin/views/schedule-form.php'; ?> 