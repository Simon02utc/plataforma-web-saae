//========= VARIABLES GLOBALES =========
let graficaResumen = null;
let graficaFaltas = null;
let estudiantes = obtenerEstudiantesDashboard();

let dashboardController = null;


//========= DOM =========
document.addEventListener('DOMContentLoaded', () => {
    iniciarGraficasDashboardEstudiante();

    iniciarFiltrosDashboard();
});


//Proteges contra XSS
function escapeHtml(texto) {

    const div = document.createElement('div');

    div.textContent = texto;

    return div.innerHTML;
}


//========= CAPA DE SEGURIDAD PARA ENDPOINTS HTML =========
async function fetchHtml(url, { method = 'GET', signal } = {}) {

    const options = {
        method,
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
        signal,
    };

    const response = await fetch(url, options);

    const html = await response.text().catch(() => '');

    // 401
    if (response.status === 401) {

        displayModal('<p class="error">No autorizado. Inicia sesión.</p>');

        return {
            ok: false,
            status: response.status,
            html: null
        };
    }

    // 403
    if (response.status === 403) {

        displayModal('<p class="error">Acceso denegado.</p>');

        return {
            ok: false,
            status: response.status,
            html: null
        };
    }

    // 404
    if (response.status === 404) {

        displayModal('<p class="error">El contenido solicitado no fue encontrado.</p>');

        return {
            ok: false,
            status: response.status,
            html: null
        };
    }

    // 419
    if (response.status === 419) {

        displayModal('<p class="error">La sesión expiró. Recarga la página.</p>');

        return {
            ok: false,
            status: response.status,
            html: null
        };
    }

    // ERROR GENERAL
    if (!response.ok) {

        displayModal(`
            <p class="error">
                Error inesperado (${response.status}).
            </p>
        `);

        return {
            ok: false,
            status: response.status,
            html: null
        };
    }

    return {
        ok: true,
        status: response.status,
        html
    };
}
//==========================================================


//========= ONTENER DATOS AL DASHBOARD =========
function obtenerDatosDashboard(contenedor = document) {

    const json = contenedor.querySelector('#dashboard-data-json');

    if (!json) return {};

    try {

        return JSON.parse(json.textContent);

    } catch (error) {

        console.error(error);
        displayMensajeToast('<p class="error">No se pudieron cargar los datos del dashboard.</p>');

        return {};
    }
}


//======== OBTENER ESTUDIANTES AL DASHBOARD =========
function obtenerEstudiantesDashboard(contenedor = document) {

    const json = contenedor.querySelector('#estudiantes-dashboard-json');

    if (!json) return [];

    try {

        return JSON.parse(json.textContent);

    } catch (error) {

        console.error(error);
        displayMensajeToast('<p class="error">No se pudo cargar el listado de estudiantes.</p>');

        return [];
    }
}


//SPINNER PARA BOTONES CON ICONO O TEXTO (DE TABLA Y FORMULARIO)
function setLoadingFormulario(button, isLoading) {
    const iconoBoton = button.querySelector('i');
    const textoBoton = button.querySelector('span:not(.spinner-dashboard-botones):not(.texto-spinner-dashboard-botones)');
    const spinner = button.querySelector('.spinner-dashboard-botones');
    const textoSpinner = button.querySelector('.texto-spinner-dashboard-botones');

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


//====== SISTEMA DE GRAFICAS ======
function iniciarGraficasDashboardEstudiante(contenedor = document) {

    if (typeof Chart === 'undefined') {

        console.error('Chart.js no está disponible');

        displayMensajeToast('<p class="error">No se pudieron cargar las gráficas.</p>');

        return;
    }

    const datos = obtenerDatosDashboard(contenedor);

    crearGraficaResumenAsistenciaEstudiante(datos.graficaAsistencia || {});
    crearGraficaAsistenciaDiasEstudiante(datos.graficaAsistenciaDias || {});
}


//========= GRAFICAS =========
function crearGraficaResumenAsistenciaEstudiante(datos) {
    const canvas = document.getElementById('graficaResumenAsistenciaEstudiante');

    if (!canvas || !datos.labels || !datos.data) return;

    if (graficaResumen) {
        graficaResumen.destroy();
    }

    graficaResumen = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: datos.labels,
            datasets: [{
                data: datos.data,
                backgroundColor: [
                    '#1B396A',
                    '#D9534F',
                    '#4c1d95',
                    '#6C757D'
                ],
                borderWidth: 1,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return `${context.label}: ${context.raw}`;
                        }
                    }
                }
            }
        }
    });
}

