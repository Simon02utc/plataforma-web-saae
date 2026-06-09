document.addEventListener('DOMContentLoaded', () => {
    initTablaJustificantesEstudiante();
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



function initTablaJustificantesEstudiante() {
    const tabla = document.getElementById('tabla-justificantes-estudiante');
    const btnRefrescar = document.getElementById('btn-refrescar-tabla-justificantes');
    const btnNuevoJustificante = document.getElementById('btn-abrir-modal-crear-justificante');
    const inputBuscar = document.getElementById('input-buscar-justificantes');
    const btnBuscar = document.getElementById('btn-buscar-justificantes');
    const filtroEstado = document.getElementById('filtro-estado-justificantes');
    const filtroPerPage = document.getElementById('filtro-per-page-justificantes');

    const btnAnterior = document.getElementById('btn-anterior-justificantes');
    const btnSiguiente = document.getElementById('btn-siguiente-justificantes');
    const textoPagina = document.getElementById('texto-pagina-justificantes');
    const infoPaginacion = document.getElementById('info-paginacion-justificantes');

    if (!tabla) return;

    const tbody = tabla.querySelector('tbody');

    const cfg = {
        urlTabla: tabla.dataset.urlTablaJustificantes,
        urlFaltas: tabla.dataset.urlFaltasDisponibles,
        urlGuardar: tabla.dataset.urlGuardarJustificante,
        urlVer: tabla.dataset.urlVerJustificante,
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
            tbody.innerHTML = `<tr><td colspan="8" class="td-estado-tabla">Sin justificantes para mostrar.</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((it) => `
            <tr>
                <td class="td-id">${it.id ?? '—'}</td>
                <td class="td-clave">${escapeHtml(it.folio || '—')}</td>
                <td class="td-periodo">${escapeHtml(it.periodo?.nombre || '—')}</td>
                <td class="td-descripcion">${escapeHtml(it.motivo || '—')}</td>
                <td class="td-fecha">${escapeHtml((it.detalles || []).map(d => fmtSoloFecha(d.fecha)).join(', ') || '—')}</td>
                <td class="td-estado">${estadoJustificante(it.estado_justificante)}</td>
                <td class="td-descripcion">${escapeHtml(it.comentario_revision || '—')}</td>
                <td>
                    <div class="botones-tabla">
                        <button type="button" class="btn-ver-detalles-item btn-ver-justificante" data-id="${it.id}">
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
            infoPaginacion.textContent = `Mostrando ${state.from || 0} a ${state.to || 0} de ${state.total} justificantes`;
        }

        if (btnAnterior) btnAnterior.disabled = state.currentPage <= 1;
        if (btnSiguiente) btnSiguiente.disabled = state.currentPage >= state.lastPage;
    }

    function cargarResumen(items = []) {
        const pendientes = items.filter(x => x.estado_justificante === 'PENDIENTE').length;
        const aprobados = items.filter(x => x.estado_justificante === 'APROBADO').length;
        const rechazados = items.filter(x => x.estado_justificante === 'RECHAZADO').length;
        const cancelados = items.filter(x => x.estado_justificante === 'CANCELADO').length;

        document.getElementById('resumen-justificantes-pendientes').textContent = pendientes;
        document.getElementById('resumen-justificantes-aprobados').textContent = aprobados;
        document.getElementById('resumen-justificantes-rechazados').textContent = rechazados;
        document.getElementById('resumen-justificantes-cancelados').textContent = cancelados;
    }

    async function cargarTabla(page = 1) {
        tbody.innerHTML = `<tr><td colspan="8" class="td-estado-tabla">Cargando contenido…</td></tr>`;

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

        displayModalDetalles(`
            <h3 class="titulo-modal-detalles">Detalle del justificante #${escapeHtml(d.id || '')}</h3>

            <div class="scroll-contenido-modal-detalles">

                <div class="contenedor-detalles">
                    <div class="caja-detalle">
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

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Estado:</p>
                        <p class="info-detalle">${estadoJustificante(d.estado_justificante)}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Periodo:</p>
                        <p class="info-detalle">${escapeHtml(d.periodo || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Fechas:</p>
                        <p class="info-detalle">
                            ${(d.fechas || []).map(f => `${fmtSoloFecha(f.fecha)} - ${estadoAsistencia(f.estatus_asistencia)}`).join('<br>') || '—'}
                        </p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Motivo:</p>
                        <p class="info-detalle">${escapeHtml(d.motivo || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Descripción:</p>
                        <p class="info-detalle">${escapeHtml(d.descripcion || 'Sin descripción.')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Comentario revisión:</p>
                        <p class="info-detalle">${escapeHtml(d.comentario_revision || 'Sin comentario.')}</p>
                    </div>

                </div>
            </div>
        `);
    });


    btnNuevoJustificante?.addEventListener('click', async () => {
        const r = await fetchJson(cfg.urlFaltas);
        if (!r.ok) return;

        const faltas = r.data?.data || [];

        if (!faltas.length) {
            displayMensajeToast('<p class="advertencia">No tienes faltas disponibles para justificar.</p>');
            return;
        }

        const opcionesFaltas = faltas.map(f => `
            <label class="elementos-checkbox">
                <input type="checkbox" name="asistencias_ids[]" value="${f.id}">
                <span class="circulo-checkbox"></span>
                <span class="texto-checkbox">
                    ${fmtSoloFecha(f.fecha)} - ${estadoAsistencia(f.estatus_asistencia)} - Periodo: ${escapeHtml(f.periodo || 'Sin periodo')}
                </span>
            </label>
        `).join('');

        displayModalFormularioEditar(`
            <form id="form-enviar-justificante" class="form-editar-contenido">
                
                <div class="form-title">
                    <span>Enviar justificante</span>
                </div>
                
                <div class="form-inputs">
                    <div class="scroll-editar">

                        <div class="input-box-editar">
                            <label class="nombre-input">Motivo:</label>
                            <input class="input-field" type="text" id="motivo-input" name="motivo" placeholder="Motivo" maxlength="150" autocomplete="off" required>
                        </div>

                        <div class="input-box-editar">
                            <label class="nombre-input">Descripción:</label>
                            <textarea class="input-field" id="descripcion-input" name="descripcion" placeholder="Describe brevemente el motivo del justificante" maxlength="500" autocomplete="off"></textarea>
                            <i class="ri-text-block icon"></i>
                        </div>

                        <div class="input-box-editar">
                            <label class="nombre-input">Archivo  (.pdf, .jpg, .jpeg, .png):</label>
                            <input class="input-field" type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>

                        <div class="contenedor-elementos-extra-form">
                            <p class="subtitulo-elementos-extra-form">Faltas a justificar:</p>
                            ${opcionesFaltas}
                        </div>

                    </div>

                    <div class="informacion-extra-formulario">
                        <p>Solo puedes justificar varias fechas de un mismo periodo.</p>
                    </div>

                    <div class="botones-formulario">
                        <button type="button" class="btn-cancelar-borrar">
                            <span>Cancelar</span>
                        </button>

                        <button type="submit" class="btn-guardar-enviar" id="btn-enviar-justificante">
                            <span>Enviar</span>
                            <span class="spinner"></span>
                            <span class="texto-spinner">Espera</span>
                        </button>
                    </div>

                </div>

            </form>
        `);

        const form = document.getElementById('form-enviar-justificante');

        form?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const seleccionadas = form.querySelectorAll('input[name="asistencias_ids[]"]:checked');

            if (!seleccionadas.length) {
                displayMensajeToast('<p class="advertencia">Selecciona al menos una falta para justificar.</p>');
                return;
            }

            const btnEnviar = document.getElementById('btn-enviar-justificante');
            const formData = new FormData(form);

            setLoadingFormulario(btnEnviar, true);

            try {
                const response = await fetch(cfg.urlGuardar, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const resp = await response.json();

                if (!response.ok) {

                    if (resp.errors) {

                        const mensajes = Object.values(resp.errors)
                            .flat()
                            .map(msg => `<p>${escapeHtml(msg)}</p>`)
                            .join('');

                        displayMensajeToast(`<div class="error">${mensajes}</div>`);

                    } else {

                        displayMensajeToast(`<p class="error">${escapeHtml(resp.message || 'Error al enviar justificante.')}</p>`);
                    }

                    return;
                }

                displayMensajeToast(`<p class="exito">${escapeHtml(resp.message || 'Justificante enviado correctamente.')}</p>`);

                form.reset();
                cerrarModalFormularioEditar();

                await cargarTabla(1);

            } catch (error) {
                console.error(error);
                displayMensajeToast('<p class="error">Error de conexión al enviar el justificante.</p>');
            } finally {
                if (document.body.contains(btnEnviar)) {
                    setLoadingFormulario(btnEnviar, false);
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