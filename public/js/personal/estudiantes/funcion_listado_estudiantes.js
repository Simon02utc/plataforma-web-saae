document.addEventListener('DOMContentLoaded', () => {
    initTablaEstudiantes();
    //initTablaNOMBRE(); --- para otra tabla en el futuro
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
        displayModal(`<p class="error">${data?.message ?? `Error inesperado (${res.status}).`}</p>`);
        return { ok: false, status: res.status, data };
    }

    return { ok: true, status: res.status, data };
}
//==========================================================


//PARA MOSTRAR LAS CAJAS DE NUMERO DE CONTROL, NOMBRE, ESTATUS, AREA DE ESPECIALIDAD
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-toggle-detalles');
    if (!btn) return;

    const container = btn.closest('.contenedor-detalles-formulario');
    const isExpanded = btn.getAttribute('aria-expanded') === 'true';

    btn.setAttribute('aria-expanded', !isExpanded);
    container.classList.toggle('detalles-visibles');
});


//======TABLA DE PERSONAL (LISTA, VER, EDITAR Y ELIMINAR)======
function initTablaEstudiantes() {
    const tabla = document.getElementById('tabla-listado-estudiantes');
    const btnRefrescar = document.getElementById('btn-refrescar-tabla-estudiantes');
    const btnGenerarCorreosPendientes = document.getElementById('btn-generar-correos-institucionales-pendientes');
    const btnReenviarActivaciones = document.getElementById('btn-reenviar-activaciones-estudiantes');
    const inputBuscar = document.getElementById('input-buscar-estudiantes');
    const btnBuscar = document.getElementById('btn-buscar-estudiantes');

    const filtroArea = document.getElementById('filtro-area-estudiantes');
    const filtroEstatus = document.getElementById('filtro-estatus-estudiantes');
    const filtroActivo = document.getElementById('filtro-activo-estudiantes');
    const filtroPerPage = document.getElementById('filtro-per-page-estudiantes');
    const btnLimpiarFiltros = document.getElementById('btn-limpiar-filtros-estudiantes');

    const btnExportarExcel = document.getElementById('btn-exportar-estudiantes-excel');

    const btnAnterior = document.getElementById('btn-anterior-estudiantes');
    const btnSiguiente = document.getElementById('btn-siguiente-estudiantes');
    const textoPagina = document.getElementById('texto-pagina-estudiantes');
    const infoPaginacion = document.getElementById('info-paginacion-estudiantes');

    if (!tabla || !btnRefrescar || !inputBuscar || !btnBuscar) return;

    const tbody = tabla.querySelector('tbody');

    const cfgTabEstudiantes = {
        urlListadoEstudiantes: tabla.dataset.urlTableListadoEstudiantes,

        urlGenerarCorreoInstitucional: tabla.dataset.urlTableGenerarCorreoInstitucional,
        urlGenerarCorreosInstitucionales: tabla.dataset.urlTableGenerarCorreosInstitucionales,
        urlReenviarActivacion: tabla.dataset.urlTableReenviarActivacion,
        urlReenviarActivaciones: tabla.dataset.urlTableReenviarActivaciones,

        urlVerDatosEscolaresEstudiante: tabla.dataset.urlTableVerDatosEscolaresEstudiante,
        urlVerEstudiante: tabla.dataset.urlTableVerEstudiante,

        urlVerAsignacionesEstudiante: tabla.dataset.urlTableVerAsignacionesEstudiante,
        urlGuardarAsignacionEstudiante: tabla.dataset.urlTableGuardarAsignacionEstudiante,
        urlDesactivarAsignacionEstudiante: tabla.dataset.urlTableDesactivarAsignacionEstudiante,
        urlReactivarAsignacionEstudiante: tabla.dataset.urlTableReactivarAsignacionEstudiante,
        urlEliminarAsignacionEstudiante: tabla.dataset.urlTableEliminarAsignacionEstudiante,

        urlEditarEstudiante: tabla.dataset.urlTableEditarEstudiante,
        urlEliminarEstudiante: tabla.dataset.urlTableEliminarEstudiante,

        urlExportarEstudiantesExcel: tabla.dataset.urlTableExportarEstudiantesExcel,
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


    //PINTADO DE LA TABLA DE LOS ESTUDIANTES
    function renderRows(items) {
        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="11" class="td-estado-tabla">Sin estudiantes para mostrar.</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((it) => {
            const estadoTexto = it.estado_cuenta_estudiante
                ? '<span class="estado-activado-personal"><i class="fa-solid fa-square-check"></i> Activado</span>'
                : '<span class="estado-desactivado-personal"><i class="fa-solid fa-square-xmark"></i> Desactivado</span>';

            
            const numeroControl = String(it.numero_control || '').toUpperCase().trim();
            const correo = String(it.correo_electronico || '').toLowerCase().trim();

            const numeroControlFormal = /^[A-Z][0-9]{2}[A-Z]{2}[0-9]{3}$/.test(numeroControl);
            const esCorreoInstitucional = correo.endsWith('@cenidet.tecnm.mx');

            const cuentaActiva = Boolean(it.estado_cuenta_estudiante);

            let accionCorreoInstitucionalHtml = '<span class="informacion-no-disponible-valido">No valido</span>';

            if (esCorreoInstitucional && !cuentaActiva) {
                accionCorreoInstitucionalHtml = `
                    <button type="button" class="btn-asignar-elementos-item btn-reenviar-activacion-estudiante" data-id="${it.id}" title="Reenviar enlace de activación de cuenta">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span class="spinner-tabla"></span>
                    </button>
                `;
            } else if (esCorreoInstitucional && cuentaActiva) {
                accionCorreoInstitucionalHtml = `
                    <span class="estado-activado">
                        <i class="fa-solid fa-square-check"></i> Generado
                    </span>
                `;
            } else if (!correo && numeroControlFormal && !cuentaActiva) {
                accionCorreoInstitucionalHtml = `
                    <button type="button" class="btn-asignar-elementos-item btn-generar-correo-institucional-estudiante" data-id="${it.id}" title="Generar correo insitucional y envio de enlace">
                        <i class="fa-solid fa-at"></i>
                        <span class="spinner-tabla"></span>
                    </button>
                `;
            }

            return `
                <tr>
                    <td class="td-id">${it.id}</td>
                    <td class="td-clave">${escapeHtml(it.numero_control || '—')}</td>
                    <td class="td-nombre-apellidos">${escapeHtml(it.nombre_completo_estudiante || '—')}</td>
                    <td class="td-correo">
                        ${escapeHtml(it.correo_electronico || '—')}
                        <div class="botones-tabla">
                            ${accionCorreoInstitucionalHtml}
                        </div>
                    </td>
                    <td class="td-telefono">${escapeHtml(it.telefono || '—')}</td>
                    <td class="td-listado-informacion">
                        <div class="contenedor-td-listado-informacion">
                            ${escapeHtml(it.datos_escolares_resumen || 'Sin datos.')}
                        </div>
                        <div class="botones-tabla">
                            <button type="button" class="btn-ver-detalles-item ver-datos-escolares-estudiante" data-id="${it.id}">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </td>
                    <td class="td-estado-personal">${estadoTexto}</td>
                    <td class="td-fecha">${fmtFecha(it.ultimo_acceso)}</td>
                    <td class="td-fecha-mini-tabla">
                        <table class="mini-tabla-items">
                            <tr>
                                <td class='td-nombre-mini-tabla primero'>Registro:</td>
                                <td class='td-contenido-mini-tabla'>${fmtFecha(it.registrado_en)}</td>
                            </tr>
                            <tr>
                                <td class='td-nombre-mini-tabla ultimo'>Edición:</td>
                                <td class='td-contenido-mini-tabla'>${fmtFecha(it.editado_en)}</td>
                            </tr>
                        </table>    
                    </td>
                    <td>
                        <div class="botones-tabla">
                            <button type="button" class="btn-ver-detalles-item btn-gestionar-asignacion-estudiante" data-id="${it.id}" title="Asignar estudiante con personal">
                                <i class="fa-solid fa-user-group"></i>
                            </button>
                            <button type="button" class="btn-editar-item btn-editar-estudiante" data-id="${it.id}" title="Editar datos del estudiante">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <div class="botones-tabla">
                            <button type="button" class="btn-eliminar-item btn-eliminar-estudiante" data-id="${it.id}" title="Eliminar estudiante">
                                <i class="fa-solid fa-trash"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
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

        if (btnAnterior) btnAnterior.disabled = state.currentPage <= 1;
        if (btnSiguiente) btnSiguiente.disabled = state.currentPage >= state.lastPage;
    }


    async function cargar(page = 1) {
        tbody.innerHTML = `<tr><td colspan="11" class="td-estado-tabla">Cargando contenido…</td></tr>`;

        if (aborter) aborter.abort();
        aborter = new AbortController();
        state.currentPage = page;

        try {
            const url = new URL(cfgTabEstudiantes.urlListadoEstudiantes, window.location.origin);

            const buscar = inputBuscar.value.trim();
            const areaId = filtroArea?.value ?? '';
            const estatusId = filtroEstatus?.value ?? '';
            const activo = filtroActivo?.value ?? '';
            const perPage = filtroPerPage?.value ?? '50';

            if (buscar) url.searchParams.set('buscar', buscar);
            if (areaId) url.searchParams.set('area_id', areaId);
            if (estatusId) url.searchParams.set('estatus_id', estatusId);
            if (activo !== '') url.searchParams.set('activo', activo);

            url.searchParams.set('per_page', perPage);
            url.searchParams.set('page', page);

            const r = await fetchJson(url.toString(), { signal: aborter.signal });
            if (!r.ok) return;

            renderRows(r.data?.data || []);
            renderMeta(r.data?.meta || {});
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al cargar los estudiantes.</p>');
        }
    }


    //VER DATOS ESCOLARES DEL ESTUDIANTE
    async function verDetalle(id) {
        try {
            const r = await fetchJson(buildUrl(cfgTabEstudiantes.urlVerDatosEscolaresEstudiante, id));
            if (!r.ok) return;

            const d = r.data?.data;
            if (!d) {
                displayMensajeToast('<p class="error">No se pudo obtener los datos escolares del estudiante.</p>')
                return;
            }

            //AQUI SE MODIFICARIA PARA MOSTRAR LOS DATOS ESCOLARES DEL ESTUDIANTE
            const rolesPersonalHtml = (d.roles_del_personal && d.roles_del_personal.length)
                ? `<ul>
                    ${d.roles_del_personal.map(r => `
                        <li>${escapeHtml(r.nombre_rol)} <span class="informacion-extra-li-modal-detalles">(${escapeHtml(r.clave_rol)})</span></li>
                    `).join('')}
                </ul>`
                : '<p class="info-detalle">Este personal no tiene un rol asignado.</p>';

            displayModalDetalles(`
            <h3 class="titulo-modal-detalles">Datos escolares del estudiante #${d.id}</h3>

            <div class="scroll-contenido-modal-detalles">

                <div class="contenedor-detalles">

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Numero de control:</p>
                        <p class="info-detalle">${escapeHtml(d.numero_control || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Nombre y apellidos:</p>
                        <p class="info-detalle">${escapeHtml(d.nombre_completo  || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Año y mes de ingreso:</p>
                        <p class="info-detalle">${escapeHtml(d.mes_ingreso || '—')} del ${escapeHtml(d.anio_ingreso || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Periodo de ingreso:</p>
                        <p class="info-detalle">${escapeHtml(d.periodo_ingreso_texto || '—')} (no relevante, solo es de importacion)</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Área de especialidad:</p>
                        <p class="info-detalle">${escapeHtml(d.area_especialidad || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Estatus escolar:</p>
                        <p class="info-detalle">${escapeHtml(d.estatus_escolar || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Última importación de datos escolares:</p>
                        <p class="info-detalle">
                            ID: ${escapeHtml(d.ultima_importacion?.id || '—')} | 
                            Archivo: ${escapeHtml(d.ultima_importacion?.archivo_nombre || '—')} |
                            Tipo importación: ${escapeHtml(d.ultima_importacion?.tipo_importacion || '—')}
                        </p>
                    </div>

                </div>

            </div>
        `);

        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al ver los datos escolares del estudiante.</p>');
        }
    }


    //GENERAR CORREO DEL ESTUDIANTE + ENVIAO DE ENLACE PARA ACTIVAR CUENTA (UNO x UNO)
    async function generarCorreoInstitucionalEstudiante(id, button) {

        // modal de confirmacion
        const ok = await modalConfirmarAccion({
            titulo: 'Generar correo',
            mensaje: `
                <p>¿Generar el correo institucional para el estudiante #${id}?</p>
                <ul>
                    <li>Se enviará a cola la generación del correo y el envío del enlace de activación.</li>
                </ul>
            `,
            txtConfirmar: 'Si, generar',
            tipo: 'aceptar-enviar'
        });

        if (!ok) return;

        try {
            setLoadingTabla(button, true);

            const r = await fetchJson(buildUrl(cfgTabEstudiantes.urlGenerarCorreoInstitucional, id), {
                method: 'POST',
            });

            if (!r.ok) return;

            displayMensajeToast(`<p class="exito">${escapeHtml(r.data?.message || 'Solicitud enviada correctamente.')}</p>`);

            // esperar un poco antes de refrescar para dar tiempo a la cola
            setTimeout(() => {
                cargar(state.currentPage);
            }, 3000);
        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al generar el correo institucional.</p>');
        } finally {
            setLoadingTabla(button, false);
        }
    }


    //GENERAR VARIOS CORREOS DE ESTUDIANTES + ENVIOS DE ENLACES PARA ACTIVAR SUS CUENTAS (MUCHOS)
    async function generarCorreosInstitucionalesPendientes() {

        // modal de confirmacion
        const ok = await modalConfirmarAccion({
            titulo: 'Generar correos pendientes',
            mensaje: `
                <p>¿Generar correos institucionales para todos los estudiantes pendientes aptos?</p>
                <ul>
                    <li>Solo se tomarán los que no tengan correo y sí tengan número de control formalizado. </li>
                    <li>Se enviaran enlaces de activación a los correos generados.</li>
                </ul>
            `,
            txtConfirmar: 'Si, generar',
            tipo: 'aceptar-enviar'
        });

        if (!ok) return;

        try {
            setLoadingTabla(btnGenerarCorreosPendientes, true);

            const r = await fetchJson(cfgTabEstudiantes.urlGenerarCorreosInstitucionales, {
                method: 'POST',
            });

            if (!r.ok) return;

            const total = r.data?.total_despachados ?? 0;

            if (total <= 0) {
                displayMensajeToast('<p class="advertencia">No se encontraron estudiantes pendientes aptos para generar correo institucional.</p>');
                return;
            }

            displayModal(`<p class="exito">${escapeHtml(r.data?.message || 'Generacion masiva enviada correctamente.')}</p>`);

            setTimeout(() => {
                cargar(1);
            }, 3000);
        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al generar correos institucionales pendientes.</p>');
        } finally {
            setLoadingTabla(btnGenerarCorreosPendientes, false);
        }
    }


    //REENVIAR ENLACE PARA ACTIVAR LA CUENTA DEL ESTUDIANTE (UNO x UNO)
    async function reenviarActivacionEstudiante(id, button) {

        // modal de confirmacion
        const ok = await modalConfirmarAccion({
            titulo: 'Reenviar enlace de activación',
            mensaje: `
                <p>¿Reenviar el enlace de activación al estudiante #${id}?</p>
                <ul>
                    <li>Se generará un nuevo enlace y las activaciones anteriores pendientes quedarán invalidadas. </li>
                </ul>
            `,
            txtConfirmar: 'Si, reenviar',
            tipo: 'aceptar-enviar'
        });

        if (!ok) return;

        try {
            setLoadingTabla(button, true);

            const r = await fetchJson(buildUrl(cfgTabEstudiantes.urlReenviarActivacion, id), {
                method: 'POST',
            });

            if (!r.ok) return;

            displayMensajeToast(`<p class="exito">${escapeHtml(r.data?.message || 'Reenvío enviado correctamente.')}</p>`);

            setTimeout(() => {
                cargar(state.currentPage);
            }, 3000);
        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al reenviar la activación.</p>');
        } finally {
            setLoadingTabla(button, false);
        }
    }


    //REENVIAR VARIOS ENLACES PARA ACTIVAR LAS CUENTAS DE LOS ESTUDIANTES (MUCHOS)
    async function reenviarActivacionesPendientes() {

        // modal de confirmacion
        const ok = await modalConfirmarAccion({
            titulo: 'Reenviar enlaces de activación',
            mensaje: `
                <p>¿Reenviar activaciones a todos los estudiantes con correo institucional que aún no activan su cuenta?</p>
                <ul>
                    <li>Se generará un nuevo enlace y las activaciones anteriores pendientes quedarán invalidadas. </li>
                </ul>
            `,
            txtConfirmar: 'Si, reenviar',
            tipo: 'aceptar-enviar'
        });

        if (!ok) return;

        try {
            setLoadingTabla(btnReenviarActivaciones, true);

            const r = await fetchJson(cfgTabEstudiantes.urlReenviarActivaciones, {
                method: 'POST',
            });

            if (!r.ok) return;
            
            const total = r.data?.total_despachados ?? 0;

            if (total <= 0) {
                displayMensajeToast('<p class="advertencia">No se encontraron estudiantes aptos para reenviar activación.</p>');
                return;
            }

            displayModal(`<p class="exito">${escapeHtml(r.data?.message || 'Reenvío masivo enviado correctamente.')}</p>`);

            setTimeout(() => {
                cargar(1);
            }, 3000);
        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al reenviar activaciones.</p>');
        } finally {
            setLoadingTabla(btnReenviarActivaciones, false);
        }
    }


    //GESTIONAR ASIGNACION DE ESTUDIANTES A PERSONAL
    async function gestionarAsignacionEstudiante(id) {
        try {
            const r = await fetchJson(buildUrl(cfgTabEstudiantes.urlVerAsignacionesEstudiante, id));
            if (!r.ok) return;

            const d = r.data?.data;
            if (!d) {
                displayMensajeToast('<p class="error">No se pudo obtener la asignación del estudiante.</p>');
                return;
            }

            const opcionesRoles = (d.roles_disponibles || []).map(rol => `
                <option class="option-input-field-select" value="${rol.id}">${escapeHtml(rol.nombre)}</option>
            `).join('');

            const opcionesPersonal = (d.personal_disponible || []).map(p => `
                <option class="option-input-field-select" value="${p.id}">${escapeHtml(p.nombre_completo)}</option>
            `).join('');

            const asignacionesActivasHtml = (d.asignaciones_activas || []).length
                ? d.asignaciones_activas.map(a => `
                    <div class="asignacion-fila">
                        <div class="asignacion-info">
                            <span class="asignacion-badge activa">ACTIVA</span>
                            <span class="asignacion-rol">${escapeHtml(a.nombre_rol || '—')}</span>
                            <span class="asignacion-datos"> | ${escapeHtml(a.nombre_personal || '—')} | ${escapeHtml(a.email_personal || '—')}</span>
                        </div>
                        <div class="asignacion-acciones">
                            <button type="button" class="btn-asignacion btn-desactivar-asignacion-estudiante btn-desactivar" title="Desactivar asignación con el personal" data-id="${a.id}">
                                <i class="fa-solid fa-ban"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>
                `).join('')
                : '<p class="asignacion-vacio">Sin asignaciones activas.</p>';

            const asignacionesInactivasHtml = (d.asignaciones_inactivas || []).length
                ? d.asignaciones_inactivas.map(a => `
                    <div class="asignacion-fila">
                        <div class="asignacion-info">
                            <span class="asignacion-badge inactiva">INACTIVA</span>
                            <span class="asignacion-rol">${escapeHtml(a.nombre_rol || '—')} </span>
                            <span class="asignacion-datos"> | ${escapeHtml(a.nombre_personal || '—')} | ${escapeHtml(a.email_personal || '—')}</span>
                        </div>
                        <div class="asignacion-acciones">
                            <button type="button" class="btn-asignacion btn-reactivar-asignacion-estudiante btn-reactivar" title="Reactivar asignación con el personal" data-id="${a.id}">
                                <i class="fa-solid fa-rotate-left"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                            <button type="button" class="btn-asignacion btn-eliminar-definitivo-asignacion-estudiante btn-eliminar" title="Eliminar asignación con el personal" data-id="${a.id}">
                                <i class="fa-solid fa-trash"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>
                `).join('')
                : '<p class="asignacion-vacio">Sin asignaciones inactivas.</p>';

            displayModalFormularioEditar(`
                <form id="form-asignacion-estudiante" class="form-editar-contenido">

                    <div class="form-title">
                        <span>Asignación académica del estudiante #${d.id}</span>
                    </div>

                    <div class="form-inputs">
                        <div class="scroll-editar">

                            <p class="titulo-caja-detalle">Datos del estudiante</p>
                            <div class="contenedor-detalles-formulario">
                                <div class="caja-detalle-formulario">
                                    <p class="info-detalle"><b>Nombre</b> ${escapeHtml(d.nombre_completo || '—')}</p>
                                    <p class="info-detalle"><b>No. control:</b> ${escapeHtml(d.numero_control || '—')}</p>
                                </div>

                                <div class="caja-detalle-formulario">
                                    <p class="info-detalle"><b>Estatus:</b>  ${escapeHtml(d.estatus_escolar || '—')}</p>
                                    <p class="info-detalle"><b>Área de especialidad:</b> ${escapeHtml(d.area_especialidad || '—')}</p>
                                </div>
                            </div>

                            <p class="titulo-caja-detalle">Selección del personal</p>
                            <div class="input-box-editar">
                                <div class="input-box">
                                    <select class="input-field" name="personal_id" required>
                                        <option class="option-input-field-select" value="">-- Selecciona un personal --</option>
                                        ${opcionesPersonal}
                                    </select>

                                    <select class="input-field" name="role_id" required>
                                        <option class="option-input-field-select" value="">-- Selecciona su rol --</option>
                                        ${opcionesRoles}
                                    </select>
                                </div>
                            </div>

                            <div class="bloque-asignaciones-formulario primero">
                                <div class="encabezado-bloque-asignaciones">
                                    <h4 class="titulo-bloque-asignaciones">Asignaciones activas</h4>
                                </div>

                                <div class="listado-asignaciones">
                                    ${asignacionesActivasHtml}
                                </div>
                            </div>

                            <div class="bloque-asignaciones-formulario">
                                <div class="encabezado-bloque-asignaciones">
                                    <h4 class="titulo-bloque-asignaciones">Asignaciones inactivas</h4>
                                </div>

                                <div class="listado-asignaciones">
                                    ${asignacionesInactivasHtml}
                                </div>
                            </div>

                        </div>

                        <div class="botones-formulario">
                            <button type="submit" class="btn-guardar-enviar">
                                <span>Guardar asignación</span>
                                <span class="spinner"></span>
                                <span class="texto-spinner">Espera</span>
                            </button>
                        </div>
                    </div>

                </form>
            `);

            const form = document.getElementById('form-asignacion-estudiante');
            if (!form) return;

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const button = form.querySelector('.btn-guardar-enviar');
                setLoadingFormulario(button, true);

                try {
                    const rr = await fetchJson(buildUrl(cfgTabEstudiantes.urlGuardarAsignacionEstudiante, id), {
                        method: 'POST',
                        body: {
                            role_id: form.querySelector('[name="role_id"]').value.trim(),
                            personal_id: form.querySelector('[name="personal_id"]').value.trim(),
                        },
                    });

                    if (!rr.ok) return;

                    cerrarModalFormularioEditar();
                    displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Asignación guardada correctamente.')}</p>`);
                    await cargar(state.currentPage);
                } catch (error) {
                    console.error(error);
                    displayMensajeToast('<p class="error">Error al guardar la asignación.</p>');
                } finally {
                    setLoadingFormulario(button, false);
                }
            });

            document.querySelectorAll('.btn-desactivar-asignacion-estudiante').forEach((btn) => {
                btn.addEventListener('click', async () => {

                    // modal de confirmacion
                    const ok = await modalConfirmarAccion({
                        titulo: 'Desactivar esta asignación',
                        mensaje: `
                            <p>¿Seguro que quieres desactivar la asignación del estudiante con el personal?</p>
                            <ul>
                                <li>Si el estudiante no esta asignado con ningun personal, no se podra dar un correcto seguimiento en la gestión de su asistencia, justificantes y alertas.</li>
                                <li>Esta acción podria causar problemas si no se verifica correctamente.</li>
                                <li>Una vez desactivada la asignación, podras volver a reactivarla.</li>
                            </ul>
                        `,
                        txtConfirmar: 'Si, desactivar',
                        tipo: 'eliminar-desactivar'
                    });

                    if (!ok) return;

                    setLoadingTabla(btn, true);

                    try {
                        const rr = await fetchJson(buildUrl(cfgTabEstudiantes.urlDesactivarAsignacionEstudiante, btn.dataset.id), {
                            method: 'PUT',
                        });

                        if (!rr.ok) return;
                        
                        cerrarModalFormularioEditar();
                        displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Asignación desactivada correctamente.')}</p>`);
                        await cargar(state.currentPage);
                    } catch (error) {
                        console.error(error);
                        displayMensajeToast('<p class="error">Error al desactivar la asignación.</p>');
                    } finally {
                        setLoadingTabla(btn, false);
                    }
                });
            });

            document.querySelectorAll('.btn-reactivar-asignacion-estudiante').forEach((btn) => {
                btn.addEventListener('click', async () => {

                    // modal de confirmacion
                    const ok = await modalConfirmarAccion({
                        titulo: 'Reactivar esta asignación',
                        mensaje: `
                            <p>¿Quieres reactivar esta asignacion del estudiante con dicho personal?</p>
                            <ul>
                                <li>El seguimiento de su asistencia, alertas y justifiantes, le estaran nuevamente disponibles.</li>
                            </ul>
                        `,
                        txtConfirmar: 'Si, reactivar',
                        tipo: 'aceptar-enviar'
                    });

                    if (!ok) return;

                    setLoadingTabla(btn, true);

                    try {
                        const rr = await fetchJson(buildUrl(cfgTabEstudiantes.urlReactivarAsignacionEstudiante, btn.dataset.id), {
                            method: 'PUT',
                        });

                        if (!rr.ok) return;

                        cerrarModalFormularioEditar();
                        displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Asignación reactivada correctamente.')}</p>`);
                        await cargar(state.currentPage);
                    } catch (error) {
                        console.error(error);
                        displayMensajeToast('<p class="error">Error al reactivar la asignación.</p>');
                    } finally {
                        setLoadingTabla(btn, false);
                    }
                });
            });

            document.querySelectorAll('.btn-eliminar-definitivo-asignacion-estudiante').forEach((btn) => {
                btn.addEventListener('click', async () => {

                    // modal de confirmacion
                    const ok = await modalConfirmarAccion({
                        titulo: 'Eliminar esta asignación',
                        mensaje: `
                            <p>¿Eliminar definitivamente esta asignación?</p>
                            <ul>
                                <li>El seguimiento de su asistencia, justifiantes y alertas, ya no estaran disponibles para dicho personal.</li>
                                <li>Elimina esta asignación cuando el estudiante ya no requiera un seguimiento en la gestión de su asistencia.</li>
                                <li>Esta acción no se puede deshacer.</li>
                            </ul>
                        `,
                        txtConfirmar: 'Si, eliminar',
                        tipo: 'eliminar-desactivar'
                    });

                    if (!ok) return;

                    setLoadingTabla(btn, true);

                    try {
                        const rr = await fetchJson(buildUrl(cfgTabEstudiantes.urlEliminarAsignacionEstudiante, btn.dataset.id), {
                            method: 'DELETE',
                        });

                        if (!rr.ok) return;

                        cerrarModalFormularioEditar();
                        displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Asignación eliminada definitivamente.')}</p>`);
                        await cargar(state.currentPage);
                    } catch (error) {
                        console.error(error);
                        displayMensajeToast('<p class="error">Error al eliminar definitivamente la asignación.</p>');
                    } finally {
                        setLoadingTabla(btn, false);
                    }
                });
            });

        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al gestionar asignaciones.</p>');
        }
    }


    //FORMULARIO DE EDITAR AL ESTUDIANTE
    async function editarEstudiante(id) {
        try {
            const r = await fetchJson(buildUrl(cfgTabEstudiantes.urlVerEstudiante, id));
            if (!r.ok) return;

            const d = r.data?.data;
            if (!d) {
                displayMensajeToast('<p class="error">No se pudo obtener la información del estudiante.</p>');
                return;
            }

            const activoChecked = d.estado_cuenta_estudiante ? 'checked' : '';
            const currentYear = new Date().getFullYear();

            const especialidadSeleccionadaId = Number(d.especialidad_seleccionada_id ?? 0);
            const estatusSeleccionadoId = Number(d.estatus_seleccionado_id ?? 0);
            const mesSeleccionado = String(d.mes_ingreso ?? '');

            const opcionesAreasEspecialidad =
                Array.isArray(d.areas_especialidad_disponibles) && d.areas_especialidad_disponibles.length
                    ? d.areas_especialidad_disponibles.map((especialidad) => {
                        const especialidadId = Number(especialidad.id);
                        const selected = especialidadId === especialidadSeleccionadaId ? 'selected' : '';
                        return `
                            <option class="option-input-field-select" value="${especialidadId}" ${selected}>
                                ${escapeHtml(especialidad.nombre_especialidad)}
                            </option>
                        `;
                    }).join('')
                    : '<option class="option-input-field-select" value="">No hay áreas de especialidad disponibles.</option>';

            const opcionesEstatusEscolar =
                Array.isArray(d.estatus_escolares_disponibles) && d.estatus_escolares_disponibles.length
                    ? d.estatus_escolares_disponibles.map((estatus) => {
                        const estatusId = Number(estatus.id);
                        const selected = estatusId === estatusSeleccionadoId ? 'selected' : '';
                        return `
                            <option class="option-input-field-select" value="${estatusId}" ${selected}>
                                ${escapeHtml(estatus.nombre_estatus)}
                            </option>
                        `;
                    }).join('')
                    : '<option class="option-input-field-select" value="">No hay estatus escolares disponibles.</option>';

            const meses = [
                { value: '1', label: 'Enero' },
                { value: '2', label: 'Febrero' },
                { value: '3', label: 'Marzo' },
                { value: '4', label: 'Abril' },
                { value: '5', label: 'Mayo' },
                { value: '6', label: 'Junio' },
                { value: '7', label: 'Julio' },
                { value: '8', label: 'Agosto' },
                { value: '9', label: 'Septiembre' },
                { value: '10', label: 'Octubre' },
                { value: '11', label: 'Noviembre' },
                { value: '12', label: 'Diciembre' },
            ];

            const opcionesMesIngreso = meses.map((mes) => {
                const selected = mes.value === mesSeleccionado ? 'selected' : '';
                return `<option class="option-input-field-select" value="${mes.value}" ${selected}>${mes.label}</option>`;
            }).join('');

            displayModalFormularioEditar(`
                <form id="form-editar-estudiante" class="form-editar-contenido">

                    <div class="form-title">
                        <span>Editar estudiante #${d.id}</span>
                    </div>

                    <div class="form-inputs">
                        <div class="scroll-editar">

                            <div class="input-box-editar">
                                <label class="nombre-input">Número de control:</label>
                                <input class="input-field" type="text" name="numero_control" placeholder="Número de control" pattern="^[A-Z][0-9]{2}[A-Z]{2}[0-9]{3}$" maxlength="8" title="Solo 8 caracteres: 1 letra mayúscula, 2 números, 2 letras mayúsculas y 3 números. Ejemplo: M01CE001" value="${escapeHtml(d.numero_control || '')}" autocomplete="off" disabled required>
                                <i class="ri-id-card-line icon"></i>
                            </div>

                            <div class="input-box-editar">
                                <label class="nombre-input">Nombre y apellidos: ${escapeHtml(d.nombre_completo)}</label>
                                <div class="input-box">
                                    <input class="input-field" type="text" name="nombre" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ejem: Fernando" value="${escapeHtml(d.nombre_estudiante || '')}" autocomplete="off" autocapitalize="words" disabled required>

                                    <input class="input-field" type="text" name="apellidos" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ejem: Cuevas Cuevas" value="${escapeHtml(d.apellidos_estudiante || '')}" autocomplete="off" autocapitalize="words" disabled required>
                                </div>
                            </div>

                            <div class="input-box-editar">
                                <label class="nombre-input">Correo electrónico:</label>
                                <input class="input-field" type="email" name="email" value="${escapeHtml(d.email || '')}" autocomplete="off" autocapitalize="none" spellcheck="false" disabled required>
                            </div>

                            <div class="input-box-editar">
                                <label class="nombre-input">Número de teléfono:</label>
                                <input class="input-field" type="tel" name="telefono" pattern="[0-9]{10}" title="Tu número debe ser válido (10 dígitos)" value="${escapeHtml(d.telefono || '')}" autocomplete="off" disabled required>
                            </div>

                            <div class="input-box-editar">
                                <label class="nombre-input">Cambiar contraseña:</label>
                                <input class="input-field" type="password" id="password-input-editar-estudiante" name="password" placeholder="* * * * * *" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$" title="Mínimo 6 caracteres (1 letra mayúscula, 1 número y 1 símbolo)" autocomplete="off" disabled>
                                <i class="ri-lock-2-line icon" id="togglePasswordEditarEstudiante"></i>
                            </div>

                            <div class="input-box-editar">
                                <label class="nombre-input">Repetir contraseña:</label>
                                <input class="input-field" type="password" id="confirm-password-input-editar-estudiante" name="password_confirmation" placeholder="* * * * * *" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$" title="Mínimo 6 caracteres (1 letra mayúscula, 1 número y 1 símbolo)" autocomplete="off" disabled>
                                <i class="ri-lock-2-line icon" id="toggleConfirmPasswordEditarEstudiante"></i>
                            </div>

                            <div class="input-box-editar">
                                <label class="nombre-input">Año y mes de ingreso:</label>
                                <div class="input-box">
                                    <input class="input-field" type="number" name="anio_ingreso" placeholder="Año de ingreso" min="2000" max="${currentYear}" value="${escapeHtml(d.anio_ingreso ?? '')}" disabled required>
                                    <i class="ri-calendar-line icon"></i>

                                    <select class="input-field" name="mes_ingreso" disabled required>
                                        <option class="option-input-field-select" value="">-- Su mes de ingreso --</option>
                                        ${opcionesMesIngreso}
                                    </select>
                                </div>
                            </div>

                            <div class="input-box-editar">
                                <label class="nombre-input">Área de especialidad:</label>
                                <select class="input-field" name="area_id" disabled required>
                                    <option class="option-input-field-select" value="">-- Selecciona su especialidad --</option>
                                    ${opcionesAreasEspecialidad}
                                </select>
                            </div>

                            <div class="input-box-editar">
                                <label class="nombre-input">Estatus escolar:</label>
                                <select class="input-field" name="estatus_id" disabled required>
                                    <option class="option-input-field-select" value="">-- Selecciona su estatus --</option>
                                    ${opcionesEstatusEscolar}
                                </select>
                            </div>

                            <div class="input-box">
                                <label class="input-field switch-estado">
                                    <input type="checkbox" name="activo" value="1" ${activoChecked} disabled>
                                    <span class="slider-switch-estado"></span>
                                    <span class="texto-switch-estado">Activar cuenta</span>
                                </label>
                            </div>

                        </div>

                        <div class="botones-formulario">
                            <button type="button" class="btn-editar-form">Editar</button>

                            <button type="button" class="btn-cancelar-borrar" style="display: none;">
                                <span>Cancelar</span>
                            </button>

                            <button type="submit" class="btn-guardar-enviar" style="display: none;">
                                <span>Guardar</span>
                                <span class="spinner"></span>
                                <span class="texto-spinner">Espera</span>
                            </button>
                        </div>

                    </div>

                </form>
            `);

            const form = document.getElementById('form-editar-estudiante');
            if (!form) return;

            const btnEditarEstudiante = form.querySelector('.btn-editar-form');
            const btnCancelarEdicionEstudiante = form.querySelector('.btn-cancelar-borrar');
            const btnGuardarEdicionEstudiante = form.querySelector('.btn-guardar-enviar');

            const camposEditables = form.querySelectorAll(`
                input[name="numero_control"],
                input[name="nombre"],
                input[name="apellidos"],
                input[name="email"],
                input[name="telefono"],
                input[name="password"],
                input[name="password_confirmation"],
                input[name="anio_ingreso"],
                select[name="mes_ingreso"],
                select[name="area_id"],
                select[name="estatus_id"],
                input[name="activo"]
            `);

            function setModoEdicion(activo) {
                camposEditables.forEach(el => {
                    el.disabled = !activo;
                });

                btnEditarEstudiante.style.display = activo ? 'none' : 'inline-flex';
                btnCancelarEdicionEstudiante.style.display = activo ? 'inline-flex' : 'none';
                btnGuardarEdicionEstudiante.style.display = activo ? 'inline-flex' : 'none';
            }

            btnEditarEstudiante?.addEventListener('click', () => {
                setModoEdicion(true);
                form.querySelector('[name="nombre"]')?.focus();
            });

            btnCancelarEdicionEstudiante?.addEventListener('click', () => {
                form.reset();

                const passwordInput = form.querySelector('#password-input-editar-estudiante');
                const confirmPasswordInput = form.querySelector('#confirm-password-input-editar-estudiante');
                const togglePassword = form.querySelector('#togglePasswordEditarEstudiante');
                const toggleConfirmPassword = form.querySelector('#toggleConfirmPasswordEditarEstudiante');

                if (passwordInput) {
                    passwordInput.type = 'password';
                    passwordInput.classList.remove('error');
                }

                if (confirmPasswordInput) {
                    confirmPasswordInput.type = 'password';
                    confirmPasswordInput.classList.remove('error');
                }

                if (togglePassword) {
                    togglePassword.classList.remove('active');
                }

                if (toggleConfirmPassword) {
                    toggleConfirmPassword.classList.remove('active');
                }

                setModoEdicion(false);
            });

            function bindToggle(btnSelector, inputSelector) {
                const btn = form.querySelector(btnSelector);
                const input = form.querySelector(inputSelector);

                if (!btn || !input) return;

                btn.addEventListener('click', () => {
                    if (input.disabled) return;
                    input.type = input.type === 'password' ? 'text' : 'password';
                    btn.classList.toggle('active');
                });
            }

            bindToggle('#togglePasswordEditarEstudiante', '#password-input-editar-estudiante');
            bindToggle('#toggleConfirmPasswordEditarEstudiante', '#confirm-password-input-editar-estudiante');

            const password = form.querySelector('#password-input-editar-estudiante');
            const confirmPassword = form.querySelector('#confirm-password-input-editar-estudiante');

            if (password && confirmPassword) {
                function validatePasswords() {
                    const ambosVacios = !password.value && !confirmPassword.value;
                    const ok = ambosVacios || password.value === confirmPassword.value;

                    password.classList.toggle('error', !ok);
                    confirmPassword.classList.toggle('error', !ok);
                }

                password.addEventListener('input', validatePasswords);
                confirmPassword.addEventListener('input', validatePasswords);
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const button = form.querySelector('.btn-guardar-enviar');
                setLoadingFormulario(button, true);

                try {
                    const payload = {
                        numero_control: form.querySelector('[name="numero_control"]').value.trim(),
                        nombre: form.querySelector('[name="nombre"]').value.trim(),
                        apellidos: form.querySelector('[name="apellidos"]').value.trim(),
                        email: form.querySelector('[name="email"]').value.trim(),
                        telefono: form.querySelector('[name="telefono"]').value.trim(),
                        password: form.querySelector('[name="password"]').value.trim(),
                        password_confirmation: form.querySelector('[name="password_confirmation"]').value.trim(),
                        anio_ingreso: form.querySelector('[name="anio_ingreso"]').value.trim(),
                        mes_ingreso: form.querySelector('[name="mes_ingreso"]').value.trim(),
                        area_id: form.querySelector('[name="area_id"]').value.trim(),
                        estatus_id: form.querySelector('[name="estatus_id"]').value.trim(),
                        activo: form.querySelector('[name="activo"]').checked ? 1 : 0,
                    };

                    const rr = await fetchJson(buildUrl(cfgTabEstudiantes.urlEditarEstudiante, id), {
                        method: 'PUT',
                        body: payload,
                    });

                    if (!rr.ok) return;

                    await cargar(state.currentPage);
                    cerrarModalFormularioEditar();
                    displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Estudiante actualizado correctamente.')}</p>`);
                } catch (error) {
                    console.error(error);
                    displayMensajeToast('<p class="error">Error de conexión al actualizar el estudiante. <span class="ext">Revisa tu servidor o recarga la página.</span></p>');
                } finally {
                    setLoadingFormulario(button, false);
                }
            });

            setModoEdicion(false);

        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al editar el estudiante.</p>');
        }
    }


    //ELIMIANR EL ESTUDIANTE
    async function eliminarEstudiante(id, button) {

        // modal de confirmacion
        const ok = await modalConfirmarAccion({
            titulo: 'Eliminar estudiante',
            mensaje: `
                <p>¿Seguro que deseas eliminar el estudiante #${id}?</p>
                <ul>
                    <li>Si el estudiante tiene registros de eventos o acciones en la plataforma, no podra eliminarse, ya que se utilizan para auditoría.</li>
                    <li>Se recomienda el desactivar la cuenta del estudiante.</li>
                    <li>Si en verdad deseas eliminar al estudiante, primero elimina sus vinculos o asignaciones que se le han echo.</li>
                    <li>Esta acción no se puede deshacer. Por ello, verifica por ultima vez al estudiante.</li>
                </ul>
            `,
            txtConfirmar: 'Sí, eliminar',
            tipo: 'eliminar-desactivar'
        });

        if (!ok) return;

        try {
            setLoadingTabla(button, true);

            const r = await fetchJson(buildUrl(cfgTabEstudiantes.urlEliminarEstudiante, id), {
                method: 'DELETE',
            });

            if (!r.ok) return;

            await cargar(state.currentPage);
            if (typeof displayModal === 'function') {
                displayMensajeToast(`<p class="exito">${escapeHtml(r.data?.message || 'Estudiante eliminado correctamente.')}</p>`);
            }
        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al eliminar el estudiante.</p>');
        } finally {
            setLoadingTabla(button, false);
        }
    }


    //==================LISTENER PARA LA TABLA==================
    tabla.addEventListener('click', (ev) => {

        const btnDetalle = ev.target.closest('.ver-datos-escolares-estudiante');
        if (btnDetalle) {
            verDetalle(btnDetalle.dataset.id);
            return;
        }

        const btnGenerarCorreo = ev.target.closest('.btn-generar-correo-institucional-estudiante');
        if (btnGenerarCorreo) {
            generarCorreoInstitucionalEstudiante(btnGenerarCorreo.dataset.id, btnGenerarCorreo);
            return;
        }

        const btnReenviarActivacion = ev.target.closest('.btn-reenviar-activacion-estudiante');
        if (btnReenviarActivacion) {
            reenviarActivacionEstudiante(btnReenviarActivacion.dataset.id, btnReenviarActivacion);
            return;
        }
    

        const btnAsignacion = ev.target.closest('.btn-gestionar-asignacion-estudiante');
        if (btnAsignacion) {
            gestionarAsignacionEstudiante(btnAsignacion.dataset.id);
            return;
        }

        const btnEditar = ev.target.closest('.btn-editar-estudiante');
        if (btnEditar) {
            editarEstudiante(btnEditar.dataset.id);
            return;
        }

        const btnEliminar = ev.target.closest('.btn-eliminar-estudiante');
        if (btnEliminar) {
            eliminarEstudiante(btnEliminar.dataset.id, btnEliminar);
            return;
        }
    });

    //PARA GENERAR VARIOS CORREOS DE ESTUDIANTES + ENVIOS DE ENLACES PARA ACTIVAR SUS CUENTAS (MUCHOS)
    btnGenerarCorreosPendientes?.addEventListener('click', generarCorreosInstitucionalesPendientes);

    //PARA REENVIAR VARIOS ENLACES PARA ACTIVAR LAS CUENTAS DE LOS ESTUDIANTES (MUCHOS)
    btnReenviarActivaciones?.addEventListener('click', reenviarActivacionesPendientes); 

    btnAnterior?.addEventListener('click', async () => {
        if (state.currentPage <= 1) return;
        await cargar(state.currentPage - 1);
    });

    btnSiguiente?.addEventListener('click', async () => {
        if (state.currentPage >= state.lastPage) return;
        await cargar(state.currentPage + 1);
    });

    btnRefrescar.addEventListener('click', async () => {
        setLoadingTabla(btnRefrescar, true);
        await cargar(1);
        setLoadingTabla(btnRefrescar, false);
    });

    btnBuscar.addEventListener('click', async () => {
        setLoadingTabla(btnBuscar, true);
        await cargar(1);
        setLoadingTabla(btnBuscar, false);
    });

    inputBuscar.addEventListener('keydown', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            setLoadingTabla(btnBuscar, true);
            await cargar(1);
            setLoadingTabla(btnBuscar, false);
        }
    });


    btnExportarExcel?.addEventListener('click', async () => {
        try {
            setLoadingTabla(btnExportarExcel, true);

            const url = new URL(cfgTabEstudiantes.urlExportarEstudiantesExcel, window.location.origin);

            const buscar = inputBuscar.value.trim();
            const areaId = filtroArea?.value ?? '';
            const estatusId = filtroEstatus?.value ?? '';
            const activo = filtroActivo?.value ?? '';

            if (buscar) url.searchParams.set('buscar', buscar);
            if (areaId) url.searchParams.set('area_id', areaId);
            if (estatusId) url.searchParams.set('estatus_id', estatusId);
            if (activo !== '') url.searchParams.set('activo', activo);

            window.location.href = url.toString();

        } catch (error) {
            console.error(error);
            displayMensajeToast('<p class="error">No se pudo iniciar la exportación del Excel de estudiantes.</p>');
        } finally {
            setTimeout(() => {
                setLoadingTabla(btnExportarExcel, false);
            }, 1200);
        }
    });


    filtroArea?.addEventListener('change', () => cargar(1));
    filtroEstatus?.addEventListener('change', () => cargar(1));
    filtroActivo?.addEventListener('change', () => cargar(1));
    filtroPerPage?.addEventListener('change', () => cargar(1));

    btnLimpiarFiltros?.addEventListener('click', async () => {
        inputBuscar.value = '';
        if (filtroArea) filtroArea.value = '';
        if (filtroEstatus) filtroEstatus.value = '';
        if (filtroActivo) filtroActivo.value = '';
        if (filtroPerPage) filtroPerPage.value = '20';
        await cargar(1);
    });

    //==========================================================

    cargar(1);
}
//==========================================================

