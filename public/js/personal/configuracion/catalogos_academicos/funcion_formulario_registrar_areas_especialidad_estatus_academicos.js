/*---------------------Limpiar formulario-------------------------------*/
function clearForm(formId) {
    document.getElementById(formId).reset();
}
/*-----------------------------------------------------------------------*/


//SPINNER PARA BOTONES CON ICONO O TEXTO (DE TABLA Y FORMULARIO)
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

function setLoadingTabla(button, isLoading) {
    const iconoBoton = button.querySelector('i');
    const textoBoton = button.querySelector('span:not(.spinner-tabla):not(.texto-spinner-tabla)');
    const spinner = button.querySelector('.spinner-tabla');
    const textoSpinner = button.querySelector('.texto-spinner-tabla');

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


/*-----1 solo document.addEventListener('DOMContentLoaded', () => {-----*/
document.addEventListener('DOMContentLoaded', () => {

/*--------------------FUNCION REGISTRAR RELOJES------------------------*/
    const personalForm = document.getElementById('form-registrar-area-especialidad');
    if (personalForm) {
        personalForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(personalForm);
            const button = personalForm.querySelector('.btn-guardar-enviar');
            setLoadingFormulario(button, true);

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = csrfMeta ? csrfMeta.getAttribute('content') : null;

            try {
                const response = await fetch(personalForm.action, {
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
                    displayModal(`<p class="advertencia">Problemas de validación:</p><ul class="listado-error-422">${list}</ul>`);
                    return;
                }

                if (!response.ok) {
                    displayModal(`<p class="error">${data?.message ?? `Error inesperado (${response.status}).`}</p>`);
                    return;
                }

                displayMensajeToast(`<p class="exito">${data?.message ?? 'Área de especialidad registrado correctamente.'}</p>`);
                clearForm('form-registrar-area-especialidad');
                setTimeout(() => location.reload(), 3000); //Recargar la pagina 3segundos despues de recibir Exito

            } catch (error) {
                console.error(error);
                displayMensajeToast(`<p class="error">Error de conexión al registrar la área. Revisa tu servidor o recarga la página.</p>`);
            } finally {
                setLoadingFormulario(button, false);
            }
        });
    }
/*-----------------------------------------------------------------------*/


/*------------------------FUNCION REGISTRAR PARSERS---------------------------*/
    const roleForm = document.getElementById('form-registrar-estatus-escolar');
    if (roleForm) {
        roleForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(roleForm);
            const button = roleForm.querySelector('.btn-guardar-enviar');
            setLoadingFormulario(button, true);

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = csrfMeta ? csrfMeta.getAttribute('content') : null;

            try {
                const response = await fetch(roleForm.action, {
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
                    displayModal(`<p class="advertencia">Problemas de validación:</p><ul class="listado-error-422">${list}</ul>`);
                    return;
                }

                if (!response.ok) {
                    displayMensajeToast(`<p class="error">${data?.message ?? `Error inesperado (${response.status}).`}</p>`);
                    return;
                }

                displayMensajeToast(`<p class="exito">${data?.message ?? 'Estatus escolar registrado correctamente.'}</p>`);
                clearForm('form-registrar-estatus-escolar');
                setTimeout(() => location.reload(), 3000); //Recargar la pagina 3segundos despues de recibir Exito


            } catch (error) {
                console.error(error);
                displayMensajeToast(`<p class="error">Error de conexión al registrar el estatus. Revisa tu servidor o recarga la página.</p>`);
            } finally {
                setLoadingFormulario(button, false);
            }
        });
    }
/*-----------------------------------------------------------------------*/
});