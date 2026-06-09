document.addEventListener('DOMContentLoaded', () => {
    initTablaHistorialAlertas();
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

    const texto = String(valor).trim().slice(0, 10);
    const partes = texto.split('-');
    if (partes.length !== 3) return '—';

    const [anio, mes, dia] = partes;
    return `${dia}/${mes}/${anio}`;
}

function fmtFechaHoraLocal(valor) {
    if (!valor) return '—';

    const texto = String(valor).trim().replace('T', ' ').slice(0, 19);
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
        displayModal(`<p class="error">Este registro ya no se encuentra disponible o dejó de existir. Actualiza la tabla.</p>`);
        return { ok: false, status: res.status, data };
    }

    if (res.status === 419) {
        displayModal(`<p class="error">Sesión expirada. Recarga la página.</p>`);
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


//DAR HTML Y ESTILOS A LOS ESTADOS DE ALERTAS
function tipoAlerta(tipo_alerta) {

    const e = String(tipo_alerta || 'SIN_REGISTRO_ALERTA').toUpperCase();

    let cls = 'tipo-alerta';

    switch (e) {

        case 'FALTA ACUMULADA':
            cls += ' falta-acumulada';
            break;

        case 'SUSPENCIÓN DE BECA ESCOLAR':
            cls += ' suspencion-beca-escolar';
            break;

        case 'SIN_REGISTRO_ALERTA':
            cls += ' sin-registro-alerta';
            break;

        default:
            cls += ' tipo-alerta-default';
            break;
    }

    return `<span class="${cls}">${escapeHtml(e)}</span>`;
}


//DAR HTML Y ESTILOS A LOS ESTADOS DE ALERTAS
function estadoAlerta(estado_alerta) {

    const e = String(estado_alerta || 'SIN_REGISTRO').toUpperCase();

    let cls = 'estado-alerta';

    switch (e) {

        case 'ATENDIDA':
            cls += ' estado-atendida';
            break;

        case 'PENDIENTE':
            cls += ' estado-pendiente';
            break;
        
        case 'CERRADA':
            cls += ' estado-cerrada';
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


//DAR HTML Y ESTILOS A LOS ESTADO DEL ENVIO DEL CORREO
function estadoCorreo(estado) {

    const e = String(estado || 'SIN_REGISTRO').toUpperCase();

    let cls = 'correo-estado';

    switch (e) {

        case 'ENVIADO':
            cls += ' correo-enviado';
            break;

        case 'FALLIDO':
            cls += ' correo-fallido';
            break;

        case 'PENDIENTE':
            cls += ' correo-pendiente';
            break;

        case 'OMITIDO':
            cls += ' correo-omitido';
            break;

        default:
            cls += ' correo-default';
            break;
    }

    return `<span class="${cls}">${escapeHtml(e)}</span>`;
}


//======TABLA DE ALERTAS (LISTA y VER)======
function initTablaHistorialAlertas() {
    const tabla = document.getElementById('tabla-historial-alertas');
    const btnRefrescar = document.getElementById('btn-refrescar-tabla-historial-alertas');
    const inputBuscar = document.getElementById('input-buscar-historial-alertas');
    const btnBuscar = document.getElementById('btn-buscar-historial-alertas');
    const filtroPeriodo = document.getElementById('filtro-periodo-historial-alertas');
    const filtroTipo = document.getElementById('filtro-tipo-historial-alertas');
    const filtroEstado = document.getElementById('filtro-estado-historial-alertas');
    const filtroPerPage = document.getElementById('filtro-per-page-historial-alertas');

    const btnAnterior = document.getElementById('btn-anterior-historial-alertas');
    const btnSiguiente = document.getElementById('btn-siguiente-historial-alertas');
    const textoPagina = document.getElementById('texto-pagina-historial-alertas');
    const infoPaginacion = document.getElementById('info-paginacion-historial-alertas');

    if (!tabla || !btnRefrescar || !inputBuscar || !btnBuscar) return;

    const tbody = tabla.querySelector('tbody');

    const cfg = {
        urlTabla: tabla.dataset.urlTablaHistorialAlertas,
        urlResumen: tabla.dataset.urlResumenHistorialAlertas,
        urlVer: tabla.dataset.urlVerAlerta,
    };

    let aborter = null;

    const state = {
        currentPage: 1,
        lastPage: 1,
        perPage: Number(filtroPerPage?.value || 20),
        total: 0,
        from: 0,
        to: 0,
    };

    function renderRows(items) {
        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="11" class="td-estado-tabla">Sin alertas históricas para mostrar.</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((it) => `
            <tr>
                <td class="td-id">${it.id ?? '—'}</td>
                <td class="td-clave">${escapeHtml(it.numero_control || '—')}</td>
                <td class="td-nombre-apellidos">${escapeHtml(it.nombre_estudiante || '—')}</td>
                <td class="td-estado">${tipoAlerta(it.tipo_alerta)}</td>
                <td>${it.valor_detectado ?? 0}</td>
                <td class="td-fecha">${fmtSoloFecha(it.fecha_referencia)}</td>
                <td class="td-fecha">${fmtFechaHoraLocal(it.fecha_disparo)}</td>
                <td class="td-estado">${estadoAlerta(it.estado_alerta)}</td>
                <td>${escapeHtml(it.atendida_por_nombre || '—')}</td>
                <td class="td-fecha">${fmtFechaHoraLocal(it.atendida_en)}</td>
                <td>
                    <div class="botones-tabla">
                        <button type="button" class="btn-ver-detalles-item btn-ver-alerta" data-id="${it.id}">
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
        state.perPage = Number(meta.per_page || 20);
        state.total = Number(meta.total || 0);
        state.from = meta.from ?? 0;
        state.to = meta.to ?? 0;

        if (textoPagina) {
            textoPagina.textContent = `Página ${state.currentPage} de ${state.lastPage}`;
        }

        if (infoPaginacion) {
            infoPaginacion.textContent = `Mostrando ${state.from || 0} a ${state.to || 0} de ${state.total} alertas`;
        }

        if (btnAnterior) btnAnterior.disabled = state.currentPage <= 1;
        if (btnSiguiente) btnSiguiente.disabled = state.currentPage >= state.lastPage;
    }

    async function cargarTabla(page = 1) {
        tbody.innerHTML = `<tr><td colspan="11" class="td-estado-tabla">Cargando contenido…</td></tr>`;

        if (aborter) aborter.abort();
        aborter = new AbortController();

        try {
            const url = new URL(cfg.urlTabla, window.location.origin);

            const buscar = inputBuscar.value.trim();
            const periodoId = filtroPeriodo?.value ?? '';
            const tipo = filtroTipo?.value ?? '';
            const estado = filtroEstado?.value ?? '';
            const perPage = filtroPerPage?.value ?? '20';

            if (buscar) url.searchParams.set('buscar', buscar);
            if (periodoId) url.searchParams.set('periodo_id', periodoId);
            if (tipo) url.searchParams.set('tipo', tipo);
            if (estado) url.searchParams.set('estado', estado);
            if (perPage) url.searchParams.set('per_page', perPage);

            url.searchParams.set('page', String(page));

            const r = await fetchJson(url.toString(), { signal: aborter.signal });
            if (!r.ok) return;

            renderRows(r.data?.data || []);
            renderMeta(r.data?.meta || {});
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al cargar el historial de alertas.</p>');
        }
    }

    async function cargarResumen() {
        const url = new URL(cfg.urlResumen, window.location.origin);

        if (filtroPeriodo?.value) url.searchParams.set('periodo_id', filtroPeriodo.value);
        if (filtroEstado?.value) url.searchParams.set('estado', filtroEstado.value);

        const r = await fetchJson(url.toString());
        if (!r.ok) return;

        const d = r.data?.data || {};

        document.getElementById('resumen-historial-total').textContent = d.total_historicas ?? 0;
        document.getElementById('resumen-historial-atendidas').textContent = d.atendidas ?? 0;
        document.getElementById('resumen-historial-cerradas').textContent = d.cerradas ?? 0;
        document.getElementById('resumen-historial').textContent = d.gestionadas ?? 0;
    }

    async function cargarTodo(page = 1) {
        await cargarResumen();
        await cargarTabla(page);
    }

    tabla.addEventListener('click', async (ev) => {
        const btnVer = ev.target.closest('.btn-ver-alerta');
        if (!btnVer) return;

        const url = new URL(buildUrl(cfg.urlVer, btnVer.dataset.id), window.location.origin);
        const r = await fetchJson(url.toString());
        if (!r.ok) return;

        const d = r.data?.alerta;
        if (!d) return;

        displayModalDetalles(`
            <h3 class="titulo-modal-detalles">Detalle de alerta #${d.id}</h3>

            <div class="scroll-contenido-modal-detalles">
                <div class="contenedor-detalles">
                    <div class="caja-detalle">
                        <p class="nombre-detalle">Tipo:</p>
                        <p class="info-detalle">${escapeHtml(d.tipo_alerta)}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Número de control:</p>
                        <p class="info-detalle">${escapeHtml(d.estudiante?.numero_control || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Estudiante:</p>
                        <p class="info-detalle">${escapeHtml(d.estudiante?.nombre || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Periodo:</p>
                        <p class="info-detalle">${escapeHtml(d.periodo?.nombre || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Valor detectado:</p>
                        <p class="info-detalle">${d.valor_detectado ?? 0}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Fecha referencia:</p>
                        <p class="info-detalle">${fmtSoloFecha(d.fecha_referencia)}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Fecha disparo:</p>
                        <p class="info-detalle">${fmtFechaHoraLocal(d.fecha_disparo)}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Estado:</p>
                        <p class="info-detalle">${estadoAlerta(d.estado_alerta)}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Observaciones:</p>
                        <p class="info-detalle">${escapeHtml(d.observaciones || 'Sin observaciones.')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Atendida por:</p>
                        <p class="info-detalle">${escapeHtml(d.atendida_por?.nombre || '—')}</p>
                    </div>
                </div>
            </div>
        `);
    });

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

    filtroTipo?.addEventListener('change', async () => {
        state.currentPage = 1;
        await cargarTodo(1);
    });

    filtroEstado?.addEventListener('change', async () => {
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

    cargarTodo(1);
}