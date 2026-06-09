function clearForm(formId) {
    document.getElementById(formId).reset();
}

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
    const form = document.getElementById('form-login-estudiante');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        const button = form.querySelector('.acceder-enviar');
        setLoadingFormulario(button, true);//---Activa el spinner

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = csrfMeta ? csrfMeta.getAttribute('content') : null;

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

            //validacion de campos (Request->validate)
            if (response.status === 422) {
                const errors = data?.errors ?? {};
                const list = Object.values(errors).flat().map(msg => `<li>${msg}</li>`).join('');
                displayModal(`<p class="advertencia">Problemas de validación:</p>
                                <ul class="listado-error-422">${list}</ul>
                            `);
                return;
            }

            //credenciales incorrectas
            if (response.status === 401) {
                displayMensajeToast(`<p class="advertencia">${data?.message ?? 'Credenciales incorrectas.'}</p>`);
                return;
            }

            //403 = cuenta desactivada / no autorizado
            if (response.status === 403) {
                displayModal(`<p class="advertencia">${data?.message ?? 'No autorizado.'}</p>`);
                return;
            }

            //CSRF expirado
            if (response.status === 419) {
                displayModal(`<p class="error">Sesión expirada. Recarga la página.</p>`);
                return;
            }
            
            //otros no ok, son errores(500, etc):
            if (!response.ok) {
                displayModal(`<p class="error">Error inesperado (${response.status}).</p>`);
                return;
            }

            displayMensajeToast(`<p class="exito">${data?.message ?? 'Sesión iniciada correctamente.'}</p>`);
            clearForm('form-login-estudiante');

            //redireccionar con tiempo
            const redirect = data?.redirect_url;
            if (redirect) {
                setTimeout(() => {
                    window.location.href = redirect;
                }, 2000); //2000 ms = 2 segundos
            }

        } catch (error) {
            console.error(error);
            displayMensajeToast(`<p class="error">Error de conexión al iniciar sesión. <span class="ext">Revisa tu servidor o recarga la página.</span></p>`);
        } finally {
            setLoadingFormulario(button, false);
        }
    });
});