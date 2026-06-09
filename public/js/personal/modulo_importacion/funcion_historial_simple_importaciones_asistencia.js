document.addEventListener('DOMContentLoaded', () => {
    initTablaHistorialImportaciones();
    // initTablaNOMBRE(); // para otra tabla en el futuro
});

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';


//Para formatear JSON antes de insertarlo al modal (por ejemplo con JSON.stringify)
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
//==========================================================


//===============CONSTRUCCIONES DE URLs POR ID===============
//laravel genera la ruta con __ID__, y el JS solo reemplaza.
function buildUrl(template, id) {
    if (!template) return '#';
    if (template.includes('__ID__')) return template.replace('__ID__', String(id));
    return template.replace(/\/0$/, `/${id}`);
}
//==========================================================


//SPINNER PARA BOTONES CON ICONO O TEXTO (DE TABLA Y FORMULARIO)
function setLoadingFormulario(button, isLoading) {
    if (!button) return;

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
    if (!button) return;

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
        displayModal(`<p class="error">${escapeHtml(data?.message ?? `Error inesperado (${res.status}).`)}</p>`);
        return { ok: false, status: res.status, data };
    }

    return { ok: true, status: res.status, data };
}
//==========================================================



//======TABLA DE HISTORIAL DE IMPORTACIONES (LISTA, VER Y DESCARGAR)======
function initTablaHistorialImportaciones() {
    const tabla = document.getElementById('tabla-historial-importaciones');
    const btnRefrescar = document.getElementById('btn-refrescar-historial');

    //No ejecutar si la tabla o el boton no existen en esta vista
    if (!tabla || !btnRefrescar) return;

    const tbody = tabla.querySelector('tbody');

    const cfgTabHistorialSimpleImportacionesAsistencia = {
        urlListadoHistorialSimpleImportacionesAsistencia: tabla.dataset.urlTablaListadoHistorialSimpleImportacionesAsistencia,
        urlVerDetallesImportacionAsistenciaSimple: tabla.dataset.urlTablaVerDetallesImportacionAsistenciaSimple,
        urlDescargarArchivoImportacionAsistenciaSimple: tabla.dataset.urlTablaDescargarArchivoImportacionAsistenciaSimple,
        limitePorDefecto: 10,
        limiteMaximo: 50,
    };

    let aborter = null;


    //para mostrar arrays, objetos o JSON bonito dentro del modal
    function renderValorJson(value) {
        if (value == null) return 'Sin resultados.';

        let obj = value;

        if (typeof value === 'string') {
            try {
                obj = JSON.parse(value);
            } catch {
                return escapeHtml(value);
            }
        }

        if (typeof obj === 'object') {
            return `<pre class="resultados-json-pre">${escapeHtml(JSON.stringify(obj, null, 2))}</pre>`;
        }

        return escapeHtml(obj);
    }

    //dar HTML para estilos
    function estadoImportacion(estado) {
        const e = String(estado || 'OK').toUpperCase();
        const cls = e === 'ERROR' ? 'estado-resultado-error' : 'estado-resultado-ok';
        return `<span class="${cls}">${e}</span>`;
    }


    //PINTADO DE LA TABLA DEL HISTORIAL
    function renderRows(items) {
        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="td-estado-tabla">Sin importaciones aún.</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((it) => `
            <tr>
                <td class="td-id">${it.id}</td>
                <td class="td-nombre-archivo" title="${escapeHtml(it.archivo ?? '')}">
                    ${escapeHtml(it.archivo ?? '—')}
                </td>
                <td class="td-periodo">${escapeHtml(it.periodo ?? '—')}</td>
                <td class="td-fuente-importacion">${escapeHtml(it.reloj ?? '—')}</td>
                <td class="td-tipo-importacion">${escapeHtml(it.tipo_importacion ?? '—')}</td>
                <td class="td-estado-importacion">${estadoImportacion(it.estado)}</td>
                <td class="td-fecha">${fmtFecha(it.importado_en)}</td>
                <td>
                    <div class="botones-tabla">
                        <button type="button" class="btn-ver-detalles-item" data-id="${it.id}">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </td>
                <td>
                    <div class="botones-tabla">
                        <button type="button" class="btn-descargar-archivo" data-id="${it.id}">
                            <i class="fa-regular fa-file-excel"></i>
                            <span class="spinner-tabla"></span>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }


    //CARGA DE LA TABLA DE HISTORIAL
    async function cargarHistorial(limit = cfgTabHistorialSimpleImportacionesAsistencia.limitePorDefecto) {
        tbody.innerHTML = `<tr><td colspan="9" class="td-estado-tabla">Cargando contenido…</td></tr>`;

        if (aborter) aborter.abort();
        aborter = new AbortController();

        try {
            const safeLimit = Math.max(
                1,
                Math.min(Number(limit) || cfgTabHistorialSimpleImportacionesAsistencia.limitePorDefecto, cfgTabHistorialSimpleImportacionesAsistencia.limiteMaximo)
            );

            const url = new URL(cfgTabHistorialSimpleImportacionesAsistencia.urlListadoHistorialSimpleImportacionesAsistencia, window.location.origin);
            url.searchParams.set('limit', safeLimit);

            const r = await fetchJson(url.toString(), { signal: aborter.signal });
            if (!r.ok) return;

            renderRows(r.data?.data || []);
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error(err);
            displayMensajeToast(`<p class="error">Error de conexión. <span class="ext">Revisa tu servidor o recarga la página.</span></p>`);
        }
    }


    //VER DETALLE DE UNA IMPORTACION
    async function verDetalleImportacion(id) {
        try {
            const r = await fetchJson(buildUrl(cfgTabHistorialSimpleImportacionesAsistencia.urlVerDetallesImportacionAsistenciaSimple, id));
            if (!r.ok) return;

            const d = r.data?.data;
            if (!d) {
                displayMensajeToast('<p class="error">No se pudo leer el detalle de la importación.</p>');
                return;
            }

            displayModalDetalles(`
                <h3 class="titulo-modal-detalles">Detalles de la importación #${d.id}</h3>

                <div class="scroll-contenido-modal-detalles">

                    <div class="contenedor-detalles">

                        <div class="caja-detalle">
                            <p class="nombre-detalle">Archivo:</p>
                            <p class="info-detalle">${escapeHtml(d.archivo ?? '—')}</p>
                        </div>

                         <div class="caja-detalle">
                            <p class="nombre-detalle">Reloj:</p>
                            <p class="info-detalle">${escapeHtml(d.reloj ?? '—')}</p>
                        </div>

                        <div class="caja-detalle">
                            <p class="nombre-detalle">Parser del reloj utilizado:</p>
                            <p class="info-detalle">${escapeHtml(d.parser_clave_reloj ?? '—')}</p>
                        </div>

                        <div class="caja-detalle">
                            <p class="nombre-detalle">Tipo de importación:</p>
                            <p class="info-detalle">${escapeHtml(d.tipo_importacion ?? '—')}</p>
                        </div>

                        <div class="caja-detalle">
                            <p class="nombre-detalle">Periodo:</p>
                            <p class="info-detalle">${escapeHtml(d.periodo ?? '—')}</p>
                        </div>

                        <div class="caja-detalle">
                            <p class="nombre-detalle">Hojas detectadas del archivo:</p>
                            <p class="info-detalle">${renderValorJson(d.hojas_detectadas_perser_reloj)}</p>
                        </div>

                        <div class="caja-detalle">
                            <p class="nombre-detalle">Importado por:</p>
                            <p class="info-detalle">${escapeHtml(d.importado_por ?? 'Módulo de Importación (automático)')}</p>
                        </div>

                        <div class="caja-detalle">
                            <p class="nombre-detalle">Estado:</p>
                            <p class="info-detalle">${estadoImportacion(d.estado_importacion ?? '—')}</p>
                        </div>

                        <div class="caja-detalle">
                            <p class="nombre-detalle">Fecha de importación:</p>
                            <p class="info-detalle">${fmtFecha(d.importado_en)}</p>
                        </div>

                        <div class="caja-detalle">
                            <p class="nombre-detalle">Notas de la importación:</p>
                            <p class="info-detalle">${escapeHtml(d.notas ?? 'Sin notas.')}</p>
                        </div>

                    </div>

                    <div class="caja-detalle caja-detalle-resultados">
                        <p class="nombre-detalle">Advertencias:</p>
                        <p class="info-detalle">${renderValorJson(d.advertencias_importacion)}</p>
                    </div>
                    
                    <div class="caja-detalle caja-detalle-resultados">
                        <p class="nombre-detalle">Resultados de la importación:</p>
                        <p class="info-detalle">${renderValorJson(d.resultados_importacion)}</p>
                    </div>

                </div>
            `);

        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al ver el detalle de la importación.</p>');
        }
    }


    //DESCARGAR ARCHIVO DE IMPORTACION
    async function descargarArchivoImportacion(id) {
        try {
            const url = buildUrl(cfgTabHistorialSimpleImportacionesAsistencia.urlDescargarArchivoImportacionAsistenciaSimple, id);
            const res = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                }
            });

            if (res.status === 401) {
                displayModal(`<p class="error">No autorizado. Inicia sesión.</p>`);
                return;
            }

            if (res.status === 403) {
                let data = null;
                try { data = await res.json(); } catch {}
                displayModal(`<p class="error">${escapeHtml(data?.message ?? 'Acceso denegado.')}</p>`);
                return;
            }

            if (res.status === 404) {
                displayMensajeToast(`<p class="error">El archivo ya no se encuentra disponible para descargar.</p>`);
                return;
            }

            if (res.status === 419) {
                displayModal(`<p class="error">Sesión expiró. Recarga la página.</p>`);
                return;
            }

            if (!res.ok) {
                let msg = `No se pudo descargar (HTTP ${res.status}).`;
                const ct = res.headers.get('content-type') || '';

                if (ct.includes('application/json')) {
                    const j = await res.json().catch(() => null);
                    msg = j?.message || msg;
                }

                displayModal(`<p class="error">${escapeHtml(msg)}</p>`);
                return;
            }

            const blob = await res.blob();

            let filename = `importacion_${id}.xlsx`;
            const disp = res.headers.get('content-disposition') || '';
            const m = /filename\*=UTF-8''([^;]+)|filename="?([^"]+)"?/i.exec(disp);
            const raw = m?.[1] || m?.[2];

            if (raw) {
                filename = decodeURIComponent(raw);
            }

            const a = document.createElement('a');
            const objectUrl = URL.createObjectURL(blob);

            a.href = objectUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();

            URL.revokeObjectURL(objectUrl);

            displayMensajeToast(`<p class="exito">Archivo descargado con éxito.</p>`);

        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al descargar el archivo.</p>');
        }
    }


    //==================LISTENER PARA LA TABLA==================
    tabla.addEventListener('click', async (ev) => {
        const btnDetalle = ev.target.closest('.btn-ver-detalles-item');
        if (btnDetalle) {
            verDetalleImportacion(btnDetalle.dataset.id);
            return;
        }

        const btnDescargar = ev.target.closest('.btn-descargar-archivo');
        if (btnDescargar) {
            setLoadingTabla(btnDescargar, true);
            try {
                await descargarArchivoImportacion(btnDescargar.dataset.id);
            } finally {
                setLoadingTabla(btnDescargar, false);
            }
        }
    });

    btnRefrescar.addEventListener('click', async () => {
        setLoadingTabla(btnRefrescar, true);
        await cargarHistorial(cfgTabHistorialSimpleImportacionesAsistencia.limitePorDefecto);
        setLoadingTabla(btnRefrescar, false);
    });
    //==========================================================

    cargarHistorial(cfgTabHistorialSimpleImportacionesAsistencia.limitePorDefecto);
}
//==========================================================