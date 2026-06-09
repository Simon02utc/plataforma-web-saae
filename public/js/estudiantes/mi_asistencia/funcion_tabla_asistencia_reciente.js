document.addEventListener('DOMContentLoaded', () => {
    initTablaMiAsistenciaReciente();
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


//======TABLA DE ASISTENCIA DE HOY (LISTA, VER======
function initTablaMiAsistenciaReciente() {
    const tabla = document.getElementById('tabla-mi-asistencia-reciente');
    const btnRefrescar = document.getElementById('btn-refrescar-tabla-mi-asistencia-reciente');
    const filtroPeriodo = document.getElementById('filtro-periodo-mi-asistencia-reciente');
    const filtroEstatus = document.getElementById('filtro-estatus-mi-asistencia-reciente');

    if (!tabla || !btnRefrescar) return;

    if (filtroPeriodo && !filtroPeriodo.value) {
        const primeraOpcionValida = Array.from(filtroPeriodo.options).find(opt => opt.value);
        if (primeraOpcionValida) filtroPeriodo.value = primeraOpcionValida.value;
    }

    const tbody = tabla.querySelector('tbody');

    const cfg = {
        urlTabla: tabla.dataset.urlTablaMiAsistenciaReciente,
        urlResumen: tabla.dataset.urlResumenMiAsistenciaReciente,
        urlDetalle: tabla.dataset.urlDetalleMiAsistenciaEstudiante,
    };

    let aborter = null;

    function renderRows(items) {
        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="td-estado-tabla">No se encontró asistencia para mostrar.</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((it) => `
            <tr>
                <td class="td-fecha">${fmtSoloFecha(it.fecha)}</td>
                <td class="td-estado">${estadoAsistencia(it.estatus_asistencia)}</td>
                <td class="td-fuente-importacion">${escapeHtml(it.fuente || '—')}</td>
                <td class="td-fecha">${fmtFechaHoraLocal(it.primera_entrada)}</td>
                <td class="td-fecha">${fmtFechaHoraLocal(it.ultima_salida)}</td>
                <td>${it.conteo_marcaciones ?? 0}</td>
                <td>
                    <div class="botones-tabla">
                        <button type="button" class="btn-ver-detalles-item btn-ver-detalle-mi-asistencia">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async function cargarTabla() {
        tbody.innerHTML = `<tr><td colspan="7" class="td-estado-tabla">Cargando contenido…</td></tr>`;

        if (aborter) aborter.abort();
        aborter = new AbortController();

        try {
            const url = new URL(cfg.urlTabla, window.location.origin);

            if (filtroPeriodo?.value) url.searchParams.set('periodo_id', filtroPeriodo.value);
            if (filtroEstatus?.value) url.searchParams.set('estatus', filtroEstatus.value);

            const r = await fetchJson(url.toString(), { signal: aborter.signal });
            if (!r.ok) return;

            renderRows(r.data?.data || []);
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al cargar tu asistencia.</p>');
        }
    }

    async function cargarResumen() {
        const url = new URL(cfg.urlResumen, window.location.origin);

        if (filtroPeriodo?.value) url.searchParams.set('periodo_id', filtroPeriodo.value);

        const r = await fetchJson(url.toString());
        if (!r.ok) return;

        const d = r.data?.data || {};

        document.getElementById('resumen-total-registros').textContent = d.total_registros ?? 0;
        document.getElementById('resumen-presentes').textContent = d.presentes ?? 0;
        document.getElementById('resumen-faltas').textContent = d.faltas ?? 0;
        document.getElementById('resumen-faltas-justificadas').textContent = d.faltas_justificadas ?? 0;
        document.getElementById('resumen-no-aplica').textContent = d.no_aplica ?? 0;
        document.getElementById('resumen-porcentaje-asistencia').textContent = `${d.porcentaje_asistencia ?? 0}%`;
    }

    async function cargarTodo() {
        await cargarResumen();
        await cargarTabla();
    }

    tabla.addEventListener('click', async (ev) => {
        const btnDetalle = ev.target.closest('.btn-ver-detalle-mi-asistencia');
        if (!btnDetalle) return;

        const periodoId = filtroPeriodo?.value ?? '';

        if (!periodoId) {
            displayMensajeToast('<p class="advertencia">Selecciona un periodo para ver el detalle.</p>');
            return;
        }

        const url = new URL(cfg.urlDetalle, window.location.origin);
        url.searchParams.set('periodo_id', periodoId);

        const r = await fetchJson(url.toString());
        if (!r.ok) return;

        const d = r.data?.data;
        if (!d) return;

        const detalleHtml = (d.detalle || []).length
            ? d.detalle.map(it => `
                <tr>
                    <td>${fmtSoloFecha(it.fecha)}</td>
                    <td class="td-estado">${estadoAsistencia(it.estatus_asistencia)}</td>
                    <td>${escapeHtml(it.fuente || '—')}</td>
                    <td>${fmtFechaHoraLocal(it.primera_entrada)}</td>
                    <td>${fmtFechaHoraLocal(it.ultima_salida)}</td>
                    <td>${it.conteo_marcaciones ?? 0}</td>
                </tr>
            `).join('')
            : `<tr><td colspan="6" class="td-estado-tabla">Sin registros.</td></tr>`;

        displayModalDetalles(`
            <h3 class="titulo-modal-detalles">Detalle de mi asistencia</h3>

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

                <div class="tabla-scroll">
                    <div class="contenedor-tabla">
                        <table class="tabla-contenido">
                            <thead>
                                <tr>
                                    <th class="th-primero">Fecha</th>
                                    <th>Estatus</th>
                                    <th>Fuente</th>
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

    btnRefrescar.addEventListener('click', async () => {
        setLoadingTabla(btnRefrescar, true);
        await cargarTodo();
        setLoadingTabla(btnRefrescar, false);
    });

    filtroPeriodo?.addEventListener('change', async () => {
        await cargarTodo();
    });

    filtroEstatus?.addEventListener('change', async () => {
        await cargarTabla();
    });

    cargarTodo();
}