document.addEventListener('DOMContentLoaded', () => {
    initTablaAreasEspecialidad();
    initTablaEstatusEscolares();
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



//======TABLA DE AREAS DE ESPECIALIDAD(LISTA, VER, EDITAR Y ELIMINAR)======
function initTablaAreasEspecialidad() {
    const tabla = document.getElementById('tabla-listado-areas-especialidad');
    const btnRefrescar = document.getElementById('btn-refrescar-tabla-areas-especialidad');
    const inputBuscar = document.getElementById('input-buscar-areas-especialidad');
    const btnBuscar = document.getElementById('btn-buscar-areas-especialidad');

    if (!tabla || !btnRefrescar || !inputBuscar || !btnBuscar) return;

    const tbody = tabla.querySelector('tbody');

    const cfgTabAreaEspecialidad = {
        urlListadoAreasEspecialidad: tabla.dataset.urlTablaListadoAreasEspecialidad,
        urlVerAreaEspecialidad: tabla.dataset.urlTablaVerAreaEspecialidad,
        urlEditarAreaEspecialidad: tabla.dataset.urlTablaEditarAreaEspecialidad,
        urlEliminarAreaEspecialidad: tabla.dataset.urlTablaEliminarAreaEspecialidad,
    };

    let aborter = null;


    //PINTADO DE LA TABLA DE AREAS DE ESPECIALIDAD
    function renderRows(items) {
        if (!items || items.length === 0) {
            const texto = inputBuscar.value.trim()
                ? 'No se encontraron áreas de especialidad con esa búsqueda.'
                : 'Sin áreas de especialidad registradas.';

            tbody.innerHTML = `<tr><td colspan="7" class="td-estado-tabla">${texto}</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((it) => {
            const estadoTexto = it.estado_area ?
                '<span class="estado-activado"><i class="fa-solid fa-square-check"></i> Activado</span>' :
                '<span class="estado-desactivado"><i class="fa-solid fa-square-xmark"></i> Desactivado</span>';

            return `  
        <tr>
            <td class="td-id">${it.id}</td>
            <td class="td-clave">${escapeHtml(it.clave_area || '—')}</td>
            <td class="td-nombre">${escapeHtml(it.nombre_area || '—')}</td>
            <td class="td-estado">${estadoTexto}</td>
            <td class="td-fecha-mini-tabla">
                <table class="mini-tabla-items">
                    <tr>
                        <td class='td-nombre-mini-tabla primero'>Registro:</td>
                        <td class='td-contenido-mini-tabla'>${fmtFecha(it.creado_en)}</td>
                    </tr>
                    <tr>
                        <td class='td-nombre-mini-tabla ultimo'>Edición:</td>
                        <td class='td-contenido-mini-tabla'>${fmtFecha(it.editado_en)}</td>
                    </tr>
                </table>    
            </td>
            <td>
                <div class="botones-tabla">
                <button type="button" class="btn-editar-item btn-editar-area-especialidad" data-id="${it.id}">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                </div>
            </td>
            <td>
                <div class="botones-tabla">
                <button type="button" class="btn-eliminar-item btn-eliminar-area-especialidad" data-id="${it.id}">
                    <i class="fa-solid fa-trash"></i>
                    <span class="spinner-tabla"></span>
                </button>
                </div>
            </td>
            </tr>
        `;
        }).join('');
    }

    //CARGA DEL PINTADO DE LA TABLA Y LA FUNCION DE BUSCAR AREAS DE ESPECIALIDAD
    async function cargar() {
        tbody.innerHTML = `<tr><td colspan="7" class="td-estado-tabla">Cargando contenido…</td></tr>`;

        if (aborter) aborter.abort();
        aborter = new AbortController();

        try {
            const url = new URL(cfgTabAreaEspecialidad.urlListadoAreasEspecialidad, window.location.origin);
            const buscar = inputBuscar.value.trim();

            if (buscar) {
                url.searchParams.set('buscar', buscar);
            }

            const r = await fetchJson(url.toString(), { signal: aborter.signal });
            if (!r.ok) return;

            renderRows(r.data?.data || []);
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al cargar las áreas de especialidad.</p>');
        }
    }

    //FORMULARIO DE EDITAR AREA DE ESPECIALIDAD
    async function editarAreaEspecialidad(id) {
        try {
            const r = await fetchJson(buildUrl(cfgTabAreaEspecialidad.urlVerAreaEspecialidad, id));
            if (!r.ok) return;

            const d = r.data?.data;
            if (!d) {
                displayMensajeToast('<p class="error">No se pudo obtener la información de la áreas de especialidad..</p>');
                return;
            }

            const activoChecked = d.estado_area ? 'checked' : '';

            displayModalFormularioEditar(`
                <form id="form-editar-area-especialidad" class="form-editar-contenido">
                    
                    <div class="form-title">
                        <span>Editar área de especialidad #${d.id}</span>
                    </div>
                    
                    <div class="form-inputs">
                        <div class="scroll-editar">

                            <div class="input-box-editar">
                                <label class="nombre-input">Clave:</label>
                                <input class="input-field" type="text" name="clave" pattern="^[a-z]+(?:_[a-z]+)*$" title="Solo letras minúsculas y guion bajo '_' como separador. Ejemplo: ingenieria_de_software" value="${escapeHtml(d.clave_area || '')}" autocomplete="off" autocapitalize="none" disabled required>
                            </div>

                            <div class="input-box-editar">
                                <label class="nombre-input">Nombre:</label>
                                <input class="input-field" type="text" name="nombre" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: INGENIERIA DE SOFTWARE" value="${escapeHtml(d.nombre_area || '')}" autocomplete="off" autocapitalize="characters" disabled  required>
                            </div>

                            <div class="input-box">
                                <label class="input-field switch-estado">
                                    <input type="checkbox" name="activo" value="1" ${activoChecked} disabled>
                                    <span class="slider-switch-estado"></span>
                                    <span class="texto-switch-estado">Activar área</span>
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

            const form = document.getElementById('form-editar-area-especialidad');
            if (!form) return;


            //====================HABILITAR EDICION====================
            //En los <input>, <textarea> y el <input type="checkbox"> tienen "disabled"
            const btnEditarAreaEspecialidad = form.querySelector('.btn-editar-form');
            const btnCancelarEdicionAreaEspecialidad = form.querySelector('.btn-cancelar-borrar');
            const btnGuardarEdicionAreaEspecialidad = form.querySelector('.btn-guardar-enviar');

            btnEditarAreaEspecialidad?.addEventListener('click', () => {
                form.querySelectorAll(`
                    input[name="clave"],
                    input[name="nombre"], 
                    input[name="activo"]
            `).forEach(el => el.disabled = false);

                btnEditarAreaEspecialidad.style.display = 'none';
                btnCancelarEdicionAreaEspecialidad.style.display = 'inline-flex';
                btnGuardarEdicionAreaEspecialidad.style.display = 'inline-flex';

                form.querySelector('[name="nombre"]')?.focus();//Enfocarse a ese input
            });

            //=====================CANCELAR EDICION=====================
            btnCancelarEdicionAreaEspecialidad?.addEventListener('click', () => {
                form.reset();

                form.querySelectorAll(`
                    input[name="clave"],
                    input[name="nombre"], 
                    input[name="activo"]
                `).forEach(el => el.disabled = true);

                btnEditarAreaEspecialidad.style.display = 'inline-flex';
                btnCancelarEdicionAreaEspecialidad.style.display = 'none';
                btnGuardarEdicionAreaEspecialidad.style.display = 'none';
            });
            //==========================================================


            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const button = form.querySelector('.btn-guardar-enviar');
                setLoadingFormulario(button, true);

                try {

                    const payload = {
                        clave: form.querySelector('[name="clave"]').value.trim(),
                        nombre: form.querySelector('[name="nombre"]').value.trim(),
                        activo: form.querySelector('[name="activo"]').checked ? 1 : 0,
                    };

                    const rr = await fetchJson(buildUrl(cfgTabAreaEspecialidad.urlEditarAreaEspecialidad, id), {
                        method: 'PUT',
                        body: payload,
                    });

                    if (!rr.ok) return;

                    await cargar();
                    if (typeof displayMensajeToast === 'function') {
                        cerrarModalFormularioEditar();
                        displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Área de especialidad actualizadoacorrectamente.')}</p>`);
                    }

                } catch (error) {
                    console.error(error);
                    displayMensajeToast(`<p class="error">Error de conexión al actualizar la área de especialidad. <span class="ext">Revisa tu servidor o recarga la página.</span></p>`);
                } finally {
                    setLoadingFormulario(button, false);
                }
            });
        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al editar la área de especialidad.</p>');
        }
    }


    //ELIMIANR UNA FUENTE DE DATOS
    async function eliminarAreaEspecialidad(id, button) {

        // modal de confirmacion
        const ok = await modalConfirmarAccion({
            titulo: 'Eliminar área de especialidad',
            mensaje: `
                <p>¿Seguro que quieres eliminar esta área de especialidad #${id}?</p>
                <ul>
                    <li>Esta acción eliminará el registro de la área de especialidad.</li>
                    <li>Si hay estudiantes que ocupan esta área de especialidad, podría causar problemas en la gestión, seguimiento y análisis de asistencia escolar.</li>
                    <li>Se recomienda el desactivar la área de especialidad.</li>
                    <li>Esta acción no se puede deshacer. Por ello, verificala por ultima vez.</li>
                </ul>
            `,
            txtConfirmar: 'Sí, eliminar',
            tipo: 'eliminar-desactivar'
        });

        if (!ok) return;

        try {
            setLoadingTabla(button, true);

            const r = await fetchJson(buildUrl(cfgTabAreaEspecialidad.urlEliminarAreaEspecialidad, id), {
                method: 'DELETE',
            });

            if (!r.ok) return;

            await cargar();
            if (typeof displayMensajeToast === 'function') {
                displayMensajeToast(`<p class="exito">${escapeHtml(r.data?.message || 'área de especialidad eliminada correctamente.')}</p>`);
            }
        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al eliminar la Área de especialidad.</p>');
        } finally {
            setLoadingTabla(button, false);
        }
    }


    //==================LISTENER PARA LA TABLA==================
    tabla.addEventListener('click', (ev) => {
        const btnDetalle = ev.target.closest('.btn-ver-detalles-item');
        if (btnDetalle) {
            verDetalle(btnDetalle.dataset.id);
            return;
        }

        const btnEditar = ev.target.closest('.btn-editar-area-especialidad');
        if (btnEditar) {
            editarAreaEspecialidad(btnEditar.dataset.id);
            return;
        }

        const btnEliminar = ev.target.closest('.btn-eliminar-area-especialidad');
        if (btnEliminar) {
            eliminarAreaEspecialidad(btnEliminar.dataset.id, btnEliminar);
        }
    });

    btnRefrescar.addEventListener('click', async () => {
        setLoadingTabla(btnRefrescar, true);
        await cargar();
        setLoadingTabla(btnRefrescar, false);
    });

    btnBuscar.addEventListener('click', async () => {
        setLoadingTabla(btnBuscar, true);
        await cargar();
        setLoadingTabla(btnBuscar, false);
    });

    inputBuscar.addEventListener('keydown', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            setLoadingTabla(btnBuscar, true);
            await cargar();
            setLoadingTabla(btnBuscar, false);
        }
    });
    //==========================================================

    cargar();
}
//==========================================================



