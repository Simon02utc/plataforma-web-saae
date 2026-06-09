document.addEventListener('DOMContentLoaded', () => {
    initHistorialAsistencia();
});

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

//Para formatear ese JSON antes de insertarlo al modal (por ejemplo con JSON.stringify)
//y de paso escapar HTML para evitar XSS.
function escapeHtml(str) {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}


//=====================HELPERS VISUALES=====================
function fmtSoloFecha(valor) {
    if (!valor) return '—';

    const texto = String(valor).trim().slice(0, 10); // YYYY-MM-DD
    const partes = texto.split('-');
    if (partes.length !== 3) return '—';

    const [anio, mes, dia] = partes;
    return `${dia}/${mes}/${anio}`;
}

function fmtFechaHoraLocal(valor) {
    if (!valor) return '—';

    const texto = String(valor).trim().replace('T', ' ').slice(0, 19); // YYYY-MM-DD HH:mm:ss
    const partes = texto.split(' ');
    if (partes.length < 2) return '—';

    const [fecha, hora] = partes;
    const fechaPartes = fecha.split('-');
    const horaPartes = hora.split(':');

    if (fechaPartes.length !== 3 || horaPartes.length < 2) return '—';

    const [anio, mes, dia] = fechaPartes;
    let hh = parseInt(horaPartes[0], 10);
    const mm = horaPartes[1];

    if (Number.isNaN(hh)) return '—';

    const sufijo = hh >= 12 ? 'p.m.' : 'a.m.';
    let hora12 = hh % 12;
    if (hora12 === 0) hora12 = 12;

    return `${dia}/${mes}/${anio}, ${String(hora12).padStart(2, '0')}:${mm} ${sufijo}`;
}
//==========================================================


//===============CONSTRUCCIONES DE URLs POR ID===============
function buildUrl(template, id) {
    if (!template) return '#';
    if (template.includes('__ID__')) return template.replace('__ID__', String(id));
    return template.replace(/\/0$/, `/${id}`);
}
//==========================================================


//=========Capa de seguridad para endpoints JSON=========
async function fetchJson(url, { method = 'GET', body = null, signal } = {}) {
    const options = {
        method,
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
        signal,
    };

    if (body !== null) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    const res = await fetch(url, options);
    const data = await res.json().catch(() => null);

    if (res.status === 422) {
        const erroresHtml = data?.errors
            ? Object.values(data.errors).flat().map(msg => `<li>${escapeHtml(msg)}</li>`).join('')
            : `<li>${escapeHtml(data?.message || 'Los datos enviados no son válidos.')}</li>`;

        displayModal(`
            <p class="advertencia">Problemas de validación:</p>
            <ul class="listado-error-422">${erroresHtml}</ul>
        `);

        return { ok: false, status: res.status, data };
    }

    if (res.status === 401) {
        displayModal(`<p class="error">No autorizado. Inicia sesión.</p>`);
        return { ok: false, status: res.status, data };
    }

    if (res.status === 403) {
        displayModal(`<p class="error">${escapeHtml(data?.message ?? 'Acceso denegado.')}</p>`);
        return { ok: false, status: res.status, data };
    }

    if (res.status === 404) {
        displayModal(`<p class="error">Este registro ya no se encuentra disponible, porque fue eliminado o dejó de existir desde otro dispositivo. Actualiza la tabla para ver la información actual.</p>`);
        return { ok: false, status: res.status, data };
    }

    if (res.status === 419) {
        displayModal(`<p class="error">Sesión expiró. Recarga la página.</p>`);
        return { ok: false, status: res.status, data };
    }

    if (!res.ok) {
        displayModal(`<p class="error">${data?.message ?? `Error inesperado (${res.status}).`}</p>`);
        return { ok: false, status: res.status, data };
    }

    return { ok: true, status: res.status, data };
}
//==========================================================


//SPINNER PARA BOTONES CON ICONO O TEXTO (DE TABLA Y FORMULARIO)
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

