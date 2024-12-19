<?php
if (!defined('ABSPATH')) exit;
?>

<div class="menphis-admin">
    <div class="row">
        <div class="col s12">
            <div class="card-panel blue darken-2 white-text">
                <h4>Calendario de Reservas</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.fc {
    max-width: 100%;
    background: white;
    padding: 10px;
}

.fc .fc-toolbar-title {
    font-size: 1.5em;
}

.fc .fc-button {
    background-color: #26a69a;
    border-color: #26a69a;
}

.fc .fc-button:hover {
    background-color: #2bbbad;
    border-color: #2bbbad;
}

.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active {
    background-color: #00897b;
    border-color: #00897b;
}

.fc-event {
    cursor: pointer;
}

.fc-event:hover {
    opacity: 0.9;
}
</style> 