function crearGraficaAsistenciaDiasEstudiante(datos) {

    const canvas = document.getElementById('graficaAsistenciaDiasEstudiante');

    if (!canvas || !datos.labels || !datos.data) return;

    if (graficaFaltas) {
        graficaFaltas.destroy();
    }

    graficaFaltas = new Chart(canvas, {

        type: 'bar',

        data: {
            labels: datos.labels,

            datasets: [{
                label: 'Asistencia diaria',
                data: datos.data,

                backgroundColor: function(context) {

                    const value = context.raw;

                    // PRESENTE
                    if (value === 3) return '#1B396A';
                    // JUSTIFICADA
                    if (value === 2) return '#4c1d95';
                    // FALTA
                    if (value === 1) return '#D9534F';
                    // NO APLICA
                    return '#6C757D';
                },

                borderRadius: 4
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            scales: {
                y: {
                    beginAtZero: true,
                    max: 3,

                    ticks: {
                        stepSize: 1,

                        callback: function(value) {

                            switch (value) {
                                case 3:
                                    return 'Presente';
                                case 2:
                                    return 'Justificada';
                                case 1:
                                    return 'Falta';
                                default:
                                    return 'No aplica';
                            }
                        }
                    }
                }
            },

            plugins: {

                legend: {
                    display: false
                },

                tooltip: {
                    callbacks: {

                        label: function(context) {

                            return datos.estados?.[context.dataIndex]
                                ?? context.raw;
                        }
                    }
                }
            }
        }
    });
}

//====== FILTROS DE DASHBOARD ======
function iniciarFiltrosDashboard() {

    const form = document.getElementById('form-filtros-dashboard');

    if (!form) return;


    // EVITAR DUPLICAR EVENTOS
    if (form.dataset.eventsLoaded) return;

    form.dataset.eventsLoaded = 'true';


    // // SELECTS, aplica inmediatamente los filtro al panorama del panel
    // const selects = form.querySelectorAll('select');

    // selects.forEach(select => {

    //     select.addEventListener('change', () => {

    //         enviarFiltrosDashboard();
    //     });
    // });

    // BOTON BUSCAR
    const btnBuscar = document.getElementById('btn-aplicar-filtros-dashboard');

    if (btnBuscar) {

        btnBuscar.addEventListener('click', () => {

            enviarFiltrosDashboard(btnBuscar);
        });
    }


    // BOTON LIMPIAR FILTROS
    const btnLimpiar = document.getElementById('btn-limpiar-filtros-dashboard');

    if (btnLimpiar) {

        btnLimpiar.addEventListener('click', () => {

            // periodo se mantiene el actual
            // rol se mantiene actual (sobre todo admin)
            const selectArea = form.querySelector('select[name="area_id"]');
            const selectEstatus = form.querySelector('select[name="estatus_id"]');

            if (selectArea) {
                selectArea.value = '';
            }


            // ENVIAR FILTROS
            enviarFiltrosDashboard(btnLimpiar);
        });
    }

}


//========= ENVIAR LOS FILTRO PARA CAMBIAR EL PANORAMA =========
async function enviarFiltrosDashboard(button = null) {

    const form = document.getElementById('form-filtros-dashboard');
    const btnConsultar = document.getElementById('btn-aplicar-filtros-dashboard');

    if (!form) return;


    const formData = new FormData(form);

    const queryParams = new URLSearchParams(formData);

    try {

        // cancelar request anterior
        if (dashboardController) {
            dashboardController.abort();
        }

        dashboardController = new AbortController();

        if (button) {
            setLoadingFormulario(button, true);
        }

        const response = await fetchHtml(
            `${form.action}?${queryParams.toString()}`,
            {
                signal: dashboardController.signal
            }
        );

        if (!response.ok) return;

        const parser = new DOMParser();

        const doc = parser.parseFromString(response.html, 'text/html');


        // reemplazar solo dashboard
        const nuevoDashboard = doc.querySelector('.dashboard-personal-estudiante');

        const dashboardActual = document.querySelector('.dashboard-personal-estudiante');

        if (nuevoDashboard && dashboardActual) {

            dashboardActual.innerHTML = nuevoDashboard.innerHTML;
        } else {

            displayMensajeToast('<p class="error">No se pudo actualizar el dashboard.</p>');

            return;
        }

        // reinicializar graficas y filtros
        iniciarGraficasDashboardEstudiante(doc);

        iniciarFiltrosDashboard();


    } catch (error) {

        // ignorar ABORTS
        if (error.name === 'AbortError') {
            return;
        }

        console.error(error);
        displayMensajeToast('<p class="error">No se pudo aplicar los filtros al panel.</p>');

    } finally {
        if (button) {
            setLoadingFormulario(button, false);
        }
    }
}