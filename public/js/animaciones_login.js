//==================================Animacion de Login a Registro==================================//
const loginBtn = document.querySelector("#login");
const registerBtn = document.querySelector("#register");
const loginForm = document.querySelector(".login-form");
const registerForm = document.querySelector(".register-form");
const col1 = document.querySelector(".col-1");

loginBtn.addEventListener('click', () => {
    // Cambia colores de botones
    loginBtn.style.backgroundColor = "var(--color-principal)";
    loginBtn.style.color = "#FFF";

    registerBtn.style.backgroundColor = "rgba(255, 255, 255, 0.6)";
    registerBtn.style.color = "var(--color-principal)";

    // Transforma formularios
    loginForm.style.transform = "translateX(0)";
    registerForm.style.transform = "translateX(100%)";

    // Cambia opacidades
    loginForm.style.opacity = 1;
    registerForm.style.opacity = 0;

    // Ajusta el borde del contenedor
    col1.style.borderRadius = "0 0% 0% 0";
});

registerBtn.addEventListener('click', () => {
    // Cambia colores de botones
    loginBtn.style.backgroundColor = "rgba(255, 255, 255, 0.6)";
    loginBtn.style.color = "var(--color-principal)";

    registerBtn.style.backgroundColor = "var(--color-principal)";
    registerBtn.style.color = "#FFF";

    // Transforma formularios
    loginForm.style.transform = "translateX(-100%)";
    registerForm.style.transform = "translateX(0)";

    // Cambia opacidades
    loginForm.style.opacity = 0;
    registerForm.style.opacity = 1;

    // Ajusta el borde del contenedor
    col1.style.borderRadius = "0 0% 0% 0";
});
//================================================================================================//

/*
const formsConfig = [
    { btn: '#btn-formulario-1', form: '.form-1', index: 0 },
    { btn: '#btn-formulario-2', form: '.form-2', index: 1 },
    { btn: '#btn-formulario-3', form: '.form-3', index: 2 },
].map(config => ({
    ...config,
    btnEl: document.querySelector(config.btn),
    formEl: document.querySelector(config.form)
})).filter(config => config.btnEl && config.formEl); // Solo los que existen

const col1 = document.querySelector(".col-1");
const allForms = formsConfig.map(c => c.formEl);
const allBtns = formsConfig.map(c => c.btnEl);

function activateForm(activeIndex) {
    // Actualizar botones
    allBtns.forEach((btn, i) => {
        const isActive = i === activeIndex;
        btn.style.backgroundColor = isActive ? "var(--color-principal)" : "var(--color-btn)";
        btn.style.color = isActive ? "var(--color-texto-btn-activo)" : "var(--color-texto-btn)";
    });

    // Actualizar formularios
    allForms.forEach((form, i) => {
        const offset = i - activeIndex;
        form.style.transform = `translateX(${offset * 100}%)`;
        form.style.opacity = i === activeIndex ? 1 : 0;
    });

    // Ajustar contenedor
    if (col1) col1.style.borderRadius = "0 0% 0% 0";
}

// Agregar eventos solo a los botones que existen
formsConfig.forEach((config, index) => {
    config.btnEl.addEventListener('click', () => activateForm(index));
});*/