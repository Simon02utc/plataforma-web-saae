document.addEventListener('DOMContentLoaded', () => {

    /*--------------------FUNCION ABRIR MODAL DEL FORMULARIO------------------------*/
    const botonesAbrirFormulario = document.querySelectorAll('.btn-abrir-modal-formulario-registro');

    botonesAbrirFormulario.forEach(boton => {

        boton.addEventListener('click', () => {

            // obtener el ID del modal
            const idModal = boton.dataset.modalFormularioRegistro;

            // buscar el modal
            const modal = document.getElementById(idModal);

            if(modal){
                modal.style.display = 'flex';
            }

        });

    });


    // boton de cerrar
    const botonesCerrarFormulario = document.querySelectorAll('.btn-cerrar-modal-fomulario');

    botonesCerrarFormulario.forEach(btnCerrar => {

        btnCerrar.addEventListener('click', () => {

            // Busca el modal padre mas cercano
            const modal = btnCerrar.closest('.modal-formulario');

            if(modal){
                modal.style.display = 'none';
            }

        });

    });


    /*--------------------FUNCION LIMPIAR FORMULARIO------------------------*/
    function clearFormulario(formId) {

        const form = document.getElementById(formId);

        if(form){
            form.reset();
        }

    }

    // TODOS los botones limpiar
    const botonesLimpiarFormulario = document.querySelectorAll('.btn-limpiar-formulario');

    botonesLimpiarFormulario.forEach(btn => {

        btn.addEventListener('click', () => {

            // obtener el ID del formulario
            const formId = btn.dataset.limpiarFormulario;

            // limpiar formulario
            clearFormulario(formId);

        });

    });

});