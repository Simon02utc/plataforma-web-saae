function setLoadingFormulario(button, isLoading) {
    const iconoBoton = button.querySelector('i');
    const textoBoton = button.querySelector('span:not(.spinner):not(.texto-spinner)');
    const spinner = button.querySelector('.spinner');
    const textoSpinner = button.querySelector('.texto-spinner');

    button.disabled = isLoading;

    if (iconoBoton) {
        iconoBoton.style.display = isLoading ? 'none' : 'inline';
    }

    if (textoBoton) {
        textoBoton.style.display = isLoading ? 'none' : 'inline';
    }

    if (spinner) {
        spinner.style.display = isLoading ? 'inline-block' : 'none';
    }

    if (textoSpinner) {
        textoSpinner.style.display = isLoading ? 'inline' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const contador = document.getElementById('contador-activacion');
    const mensajeExpirado = document.getElementById('mensaje-expirado-activacion');
    const mensajeFormulario = document.getElementById('mensaje-formulario-activacion');
    const form = document.getElementById('form-activar-cuenta-estudiante');
    const button = document.getElementById('btn-activar-cuenta');

    if (!contador || !form || !button) return;

    let segundosRestantes = Math.max(0, Math.ceil(Number(contador.dataset.segundosRestantes || 0)));
    const elementosFormulario = form.querySelectorAll('input, button');

    function mostrarMensaje(html, tipo = 'error') {
        if (!mensajeFormulario) return;

        mensajeFormulario.className = `alerta alerta-${tipo}`;
        mensajeFormulario.innerHTML = html;
        mensajeFormulario.style.display = 'block';
    }

    function limpiarMensaje() {
        if (!mensajeFormulario) return;

        mensajeFormulario.innerHTML = '';
        mensajeFormulario.style.display = 'none';
        mensajeFormulario.className = 'alerta';
    }

    function formatearTiempo(totalSegundos) {
        totalSegundos = Math.max(0, Math.floor(totalSegundos));

        const horas = Math.floor(totalSegundos / 3600);
        const minutos = Math.floor((totalSegundos % 3600) / 60);
        const segundos = totalSegundos % 60;

        return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
    }

    function bloquearFormulario() {
        elementosFormulario.forEach(el => {
            el.disabled = true;
        });
    }

    function actualizarContador() {
        if (segundosRestantes <= 300) {
            contador.style.color = '#B42318';
        }

        if (segundosRestantes <= 0) {
            contador.textContent = '00:00:00';
            bloquearFormulario();

            if (mensajeExpirado) {
                mensajeExpirado.style.display = 'block';
            }

            mostrarMensaje('El enlace ya expiró. Ya no puedes activar tu cuenta desde esta página.', 'error');
            return false;
        }

        contador.textContent = formatearTiempo(segundosRestantes);
        segundosRestantes--;
        return true;
    }

    // Si el navegador restaura la página desde bfcache, se recarga para volver a validar el token
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
        }
    });

    /*-------------FUNCION FORMULARIO DE ACTIVAR CUENTA-----------------*/
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        limpiarMensaje();

        if (segundosRestantes <= 0) {
            bloquearFormulario();
            mostrarMensaje('El enlace ya expiró. Ya no puedes activar tu cuenta desde esta página.', 'error');
            return;
        }

        const formData = new FormData(form);
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = csrfMeta ? csrfMeta.getAttribute('content') : null;

        setLoadingFormulario(button, true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    'Accept': 'application/json'
                },
                body: formData,
                credentials: 'same-origin',
            });

            const data = await response.json().catch(() => null);

            if (response.status === 422) {
                const errors = data?.errors ?? {};
                const list = Object.values(errors).flat().map(msg => `<li>${msg}</li>`).join('');

                if (list) {
                    mostrarMensaje(
                        `<p>Problemas de validación:</p><ul class="lista-errores">${list}</ul>`,
                        'error'
                    );
                } else {
                    mostrarMensaje(data?.message ?? 'La solicitud no es válida.', 'error');
                }

                return;
            }

            if (!response.ok) {
                mostrarMensaje(data?.message ?? `Error inesperado (${response.status}).`, 'error');
                return;
            }

            mostrarMensaje(data?.message ?? 'Tu cuenta ha sido activada correctamente.', 'exito');
            bloquearFormulario();

            setTimeout(() => {
                window.location.replace(data?.redirect_url ?? '/');
            }, 3000);

        } catch (error) {
            console.error(error);
            mostrarMensaje('Error de conexión. Revisa tu servidor o intenta de nuevo.', 'error');
        } finally {
            setLoadingFormulario(button, false);
        }
    });
    /*------------------------------------------------------------------*/

    actualizarContador();

    const intervalo = setInterval(() => {
        const seguir = actualizarContador();

        if (!seguir) {
            clearInterval(intervalo);
        }
    }, 1000);
});