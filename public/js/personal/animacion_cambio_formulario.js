const formsConfig = [
    { btn: '#btn-formulario-1', form: '.form-1', index: 0 },
    { btn: '#btn-formulario-2', form: '.form-2', index: 1 },
    { btn: '#btn-formulario-3', form: '.form-3', index: 2 },
].map(config => ({
    ...config,
    btnEl: document.querySelector(config.btn),
    formEl: document.querySelector(config.form)
})).filter(config => config.btnEl && config.formEl);

const col1 = document.querySelector(".col-1");
const allForms = formsConfig.map(c => c.formEl);
const allBtns = formsConfig.map(c => c.btnEl);

function activateForm(activeIndex) {
    allBtns.forEach((btn, i) => {
        const isActive = i === activeIndex;
        btn.style.backgroundColor = isActive ? "var(--color-principal)" : "var(--color-btn)";
        btn.style.color = isActive ? "var(--color-texto-btn-activo)" : "var(--color-texto-btn)";
    });

    allForms.forEach((form, i) => {
        form.classList.toggle('form-activo', i === activeIndex);
    });

    if (col1) col1.style.borderRadius = "0 0% 0% 0";
}

formsConfig.forEach((config, index) => {
    config.btnEl.addEventListener('click', () => activateForm(index));
});

document.addEventListener('DOMContentLoaded', () => {
    activateForm(0);
});