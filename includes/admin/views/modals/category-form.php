<div id="modal-category" class="modal">
    <div class="modal-content">
        <h4>Categorías de Servicios</h4>
        
        <!-- Formulario para nueva categoría -->
        <form id="category-form" class="row">
            <div class="input-field col s12 m8">
                <input type="text" id="category-name" required>
                <label for="category-name">Nombre de la categoría</label>
            </div>
            <div class="col s12 m4">
                <button type="submit" class="btn waves-effect waves-light green">
                    <i class="material-icons left">add</i>Agregar
                </button>
            </div>
        </form>

        <!-- Lista de categorías -->
        <div class="collection" id="categories-list">
            <?php
            $categories = get_terms(array(
                'taxonomy' => 'service_category',
                'hide_empty' => false,
            ));
            foreach ($categories as $category): ?>
                <div class="collection-item" data-id="<?php echo $category->term_id; ?>">
                    <div class="category-item">
                        <span class="category-name"><?php echo esc_html($category->name); ?></span>
                        <div class="secondary-content">
                            <a href="#!" class="edit-category">
                                <i class="material-icons">edit</i>
                            </a>
                            <a href="#!" class="delete-category">
                                <i class="material-icons red-text">delete</i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-light btn-flat">Cerrar</a>
    </div>
</div> 