<div class="menphis-booking-form container">
    <div class="row">
        <div class="col s12">
            <h4 class="center-align">Reserva tu cita</h4>
            
            <!-- Stepper -->
            <ul class="stepper horizontal">
                <li class="step completed">
                    <div class="step-title waves-effect">Servicios</div>
                </li>
                <li class="step active">
                    <div class="step-title waves-effect">Fecha y Hora</div>
                </li>
                <li class="step">
                    <div class="step-title waves-effect">Confirmación</div>
                </li>
            </ul>

            <div class="step-content">
                <!-- Selección de fecha -->
                <div class="section">
                    <h5>Seleccione fecha</h5>
                    <div class="input-field">
                        <input type="text" id="booking_date" class="datepicker" readonly>
                        <label for="booking_date">Fecha</label>
                    </div>
                </div>

                <!-- Selección de hora -->
                <div class="section time-section" style="display: none;">
                    <h5>Seleccione hora</h5>
                    <div class="time-slots">
                        <div class="preloader-wrapper small active">
                            <div class="spinner-layer spinner-blue-only">
                                <div class="circle-clipper left">
                                    <div class="circle"></div>
                                </div>
                                <div class="gap-patch">
                                    <div class="circle"></div>
                                </div>
                                <div class="circle-clipper right">
                                    <div class="circle"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de navegación -->
                <div class="section">
                    <div class="row">
                        <div class="col s6">
                            <button class="btn waves-effect waves-light grey" id="back-to-step-1">
                                <i class="material-icons left">arrow_back</i>Anterior
                            </button>
                        </div>
                        <div class="col s6 right-align">
                            <button class="btn waves-effect waves-light" id="goto-step-3" disabled>
                                Siguiente<i class="material-icons right">arrow_forward</i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 