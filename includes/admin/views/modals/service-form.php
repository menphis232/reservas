<div id="modal-service" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h4 id="service-modal-title">Nuevo Servicio</h4>
        <form id="service-form">
            <input type="hidden" id="service-id">
            
            <div class="row">
                <div class="input-field col s12">
                    <input type="text" id="service-name" required>
                    <label for="service-name">Nombre del servicio</label>
                </div>
            </div>

            <div class="row">
                <div class="input-field col s12 m6">
                    <select id="service-category" required>
                        <option value="" disabled selected>Seleccionar categoría</option>
                        <?php
                        $categories = get_terms(array(
                            'taxonomy' => 'service_category',
                            'hide_empty' => false,
                        ));
                        foreach ($categories as $category): ?>
                            <option value="<?php echo $category->term_id; ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Categoría</label>
                </div>
                <div class="input-field col s12 m6">
                    <input type="number" id="service-duration" min="1" value="60" required>
                    <label for="service-duration">Duración (minutos)</label>
                </div>
            </div>

            <div class="row">
                <div class="input-field col s12 m6">
                    <input type="number" id="service-price" min="0" step="0.01" required>
                    <label for="service-price">Precio</label>
                </div>
                <div class="col s12 m6">
                    <label>
                        <input type="checkbox" class="filled-in" id="service-active" checked>
                        <span>Activo</span>
                    </label>
                </div>
            </div>

            <div class="row">
                <div class="input-field col s12">
                    <textarea id="service-description" class="materialize-textarea"></textarea>
                    <label for="service-description">Descripción</label>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-light btn-flat">Cancelar</a>
        <button type="submit" form="service-form" class="waves-effect waves-light btn green">
            <i class="material-icons left">save</i>Guardar
        </button>
    </div>
</div> 