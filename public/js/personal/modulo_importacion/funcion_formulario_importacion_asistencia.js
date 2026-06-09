/*---------------------Limpiar formulario-------------------------------*/
function clearForm(formId) {
    document.getElementById(formId).reset();
}

document.getElementById('btn-cancelar').addEventListener('click', function() {
    clearForm('formulario-importar-excel')
});
/*-----------------------------------------------------------------------*/


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

/*------------------------Envio del Formulario---------------------------*/
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formulario-importar-excel');
    const btn = document.getElementById('btn-importar');

    if (form && btn) {
        form.addEventListener('submit', function() {
            setLoadingFormulario(btn, true);
        });
    }

});
/*-----------------------------------------------------------------------*/