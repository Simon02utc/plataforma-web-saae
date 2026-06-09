function modalConfirmarAccion({ 
    titulo = 'Confirmar', 
    mensaje = '¿Estás seguro?', 
    txtConfirmar = 'Confirmar', 
    tipo = 'eliminar-desactivar',

    //con 1 input
    conObservaciones = false,
    placeholderObservaciones = 'Escribe una observación...',
    observacionesRequeridas = true,
} = {}) {
    return new Promise((resolve) => {

        const modal               = document.getElementById('modalConfirmacion');
        const tituloEl            = document.getElementById('modalConfirmacionTitulo');
        const mensajeEl           = document.getElementById('modalConfirmacionMensaje');
        const btnConfirm          = document.getElementById('btnConfirmarAccion');
        const btnCancelar         = document.getElementById('btnCancelarConfirmacion');
        const btnCerrar           = document.getElementById('btnCerrarModalConfirmacion');
        const campoObservaciones  = document.getElementById('campoObservacionesModal');
        const inputObs            = document.getElementById('inputObservacionesModal');

        // Inyectar contenido
        tituloEl.textContent   = titulo;
        mensajeEl.innerHTML    = mensaje;
        btnConfirm.textContent = txtConfirmar;

        // Tipo visual del botón confirmar
        btnConfirm.className = 'btn-confirmar-accion';
        btnConfirm.classList.add(`btn-confirmar-accion-${tipo}`);

        // Mostrar u ocultar campo de observaciones
        if (conObservaciones) {
            inputObs.placeholder          = placeholderObservaciones;
            inputObs.value                = '';
            campoObservaciones.style.display = 'block';
        } else {
            campoObservaciones.style.display = 'none';
            inputObs.value = '';
        }

        // Abrir modal
        modal.classList.add('active');

        // Limpiar listeners anteriores clonando
        const nuevoConfirm  = btnConfirm.cloneNode(true);
        const nuevoCancelar = btnCancelar.cloneNode(true);
        const nuevoCerrar   = btnCerrar.cloneNode(true);

        btnConfirm.replaceWith(nuevoConfirm);
        btnCancelar.replaceWith(nuevoCancelar);
        btnCerrar.replaceWith(nuevoCerrar);

        function cerrar(resultado) {
            modal.classList.remove('active');
            resolve(resultado);
        }

        nuevoConfirm.addEventListener('click', () => {
            if (conObservaciones && observacionesRequeridas && !inputObs.value.trim()) {
                // Marcar el campo si está vacío y es requerido
                inputObs.classList.add('error');
                inputObs.focus();
                return;
            }
            inputObs.classList.remove('error');

            // Si tiene observaciones, devolver el texto; si no, true
            cerrar(conObservaciones ? inputObs.value.trim() : true);
        });

        nuevoCancelar.addEventListener('click', () => cerrar(false));
        nuevoCerrar.addEventListener('click',   () => cerrar(false));

        //quitar error al escribir
        inputObs.addEventListener('input', () => {
            if (inputObs.value.trim()) {
                inputObs.classList.remove('error');
            }
        });

    });
}