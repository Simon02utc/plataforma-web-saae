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

    const form = document.getElementById('logoutForm');
    const btnLogout = document.getElementById('btnLogout');

    const modal = document.getElementById('modalCerrarSesion');
    const btnCancelar = document.getElementById('btnCancelarSesion');
    const btnCerrar = document.getElementById('btnCancelarCerrar');
    const btnConfirmar = document.getElementById('btnConfirmarLogout');

    if (!form || !btnLogout || !modal) return;

    /*=========== ABRIR MODAL ===========*/
    btnLogout.addEventListener('click', () => {
        modal.classList.add('active');
    });


    /*=========== CERRAR MODAL ===========*/
    function cerrarModalCierreSesion(){
        modal.classList.remove('active');
    }

    btnCancelar.addEventListener('click', cerrarModalCierreSesion);
    btnCerrar.addEventListener('click', cerrarModalCierreSesion);


    /*=========== CONFIRMAR LOGOUT ===========*/
    btnConfirmar.addEventListener('click', async () => {

        // setLoadingFormulario(btnConfirmar, true);

        //mostrar spinner manualmente
        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = `
            <span class="spinner-boton-cerrar-sesion"></span>
            <span>Espera</span>
        `;
        
        const csrfMeta =
            document.querySelector('meta[name="csrf-token"]');

        const csrf =
            csrfMeta ? csrfMeta.getAttribute('content') : null;

        try {

            const response = await fetch(form.action, {

                method: 'POST',
                headers: {
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    'Accept': 'application/json'
                },
                body: new FormData(form),
                credentials: 'same-origin'
            });

            const data =
                await response.json().catch(() => null);

            /*===== CSRF expirado =====*/
            if (response.status === 419){
                cerrarModalCierreSesion();

                displayModal(`<p class="error">Sesión expirada. Recarga la página.</p>`);
                return;
            }

            /*===== ERROR =====*/
            if (!response.ok){
                cerrarModalCierreSesion();

                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = `<span>Sí, cerrar sesión</span>
                <span class="spinner-boton-cerrar-sesion"></span>
                <span class="texto-spinner-boton-cerrar-sesion">Espera</span>`;

                displayModal(`<p class="error">Error al cerrar sesión (${response.status}).</p>`);
                return;
            }

            /*===== EXITO =====*/
            const redirect = data?.redirect_url;
            
            if (redirect) {
                setTimeout(() => {
                window.location.replace(redirect);
                }, 2000); //2000 ms = 2 segundos
            }

        } catch (e){

            console.error(e);
            cerrarModalCierreSesion();

            displayMensajeToast(`<p class="error">Error de conexión. Intenta de nuevo.</p>`);

            // setLoadingFormulario(btnConfirmar, false);

            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = `<span>Sí, cerrar sesión</span>
                <span class="spinner-boton-cerrar-sesion"></span>
                <span class="texto-spinner-boton-cerrar-sesion">Espera</span>`;
        }

    });

});
