//========= VARIABLES GLOBALES =========
let graficaResumen = null;
let graficaAsistenciaDias = null;
let graficaFaltas = null;
let estudiantes = obtenerEstudiantesDashboard();

let dashboardController = null;


//========= DOM =========
document.addEventListener('DOMContentLoaded', () => {
    iniciarGraficasDashboardPersonal();

    iniciarFiltrosDashboard();
    iniciarBuscadorEstudiantesDashboard();

    iniciarExportacionDashboard();
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


// ========= OBTENER DATOS AL DASHBOARD =========
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


// ======== OBTENER ESTUDIANTES AL DASHBOARD =========
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


    // ENTER EN INPUT
    const inputBuscar = document.getElementById('input-buscar-estudiante');

    if (inputBuscar) {

        inputBuscar.addEventListener('keydown', (e) => {

            if (e.key === 'Enter') {

                e.preventDefault();

                const btnBuscar = document.getElementById('btn-aplicar-filtros-dashboard');

                enviarFiltrosDashboard(btnBuscar);
            }
        });
    }


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

            // dejar el estaus INSCRITO por defecto
            if (selectEstatus) {
                const opcionInscrito = Array.from(selectEstatus.options)
                    .find(option =>
                        option.textContent.trim().toUpperCase() === 'INSCRITO'
                    );

                selectEstatus.value = opcionInscrito
                    ? opcionInscrito.value
                    : '';
            }


            // limipiar el select de busqueda de estudiante
            const inputBuscarEstudiante = document.getElementById('input-buscar-estudiante');

            const inputEstudianteId = document.getElementById('filtro-estudiante');

            if (inputBuscarEstudiante) {
                inputBuscarEstudiante.value = '';
            }

            if (inputEstudianteId) {
                inputEstudianteId.value = '';
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

        // actualizar JSON global nuevo
        estudiantes = obtenerEstudiantesDashboard(doc);

        // reinicializar graficas y filtros
        iniciarGraficasDashboardPersonal(doc);

        iniciarBuscadorEstudiantesDashboard();

        iniciarFiltrosDashboard();

        iniciarExportacionDashboard();

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


//========= BUSCADOR DE ESTUDIANTE =========
function iniciarBuscadorEstudiantesDashboard() {

    const inputBuscar = document.getElementById('input-buscar-estudiante');

    const contenedorResultados = document.getElementById('resultados-busqueda-estudiante');

    const inputEstudianteId = document.getElementById('filtro-estudiante');

    if (!inputBuscar || !contenedorResultados || !inputEstudianteId) return;


    function limpiarResultados() {

        contenedorResultados.innerHTML = '';

        contenedorResultados.style.display = 'none';
    }


    function renderResultados(items) {

        if (!items.length) {

            contenedorResultados.innerHTML = `
                <div class="item-resultado-busqueda-vacio">
                    Sin coincidencias.
                </div>
            `;

            contenedorResultados.style.display = 'block';

            return;
        }

        contenedorResultados.innerHTML = items.map(it => `
            <button type="button"
                    class="item-resultado-busqueda"
                    data-id="${escapeHtml(it.id)}"
                    data-nombre="${escapeHtml(it.nombre_completo)}"
                    data-numero="${escapeHtml(it.numero_control)}">

                <span class="texto-resultado-principal">
                    ${escapeHtml(it.numero_control || '—')}
                </span>

                <span class="texto-resultado-secundario">
                    ${escapeHtml(it.nombre_completo || '—')}
                </span>

            </button>
        `).join('');

        contenedorResultados.style.display = 'block';

        contenedorResultados
            .querySelectorAll('.item-resultado-busqueda')
            .forEach(btn => {

                btn.addEventListener('click', () => {

                    const id = btn.dataset.id;

                    const nombre = btn.dataset.nombre;

                    const numero = btn.dataset.numero;

                    inputBuscar.value = `${numero} | ${nombre}`;

                    inputEstudianteId.value = id;

                    limpiarResultados();
                });
            });
    }


    function buscarCoincidencias(texto) {

        const termino = texto.trim().toLowerCase();

        if (!termino) {
            limpiarResultados();
            return;
        }

        const resultados = estudiantes
            .filter(it => {

                const numero = String(it.numero_control || '').toLowerCase();

                const nombre = String(it.nombre_completo || '').toLowerCase();

                return numero.includes(termino)
                    || nombre.includes(termino);
            })
            .slice(0, 8);

        renderResultados(resultados);
    }


    // ===== EVENTO INPUT =====
    inputBuscar.addEventListener('input', () => {

        inputEstudianteId.value = '';

        buscarCoincidencias(inputBuscar.value);
    });


    // ===== CERRAR AL HACER CLICK AFUERA =====
    document.addEventListener('click', (e) => {

        if (!contenedorResultados.contains(e.target)
            && e.target !== inputBuscar) {

            limpiarResultados();
        }
    });
}


//====== SISTEMA DE GRAFICAS ======
function iniciarGraficasDashboardPersonal(contenedor = document) {

    if (typeof Chart === 'undefined') {

        console.error('Chart.js no está disponible');

        displayMensajeToast('<p class="error">No se pudieron cargar las gráficas.</p>');

        return;
    }

    const datos = obtenerDatosDashboard(contenedor);


    crearGraficaResumenAsistenciaEstudiante(
        datos.graficaResumenAsistencia || {}
    );

    crearGraficaAsistenciaDiasEstudiante(
        datos.graficaAsistenciaDias || {}
    );

    crearGraficaFaltasDiasEstudiante(
        datos.graficaFaltasDias || {}
    );

}


//========= GRAFICAS =========
function crearGraficaResumenAsistenciaEstudiante(datos) {
    const canvas = document.getElementById('graficaResumenAsistencia');

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
                borderColor: '#ffffff',
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

    const canvas = document.getElementById(
        'graficaAsistenciaDiasEstudiante'
    );

    if (!canvas || !datos.labels || !datos.data) return;

    if (graficaAsistenciaDias) {
        graficaAsistenciaDias.destroy();
    }

    graficaAsistenciaDias = new Chart(canvas, {

        type: 'bar',

        data: {
            labels: datos.labels,

            datasets: [{
                label: 'Asistencias',
                data: datos.data,

                backgroundColor: function(context) {

                    const estado =
                        datos.estados?.[context.dataIndex];

                    // tiene justificadas
                    if (estado?.justificadas > 0) {
                        return '#4c1d95';
                    }

                    // solo presentes
                    return '#1B396A';
                },

                borderWidth: 1,
                borderColor: '#ffffff',
                borderRadius: 2
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            scales: {
                y: {
                    beginAtZero: true,

                    ticks: {
                        precision: 0
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

                            const estado =
                                datos.estados?.[context.dataIndex];

                            return [
                                `Presentes: ${estado?.presentes ?? 0}`,
                                `Justificadas: ${estado?.justificadas ?? 0}`,
                                `Total: ${context.raw}`
                            ];
                        }
                    }
                }
            }
        }
    });
}


function crearGraficaFaltasDiasEstudiante(datos) {

    const canvas = document.getElementById('graficaFaltasDiasEstudiante');

    if (!canvas || !datos.labels || !datos.data) return;

    if (graficaFaltas) {
        graficaFaltas.destroy();
    }

    graficaFaltas = new Chart(canvas, {

        type: 'bar',

        data: {
            labels: datos.labels,

            datasets: [{
                label: 'Faltas',
                data: datos.data,
                backgroundColor: '#D9534F',
                borderRadius: 2,
                borderWidth: 1,
                borderColor: '#ffffff'
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            scales: {
                y: {
                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }
                }
            },

            plugins: {
                legend: {
                    display: false
                },

                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return `Faltas: ${context.raw}`;
                        }
                    }
                }
            }
        }
    });
}


//====== EXPORTACION DE DATOS EN EXCEL ======
function iniciarExportacionDashboard() {

    const btnExportar = document.getElementById(
        'btn-exportar-datos-excel'
    );

    const form = document.getElementById(
        'form-filtros-dashboard'
    );

    if (!btnExportar || !form) return;

    // evitar eventos duplicados
    if (btnExportar.dataset.eventsLoaded) return;

    btnExportar.dataset.eventsLoaded = 'true';


    btnExportar.addEventListener('click', async () => {

        try {

            setLoadingFormulario(btnExportar, true);

            const formData = new FormData(form);
            const queryParams = new URLSearchParams(formData);

            const response = await fetch(
                `exportar_excel_dashboard?${queryParams.toString()}`,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                const errorData = await response.json();

                const error = new Error(
                    errorData.message || 'No se pudo realizar la exportación del Excel'
                );

                // marcar si es un error controlado
                error.tipo = response.status === 422
                    ? 'informativo'
                    : 'grave';

                throw error;
            }


            // ========= OBTENER NOMBRE DEL ARCHIVO =========
            let nombreArchivo = 'reporte.xlsx';

            const disposition = response.headers.get(
                'Content-Disposition'
            );

            if (disposition) {

                const match = disposition.match(
                    /filename="?([^"]+)"?/i
                );

                if (match?.[1]) {
                    nombreArchivo = match[1];
                }
            }


            // ========= CREAR ARCHIVO =========
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const enlace = document.createElement('a');

            enlace.href = url;
            enlace.download = nombreArchivo;

            document.body.appendChild(enlace);

            enlace.click();
            enlace.remove();

            window.URL.revokeObjectURL(url);

            displayMensajeToast(`<p class="exito">Exportacion exitosa.</p>`);

        } catch (error) {
            console.error(error);

            const claseMensaje =
                error.tipo === 'informativo'
                    ? 'advertencia'
                    : 'error';

            displayMensajeToast(
                `<p class="${claseMensaje}">${error.message}</p>`
            );

        } finally {
            setLoadingFormulario(btnExportar, false);
        }
    });
}