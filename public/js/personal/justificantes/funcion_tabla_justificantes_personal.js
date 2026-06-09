document.addEventListener('DOMContentLoaded', () => {
    initTablaJustificantesPersonal();
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



//===============CONSTRUCCIONES DE URLs POR ID===============
// function buildUrl(template, id) {
//     if (!template) return '#';
//     return template.replace('__ID__', String(id));
// }

//laravel genera la ruta con __ID__, y el JS solo reemplaza.
function buildUrl(template, id) {
    if (!template) return '#';
    if (template.includes('__ID__')) return template.replace('__ID__', String(id));
    return template.replace(/\/0$/, `/${id}`);
}
//==========================================================


//=========Capa de seguridad para endpoints JSON=======
// INICIAL
// async function fetchJson(url, { method = 'GET', body = null, signal } = {}) { 
//     const options = {
//         method,
//         credentials: 'same-origin',
//         headers: {
//             'Accept': 'application/json',
//             'X-Requested-With': 'XMLHttpRequest',
//             ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
//         },
//         signal,
//     };

//     if (body !== null) {
//         options.headers['Content-Type'] = 'application/json';
//         options.body = JSON.stringify(body);
//     }

//     const res = await fetch(url, options);
//     const data = await res.json().catch(() => null);

//     if (!res.ok) {
//         displayModal(`<p class="error">${escapeHtml(data?.message || 'Ocurrió un error.')}</p>`);
//         return { ok: false, status: res.status, data };
//     }

//     return { ok: true, status: res.status, data };
// }

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

        case 'NO_APLICA':
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


//DAR HTML Y ESTILOS A LOS ESTADOS DE JUSTIFICANTE
function estadoJustificante(estado_justificante) {

    const e = String(estado_justificante || 'SIN_ESTADO').toUpperCase();

    let cls = 'estado-justificante';

    switch (e) {

        case 'APROBADO':
            cls += ' estado-aprovado';
            break;

        case 'PENDIENTE':
            cls += ' estado-pendiente';
            break;

        case 'RECHAZADO':
            cls += ' estado-rechazado';
            break;

        case 'CANCELADO':
            cls += ' estado-cancelado';
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


//PARA MOSTRAR LAS CAJAS DE NUMERO DE CONTROL, NOMBRE, ESTATUS, AREA DE ESPECIALIDAD
// document.addEventListener('click', function(e) {
//     const btn = e.target.closest('.btn-toggle-detalles');
//     if (!btn) return;

//     const container = btn.closest('.contenedor-detalles-formulario');
//     const isExpanded = btn.getAttribute('aria-expanded') === 'true';

//     btn.setAttribute('aria-expanded', !isExpanded);
//     container.classList.toggle('detalles-visibles');
// });


function initTablaJustificantesPersonal() {
    const tabla = document.getElementById('tabla-justificantes-personal');
    const btnRefrescar = document.getElementById('btn-refrescar-tabla-justificantes-personal');
    const inputBuscar = document.getElementById('input-buscar-justificantes-personal');
    const btnBuscar = document.getElementById('btn-buscar-justificantes-personal');
    const filtroEstado = document.getElementById('filtro-estado-justificantes-personal');
    const filtroPerPage = document.getElementById('filtro-per-page-justificantes-personal');

    const btnAnterior = document.getElementById('btn-anterior-justificantes-personal');
    const btnSiguiente = document.getElementById('btn-siguiente-justificantes-personal');
    const textoPagina = document.getElementById('texto-pagina-justificantes-personal');
    const infoPaginacion = document.getElementById('info-paginacion-justificantes-personal');

    if (!tabla) return;

    const tbody = tabla.querySelector('tbody');

    const cfg = {
        urlTabla: tabla.dataset.urlTablaJustificantes,
        urlVer: tabla.dataset.urlVerJustificante,
        urlAprobar: tabla.dataset.urlAprobarJustificante,
        urlRechazar: tabla.dataset.urlRechazarJustificante,
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
            tbody.innerHTML = `<tr><td colspan="9" class="td-estado-tabla">Sin justificantes para mostrar.</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((it) => `
            <tr>
                <td class="td-id">${it.id ?? '—'}</td>
                <td class="td-clave">${escapeHtml(it.folio || '—')}</td>
                <td class="td-clave">${escapeHtml(it.estudiante?.numero_control || '—')}</td>
                <td class="td-nombre-apellidos">${escapeHtml(it.estudiante?.nombre_completo || '—')}</td>
                <td class="td-periodo">${escapeHtml(it.periodo?.nombre || '—')}</td>
                <td class="td-descripcion">${escapeHtml(it.motivo || '—')}</td>
                <td class="td-fecha">${escapeHtml((it.detalles || []).map(d => fmtSoloFecha(d.fecha)).join(', ') || '—')}</td>
                <td class="td-estado">${estadoJustificante(it.estado_justificante)}</td>
                <td>
                    <div class="botones-tabla">
                        <button type="button" class="btn-ver-detalles-item btn-ver-justificante" data-id="${it.id}">
                            <i class="fa-solid fa-magnifying-glass"></i>
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
            infoPaginacion.textContent = `Mostrando ${state.from || 0} a ${state.to || 0} de ${state.total} justificantes`;
        }

        if (btnAnterior) btnAnterior.disabled = state.currentPage <= 1;
        if (btnSiguiente) btnSiguiente.disabled = state.currentPage >= state.lastPage;
    }


    //==================TARJETAS DE RESUMES==================
    function cargarResumen(items = []) {
        document.getElementById('resumen-justificantes-pendientes').textContent =
            items.filter(x => x.estado_justificante === 'PENDIENTE').length;

        document.getElementById('resumen-justificantes-aprobados').textContent =
            items.filter(x => x.estado_justificante === 'APROBADO').length;

        document.getElementById('resumen-justificantes-rechazados').textContent =
            items.filter(x => x.estado_justificante === 'RECHAZADO').length;

        document.getElementById('resumen-justificantes-cancelados').textContent =
            items.filter(x => x.estado_justificante === 'CANCELADO').length;
    }


    async function cargarTabla(page = 1) {
        tbody.innerHTML = `<tr><td colspan="9" class="td-estado-tabla">Cargando contenido…</td></tr>`;

        if (aborter) aborter.abort();
        aborter = new AbortController();

        try {
            const url = new URL(cfg.urlTabla, window.location.origin);

            const buscar = inputBuscar?.value.trim() || '';
            const estado = filtroEstado?.value || '';
            const perPage = filtroPerPage?.value || '20';

            if (buscar) url.searchParams.set('buscar', buscar);
            if (estado) url.searchParams.set('estado', estado);
            if (perPage) url.searchParams.set('per_page', perPage);

            url.searchParams.set('page', String(page));

            const r = await fetchJson(url.toString(), { signal: aborter.signal });
            if (!r.ok) return;

            const items = r.data?.data || [];

            renderRows(items);
            renderMeta(r.data?.meta || r.data || {});
            cargarResumen(items);

        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al cargar justificantes.</p>');
        }
    }


    tabla.addEventListener('click', async (ev) => {
        const btnVer = ev.target.closest('.btn-ver-justificante');
        if (!btnVer) return;

        const url = buildUrl(cfg.urlVer, btnVer.dataset.id);
        const r = await fetchJson(url);
        if (!r.ok) return;

        const d = r.data || {};

        const puedeRevisar = d.estado_justificante === 'PENDIENTE';
        const puedeVer = ['APROBADO', 'RECHAZADO', 'CANCELADO'].includes(d.estado_justificante);

        const inputRevision = puedeRevisar ? `
            <div class="input-box-editar">
                <label class="nombre-input">Comentario de revisión:</label>
                <textarea class="input-field" id="comentario-revision-justificante" maxlength="2000"
                    placeholder="Escribe un comentario de revisión"></textarea>
            </div>
        ` : '';

        const botonesRevision = puedeRevisar ? `
            <div class="botones-formulario">
                <button type="button" class="btn-cancelar-borrar" id="btn-rechazar-justificante" data-id="${d.id}">
                    <span>Rechazar</span>
                    <span class="spinner"></span>
                    <span class="texto-spinner">Espera</span>
                </button>

                <button type="button" class="btn-guardar-enviar" id="btn-aprobar-justificante" data-id="${d.id}">
                    <span>Aprobar</span>
                    <span class="spinner"></span>
                    <span class="texto-spinner">Espera</span>
                </button>
            </div>
        ` : '';

        const cajasDetalle = puedeVer ? `
            <p class="titulo-caja-detalle">Extras:</p>
            <div class="contenedor-detalles-formulario">
                <div class="caja-detalle-formulario ultimo">
                    <p class="nombre-detalle">Comentario de revisión:</p>
                    <p class="info-detalle">${escapeHtml(d.comentario_revision || 'Sin comentario.')}</p>
                </div>
            </div>
        ` : '';

        displayModalFormularioEditar(`
            <div class="form-editar-contenido">

                <div class="form-title">
                    <span>Detalles del justificante</span>
                </div>

                <div class="form-inputs">
                    <div class="scroll-editar">

                        <p class="titulo-caja-detalle">Datos del alumno:</p>
                        <div class="contenedor-detalles-formulario">
                            <div class="caja-detalle-formulario">
                                <p class="nombre-detalle">Estudiante:</p>
                                <p class="info-detalle">${escapeHtml(d.estudiante?.nombre_completo || '—')} - ${escapeHtml(d.estudiante?.numero_control || '—')}</p>
                            </div>

                            <div class="caja-detalle-formulario">
                                <p class="nombre-detalle">Correo:</p>
                                <p class="info-detalle">${escapeHtml(d.estudiante?.email || '—')}</p>
                            </div>
                        </div>

                        <p class="titulo-caja-detalle">Datos del justificante:</p>
                        <div class="contenedor-detalles-formulario">
                            <div class="caja-detalle-formulario">
                                <p class="nombre-detalle">Folio:</p>
                                <p class="info-detalle">${escapeHtml(d.folio || '—')}</p>
                                <div class="contenedor-botones-modal-justificante">
                                    ${d.archivo_url
                                        ? `<a class="btn-justificante btn-ver-justificante" href="${escapeHtml(d.archivo_url)}" draggable="false"           ondragstart="return false;" target="_blank">
                                            <i class="fa-solid fa-file"></i> Ver archivo
                                            </a>`
                                        : 'Sin archivo'}
                                </div>
                            </div>

                            <div class="caja-detalle-formulario">
                                <p class="nombre-detalle">Estado:</p>
                                <p class="info-detalle">${estadoJustificante(d.estado_justificante)}</p>
                            </div>

                            <div class="caja-detalle-formulario">
                                <p class="nombre-detalle">Motivo:</p>
                                <p class="info-detalle">${escapeHtml(d.motivo || '—')}</p>
                            </div>

                            <div class="caja-detalle-formulario">
                                <p class="nombre-detalle">Descripción:</p>
                                <p class="info-detalle">${escapeHtml(d.descripcion || 'Sin descripción.')}</p>
                            </div>
                        </div>
                        
                        <p class="titulo-caja-detalle">Referencia:</p>
                        <div class="contenedor-detalles-formulario">
                            <div class="caja-detalle-formulario">
                                <p class="nombre-detalle">Periodo:</p>
                                <p class="info-detalle">${escapeHtml(d.periodo || '—')}</p>
                            </div>

                            <div class="caja-detalle-formulario">
                                <p class="nombre-detalle">Fecha(s):</p>
                                <p class="info-detalle">
                                    ${(d.fechas || []).map(f => `${fmtSoloFecha(f.fecha)} - ${estadoAsistencia(f.estatus_asistencia)}`).join('<br>') || '—'}
                                </p>
                            </div>
                        </div>

                        ${cajasDetalle}
                    
                        ${inputRevision}

                    </div>

                    ${botonesRevision}
                </div>
            </div>
        `);

        document.getElementById('btn-aprobar-justificante')?.addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            const comentario = document.getElementById('comentario-revision-justificante')?.value || '';

            setLoadingFormulario(btn, true);

            try {
                const resp = await fetchJson(buildUrl(cfg.urlAprobar, btn.dataset.id), {
                    method: 'POST',
                    body: { comentario_revision: comentario }
                });

                if (!resp.ok) return;

                displayMensajeToast(`<p class="exito">${escapeHtml(resp.data?.message || 'Justificante aprobado correctamente.')}</p>`);
                cerrarModalFormularioEditar();
                await cargarTabla(state.currentPage);

            } catch (error) {
                console.error(error);
                displayMensajeToast('<p class="error">Error de conexión al aprobar justificante.</p>');
            } finally {
                if (document.body.contains(btn)) {
                    setLoadingFormulario(btn, false);
                }
            }
        });

        document.getElementById('btn-rechazar-justificante')?.addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            const comentario = document.getElementById('comentario-revision-justificante')?.value || '';

            if (!comentario.trim()) {
                displayMensajeToast('<p class="advertencia">El comentario es obligatorio para rechazar.</p>');
                return;
            }

            setLoadingFormulario(btn, true);

            try {
                const resp = await fetchJson(buildUrl(cfg.urlRechazar, btn.dataset.id), {
                    method: 'POST',
                    body: { comentario_revision: comentario }
                });

                if (!resp.ok) return;

                displayMensajeToast(`<p class="exito">${escapeHtml(resp.data?.message || 'Justificante rechazado correctamente.')}</p>`);
                cerrarModalFormularioEditar();
                await cargarTabla(state.currentPage);

            } catch (error) {
                console.error(error);
                displayMensajeToast('<p class="error">Error de conexión al rechazar justificante.</p>');
            } finally {
                if (document.body.contains(btn)) {
                    setLoadingFormulario(btn, false);
                }
            }
        });
    });


    btnRefrescar?.addEventListener('click', async () => {
        setLoadingTabla(btnRefrescar, true);
        await cargarTabla(state.currentPage);
        setLoadingTabla(btnRefrescar, false);
    });

    btnBuscar?.addEventListener('click', async () => {
        state.currentPage = 1;
        setLoadingTabla(btnBuscar, true);
        await cargarTabla(1);
        setLoadingTabla(btnBuscar, false);
    });

    inputBuscar?.addEventListener('keydown', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            state.currentPage = 1;
            setLoadingTabla(btnBuscar, true);
            await cargarTabla(1);
            setLoadingTabla(btnBuscar, false);
        }
    });

    filtroEstado?.addEventListener('change', async () => {
        state.currentPage = 1;
        await cargarTabla(1);
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

    cargarTabla(1);
}