document.addEventListener('DOMContentLoaded', () => {
    initTablaAsistenciaReciente();
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
//fmtFecha(iso) convierte "2026-03-02T10:30:00" a formato local es-MX
function fmtFecha(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '—';

    return d.toLocaleString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

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
//laravel genera la ruta con __ID__, y el JS solo reemplaza.
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

    //excepcion si ya no esta disponible o fue eliminado
    if (res.status === 404) {
        displayModal(`<p class="error">Este registro ya no se encuentra disponible, porque fue eliminado o dejó de existir desde otro dispositivo. Actualiza la tabla para ver la información actual.</p>`);
        return { ok: false, status: res.status, data };
    }

    if (res.status === 419) {
        displayModal(`<p class="error">Sesión expiró. Recarga la página.</p>`);
        return { ok: false, status: res.status, data };
    }

    if (!res.ok) {
        displayModal(`<p class="error">${escapeHtml(data?.message ?? `Error inesperado (${res.status}).`)}</p>`);
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


//======TABLA DE ASISTENCIA DE HOY (LISTA, VER)======
function initTablaAsistenciaReciente() {
    const tabla = document.getElementById('tabla-asistencia-reciente');
    const btnRefrescar = document.getElementById('btn-refrescar-tabla-asistencia-reciente');
    const inputBuscar = document.getElementById('input-buscar-asistencia-reciente');
    const btnBuscar = document.getElementById('btn-buscar-asistencia-reciente');
    const filtroPeriodo = document.getElementById('filtro-periodo-asistencia-reciente');
    const filtroEstatus = document.getElementById('filtro-estatus-asistencia-reciente');
    const filtroEstatusEscolar = document.getElementById('filtro-estatus-escolar-asistencia-reciente');
    const filtroArea = document.getElementById('filtro-area-asistencia-reciente');
    const filtroPerPage = document.getElementById('filtro-per-page-asistencia-reciente');

    const btnAnterior = document.getElementById('btn-anterior-asistencia-reciente');
    const btnSiguiente = document.getElementById('btn-siguiente-asistencia-reciente');
    const textoPagina = document.getElementById('texto-pagina-asistencia-reciente');
    const infoPaginacion = document.getElementById('info-paginacion-asistencia-reciente');

    const btnExportarExcel = document.getElementById('btn-exportar-asistencia-reciente-excel');
    const btnExportarHistorialCompletoExcel = document.getElementById('btn-exportar-historial-completo-asistencia-excel');

    if (!tabla || !btnRefrescar || !inputBuscar || !btnBuscar) return;

    if (filtroPeriodo && !filtroPeriodo.value) {
        const primeraOpcionValida = Array.from(filtroPeriodo.options).find(opt => opt.value);
        if (primeraOpcionValida) {
            filtroPeriodo.value = primeraOpcionValida.value;
        }
    }

    const tbody = tabla.querySelector('tbody');

    const cfg = {
        urlTabla: tabla.dataset.urlTablaAsistenciaReciente,
        urlResumen: tabla.dataset.urlResumenAsistenciaReciente,
        urlDetalle: tabla.dataset.urlDetalleAsistenciaEstudiante,
        urlExportarExcel: tabla.dataset.urlExportarAsistenciaRecienteExcel,
        urlExportarHistorialCompletoExcel: tabla.dataset.urlExportarHistorialCompletoAsistenciaExcel,
    };

    let aborter = null;

    const state = {
        currentPage: 1,
        lastPage: 1,
        perPage: Number(filtroPerPage?.value || 50),
        total: 0,
        from: 0,
        to: 0,
    };


    function renderRows(items) {
        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="10" class="td-estado-tabla">Sin estudiantes asignados para mostrar.</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((it) => `
            <tr>
                <td class="td-id">${it.estudiante_id ?? '—'}</td>
                <td class="td-clave">${escapeHtml(it.numero_control || '—')}</td>
                <td class="td-nombre-apellidos">${escapeHtml(it.nombre_estudiante || '—')}</td>
                <td class="td-fecha">${fmtSoloFecha(it.fecha)}</td>
                <td class="td-estado-asistencia">${estadoAsistencia(it.estatus_asistencia)}</td> <!-- estilos para los estados en: function estadoAsistencia(estatus_asistencia) -->
                <td class="td-fuente-importacion">${escapeHtml(it.fuente || '—')}</td>
                <td class="td-fecha">${fmtFecha(it.primera_entrada)}</td>
                <td class="td-fecha">${fmtFecha(it.ultima_salida)}</td>
                <td>${it.conteo_marcaciones ?? 0}</td>
                <td>
                    <div class="botones-tabla">
                        <button type="button" class="btn-ver-detalles-item btn-ver-detalle-asistencia-estudiante" data-id="${it.estudiante_id}">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderMeta(meta = {}) {
        state.currentPage = Number(meta.current_page || 1);
        state.lastPage = Number(meta.last_page || 1);
        state.perPage = Number(meta.per_page || 50);
        state.total = Number(meta.total || 0);
        state.from = meta.from ?? 0;
        state.to = meta.to ?? 0;

        if (textoPagina) {
            textoPagina.textContent = `Página ${state.currentPage} de ${state.lastPage}`;
        }

        if (infoPaginacion) {
            infoPaginacion.textContent = `Mostrando ${state.from || 0} a ${state.to || 0} de ${state.total} estudiantes`;
        }

        if (btnAnterior) {
            btnAnterior.disabled = state.currentPage <= 1;
        }

        if (btnSiguiente) {
            btnSiguiente.disabled = state.currentPage >= state.lastPage;
        }
    }

    async function cargarTabla(page = 1) {
        tbody.innerHTML = `<tr><td colspan="10" class="td-estado-tabla">Cargando contenido…</td></tr>`;

        if (aborter) aborter.abort();
        aborter = new AbortController();

        try {
            const url = new URL(cfg.urlTabla, window.location.origin);

            const buscar = inputBuscar.value.trim();
            const periodoId = filtroPeriodo?.value ?? '';
            const estatus = filtroEstatus?.value ?? '';
            const estatusEscolarId = filtroEstatusEscolar?.value ?? '';
            const areaId = filtroArea?.value ?? '';
            const perPage = filtroPerPage?.value ?? '50';

            if (buscar) url.searchParams.set('buscar', buscar);
            if (periodoId) url.searchParams.set('periodo_id', periodoId);
            if (estatus) url.searchParams.set('estatus', estatus);
            if (estatusEscolarId) url.searchParams.set('estatus_escolar_id', estatusEscolarId);
            if (areaId) url.searchParams.set('area_id', areaId);
            if (perPage) url.searchParams.set('per_page', perPage);

            url.searchParams.set('page', String(page));

            const r = await fetchJson(url.toString(), { signal: aborter.signal });
            if (!r.ok) return;

            renderRows(r.data?.data || []);
            renderMeta(r.data?.meta || {});
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al cargar la asistencia.</p>');
        }
    }


    //==================TARJETAS DE RESUMES==================
    async function cargarResumen() {
        const url = new URL(cfg.urlResumen, window.location.origin);

        if (filtroPeriodo?.value) url.searchParams.set('periodo_id', filtroPeriodo.value);

        const r = await fetchJson(url.toString());
        if (!r.ok) return;

        const d = r.data?.data || {};

        document.getElementById('resumen-total-asignados').textContent = d.total_asignados ?? 0;
        document.getElementById('resumen-presentes').textContent = d.presentes ?? 0;
        document.getElementById('resumen-faltas').textContent = d.faltas ?? 0;
        document.getElementById('resumen-faltas-justificadas').textContent = d.faltas_justificadas ?? 0;
        document.getElementById('resumen-no-aplica').textContent = d.no_aplica ?? 0;
        document.getElementById('resumen-porcentaje').textContent = d.porcentaje_asistencia ?? 0;
    }


    async function cargarTodo(page = 1) {
        await cargarResumen();
        await cargarTabla(page);
    }


    //==================VER TODAS LAS ASISTENCIAS=================
    tabla.addEventListener('click', async (ev) => {
        const btnDetalle = ev.target.closest('.btn-ver-detalle-asistencia-estudiante');
        if (!btnDetalle) return;

        const periodoId = filtroPeriodo?.value ?? '';
        if (!periodoId) {
            displayMensajeToast('<p class="advertencia">Selecciona un periodo para ver el detalle.</p>');
            return;
        }

        const url = new URL(buildUrl(cfg.urlDetalle, btnDetalle.dataset.id), window.location.origin);
        url.searchParams.set('periodo_id', periodoId);

        const r = await fetchJson(url.toString());
        if (!r.ok) return;

        const d = r.data?.data;
        if (!d) return;

        const detalleHtml = (d.detalle || []).length
            ? d.detalle.map(it => `
                <tr>
                    <td>${fmtSoloFecha(it.fecha)}</td>
                    <td class="td-estado-asistencia">${estadoAsistencia(it.estatus_asistencia)}</td>
                    <td class="td-fecha">${fmtFechaHoraLocal(it.primera_entrada)}</td>
                    <td class="td-fecha">${fmtFechaHoraLocal(it.ultima_salida)}</td>
                    <td>${it.conteo_marcaciones ?? 0}</td>
                </tr>
            `).join('')
            : `<tr><td colspan="5" class="td-estado-tabla">Sin registros.</td></tr>`;

        displayModalDetalles(`
            <h3 class="titulo-modal-detalles">Detalle de asistencia del estudiante #${d.id}</h3>

            <div class="scroll-contenido-modal-detalles">
                <div class="contenedor-detalles">
                    <div class="caja-detalle">
                        <p class="nombre-detalle">Número de control:</p>
                        <p class="info-detalle">${escapeHtml(d.numero_control || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Nombre:</p>
                        <p class="info-detalle">${escapeHtml(d.nombre_estudiante || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Total registros:</p>
                        <p class="info-detalle">${d.metricas?.total_registros ?? 0}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Presentes:</p>
                        <p class="info-detalle">${d.metricas?.presentes ?? 0}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Faltas:</p>
                        <p class="info-detalle">${d.metricas?.faltas ?? 0}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Faltas justificadas:</p>
                        <p class="info-detalle">${d.metricas?.faltas_justificadas ?? 0}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">No aplica:</p>
                        <p class="info-detalle">${d.metricas?.no_aplica ?? 0}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">% asistencia:</p>
                        <p class="info-detalle">${d.metricas?.porcentaje_asistencia ?? 0}%</p>
                    </div>
                </div>

                <div class="contenedor-tabla-modal-detalles">
                </div>

                <div class="tabla-scroll">
                    <div class="contenedor-tabla">
                        <table class="tabla-contenido">
                            <thead>
                                <tr>
                                    <th class="th-primero">Fecha</th>
                                    <th>Estatus</th>
                                    <th>Primera entrada</th>
                                    <th>Última salida</th>
                                    <th class="th-ultimo">Marcaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${detalleHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `);
    });


    //==================LISTENER PARA LA TABLA==================
    btnRefrescar.addEventListener('click', async () => {
        setLoadingTabla(btnRefrescar, true);
        await cargarTodo(state.currentPage);
        setLoadingTabla(btnRefrescar, false);
    });

    btnBuscar.addEventListener('click', async () => {
        state.currentPage = 1;
        setLoadingTabla(btnBuscar, true);
        await cargarTabla(1);
        setLoadingTabla(btnBuscar, false);
    });

    inputBuscar.addEventListener('keydown', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            state.currentPage = 1;
            setLoadingTabla(btnBuscar, true);
            await cargarTabla(1);
            setLoadingTabla(btnBuscar, false);
        }
    });

    filtroPeriodo?.addEventListener('change', async () => {
        state.currentPage = 1;
        await cargarTodo(1);
    });

    filtroEstatus?.addEventListener('change', async () => {
        state.currentPage = 1;
        await cargarTabla(1);
    });

    filtroEstatusEscolar?.addEventListener('change', async () => {
        state.currentPage = 1;
        await cargarTodo(1);
    });

    filtroArea?.addEventListener('change', async () => {
        state.currentPage = 1;
        await cargarTodo(1);
    });

    filtroPerPage?.addEventListener('change', async () => {
        state.currentPage = 1;
        await cargarTabla(1);
    });

    btnAnterior?.addEventListener('click', async () => {
        if (state.currentPage <= 1) return;
        await cargarTabla(state.currentPage - 1);
    });

    btnSiguiente?.addEventListener('click', async () => {
        if (state.currentPage >= state.lastPage) return;
        await cargarTabla(state.currentPage + 1);
    });


    btnExportarExcel?.addEventListener('click', () => {
        try {
            setLoadingTabla(btnExportarExcel, true);

            const url = new URL(cfg.urlExportarExcel, window.location.origin);

            const buscar = inputBuscar.value.trim();
            const periodoId = filtroPeriodo?.value ?? '';
            const estatus = filtroEstatus?.value ?? '';
            const estatusEscolarId = filtroEstatusEscolar?.value ?? '';
            const areaId = filtroArea?.value ?? '';

            if (buscar) url.searchParams.set('buscar', buscar);
            if (periodoId) url.searchParams.set('periodo_id', periodoId);
            if (estatus) url.searchParams.set('estatus', estatus);
            if (estatusEscolarId) url.searchParams.set('estatus_escolar_id', estatusEscolarId);
            if (areaId) url.searchParams.set('area_id', areaId);

            window.location.href = url.toString();

        } catch (error) {
            console.error(error);
            displayMensajeToast('<p class="error">No se pudo iniciar la exportación de las asistencias recientes.</p>');
        } finally {
            setTimeout(() => {
                setLoadingTabla(btnExportarExcel, false);
            }, 1200);
        }

    });


    btnExportarHistorialCompletoExcel?.addEventListener('click', () => {
        try {
            setLoadingTabla(btnExportarHistorialCompletoExcel, true);

            const url = new URL(cfg.urlExportarHistorialCompletoExcel, window.location.origin);

            const buscar = inputBuscar.value.trim();
            const periodoId = filtroPeriodo?.value ?? '';
            const estatus = filtroEstatus?.value ?? '';
            const estatusEscolarId = filtroEstatusEscolar?.value ?? '';
            const areaId = filtroArea?.value ?? '';

            if (buscar) url.searchParams.set('buscar', buscar);
            if (periodoId) url.searchParams.set('periodo_id', periodoId);
            if (estatus) url.searchParams.set('estatus', estatus);
            if (estatusEscolarId) url.searchParams.set('estatus_escolar_id', estatusEscolarId);
            if (areaId) url.searchParams.set('area_id', areaId);

            window.location.href = url.toString();

        } catch (error) {
            console.error(error);
            displayMensajeToast('<p class="error">No se pudo iniciar la exportación del historial completo de asistencia.</p>');
        } finally {
            setTimeout(() => {
                setLoadingTabla(btnExportarHistorialCompletoExcel, false);
            }, 1200);
        }

    });
    //==========================================================


    cargarTodo(1);
}
//==========================================================