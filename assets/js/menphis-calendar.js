jQuery(document).ready(function($) {
    'use strict';

    console.log('Menphis Calendar JS iniciando...');

    function initCalendar() {
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) {
            console.error('Elemento calendario no encontrado');
            return;
        }

        try {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                themeSystem: 'standard',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día'
                },
                selectable: true,
                selectMirror: true,
                dayMaxEvents: true,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                events: {
                    url: menphisAjax.ajaxurl,
                    method: 'GET',
                    extraParams: {
                        action: 'get_calendar_events',
                        _nonce: menphisAjax.calendarNonce
                    },
                    failure: function() {
                        alert('Error al cargar los eventos');
                    }
                },
                select: function(info) {
                    // Aquí puedes abrir el modal de nueva reserva con la fecha seleccionada
                    console.log('Fecha seleccionada:', info.startStr);
                },
                eventClick: function(info) {
                    // Aquí puedes abrir el modal de detalles de la reserva
                    console.log('Evento clickeado:', info.event);
                }
            });

            calendar.render();
            console.log('Calendario inicializado correctamente');
        } catch (error) {
            console.error('Error al inicializar el calendario:', error);
        }
    }

    // Esperar a que FullCalendar esté disponible
    var checkFullCalendar = setInterval(function() {
        if (typeof FullCalendar !== 'undefined') {
            clearInterval(checkFullCalendar);
            initCalendar();
        }
    }, 100);
}); 