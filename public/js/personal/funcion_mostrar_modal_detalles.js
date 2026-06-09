/*---------------------Mostrar modal de detalles-------------------------*/
function displayModalDetalles(htmlMessage) {
    const modalDetalles = document.getElementById('modal-detalles');
    const modalMessageDetalles = document.getElementById('contenido-modal-detalles');
    const closeModalDetalles = document.querySelector('.modal-detalles .contenedor-modal-detalles .btn-cerrar-modal-detalles');

    if (!modalDetalles || !modalMessageDetalles) return; // evita errores si falta elemntos del modal

    modalMessageDetalles.innerHTML = htmlMessage;
    modalDetalles.style.display = 'flex';

    closeModalDetalles.onclick = () => (modalDetalles.style.display = 'none');
}

//Cerrar modal con llamado de detaller con llamado --- IMPORTANTE
function cerrarModalDetalles() {
    const modalDetalles = document.getElementById('modal-detalles');

    if (!modalDetalles) return;

    modalDetalles.style.display = 'none';

}
/*-----------------------------------------------------------------------*/