//=====TABLA DE ESTATUS ESCOLARES (LISTA, VER, EDITAR Y ELIMINAR)=====
function initTablaEstatusEscolares() {
    const tabla = document.getElementById('tabla-listado-estatus-escolares');
    const btnRefrescar = document.getElementById('btn-refrescar-tabla-estatus-escolares');
    const inputBuscar = document.getElementById('input-buscar-estatus-escolares');
    const btnBuscar = document.getElementById('btn-buscar-estatus-escolares');

    if (!tabla || !btnRefrescar || !inputBuscar || !btnBuscar) return;

    const tbody = tabla.querySelector('tbody');

    const cfgTabEstatusEscolar = {
        urlListadoEstatusEscolares: tabla.dataset.urlTablaListadoEstatusEscolares,
        urlVerEstatusEscolar: tabla.dataset.urlTablaVerEstatusEscolar,
        urlEditarEstatusEscolar: tabla.dataset.urlTablaEditarEstatusEscolar,
        urlEliminarEstatusEscolar: tabla.dataset.urlTablaEliminarEstatusEscolar,
    };

    let aborter = null;


    //PINTADO DE LA TABLA DE ESTATUS ESCOLARES
    function renderRows(items) {
        if (!items || items.length === 0) {
            const texto = inputBuscar.value.trim()
                ? 'No se encontraron estatus escolares con esa búsqueda.'
                : 'Sin estatus escolares registrados.';

            tbody.innerHTML = `<tr><td colspan="8" class="td-estado-tabla">${texto}</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((it) => {
            const estadoTexto = it.estado_estatus ?
                '<span class="estado-activado"><i class="fa-solid fa-square-check"></i> Activado</span>' :
                '<span class="estado-desactivado"><i class="fa-solid fa-square-xmark"></i> Desactivado</span>';
            return `
            <tr>
                <td class="td-id">${it.id}</td>
                <td class="td-clave">${escapeHtml(it.clave_estatus || '—')}</td>
                <td class="td-nombre">${escapeHtml(it.nombre_estatus || '—')}</td>
                <td class="td-descripcion">${escapeHtml(it.descripcion || '—')}</td>
                <td class="td-estado">${estadoTexto}</td>
                <td class="td-fecha-mini-tabla">
                    <table class="mini-tabla-items">
                        <tr>
                            <td class='td-nombre-mini-tabla primero'>Registro:</td>
                            <td class='td-contenido-mini-tabla'>${fmtFecha(it.creado_en)}</td>
                        </tr>
                        <tr>
                            <td class='td-nombre-mini-tabla ultimo'>Edición:</td>
                            <td class='td-contenido-mini-tabla'>${fmtFecha(it.editado_en)}</td>
                        </tr>
                    </table>    
                </td>
                <td>
                    <div class="botones-tabla">
                    <button type="button" class="btn-editar-item btn-editar-estatus-escolar" data-id="${it.id}">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    </div>
                </td>
                <td>
                    <div class="botones-tabla">
                    <button type="button" class="btn-eliminar-item btn-eliminar-estatus-escolar" data-id="${it.id}">
                        <i class="fa-solid fa-trash"></i>
                        <span class="spinner-tabla"></span>
                    </button>
                    </div>
                </td>
            </tr>
        `;
        }).join('');
    }


    //CARGA DEL PINTADO DE LA TABLA Y LA FUNCION DE BUSCAR PARSER
    async function cargar() {
        tbody.innerHTML = `<tr><td colspan="8" class="td-estado-tabla">Cargando contenido…</td></tr>`;

        if (aborter) aborter.abort();
        aborter = new AbortController();

        try {
            const url = new URL(cfgTabEstatusEscolar.urlListadoEstatusEscolares, window.location.origin);
            const buscar = inputBuscar.value.trim();

            if (buscar) {
                url.searchParams.set('buscar', buscar);
            }

            const r = await fetchJson(url.toString(), { signal: aborter.signal });
            if (!r.ok) return;

            renderRows(r.data?.data || []);
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al cargar los estatus escolares.</p>');
        }
    }


    //FORMULARIO DE EDITAR ESTATUS
    async function editarEstatusEscolar(id) {
        try {
            const r = await fetchJson(buildUrl(cfgTabEstatusEscolar.urlVerEstatusEscolar, id));
            if (!r.ok) return;

            const d = r.data?.data;
            if (!d) {
                displayMensajeToast('<p class="error">No se pudo obtener la información del estatus.</p>');
                return;
            }


            const activoChecked = d.estado_estatus ? 'checked' : '';

            displayModalFormularioEditar(`
                <form id="form-editar-estatus-escolar" class="form-editar-contenido">

                    <div class="form-title">
                        <span>Editar estatus escolar #${d.id}</span>
                    </div>

                    <div class="form-inputs">
                        <div class="scroll-editar">

                        <div class="input-box-editar">
                            <label class="nombre-input">Clave:</label>
                            <input class="input-field" type="text" name="clave" pattern="^[a-z]+(?:_[a-z]+)*$" title="Solo letras minúsculas y guion bajo '_' como separador. Ejemplo: no_inscrito" value="${escapeHtml(d.clave_estatus || '')}" autocomplete="off" autocapitalize="none" disabled required>
                        </div>

                        <div class="input-box-editar">
                            <label class="nombre-input">Nombre:</label>
                            <input class="input-field" type="text" name="nombre" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: NO INSCRITO	" value="${escapeHtml(d.nombre_estatus || '')}" autocomplete="off" autocapitalize="characters" disabled required>
                        </div>

                        <div class="input-box-editar">
                            <label class="nombre-input">Descripción:</label>
                            <textarea class="input-field" maxlength="500" title="Solo letras, espacios, y signos de puntuacion" name="descripcion" autocomplete="off" disabled>${escapeHtml(d.descripcion || '')}</textarea>
                        </div>

                        <div class="input-box">
                            <label class="input-field switch-estado">
                                <input type="checkbox" name="activo" value="1" ${activoChecked} disabled>
                                <span class="slider-switch-estado"></span>
                                <span class="texto-switch-estado">Activar estatus</span>
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

            const form = document.getElementById('form-editar-estatus-escolar');
            if (!form) return;


            //====================HABILIDAR EDICION====================
            //En los <input>, <textarea> y el <input type="checkbox"> tienen "disabled"
            const btnEditarEstatusEscolar = form.querySelector('.btn-editar-form');
            const btnCancelarEdicionEstatusEscolar = form.querySelector('.btn-cancelar-borrar');
            const btnGuardarEdicionEstatusEscolar = form.querySelector('.btn-guardar-enviar');

            btnEditarEstatusEscolar?.addEventListener('click', () => {
                form.querySelectorAll(`
                    input[name="clave"], 
                    input[name="nombre"], 
                    textarea[name="descripcion"],
                    input[name="activo"]
                `).forEach(el => el.disabled = false);

                btnEditarEstatusEscolar.style.display = 'none';
                btnCancelarEdicionEstatusEscolar.style.display = 'inline-flex'
                btnGuardarEdicionEstatusEscolar.style.display = 'inline-flex';

                form.querySelector('[name="clave"]')?.focus();//Enfocarse a ese input
            });

            //=====================CANCELAR EDICION=====================
            btnCancelarEdicionEstatusEscolar?.addEventListener('click', () => {
                form.reset();

                form.querySelectorAll(`
                    input[name="clave"], 
                    input[name="nombre"], 
                    textarea[name="descripcion"],
                    input[name="activo"]
                `).forEach(el => el.disabled = true);

                btnEditarEstatusEscolar.style.display = 'inline-flex';
                btnCancelarEdicionEstatusEscolar.style.display = 'none';
                btnGuardarEdicionEstatusEscolar.style.display = 'none';
            });
            //==========================================================


            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const button = form.querySelector('.btn-guardar-enviar');
                setLoadingFormulario(button, true);

                try {
                    const payload = {
                        clave: form.querySelector('[name="clave"]').value.trim(),
                        nombre: form.querySelector('[name="nombre"]').value.trim(),
                        descripcion: form.querySelector('[name="descripcion"]').value.trim(),
                        activo: form.querySelector('[name="activo"]').checked ? 1 : 0,
                    };

                    const rr = await fetchJson(buildUrl(cfgTabEstatusEscolar.urlEditarEstatusEscolar, id), {
                        method: 'PUT',
                        body: payload,
                    });

                    if (!rr.ok) return;

                    await cargar();
                    if (typeof displayMensajeToast === 'function') {
                        cerrarModalFormularioEditar();
                        displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Estatus escolar actualizado correctamente.')}</p>`);
                    }

                } catch (error) {
                    console.error(error);
                    displayMensajeToast(`<p class="error">Error de conexión al actualizar el estatus.</p><p class="ext">Revisa tu servidor o recarga la página.</p>`);
                } finally {
                    setLoadingFormulario(button, false);
                }
            });
        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al editar el estatus escolar.</p>');
        }
    }


    //ELIMINAR ESTATUS
    async function eliminarEstatusEscolar(id, button) {

        // modal de confirmacion
        const ok = await modalConfirmarAccion({
            titulo: 'Eliminar estatus escolar',
            mensaje: `
                <p>¿Seguro que quieres eliminar este estatus escolar #${id}?</p>
                <ul>
                    <li>Esta acción eliminará el registro del estatus escolar.</li>
                    <li>Si hay estudiantes que ocupan este estatus escolar, podría causar problemas en la gestión, seguimiento y análisis de asistencia escolar.</li>
                    <li>Se recomienda el desactivar el estatus escolar.</li>
                    <li>Esta acción no se puede deshacer. Por ello, verificala por ultima vez.</li>
                </ul>
            `,
            txtConfirmar: 'Sí, eliminar',
            tipo: 'eliminar-desactivar'
        });

        try {
            setLoadingTabla(button, true);

            const r = await fetchJson(buildUrl(cfgTabEstatusEscolar.urlEliminarEstatusEscolar, id), {
                method: 'DELETE',
            });

            if (!r.ok) return;

            await cargar();
            if (typeof displayMensajeToast === 'function') {
                displayMensajeToast(`<p class="exito">${escapeHtml(r.data?.message || 'Estatus escolar eliminado correctamente.')}</p>`);
            }

        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al eliminar el estatus escolar.</p>');
        } finally {
            setLoadingTabla(button, false);
        }
    }


    //==================LISTENER PARA LA TABLA==================
    tabla.addEventListener('click', (ev) => {
        const btnEditar = ev.target.closest('.btn-editar-estatus-escolar');
        if (btnEditar) {
            editarEstatusEscolar(btnEditar.dataset.id);
            return;
        }

        const btnEliminar = ev.target.closest('.btn-eliminar-estatus-escolar');
        if (btnEliminar) {
            eliminarEstatusEscolar(btnEliminar.dataset.id, btnEliminar);
        }
    });

    btnRefrescar.addEventListener('click', async () => {
        setLoadingTabla(btnRefrescar, true);
        await cargar();
        setLoadingTabla(btnRefrescar, false);
    });

    btnBuscar.addEventListener('click', async () => {
        setLoadingTabla(btnBuscar, true);
        await cargar();
        setLoadingTabla(btnBuscar, false);
    });

    inputBuscar.addEventListener('keydown', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            setLoadingTabla(btnBuscar, true);
            await cargar();
            setLoadingTabla(btnBuscar, false);
        }
    });
    //==========================================================

    cargar();
}
//==========================================================