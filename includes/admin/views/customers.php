<?php if (!defined('ABSPATH')) exit; ?>

<div class="menphis-admin">
    <div class="row">
        <div class="col s12">
            <div class="card-panel blue darken-2 white-text">
                <h4>Gestión de Clientes</h4>
            </div>
        </div>
    </div>

    <div class="section">
        <!-- Filtros y botón nuevo cliente -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="row">
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <i class="material-icons prefix">search</i>
                                    <input type="text" id="search_customers" class="search-input">
                                    <label for="search_customers">Buscar clientes...</label>
                                </div>
                            </div>
                            <div class="col s12 m6">
                                <a class="btn waves-effect waves-light blue right modal-trigger" href="#modal-new-customer">
                                    <i class="material-icons left">person_add</i>
                                    Nuevo Cliente
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Clientes -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Listado de Clientes</span>
                        <table class="striped highlight centered responsive-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Notas</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="customers-table">
                                <tr>
                                    <td colspan="7" class="center-align">Cargando clientes...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo/Editar Cliente -->
<div id="modal-new-customer" class="modal">
    <div class="modal-content">
        <h4>Nuevo Cliente</h4>
        <div class="row">
            <form class="col s12" id="customer-form" onsubmit="return false;">
                <input type="hidden" id="customer_id" name="customer_id" value="">
                
                <div class="row">
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">person</i>
                        <input id="first_name" name="first_name" type="text" class="validate" required>
                        <label for="first_name">Nombre</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">person</i>
                        <input id="last_name" name="last_name" type="text" class="validate">
                        <label for="last_name">Apellidos</label>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">email</i>
                        <input id="email" name="email" type="email" class="validate" required>
                        <label for="email">Email</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <i class="material-icons prefix">phone</i>
                        <input id="phone" name="phone" type="tel" class="validate">
                        <label for="phone">Teléfono</label>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field col s12">
                        <i class="material-icons prefix">note</i>
                        <textarea id="notes" name="notes" class="materialize-textarea"></textarea>
                        <label for="notes">Notas</label>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
        <button type="submit" form="customer-form" class="waves-effect waves-green btn blue">
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

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.search-input {
    padding-left: 4rem !important;
    width: calc(100% - 4rem) !important;
    box-sizing: border-box !important;
}

/* Ajustes para el campo de búsqueda */
.input-field .prefix {
    width: 3rem;
    font-size: 1.5rem;
}

.input-field .prefix ~ input {
    margin-left: 3rem;
    width: calc(100% - 3rem);
}
</style> 