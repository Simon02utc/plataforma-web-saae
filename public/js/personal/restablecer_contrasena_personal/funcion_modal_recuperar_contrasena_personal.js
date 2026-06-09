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
    const formRecover = document.getElementById('form-recuperar-contrasena');

    if (formRecover) {
        formRecover.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(formRecover);
            const button = formRecover.querySelector('#submit-recover-password');
            setLoadingFormulario(button, true);//---Activa el spinner

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = csrfMeta ? csrfMeta.getAttribute('content') : null;

            try {
                const response = await fetch(formRecover.action, {
                    method: 'POST',
                    headers: {
                        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                        'Accept': 'application/json',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await response.json().catch(() => null);

                if (response.status === 422) {
                    const errors = data?.errors ?? {};
                    const list = Object.values(errors).flat().map(msg => `<li>${msg}</li>`).join('');
                    displayModal(`<p class="advertencia">Problemas de validación:</p><ul class="listado-error-422">${list}</ul>`);
                    return;
                }

                if (response.status === 429) {
                    displayModal(`<p class="error">Has agotado tus intentos permitidos por hoy.</p>`);
                    return;
                }
                
                if (response.status === 404) {
                    displayMensajeToast(`<p class="advertencia">${data?.message ?? 'No se encontró la información solicitada.'}</p>`);
                    return;
                }

                if (!response.ok) {
                    displayModal(`<p class="error">${data?.message ?? `Error inesperado (${response.status}).`}</p>`);
                    return;
                }

                displayMensajeToast(`<p class="exito">${data?.message ?? 'Si el correo existe, se enviará el enlace.'}</p>`);
                clearForm('form-recuperar-contrasena');
                //document.getElementById('modal-recuperar-contrasena').style.display = 'none';

            } catch (error) {
                console.error(error);
                displayMensajeToast(`<p class="error">Error de conexión.</p><p class="ext">Revisa tu servidor o recarga la página.</p>`);
            } finally {
                setLoadingFormulario(button, false); //---Descativa el spinner
            }
        });
    }
});


