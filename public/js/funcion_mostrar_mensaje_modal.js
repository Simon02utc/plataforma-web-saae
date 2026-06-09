/*--------------------Mostrar mensajes en modal------------------------*/
function displayModal(htmlMessage) {
    const modal = document.getElementById('modal-mensaje');
    const modalMessage = document.getElementById('mensaje-modal');
    const closeModal = document.querySelector('.modal-mensaje .btn-cerrar-modal');

    if (!modal || !modalMessage) return; // evita errores si falta elemntos del modal

    modalMessage.innerHTML = htmlMessage;
    modal.style.display = 'flex';

    closeModal.onclick = () => (modal.style.display = 'none');
}
/*-----------------------------------------------------------------------*/