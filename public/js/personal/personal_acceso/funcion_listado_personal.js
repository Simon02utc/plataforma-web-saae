document.addEventListener('DOMContentLoaded', () => {
    initTablaPersonal();
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



//======TABLA DE PERSONAL (LISTA, VER, EDITAR Y ELIMINAR)======
function initTablaPersonal() {
    const tabla = document.getElementById('tabla-listado-personal');
    const btnRefrescar = document.getElementById('btn-refrescar-tabla-personal');
    const inputBuscar = document.getElementById('input-buscar-personal');
    const btnBuscar = document.getElementById('btn-buscar-personal');
    const btnExportarExcel = document.getElementById('btn-exportar-personal-excel');

    if (!tabla || !btnRefrescar || !inputBuscar || !btnBuscar) return;

    const tbody = tabla.querySelector('tbody');

    const cfgTabPersonal = {
        urlListadoPersonal: tabla.dataset.urlTableListadoPersonal,

        urlVerRolesPersonal: tabla.dataset.urlTableVerRolesPersonal,
        
        urlVerEstudiantesAsignadosPersonal: tabla.dataset.urlTablaVerEstudiantesAsignadosPersonal,
        urlDesactivarAsignacionPersonal: tabla.dataset.urlTablaDesactivarAsignacionPersonal,
        urlReactivarAsignacionPersonal: tabla.dataset.urlTablaReactivarAsignacionPersonal,
        urlEliminarAsignacionPersonal: tabla.dataset.urlTablaEliminarAsignacionPersonal,

        urlVerPersonal: tabla.dataset.urlTableVerPersonal,
        urlEditarPersonal: tabla.dataset.urlTableEditarPersonal,

        urlEliminarPersonal: tabla.dataset.urlTableEliminarPersonal,

        urlExportarPersonalExcel: tabla.dataset.urlTableExportarPersonalExcel,
    };

    let aborter = null;


    //PINTADO DE LA TABLA DEL PERSONAL
    function renderRows(items) {
        if (!items || items.length === 0) {
            const texto = inputBuscar.value.trim()
                ? 'No se encontro ese personal con esa búsqueda.'
                : 'Sin personal registrado.';

            tbody.innerHTML = `<tr><td colspan="10" class="td-estado-tabla">${texto}</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((it) => {
            const totalRoles = Array.isArray(it.roles) ? it.roles.length : 0;
            const estadoTexto = it.estado_cuenta_personal ? 
                '<span class="estado-activado-personal"><i class="fa-solid fa-square-check"></i> Activado</span>' :
                '<span class="estado-desactivado-personal"><i class="fa-solid fa-square-xmark"></i> Desactivado</span>';
            return `  
            <tr>
                <td class="td-id">${it.id}</td>
                <td class="td-nombre-apellidos">${escapeHtml(it.nombre_personal || '—')} ${escapeHtml(it.apellidos_personal || '—')}</td>
                <td class="td-correo">${escapeHtml(it.correo_electronico || '—')}</td>
                <td class="td-telefono">${escapeHtml(it.telefono || '—')}</td>
                <td class="td-listado-informacion">
                    <div class="contenedor-td-listado-informacion">
                    ${totalRoles} rol${totalRoles === 1 ? '' : 'es'}
                    </div>

                    <div class="botones-tabla">
                        <button type="button" class="btn-ver-detalles-item btn-ver-roles-personal" data-id="${it.id}">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </td>
                <td class="td-estado-personal">${estadoTexto}</td>
                </td>
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
                        <button type="button" class="btn-ver-detalles-item btn-ver-estudiantes-asignados-personal" data-id="${it.id}">
                            <i class="fa-solid fa-user-group"></i>
                        </button>
                        <button type="button" class="btn-editar-item btn-editar-personal" data-id="${it.id}">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                    </div>
                </td>
                <td>
                    <div class="botones-tabla">
                    <button type="button" class="btn-eliminar-item btn-eliminar-personal" data-id="${it.id}">
                        <i class="fa-solid fa-trash"></i>
                        <span class="spinner-tabla"></span>
                    </button>
                    </div>
                </td>
            </tr>
            `;
        }).join('');
    }

    //CARGA DEL PINTADO DE LA TABLA Y LA FUNCION DE BUSCAR PERSONAL
    async function cargar() {
        tbody.innerHTML = `<tr><td colspan="10" class="td-estado-tabla">Cargando contenido…</td></tr>`;

        if (aborter) aborter.abort();
        aborter = new AbortController();

        try {
            const url = new URL(cfgTabPersonal.urlListadoPersonal, window.location.origin);
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
            displayMensajeToast('<p class="error">Error de conexión al cargar al personal.</p>');
        }
    }


    //VER ROLES DEL PERSONAL
    async function verDetalle(id) {
        try {
            const r = await fetchJson(buildUrl(cfgTabPersonal.urlVerRolesPersonal, id));
            if (!r.ok) return;

            const d = r.data?.data;
            if (!d) {
                displayMensajeToast('<p class="error">No se pudo obtener la información de los roles del personal.</p>')
                return;
            }

            const rolesPersonalHtml = (d.roles_del_personal && d.roles_del_personal.length)
                ? `<ul>
                    ${d.roles_del_personal.map(r => `
                        <li>${escapeHtml(r.nombre_rol)} <span class="informacion-extra-li-modal-detalles">(${escapeHtml(r.clave_rol)})</span></li>
                    `).join('')}
                </ul>`
                : '<p class="info-detalle">Este personal no tiene un rol asignado.</p>';

            displayModalDetalles(`
            <h3 class="titulo-modal-detalles">Roles del personal #${d.id}</h3>

            <div class="scroll-contenido-modal-detalles">

                <div class="contenedor-detalles">
                    <div class="caja-detalle">
                        <p class="nombre-detalle">Nombre y apellidos:</p>
                        <p class="info-detalle">${escapeHtml(d.nombre || '—')} ${escapeHtml(d.apellidos || '—')}</p>
                    </div>

                    <div class="caja-detalle">
                        <p class="nombre-detalle">Correo:</p>
                        <p class="info-detalle">${escapeHtml(d.correo || '—')}</p>
                    </div>

                    <div class="caja-detalle caja-detalle-resultados">
                        <p class="nombre-detalle">Listado de sus roles (total de ${d.total_roles ?? 0}):</p>
                        <p class="info-detalle"></p>
                        <div class="contenedor-listado-informacion-modal-detalles">
                            ${rolesPersonalHtml}
                        </div>
                    </div>
                </div>

            </div>
        `);

        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al ver los roles del personal.</p>');
        }
    }


    //FORMULARIO DE EDITAR PERSONAL
    async function editarPersonal(id) {
        try {
            const r = await fetchJson(buildUrl(cfgTabPersonal.urlVerPersonal, id));
            if (!r.ok) return;

            const d = r.data?.data;
            if (!d) {
                displayMensajeToast('<p class="error">No se pudo obtener la información del personal.</p>');
                return;
            }

            const activoChecked = d.estado_cuenta_personal ? 'checked' : '';

            const rolesHtml = (d.roles_disponibles || []).map((rol) => {
                const checked = (d.roles_seleccionados || []).includes(rol.id) ? 'checked' : '';
                return `
                    <label class="elementos-checkbox">
                        <input type="checkbox" name="roles[]" value="${rol.id}" ${checked} disabled>
                        <span class="circulo-checkbox"></span>
                        <span class="texto-checkbox">${escapeHtml(rol.nombre_rol)} (${escapeHtml(rol.clave)})</span>
                    </label>
                `;
            }).join('');

            displayModalFormularioEditar(`
                <form id="form-editar-personal" class="form-editar-contenido">
                
                    <div class="form-title">
                        <span>Editar personal #${d.id}</span>
                    </div>
                    
                    <div class="form-inputs">
                        <div class="scroll-editar">

                            <div class="input-box-editar">
                                <label class="nombre-input">Nombre y apellidos:</label>

                                <div class="input-box">
                                    <input class="input-field" type="text" id="nombre-input" name="nombre" placeholder="Nombre" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ejem: Fernando" value="${escapeHtml(d.nombre_personal || '')}"  autocomplete="off" autocapitalize="words" disabled required>

                                    <input class="input-field" type="text" id="apellidos-input" name="apellidos" placeholder="Apellidos" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ejem: Cuevas Cuevas" value="${escapeHtml(d.apellidos || '')}" autocomplete="off" autocapitalize="words" disabled required>
                                </div>
                            </div>

                            <div class="input-box-editar">
                                <label class="nombre-input">Correo electronico:</label>
                                <input class="input-field" type="email" id="email-input" name="email" value="${escapeHtml(d.email || '')}" autocomplete="off" autocapitalize="none" spellcheck="false" disabled  required>
                            </div>

                            <div class="input-box-editar">
                                <label class="nombre-input">Numero de telefono:</label>
                                <input class="input-field" type="tel" id="telefono-input" name="telefono" pattern="[0-9]{10}" title="Tu numero debe de ser valido (10 digitos)" value="${escapeHtml(d.telefono || '')}" autocomplete="off" disabled  required>
                            </div>

                            <div class="input-box-editar"><!--Laravel tiene una regla de validacion de contrañas, entonces el name= tienes que decir "password"-->
                                <label class="nombre-input">Cambiar contraseña:</label>
                                <input class="input-field" type="password" id="password-input" name="password" placeholder="* * * * * *" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$" title="Minino 6 caracteres (1 letra mayúscula, 1 número y 1 símbolo)"  autocomplete="off" disabled>
                                <i class="ri-lock-2-line icon" id="togglePassword"></i>
                            </div>

                            <div class="input-box-editar"><!--Laravel tiene una regla de validacion de contrañas, entonces el name= tienes que decir "password_confirmation"-->
                                <label class="nombre-input">Repetir contraseña:</label>
                                <input class="input-field" type="password" id="confirm-password-input" name="password_confirmation" placeholder="* * * * * *" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$" title="Minino 6 caracteres (1 letra mayúscula, 1 número y 1 símbolo)" autocomplete="off" disabled>
                                <i class="ri-lock-2-line icon" id="toggleConfirmPassword"></i>
                            </div>

                            <div class="input-box">
                                <label class="input-field switch-estado">
                                    <input type="checkbox" name="activo" value="1" ${activoChecked} disabled>
                                    <span class="slider-switch-estado"></span>
                                    <span class="texto-switch-estado">Activar cuenta</span>
                                </label>
                            </div>

                            <div class="separador-formulario"></div>

                            <div class="contenedor-elementos-extra-form">
                                <p class="subtitulo-elementos-extra-form">Asignar roles</p>
                                ${rolesHtml || '<p class="sin-elementos-extra-form">No hay roles disponibles.</p>'}
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

            const form = document.getElementById('form-editar-personal');
            if (!form) return;


            //====================HABILITAR EDICION====================
            //En los <input>, <textarea> y el <input type="checkbox"> tienen "disabled"
            const btnEditarPersonal = form.querySelector('.btn-editar-form');
            const btnCancelarEdicionPersonal = form.querySelector('.btn-cancelar-borrar');
            const btnGuardarEdicionPersonal = form.querySelector('.btn-guardar-enviar');

            btnEditarPersonal?.addEventListener('click', () => {
                form.querySelectorAll(`
                        input[name="nombre"], 
                        input[name="apellidos"], 
                        input[name="email"], 
                        input[name="telefono"], 
                        input[name="password"], 
                        input[name="password_confirmation"], 
                        input[name="activo"], 
                        input[name="roles[]"]
                    `).forEach(el => el.disabled = false);

                btnEditarPersonal.style.display = 'none';
                btnCancelarEdicionPersonal.style.display = 'inline-flex'
                btnGuardarEdicionPersonal.style.display = 'inline-flex';

                form.querySelector('[name="nombre"]')?.focus();//Enfocarse a ese input
            });

            //=====================CANCELAR EDICION=====================
            btnCancelarEdicionPersonal?.addEventListener('click', () => {
                form.reset();

                form.querySelectorAll(`
                    input[name="nombre"], 
                    input[name="apellidos"], 
                    input[name="email"], 
                    input[name="telefono"], 
                    input[name="password"], 
                    input[name="password_confirmation"], 
                    input[name="activo"], 
                    input[name="roles[]"]
                `).forEach(el => el.disabled = true);

                const passwordInput = form.querySelector('#password-input');
                const confirmPasswordInput = form.querySelector('#confirm-password-input');
                const togglePassword = form.querySelector('#togglePassword');
                const toggleConfirmPassword = form.querySelector('#toggleConfirmPassword');

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

                btnEditarPersonal.style.display = 'inline-flex';
                btnCancelarEdicionPersonal.style.display = 'none';
                btnGuardarEdicionPersonal.style.display = 'none';
            });
            //==========================================================


            //===========MOSTRAR O OCULTAR, VERIFICAR CONTRASEÑA=========
            function bindToggle(btnId, inputId) {
                const btn = document.getElementById(btnId);
                const input = document.getElementById(inputId);

                //si falta cualquiera, no se rompe el resto del JS
                if (!btn || !input) return;

                btn.addEventListener('click', () => {
                    input.type = (input.type === 'password') ? 'text' : 'password';
                    btn.classList.toggle('active');
                });
            }

            bindToggle('togglePassword', 'password-input');
            bindToggle('toggleConfirmPassword', 'confirm-password-input');


            //===Verificar si las contraseñas coinciden
            const password = document.getElementById('password-input');
            const confirmPassword = document.getElementById('confirm-password-input');

            //si no estan en esa vista, no se romper nada
            if (password && confirmPassword) {
                function validatePasswords() {
                    const ok = password.value === confirmPassword.value;
                    password.classList.toggle('error', !ok);
                    confirmPassword.classList.toggle('error', !ok);
                }

                password.addEventListener('input', validatePasswords);
                confirmPassword.addEventListener('input', validatePasswords);
            }
            //==========================================================


            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const button = form.querySelector('.btn-guardar-enviar');
                setLoadingFormulario(button, true);

                try {

                    const roles = [...form.querySelectorAll('input[name="roles[]"]:checked')]
                        .map(input => Number(input.value));

                    const payload = {
                        nombre: form.querySelector('[name="nombre"]').value.trim(),
                        apellidos: form.querySelector('[name="apellidos"]').value.trim(),
                        email: form.querySelector('[name="email"]').value.trim(),
                        telefono: form.querySelector('[name="telefono"]').value.trim(),
                        password: form.querySelector('[name="password"]').value.trim(),
                        password_confirmation: form.querySelector('[name="password_confirmation"]').value.trim(),
                        activo: form.querySelector('[name="activo"]').checked ? 1 : 0,
                        roles,
                    };

                    const rr = await fetchJson(buildUrl(cfgTabPersonal.urlEditarPersonal, id), {
                        method: 'PUT',
                        body: payload,
                    });

                    if (!rr.ok) return;

                    await cargar();
                    if (typeof displayMensajeToast === 'function') {
                        cerrarModalFormularioEditar();
                        displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Personal actualizado correctamente.')}</p>`);
                    }

                } catch (error) {
                    console.error(error);
                    displayMensajeToast(`<p class="error">Error de conexión al actualizar el personal. <span class="ext">Revisa tu servidor o recarga la página.</span></p>`);
                } finally {
                    setLoadingFormulario(button, false);
                }
            });
        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al editar el personal.</p>');
        }
    }


    //GESTIONAR ASIGNACIONES QUE EL PERSONAL TIENE CON ESTUDIANTES (DESACTIVAR ASIGNACION, REACTIVAR ASIGNACION y ELIMINAR ASIGNACION)
    async function verEstudiantesAsignadosPersonal(id) {
        try {
            const r = await fetchJson(buildUrl(cfgTabPersonal.urlVerEstudiantesAsignadosPersonal, id));
            if (!r.ok) return;

            const d = r.data?.data;
            if (!d) {
                displayMensajeToast('<p class="error">No se pudo obtener las asignaciones del personal.</p>');
                return;
            }

            const asignacionesActivasHtml = (d.asignaciones_activas || []).length
                ? d.asignaciones_activas.map(a => `
                    <div class="asignacion-fila"
                        data-search="${escapeHtml(`${a.numero_control || ''} ${a.nombre_estudiante || ''} ${a.nombre_rol || ''}`.toLowerCase())}">
                        <div class="asignacion-info">
                            <span class="asignacion-badge activa">ACTIVA</span>
                            <span class="asignacion-rol">${escapeHtml(a.nombre_rol || '—')}</span>
                            <span class="asignacion-datos"> | ${escapeHtml(a.numero_control || '—')} | ${escapeHtml(a.nombre_estudiante || '—')}</span>
                        </div>
                        <div class="asignacion-acciones">
                            <button type="button" class="btn-asignacion btn-desactivar-asignacion-personal btn-desactivar" data-id="${a.id}">
                                <i class="fa-solid fa-ban"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>
                `).join('')
                : '<p class="asignacion-vacio">Sin asignaciones activas.</p>';

            const asignacionesInactivasHtml  = (d.asignaciones_inactivas || []).length
                ? d.asignaciones_inactivas.map(a => `
                    <div class="asignacion-fila"
                        data-search="${escapeHtml(`${a.numero_control || ''} ${a.nombre_estudiante || ''} ${a.nombre_rol || ''}`.toLowerCase())}">
                        <div class="asignacion-info">
                            <span class="asignacion-badge inactiva">INACTIVA</span>
                            <span class="asignacion-rol">${escapeHtml(a.nombre_rol || '—')}</span>
                            <span class="asignacion-datos"> | ${escapeHtml(a.numero_control || '—')} | ${escapeHtml(a.nombre_estudiante || '—')}</span>
                        </div>
                        <div class="asignacion-acciones">
                            <button type="button" class="btn-asignacion btn-reactivar-asignacion-personal btn-reactivar" data-id="${a.id}">
                                <i class="fa-solid fa-rotate-left"></i>
                                <span class="spinner-tabla"></span>
                            </button>

                            <button type="button" class="btn-asignacion btn-eliminar-definitivo-asignacion-personal btn-eliminar" data-id="${a.id}">
                                <i class="fa-solid fa-trash"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>
                `).join('')
                : '<p class="asignacion-vacio">Sin asignaciones inactivas.</p>';

            displayModalDetalles(`
                <h3 class="titulo-modal-detalles">Estudiantes asignados al personal #${d.id}</h3>

                <div class="scroll-contenido-modal-detalles">
                    <div class="contenedor-detalles">

                        <div class="caja-detalle">
                            <p class="nombre-detalle">Nombre:</p>
                            <p class="info-detalle">${escapeHtml(d.nombre_completo || '—')}</p>
                        </div>

                        <div class="caja-detalle">
                            <p class="nombre-detalle">Correo:</p>
                            <p class="info-detalle">${escapeHtml(d.email || '—')}</p>
                        </div>

                    </div>

                    <div class="contenedor-buscador-asignaciones">
                        <input type="text" id="input-buscar-asignaciones-personal" class="input-buscar-asignaciones" placeholder="Buscar por número de control, nombre o rol">
                    </div>

                    <div class="bloque-asignaciones-formulario primero">
                        <div class="encabezado-bloque-asignaciones">
                            <h4 class="titulo-bloque-asignaciones">Asignaciones activas</h4>
                        </div>

                        <div class="listado-asignaciones">
                            ${asignacionesActivasHtml}
                        </div>
                    </div>

                    <div class="bloque-asignaciones-formulario primero">
                        <div class="encabezado-bloque-asignaciones">
                            <h4 class="titulo-bloque-asignaciones">Asignaciones inactivas</h4>
                        </div>

                        <div class="listado-asignaciones">
                            ${asignacionesInactivasHtml }
                        </div>
                    </div>

                </div>
            `);
            
            //PARA BUSCAR ASIGNACIONES QUE TIENE EL PERSONAL
            const inputBuscarAsignaciones = document.getElementById('input-buscar-asignaciones-personal');

            inputBuscarAsignaciones?.addEventListener('input', () => {
                const texto = inputBuscarAsignaciones.value.trim().toLowerCase();
                const filas = document.querySelectorAll('.asignacion-fila');

                filas.forEach(fila => {
                    const contenido = fila.dataset.search || '';
                    fila.style.display = contenido.includes(texto) ? '' : 'none';
                });
            });


            document.querySelectorAll('.btn-desactivar-asignacion-personal').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const ok = confirm('¿Desactivar esta asignación?');
                    if (!ok) return;

                    setLoadingTabla(btn, true);

                    try {
                        const rr = await fetchJson(buildUrl(cfgTabPersonal.urlDesactivarAsignacionPersonal, btn.dataset.id), {
                            method: 'PUT',
                        });

                        if (!rr.ok) return;

                        cerrarModalDetalles?.();
                        displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Asignación desactivada correctamente.')}</p>`);
                    } catch (error) {
                        console.error(error);
                        displayMensajeToast('<p class="error">Error al desactivar la asignación.</p>');
                    } finally {
                        setLoadingTabla(btn, false);
                    }
                });
            });

            document.querySelectorAll('.btn-reactivar-asignacion-personal').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const ok = confirm('¿Reactivar esta asignación?');
                    if (!ok) return;

                    setLoadingTabla(btn, true);

                    try {
                        const rr = await fetchJson(buildUrl(cfgTabPersonal.urlReactivarAsignacionPersonal, btn.dataset.id), {
                            method: 'PUT',
                        });

                        if (!rr.ok) return;

                        cerrarModalDetalles?.();
                        displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Asignación reactivada correctamente.')}</p>`);
                    } catch (error) {
                        console.error(error);
                        displayMensajeToast('<p class="error">Error al reactivar la asignación.</p>');
                    } finally {
                        setLoadingTabla(btn, false);
                    }
                });
            });

            document.querySelectorAll('.btn-eliminar-definitivo-asignacion-personal').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const ok = confirm('¿Eliminar definitivamente esta asignación?');
                    if (!ok) return;

                    setLoadingTabla(btn, true);

                    try {
                        const rr = await fetchJson(buildUrl(cfgTabPersonal.urlEliminarAsignacionPersonal, btn.dataset.id), {
                            method: 'DELETE',
                        });

                        if (!rr.ok) return;

                        cerrarModalDetalles?.();
                        displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Asignación eliminada definitivamente.')}</p>`);
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
            displayMensajeToast('<p class="error">Error de conexión al ver estudiantes asignados.</p>');
        }
    }


    //ELIMIANR UN PERSONAL
    async function eliminarPersonal(id, button) {

        // modal de confirmacion
        const ok = await modalConfirmarAccion({
            titulo: 'Eliminar personal',
            mensaje: `
                <p>¿Seguro que quieres eliminar el personal #${id}?</p>
                <ul>
                    <li>Si el personal tiene registros de eventos o acciones en la plataforma, no podra eliminarse, ya que se utilizan para auditoría.</li>
                    <li>Se recomienda el desactivar la cuenta del personal.</li>
                    <li>Si en verdad deseas eliminarlo, primero elimina sus vinculos o asignaciones que se le han echo.</li>
                    <li>Esta acción no se puede deshacer. Por ello, verifica por ultima vez al personal.</li>
                </ul>
            `,
            txtConfirmar: 'Sí, eliminar',
            tipo: 'eliminar-desactivar'
        });

        if (!ok) return;

        try {
            setLoadingTabla(button, true);

            const r = await fetchJson(buildUrl(cfgTabPersonal.urlEliminarPersonal, id), {
                method: 'DELETE',
            });

            if (!r.ok) return;

            await cargar();
            if (typeof displayModal === 'function') {
                displayMensajeToast(`<p class="exito">${escapeHtml(r.data?.message || 'Personal eliminado correctamente.')}</p>`);
            }
        } catch (err) {
            console.error(err);
            displayMensajeToast('<p class="error">Error de conexión al eliminar el personal.</p>');
        } finally {
            setLoadingTabla(button, false);
        }
    }

    //==================LISTENER PARA LA TABLA==================
    tabla.addEventListener('click', (ev) => {
        const btnDetalle = ev.target.closest('.btn-ver-roles-personal');
        if (btnDetalle) {
            verDetalle(btnDetalle.dataset.id);
            return;
        }

        const btnVerEstudiantesAsignados = ev.target.closest('.btn-ver-estudiantes-asignados-personal');
        if (btnVerEstudiantesAsignados) {
            verEstudiantesAsignadosPersonal(btnVerEstudiantesAsignados.dataset.id);
            return;
        }

        const btnEditar = ev.target.closest('.btn-editar-personal');
        if (btnEditar) {
            editarPersonal(btnEditar.dataset.id);
            return;
        }

        const btnEliminar = ev.target.closest('.btn-eliminar-personal');
        if (btnEliminar) {
            eliminarPersonal(btnEliminar.dataset.id, btnEliminar);
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


    btnExportarExcel?.addEventListener('click', async () => {
        try {
            setLoadingTabla(btnExportarExcel, true);

            const url = new URL(cfgTabPersonal.urlExportarPersonalExcel, window.location.origin);
            const buscar = inputBuscar.value.trim();

            if (buscar) {
                url.searchParams.set('buscar', buscar);
            }

            window.location.href = url.toString();

        } catch (error) {
            console.error(error);
            displayMensajeToast('<p class="error">No se pudo iniciar la exportación del Excel del personal.</p>');
        } finally {
            setTimeout(() => {
                setLoadingTabla(btnExportarExcel, false);
            }, 1200);
        }
    });

    //==========================================================

    cargar();
}
//==========================================================