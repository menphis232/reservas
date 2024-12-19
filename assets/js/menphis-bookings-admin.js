jQuery(document).ready(function($) {
    'use strict';

    // Debug helper
    function debug(message) {
        if (window.console && window.console.log) {
            console.log('[Menphis Debug]:', message);
        }
    }

    // Verificar que Materialize está disponible
    if (typeof M === 'undefined') {
        debug('Error: Materialize no está cargado');
        return;
    }

    try {
        debug('Inicializando componentes...');

        // Inicializar modales
        var modals = document.querySelectorAll('.modal');
        if (modals.length > 0) {
            M.Modal.init(modals, {
                dismissible: true,
                opacity: 0.5,
                inDuration: 250,
                outDuration: 250,
                startingTop: '4%',
                endingTop: '10%',
                onOpenStart: function() {
                    debug('Abriendo modal');
                },
                onOpenEnd: function() {
                    debug('Modal abierto');
                }
            });
            debug('Modales inicializados');
        }

        // Inicializar selects
        var selects = document.querySelectorAll('select');
        if (selects.length > 0) {
            M.FormSelect.init(selects);
            debug('Selects inicializados');
        }

        // Inicializar tooltips
        var tooltips = document.querySelectorAll('.tooltipped');
        if (tooltips.length > 0) {
            M.Tooltip.init(tooltips);
            debug('Tooltips inicializados');
        }

        // Manejar clic en nueva reserva
        $(document).on('click', '.new-booking', function(e) {
            e.preventDefault();
            debug('Click en nueva reserva');
            
            var modalEl = document.querySelector('#modal-booking');
            if (modalEl) {
                var modal = M.Modal.getInstance(modalEl);
                if (modal) {
                    modal.open();
                } else {
                    debug('Error: No se pudo obtener la instancia del modal');
                }
            } else {
                debug('Error: Modal no encontrado');
            }
        });

        // Inicializar datepicker si existe
        var datepickers = document.querySelectorAll('.datepicker');
        if (datepickers.length > 0) {
            M.Datepicker.init(datepickers, {
                format: 'dd/mm/yyyy',
                firstDay: 1,
                i18n: {
                    months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                    monthsShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    weekdays: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
                    weekdaysShort: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
                    cancel: 'Cancelar',
                    done: 'Aceptar'
                }
            });
            debug('Datepickers inicializados');
        }

        debug('Todos los componentes inicializados correctamente');

    } catch (error) {
        console.error('[Menphis Error]:', error);
    }
}); 