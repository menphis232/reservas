<?php if (!defined('ABSPATH')) exit; ?>

<div class="menphis-admin">
    <div class="row">
        <div class="col s12">
            <div class="card-panel blue darken-2 white-text">
                <h4>Gestión de Ubicaciones</h4>
            </div>
        </div>
    </div>

    <div class="section">
        <!-- Filtros -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="row">
                            <div class="col s12">
                                <a class="btn waves-effect waves-light blue right modal-trigger" href="#modal-new-location">
                                    <i class="material-icons left">add_location</i>
                                    Nueva Ubicación
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Ubicaciones -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Listado de Ubicaciones</span>
                        <table class="striped highlight centered responsive-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="locations-table">
                                <tr>
                                    <td colspan="6" class="center-align">Cargando ubicaciones...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Ubicación -->
<div id="modal-new-location" class="modal">
    <div class="modal-content">
        <h4>Nueva Ubicación</h4>
        <div class="row">
            <form class="col s12" id="location-form">
                <input type="hidden" id="location_id" name="location_id" value="">
                <div class="row">
                    <div class="input-field col s12">
                        <i class="material-icons prefix">business</i>
                        <input id="location_name" name="location_name" type="text" class="validate" required>
                        <label for="location_name">Nombre de la Ubicación</label>
                    </div>
                </div>
                <div class="row">
                    <div class="input-field col s12">
                        <i class="material-icons prefix">location_on</i>
                        <textarea id="location_address" name="location_address" class="materialize-textarea"></textarea>
                        <label for="location_address">Dirección</label>
                    </div>
                </div>
                <div class="row">
                    <div class="input-field col s12">
                        <i class="material-icons prefix">phone</i>
                        <input id="location_phone" name="location_phone" type="tel" class="validate">
                        <label for="location_phone">Teléfono</label>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
        <button type="submit" form="location-form" class="waves-effect waves-green btn blue">
            <i class="material-icons left">save</i>
            Guardar
        </button>
    </div>
</div>

<style>
.modal {
    max-width: 66% !important;
    width: 95% !important;
    max-height: 95% !important;
    height: auto !important;
    border-radius: 8px;
    background-color: white;
}

.card {
    width: 100% !important;
    max-width: 100% !important;
}

.input-field {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

.btn i {
    line-height: inherit;
}

.responsive-table {
    margin-bottom: 0;
}
</style> 