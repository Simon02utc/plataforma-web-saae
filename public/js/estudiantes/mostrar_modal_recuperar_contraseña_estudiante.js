/*-------------Mostrar modal de recuperacion de contraseña-----------------*/
document.getElementById('recuperar-contrasena-estudiante').addEventListener('click', function(event) {
    event.preventDefault();
    document.getElementById('modal-recuperar-contrasena-estudiante').style.display = 'flex';
});

// Cerrar el modal al hacer clic fuera de wl
//window.onclick = function(event) {
//    if (event.target == document.getElementById('modal-recuperar-contrasena')) {
//        document.getElementById('modal-recuperar-contrasena').style.display = 'none';
//    }
//}

// Cerrar el modal al hacer clic en el botón de cierre
document.querySelector('.btn-cerrar-modal-recuperar-contrasena').addEventListener('click', function() {
    document.getElementById('modal-recuperar-contrasena-estudiante').style.display = 'none';
});

/*-----------------------------------------------------------------------*/

/*-------Limpiar formulario de recuperación de contraseña-----------------*/
function clearForm(formId) {
    document.getElementById(formId).reset();
}
/*-----------------------------------------------------------------------*/