<?php if (!defined('ABSPATH')) exit; ?>
<div id="location-modal" class="menphis-modal">
    <div class="menphis-modal-content">
        <div class="menphis-modal-header">
            <h2>Nueva Ubicación</h2>
            <button class="menphis-modal-close">&times;</button>
        </div>
        <div class="menphis-modal-body">
            <form id="location-form" class="menphis-form">
                <!-- Nombre -->
                <div class="menphis-form-group">
                    <label for="location-name">Nombre</label>
                    <input type="text" id="location-name" name="name" class="menphis-input" required>
                </div>

                <!-- Información -->
                <div class="menphis-form-group">
                    <label for="location-info">Información</label>
                    <textarea id="location-info" name="info" class="menphis-textarea" rows="4"></textarea>
                    <p class="field-description">Este texto se puede insertar en las notificaciones con el código {location_info}</p>
                </div>

                <!-- Selector de Personal -->
                <div class="menphis-form-group">
                    <label for="staff-selector">Personal asignado</label>
                    <div class="staff-selector-wrapper">
                        <select id="staff-selector" class="menphis-select" data-placeholder="Ningún trabajador seleccionado">
                            <option value="">Ningún trabajador seleccionado</option>
                            <?php foreach ($this->get_staff_members() as $staff): ?>
                                <option value="<?php echo esc_attr($staff->id); ?>">
                                    <?php echo esc_html($staff->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="menphis-modal-footer">
            <button type="button" class="button modal-cancel">Cancelar</button>
            <button type="button" class="button button-primary save-location">Guardar</button>
        </div>
    </div>
</div> 