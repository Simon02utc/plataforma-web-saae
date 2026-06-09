<div id="modal-recuperar-contrasena" class="modal-recuperar-contrasena">
    <div class="contenedor-modal-recuperar-contrasena">
        <span class="btn-cerrar-modal-recuperar-contrasena">&times;</span>

        <h1 class="titulo-modal-recuperar-contrasena">
            <span>Recuperar contraseña</span>
        </h1>

        <form id="form-recuperar-contrasena" method="POST" action="{{ route('grup_personal.enviar_correo_enlace_recuperacion_contrasena_personal') }}" accept-charset="UTF-8">
            @csrf

            <div class="form-inputs">
                <div class="input-box">
                    <input type="email" name="email" class="input-field input-field-contra" placeholder="micorreo@gmail.com" title="Su correo debe ser válido" autocomplete="off" required>
                    <i class="ri-mail-line icon"></i>
                </div>

                <div class="input-box">
                    <button type="submit" id="submit-recover-password" class="input-submit acceder-enviar">
                        <span>Enviar correo</span>
                        <span class="spinner"></span>
                        <span class="texto-spinner">Espera</span>
                    </button>
                </div>

                <p class="texto-modal-recuperar-contrasena"><b>Importante:</b> Solo tendrás 3 oportunidades por día para recuperar tu contraseña.</p>
                <p class="texto-modal-recuperar-contrasena"><b>Nota:</b> Verifica la <i class="fa-solid fa-inbox"></i> bandeja principal y/o en <i class="fa-solid fa-circle-exclamation"></i> Spam si no aparece el correo.</p>
            </div>
        </form>
    </div>
</div>