document.addEventListener('DOMContentLoaded', () => {

//=================================Para mostrar/ocultar contraseña=================================//
    function bindToggle(btnId, inputId) {
        const btn = document.getElementById(btnId);
        const input = document.getElementById(inputId);

        //si falta cualquiera, no se rompe el resto del JS
        if (!btn || !input) return;

        btn.addEventListener('click', () => {
        input.type = (input.type === 'password') ? 'text' : 'password';
        btn.classList.toggle('active');
        });
    }

    bindToggle('togglePasswordLogin', 'password-login');
    bindToggle('togglePassword', 'password-input');
    bindToggle('toggleConfirmPassword', 'confirm-password-input');
//================================================================================================//



//=============================Verificar si las contraseñas coinciden=============================//
    const password = document.getElementById('password-input');
    const confirmPassword = document.getElementById('confirm-password-input');

    // Si no estan en esa vista, no se romper nada
    if (!password || !confirmPassword) return;

    function validatePasswords() {
        const ok = password.value === confirmPassword.value;

        password.classList.toggle('error', !ok);
        confirmPassword.classList.toggle('error', !ok);
    }

    password.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
//================================================================================================//


});