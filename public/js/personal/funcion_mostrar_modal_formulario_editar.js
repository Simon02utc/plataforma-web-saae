/*--------------Mostrar el modal del formualrio para editar------------------*/
function displayModalFormularioEditar(htmlMessage) {
    const modalFormularioEditar = document.getElementById('modal-formulario-editar');
    const modalContenidoFormulario = document.getElementById('contenido-modal-formulario-editar');
    const btnCerrarModalFormularioEditar = document.querySelector('#modal-formulario-editar .btn-cerrar-modal-fomulario');

    if (!modalFormularioEditar || !modalContenidoFormulario) return; // evita errores si falta elemntos del modal

    modalContenidoFormulario.innerHTML = htmlMessage;
    modalFormularioEditar.style.display = 'flex';

    btnCerrarModalFormularioEditar.onclick = () => (modalFormularioEditar.style.display = 'none');
}

//Cerrar formulario con llamado --- IMPORTANTE
function cerrarModalFormularioEditar() {
    const modalFormularioEditar = document.getElementById('modal-formulario-editar');

    if (!modalFormularioEditar) return;

    modalFormularioEditar.style.display = 'none';

}
/*-----------------------------------------------------------------------*/