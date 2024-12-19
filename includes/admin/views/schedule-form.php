<?php if (!defined('ABSPATH')) exit; ?>

<div class="modal" id="modal-schedule">
    <div class="modal-content">
        <h4>Horario de Trabajo</h4>
        <form id="schedule-form" class="col s12">
            <input type="hidden" name="staff_id" id="schedule_staff_id" value="">
            
            <?php 
            $days = array(
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miércoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sábado',
                0 => 'Domingo'
            );
            ?>

            <?php foreach ($days as $day_num => $day_name): ?>
            <div class="row day-schedule" data-day="<?php echo $day_num; ?>">
                <div class="col s12">
                    <label>
                        <input type="checkbox" class="filled-in day-enabled" name="days[<?php echo $day_num; ?>][enabled]" />
                        <span><?php echo $day_name; ?></span>
                    </label>
                </div>
                <div class="col s12 m6">
                    <div class="input-field">
                        <input type="text" class="timepicker start-time" name="days[<?php echo $day_num; ?>][start_time]" disabled>
                        <label>Hora de inicio</label>
                    </div>
                </div>
                <div class="col s12 m6">
                    <div class="input-field">
                        <input type="text" class="timepicker end-time" name="days[<?php echo $day_num; ?>][end_time]" disabled>
                        <label>Hora de fin</label>
                    </div>
                </div>
                <div class="col s12">
                    <label>
                        <input type="checkbox" class="filled-in break-enabled" name="days[<?php echo $day_num; ?>][break_enabled]" disabled />
                        <span>Incluir descanso</span>
                    </label>
                </div>
                <div class="break-times" style="display: none;">
                    <div class="col s12 m6">
                        <div class="input-field">
                            <input type="text" class="timepicker break-start" name="days[<?php echo $day_num; ?>][break_start]" disabled>
                            <label>Inicio descanso</label>
                        </div>
                    </div>
                    <div class="col s12 m6">
                        <div class="input-field">
                            <input type="text" class="timepicker break-end" name="days[<?php echo $day_num; ?>][break_end]" disabled>
                            <label>Fin descanso</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="divider"></div>
            <?php endforeach; ?>
        </form>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
        <button type="submit" form="schedule-form" class="waves-effect waves-green btn blue">Guardar</button>
    </div>
</div>

<style>
.day-schedule {
    padding: 15px 0;
}
.break-times {
    margin-top: 10px;
    padding-left: 20px;
}
</style> 