function setLoadingTabla(button, isLoading) {
    const iconoBoton = button.querySelector('i');
    const textoBoton = button.querySelector('span:not(.spinner-tabla):not(.texto-spinner-tabla)');
    const spinner = button.querySelector('.spinner-tabla');
    const textoSpinner = button.querySelector('.texto-spinner-tabla');

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


//DAR HTML Y ESTILOS A LOS ESTADOS DE ASISTENCIA
function estadoAsistencia(estatus_asistencia) {

    const e = String(estatus_asistencia || 'SIN_REGISTRO').toUpperCase();

    let cls = 'estado-asistencia';

    switch (e) {

        case 'PRESENTE':
            cls += ' estado-presente';
            break;

        case 'FALTA':
            cls += ' estado-falta';
            break;

        case 'JUSTIFICADA':
            cls += ' estado-falta-justificada';
            break;

        case 'NO APLICA':
            cls += ' estado-no-aplica';
            break;

        case 'SIN_REGISTRO':
            cls += ' estado-sin-registro';
            break;

        default:
            cls += ' estado-default';
            break;
    }

    return `<span class="${cls}">${escapeHtml(e)}</span>`;
}


//======TABLA DE HISTORIAL DE ASISTENCIA======
function initHistorialAsistencia() {
    const tabla = document.getElementById('tabla-historial-asistencia');
    const inputBuscarEstudiante = document.getElementById('input-buscar-estudiante-historial-asistencia');
    const inputEstudianteId = document.getElementById('filtro-estudiante-historial-asistencia');
    const contenedorResultados = document.getElementById('resultados-busqueda-estudiante-historial');
    const filtroPeriodo = document.getElementById('filtro-periodo-historial-asistencia');
    const btnConsultar = document.getElementById('btn-consultar-historial-asistencia');

    const btnExportarExcel = document.getElementById('btn-exportar-historial-asistencia-excel');

    if (!tabla || !inputBuscarEstudiante || !inputEstudianteId || !filtroPeriodo || !btnConsultar) return;

    const tbody = tabla.querySelector('tbody');

    const cfg = {
        urlListadoEstudiantes: tabla.dataset.urlListadoEstudiantesAsignados,
        urlDetalle: tabla.dataset.urlTablaDetalleHistorialAsistenciaEstudiante,

        urlExportarExcel: tabla.dataset.urlExportarHistorialAsistenciaExcel,
    };

    let estudiantesAsignados = [];

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
            <button type="button" class="item-resultado-busqueda" data-id="${it.id}">
                <span class="texto-resultado-principal">${escapeHtml(it.numero_control || '—')}</span>
                <span class="texto-resultado-secundario">${escapeHtml(it.nombre_estudiante || '—')}</span>
            </button>
        `).join('');

        contenedorResultados.style.display = 'block';
    }

    function buscarCoincidencias(texto) {
        const termino = String(texto || '').trim().toLowerCase();

        if (!termino) {
            limpiarResultados();
            return;
        }

        const resultados = estudiantesAsignados
            .filter(it => {
                const numeroControl = String(it.numero_control || '').toLowerCase();
                const nombre = String(it.nombre_estudiante || '').toLowerCase();

                return numeroControl.includes(termino) || nombre.includes(termino);
            })
            .slice(0, 8);

        renderResultados(resultados);
    }


    async function cargarEstudiantesAsignados() {
        const r = await fetchJson(cfg.urlListadoEstudiantes);
        if (!r.ok) return;

        estudiantesAsignados = r.data?.data || [];
    }



    async function consultarHistorial() {
        const estudianteId = inputEstudianteId.value;
        const periodoId = filtroPeriodo.value;

        if (!estudianteId || !periodoId) {
            tbody.innerHTML = `<tr><td colspan="6" class="td-estado-tabla">Selecciona un estudiante y un periodo.</td></tr>`;
            return;
        }

        tbody.innerHTML = `<tr><td colspan="6" class="td-estado-tabla">Cargando contenido…</td></tr>`;

        const url = new URL(buildUrl(cfg.urlDetalle, estudianteId), window.location.origin);
        url.searchParams.set('periodo_id', periodoId);

        const r = await fetchJson(url.toString());
        if (!r.ok) return;

        const d = r.data?.data;
        if (!d) return;

        document.getElementById('resumen-total-registros-historial').textContent = d.metricas?.total_registros ?? 0;
        document.getElementById('resumen-presentes-historial').textContent = d.metricas?.presentes ?? 0;
        document.getElementById('resumen-faltas-historial').textContent = d.metricas?.faltas ?? 0;
        document.getElementById('resumen-faltas-justificadas-historial').textContent = d.metricas?.faltas_justificadas ?? 0;
        document.getElementById('resumen-no-aplica-historial').textContent = d.metricas?.no_aplica ?? 0;
        document.getElementById('resumen-porcentaje-historial').textContent = `${d.metricas?.porcentaje_asistencia ?? 0}%`;

        const detalle = d.detalle || [];

        if (!detalle.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="td-estado-tabla">Sin registros para mostrar.</td></tr>`;
            return;
        }

        tbody.innerHTML = detalle.map(it => `
            <tr>
                <td>${fmtSoloFecha(it.fecha)}</td>
                <td class="td-estado-asistencia">${estadoAsistencia(it.estatus_asistencia)}</td>
                <td>${escapeHtml(it.fuente || '—')}</td>
                <td class="td-fecha">${fmtFechaHoraLocal(it.primera_entrada)}</td>
                <td class="td-fecha">${fmtFechaHoraLocal(it.ultima_salida)}</td>
                <td>${it.conteo_marcaciones ?? 0}</td>
            </tr>
        `).join('');
    }


    //==================LISTENER PARA LA TABLA==================
    inputBuscarEstudiante.addEventListener('input', () => {
        inputEstudianteId.value = '';
        buscarCoincidencias(inputBuscarEstudiante.value);
    });

    inputBuscarEstudiante.addEventListener('focus', () => {
        if (inputBuscarEstudiante.value.trim()) {
            buscarCoincidencias(inputBuscarEstudiante.value);
        }
    });

    contenedorResultados.addEventListener('click', (e) => {
        const item = e.target.closest('.item-resultado-busqueda');
        if (!item) return;

        const estudianteId = item.dataset.id || '';
        const estudiante = estudiantesAsignados.find(it => String(it.id) === String(estudianteId));

        inputEstudianteId.value = estudianteId;
        inputBuscarEstudiante.value = estudiante
            ? `${estudiante.numero_control || '—'} | ${estudiante.nombre_estudiante || '—'}`
            : '';

        limpiarResultados();
    });

    document.addEventListener('click', (e) => {
        const dentroDelBuscador = e.target.closest('.contenedor-buscador-informacion');
        if (!dentroDelBuscador) {
            limpiarResultados();
        }
    });

    btnConsultar.addEventListener('click', async () => {
        setLoadingTabla(btnConsultar, true);
        await consultarHistorial();
        setLoadingTabla(btnConsultar, false);
    });

    inputBuscarEstudiante.addEventListener('keydown', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();

            if (!inputEstudianteId.value) {
                const termino = inputBuscarEstudiante.value.trim().toLowerCase();

                const primeraCoincidencia = estudiantesAsignados.find(it => {
                    const numeroControl = String(it.numero_control || '').toLowerCase();
                    const nombre = String(it.nombre_estudiante || '').toLowerCase();

                    return numeroControl.includes(termino) || nombre.includes(termino);
                });

                if (primeraCoincidencia) {
                    inputEstudianteId.value = primeraCoincidencia.id;
                    inputBuscarEstudiante.value = `${primeraCoincidencia.numero_control || '—'} | ${primeraCoincidencia.nombre_estudiante || '—'}`;
                }
            }

            limpiarResultados();

            setLoadingTabla(btnConsultar, true);
            await consultarHistorial();
            setLoadingTabla(btnConsultar, false);
        }
    });

    
    btnExportarExcel?.addEventListener('click', () => {
        try {
            setLoadingTabla(btnExportarExcel, true);

            const estudianteId = inputEstudianteId.value;
            const periodoId = filtroPeriodo.value;

            if (!estudianteId || !periodoId) {
                displayMensajeToast('<p class="advertencia">Selecciona un estudiante y un periodo para exportar.</p>');
                return;
            }

            const url = new URL(cfg.urlExportarExcel, window.location.origin);
            url.searchParams.set('estudiante_id', estudianteId);
            url.searchParams.set('periodo_id', periodoId);

            window.location.href = url.toString();

        } catch (error) {
            console.error(error);
            displayMensajeToast('<p class="error">No se pudo iniciar la exportación del Excel del historial/consulta.</p>');
        } finally {
            setTimeout(() => {
                setLoadingTabla(btnExportarExcel, false);
            }, 1200);
        }

    });
    //==========================================================


    cargarEstudiantesAsignados();
}