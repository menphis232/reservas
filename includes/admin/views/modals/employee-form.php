<div id="modal-employee" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h4 id="employee-modal-title">Nuevo Empleado</h4>
        <form id="employee-form">
            <input type="hidden" id="employee-id">
            
            <div class="row">
                <div class="input-field col s12 m6">
                    <input type="text" id="employee-name" required>
                    <label for="employee-name">Nombre completo</label>
                </div>
                <div class="input-field col s12 m6">
                    <input type="email" id="employee-email" required>
                    <label for="employee-email">Email</label>
                </div>
            </div>

            <div class="row">
                <div class="input-field col s12 m6">
                    <input type="tel" id="employee-phone">
                    <label for="employee-phone">Teléfono</label>
                </div>
                <div class="input-field col s12 m6">
                    <select id="employee-status" required>
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="vacation">Vacaciones</option>
                    </select>
                    <label>Estado</label>
                </div>
            </div>

            <div class="row">
                <div class="col s12">
                    <h5>Servicios que realiza</h5>
                    <div class="collection" id="employee-services">
                        <?php
                        $services = $this->helpers->get_services_options();
                        foreach ($services as $service): ?>
                            <label class="collection-item">
                                <input type="checkbox" class="filled-in service-checkbox" value="<?php echo $service->id; ?>">
                                <span><?php echo esc_html($service->name); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="input-field col s12">
                    <textarea id="employee-notes" class="materialize-textarea"></textarea>
                    <label for="employee-notes">Notas</label>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-light btn-flat">Cancelar</a>
        <button type="submit" form="employee-form" class="waves-effect waves-light btn green">
            <i class="material-icons left">save</i>Guardar
        </button>
    </div>
</div> 