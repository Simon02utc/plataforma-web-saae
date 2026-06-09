document.addEventListener('DOMContentLoaded', () => {
  initTablaRoles();
  initTablaPermisos();
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
    displayModal(`<p class="error">${escapeHtml(data?.message ?? `Error inesperado (${res.status}).`)}</p>`);
    return { ok: false, status: res.status, data };
  }

  return { ok: true, status: res.status, data };
}
//==========================================================


function descargarArchivo(url) {
  window.location.href = url;
}


//======TABLA DE ROLES (LISTA, VER, EDITAR Y ELIMINAR)======
function initTablaRoles() {
  const tabla = document.getElementById('tabla-listado-roles');
  const btnRefrescar = document.getElementById('btn-refrescar-tabla-roles');
  const inputBuscar = document.getElementById('input-buscar-roles');
  const btnBuscar = document.getElementById('btn-buscar-roles');
  const btnExportarExcel = document.getElementById('btn-exportar-roles-excel');

  if (!tabla || !btnRefrescar || !inputBuscar || !btnBuscar) return;

  const tbody = tabla.querySelector('tbody');

  const cfgTabRoles = {
    urlListadoRoles: tabla.dataset.urlTablaListadoRoles,
    urlVerPermisosRol: tabla.dataset.urlTablaVerPermisosRol,
    urlVerRol: tabla.dataset.urlTablaVerRol,
    urlEditarRol: tabla.dataset.urlTablaEditarRol,
    urlEliminarRol: tabla.dataset.urlTablaEliminarRol,
    urlExportarRolesExcel: tabla.dataset.urlTablaExportarRolesExcel,
  };

  let aborter = null;


  //PINTADO DE LA TABLA DE ROLES
  function renderRows(items) {
    if (!items || items.length === 0) {
      const texto = inputBuscar.value.trim()
        ? 'No se encontraron roles con esa búsqueda.'
        : 'Sin roles registrados.';

      tbody.innerHTML = `<tr><td colspan="8" class="td-estado-tabla">${texto}</td></tr>`;
      return;
    }

    tbody.innerHTML = items.map((it) => {
      const totalPermisos = Number(it.total_permisos ?? 0);

      return `  
        <tr>
          <td class="td-id">${it.id}</td>
          <td class="td-clave">${escapeHtml(it.clave_rol || '—')}</td>
          <td class="td-nombre">${escapeHtml(it.nombre_rol || '—')}</td>
          <td class="td-descripcion">${escapeHtml(it.descripcion_rol || '—')}</td>
          <td class="td-listado-informacion">
            <div class="contenedor-td-listado-informacion">
              ${totalPermisos} permiso${totalPermisos === 1 ? '' : 's'}
            </div>

            <div class="botones-tabla">
            <button type="button" class="btn-ver-detalles-item" data-id="${it.id}">
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>
          </td>
          <td class="td-fecha-mini-tabla">
            <table class="mini-tabla-items">
                <tr>
                  <td class='td-nombre-mini-tabla primero'>Creación:</td>
                  <td class='td-contenido-mini-tabla'> ${fmtFecha(it.creado_en)}</td>
                </tr>
                <tr>
                  <td class='td-nombre-mini-tabla ultimo'>Edición:</td>
                  <td class='td-contenido-mini-tabla'>${fmtFecha(it.editado_en)}</td>
                </tr>
            </table>    
          </td>
          <td>
            <div class="botones-tabla">
              <button type="button" class="btn-editar-item btn-editar-rol" data-id="${it.id}">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
            </div>
          </td>
          <td>
            <div class="botones-tabla">
              <button type="button" class="btn-eliminar-item btn-eliminar-rol" data-id="${it.id}">
                <i class="fa-solid fa-trash"></i>
                <span class="spinner-tabla"></span>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  //CARGA DEL PINTADO DE LA TABLA Y LA FUNCION DE BUSCAR ROL
  async function cargar() {
    tbody.innerHTML = `<tr><td colspan="8" class="td-estado-tabla">Cargando contenido…</td></tr>`;

    if (aborter) aborter.abort();
    aborter = new AbortController();

    try {
      const url = new URL(cfgTabRoles.urlListadoRoles, window.location.origin);
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
      displayMensajeToast('<p class="error">Error de conexión al cargar los roles.</p>');
    }
  }


  //VER PERMISOSO DEL ROL
  async function verDetalle(id) {
    try {
      const r = await fetchJson(buildUrl(cfgTabRoles.urlVerPermisosRol, id));
      if (!r.ok) return;

      const d = r.data?.data;
      if (!d) {
        displayMensajeToast('<p class="error">No se pudo obtener la información de los permisos del rol.</p>')
        return;
      }

      const permisosHtml = (d.permisos_del_rol && d.permisos_del_rol.length)
        ? `<ul>
          ${d.permisos_del_rol.map(p => `
            <li>${escapeHtml(p.nombre)} <span class="informacion-extra-li-modal-detalles">(${escapeHtml(p.clave)})</span></li>
          `).join('')}
        </ul>`
        : '<p class="info-detalle">Este rol no tiene permisos asignados.</p>';

      displayModalDetalles(`
        <h3 class="titulo-modal-detalles">Permisos del rol #${d.id}</h3>

          <div class="scroll-contenido-modal-detalles">

              <div class="contenedor-detalles">
                <div class="caja-detalle">
                  <p class="nombre-detalle">Rol:</p>
                  <p class="info-detalle">${escapeHtml(d.nombre || '—')}</p>
                </div>

                <div class="caja-detalle">
                  <p class="nombre-detalle">Clave:</p>
                  <p class="info-detalle">${escapeHtml(d.clave || '—')}</p>
                </div>

                <div class="caja-detalle caja-detalle-resultados">
                  <p class="nombre-detalle">Listado de sus permisos (total de ${d.total_permisos ?? 0}):</p>
                  <p class="info-detalle"></p>
                  <div class="contenedor-listado-informacion-modal-detalles">
                    ${permisosHtml}
                  </div>
                </div>
              </div>

          </div>
      `);

    } catch (err) {
      console.error(err);
      displayMensajeToast('<p class="error">Error de conexión al ver los permisos del rol.</p>');
    }
  }


  //FORMULARIO DE EDITAR ROL
  async function editarRol(id) {
    try {
      const r = await fetchJson(buildUrl(cfgTabRoles.urlVerRol, id));
      if (!r.ok) return;

      const d = r.data?.data;
      if (!d) {
        displayMensajeToast('<p class="error">No se pudo obtener la información del rol.</p>');
        return;
      }

      const permisosHtml = (d.permisos_disponibles || []).map((permiso) => {
        const checked = (d.permisos_seleccionados || []).includes(permiso.id) ? 'checked' : '';
        return `
          <label class="elementos-checkbox">
            <input type="checkbox" name="permisos[]" value="${permiso.id}" ${checked} disabled>
            <span class="circulo-checkbox"></span>
            <span class="texto-checkbox">${escapeHtml(permiso.nombre)} (${escapeHtml(permiso.clave)})</span>
          </label>
        `;
      }).join('');

      displayModalFormularioEditar(`
        <form id="form-editar-rol" class="form-editar-contenido">
          
          <div class="form-title">
              <span>Editar rol #${d.id}</span>
          </div>
          
          <div class="form-inputs">
            <div class="scroll-editar">

              <div class="input-box-editar">
                <label class="nombre-input">Clave:</label>
                <input class="input-field" type="text" name="clave" pattern="^[a-z]+(?:_[a-z]+)*$" title="Solo letras minúsculas y guion bajo '_' como separador. Ejemplo: director_tesis" value="${escapeHtml(d.clave || '')}" autocomplete="off" autocapitalize="none" disabled  required>
              </div>

              <div class="input-box-editar">
                <label class="nombre-input">Nombre:</label>
                <input class="input-field" type="text" name="nombre" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: Director Tesis" value="${escapeHtml(d.nombre || '')}" autocomplete="off" autocapitalize="words" disabled  required>
              </div>

              <div class="input-box-editar">
                <label class="nombre-input">Descripción:</label>
                <textarea class="input-field" name="descripcion" maxlength="500" title="Solo letras, espacios, y signos de puntuacion" autocomplete="off" disabled>${escapeHtml(d.descripcion || '')}</textarea>
              </div>

              <div class="separador-formulario"></div>

                <div class="contenedor-elementos-extra-form">
                  <p class="subtitulo-elementos-extra-form">Asignar permisos</p>
                  ${permisosHtml || '<p class="sin-elementos-extra-form">No hay permisos disponibles.</p>'}
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

      const form = document.getElementById('form-editar-rol');
      if (!form) return;


      //====================HABILITAR EDICION====================
      //En los <input>, <textarea> y el <input type="checkbox"> tienen "disabled"
      const btnEditarRol = form.querySelector('.btn-editar-form');
      const btnCancelarEdicionRol = form.querySelector('.btn-cancelar-borrar');
      const btnGuardarEdicionRol = form.querySelector('.btn-guardar-enviar');

      btnEditarRol?.addEventListener('click', () => {
        form.querySelectorAll(`
            input[name="clave"], 
            input[name="nombre"], 
            textarea[name="descripcion"], 
            input[name="permisos[]"]
          `).forEach(el => el.disabled = false);

        btnEditarRol.style.display = 'none';
        btnCancelarEdicionRol.style.display = 'inline-flex';
        btnGuardarEdicionRol.style.display = 'inline-flex';

        form.querySelector('[name="clave"]')?.focus();//Enfocarse a ese input
      });

      //=====================CANCELAR EDICION=====================
      btnCancelarEdicionRol?.addEventListener('click', () => {
          form.reset();

          form.querySelectorAll(`
              input[name="clave"], 
              input[name="nombre"], 
              textarea[name="descripcion"], 
              input[name="permisos[]"]
          `).forEach(el => el.disabled = true);

          btnEditarRol.style.display = 'inline-flex';
          btnCancelarEdicionRol.style.display = 'none';
          btnGuardarEdicionRol.style.display = 'none';
      });
      //==========================================================


      form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const button = form.querySelector('.btn-guardar-enviar');
        setLoadingFormulario(button, true);

        try {

          const permisos = [...form.querySelectorAll('input[name="permisos[]"]:checked')]
            .map(input => Number(input.value));

          const payload = {
            clave: form.querySelector('[name="clave"]').value.trim(),
            nombre: form.querySelector('[name="nombre"]').value.trim(),
            descripcion: form.querySelector('[name="descripcion"]').value.trim(),
            permisos,
          };

          const rr = await fetchJson(buildUrl(cfgTabRoles.urlEditarRol, id), {
            method: 'PUT',
            body: payload,
          });

          if (!rr.ok) return;

          await cargar();
          if (typeof displayMensajeToast === 'function') {
            cerrarModalFormularioEditar();
            displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Rol actualizado correctamente.')}</p>`);
          }

        } catch (error) {
          console.error(error);
          displayMensajeToast(`<p class="error">Error de conexión al actualizar el rol. <span class="ext">Revisa tu servidor o recarga la página.</span></p>`);
        } finally {
          setLoadingFormulario(button, false);
        }
      });
    } catch (err) {
      console.error(err);
      displayMensajeToast('<p class="error">Error de conexión al editar el rol.</p>');
    }
  }


  //ELIMIANR UN ROL
  async function eliminarRol(id, button) {

    // modal de confirmacion
    const ok = await modalConfirmarAccion({
        titulo: 'Eliminar rol',
        mensaje: `
            <p>¿Seguro que deseas eliminar el rol #${id}?</p>
            <ul>
                <li>Esta acción eliminará el registro del rol.</li>
                <li>Se recomienda el desactivar el rol.</li>
                <li>Si hay personal usando este rol, podría causar problemas.</li>
                <li>Esta acción no se puede deshacer.</li>
            </ul>
        `,
        txtConfirmar: 'Sí, eliminar',
        tipo: 'eliminar-desactivar'
    });

    if (!ok) return;
  
    try {
      setLoadingTabla(button, true);

      const r = await fetchJson(buildUrl(cfgTabRoles.urlEliminarRol, id), {
        method: 'DELETE',
      });

      if (!r.ok) return;

      await cargar();
      if (typeof displayMensajeToast === 'function') {
        displayMensajeToast(`<p class="exito">${escapeHtml(r.data?.message || 'Rol eliminado correctamente.')}</p>`);
      }
    } catch (err) {
      console.error(err);
      displayMensajeToast('<p class="error">Error de conexión al eliminar el rol.</p>');
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

    const btnEditar = ev.target.closest('.btn-editar-rol');
    if (btnEditar) {
      editarRol(btnEditar.dataset.id);
      return;
    }

    const btnEliminar = ev.target.closest('.btn-eliminar-rol');
    if (btnEliminar) {
      eliminarRol(btnEliminar.dataset.id, btnEliminar);
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

      const url = new URL(cfgTabRoles.urlExportarRolesExcel, window.location.origin);
      const buscar = inputBuscar.value.trim();

      if (buscar) {
        url.searchParams.set('buscar', buscar);
      }

      window.location.href = url.toString();

    } catch (error) {
      console.error(error);
      displayMensajeToast('<p class="error">No se pudo iniciar la exportación del Excel de roles.</p>');
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



//=====TABLA DE PERMISOS (LISTA, VER, EDITAR Y ELIMINAR)=====
function initTablaPermisos() {
  const tabla = document.getElementById('tabla-listado-permisos');
  const btnRefrescar = document.getElementById('btn-refrescar-tabla-permisos');
  const inputBuscar = document.getElementById('input-buscar-permisos');
  const btnBuscar = document.getElementById('btn-buscar-permisos');
  const btnExportarExcel = document.getElementById('btn-exportar-permisos-excel');

  if (!tabla || !btnRefrescar || !inputBuscar || !btnBuscar) return;

  const tbody = tabla.querySelector('tbody');

  const cfgTabPermisos = {
    urlListadoPermisos: tabla.dataset.urlTablaListadoPermisos,
    urlVerPermiso: tabla.dataset.urlTablaVerPermiso,
    urlEditarPermiso: tabla.dataset.urlTablaEditarPermiso,
    urlEliminarPermiso: tabla.dataset.urlTablaEliminarPermiso,
    urlExportarPermisosExcel: tabla.dataset.urlTablaExportarPermisosExcel,
  };

  let aborter = null;


  //PINTADO DE LA TABLA DE PERMISOS
  function renderRows(items) {
    if (!items || items.length === 0) {
      const texto = inputBuscar.value.trim()
        ? 'No se encontraron permisos con esa búsqueda.'
        : 'Sin permisos registrados.';

      tbody.innerHTML = `<tr><td colspan="7" class="td-estado-tabla">${texto}</td></tr>`;
      return;
    }

    tbody.innerHTML = items.map((it) => `
      <tr>
        <td class="td-id">${it.id}</td>
        <td>${escapeHtml(it.clave || '—')}</td>
        <td>${escapeHtml(it.nombre || '—')}</td>
        <td class="td-descripcion">${escapeHtml(it.descripcion || '—')}</td>
        <td class="td-fecha-mini-tabla">
          <table class="mini-tabla-items">
              <tr>
                <td class='td-nombre-mini-tabla primero'>Creación:</td>
                <td class='td-contenido-mini-tabla'> ${fmtFecha(it.creado_en)}</td>
              </tr>
              <tr>
                <td class='td-nombre-mini-tabla ultimo'>Edición:</td>
                <td class='td-contenido-mini-tabla'>${fmtFecha(it.editado_en)}</td>
              </tr>
          </table>    
        </td>
        <td>
          <div class="botones-tabla">
            <button type="button" class="btn-editar-item btn-editar-permiso" data-id="${it.id}">
              <i class="fa-solid fa-pen-to-square"></i>
            </button>
          </div>
        </td>
        <td>
          <div class="botones-tabla">
            <button type="button" class="btn-eliminar-item btn-eliminar-permiso" data-id="${it.id}">
              <i class="fa-solid fa-trash"></i>
              <span class="spinner-tabla"></span>
            </button>
          </div>
        </td>
      </tr>
    `).join('');
  }


  //CARGA DEL PINTADO DE LA TABLA Y LA FUNCION DE BUSCAR PERMISOS
  async function cargar() {
    tbody.innerHTML = `<tr><td colspan="7" class="td-estado-tabla">Cargando contenido…</td></tr>`;

    if (aborter) aborter.abort();
    aborter = new AbortController();

    try {
      const url = new URL(cfgTabPermisos.urlListadoPermisos, window.location.origin);
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
      displayMensajeToast('<p class="error">Error de conexión al cargar los permisos.</p>');
    }
  }


  //FORMULARIO DE EDITAR PERMISO
  async function editarPermiso(id) {
    try {
      const r = await fetchJson(buildUrl(cfgTabPermisos.urlVerPermiso, id));
      if (!r.ok) return;

      const d = r.data?.data;
      if (!d) {
        displayMensajeToast('<p class="error">No se pudo obtener la información del permiso.</p>');
        return;
      }

      displayModalFormularioEditar(`
        <form id="form-editar-permiso" class="form-editar-contenido">

          <div class="form-title">
              <span>Editar permiso #${d.id}</span>
          </div>

          <div class="form-inputs">
            <div class="scroll-editar">

              <div class="input-box-editar">
                <label class="nombre-input">Clave:</label>
                <input class="input-field" type="text" name="clave" pattern="^[a-z]+(?:[._][a-z]+)*$" title="Solo letras minúsculas, puntos '.' y guion bajo '_' como separador. Ejemplo: (estudiantes.ver) o (auditoria_seguridad.ver)" value="${escapeHtml(d.clave || '')}" autocomplete="off" autocapitalize="none" disabled required>
              </div>

              <div class="input-box-editar">
                <label class="nombre-input">Nombre:</label>
                <input class="input-field" type="text" name="nombre" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: Ver la seccion de estudiantes" value="${escapeHtml(d.nombre || '')}" autocomplete="off" autocapitalize="words" disabled required>
              </div>

              <div class="input-box-editar">
                <label class="nombre-input">Descripción:</label>
                <textarea class="input-field" maxlength="500" title="Solo letras, espacios, y signos de puntuacion" name="descripcion" autocomplete="off" disabled>${escapeHtml(d.descripcion || '')}</textarea>
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

      const form = document.getElementById('form-editar-permiso');
      if (!form) return;


      //====================HABILIDAR EDICION====================
      //En los <input>, <textarea> y el <input type="checkbox"> tienen "disabled"
      const btnEditarPermiso = form.querySelector('.btn-editar-form');
      const btnCancelarEdicionPermiso = form.querySelector('.btn-cancelar-borrar');
      const btnGuardarEdicionPermiso = form.querySelector('.btn-guardar-enviar');

      btnEditarPermiso?.addEventListener('click', () => {
        form.querySelectorAll(`
            input[name="clave"], 
            input[name="nombre"], 
            textarea[name="descripcion"]
          `).forEach(el => el.disabled = false);

        btnEditarPermiso.style.display = 'none';
        btnCancelarEdicionPermiso.style.display = 'inline-flex'
        btnGuardarEdicionPermiso.style.display = 'inline-flex';

        form.querySelector('[name="clave"]')?.focus();//Enfocarse a ese input
      });

      //=====================CANCELAR EDICION=====================
      btnCancelarEdicionPermiso?.addEventListener('click', () => {
          form.reset();

          form.querySelectorAll(`
              input[name="clave"], 
              input[name="nombre"], 
              textarea[name="descripcion"]
          `).forEach(el => el.disabled = true);

          btnEditarPermiso.style.display = 'inline-flex';
          btnCancelarEdicionPermiso.style.display = 'none';
          btnGuardarEdicionPermiso.style.display = 'none';
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
          };

          const rr = await fetchJson(buildUrl(cfgTabPermisos.urlEditarPermiso, id), {
            method: 'PUT',
            body: payload,
          });

          if (!rr.ok) return;

          await cargar();
          if (typeof displayMensajeToast === 'function') {
            cerrarModalFormularioEditar();
            displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Permiso actualizado correctamente.')}</p>`);
          }

        } catch (error) {
          console.error(error);
          displayMensajeToast(`<p class="error">Error de conexión al actualizar el permiso. <span class="ext">Revisa tu servidor o recarga la página.</span></p>`);
        } finally {
          setLoadingFormulario(button, false);
        }
      });
    } catch (err) {
      console.error(err);
      displayMensajeToast('<p class="error">Error de conexión al editar el permiso.</p>');
    }
  }


  //ELIMINAR PERMISO
  async function eliminarPermiso(id, button) {

    // modal de confirmacion
    const ok = await modalConfirmarAccion({
        titulo: 'Eliminar permiso',
        mensaje: `
            <p>¿Seguro que quieres eliminar el permiso #${id}?</p>
            <ul>
                <li>Esta acción eliminará el registro del permiso.</li>
                <li>Se recomienda el desactivar el permiso.</li>
                <li>Si hay uno o varios roles usandolo, podría causar problemas de acceso.</li>
                <li>Esta acción no se puede deshacer. Por ello, realiza una anotación de los datos del permiso a eliminar.</li>
            </ul>
        `,
        txtConfirmar: 'Sí, eliminar',
        tipo: 'eliminar-desactivar'
    });

    if (!ok) return;

    try {
      setLoadingTabla(button, true);

      const r = await fetchJson(buildUrl(cfgTabPermisos.urlEliminarPermiso, id), {
        method: 'DELETE',
      });

      if (!r.ok) return;

      await cargar();
      if (typeof displayMensajeToast === 'function') {
        displayMensajeToast(`<p class="exito">${escapeHtml(r.data?.message || 'Permiso eliminado correctamente.')}</p>`);
      }

    } catch (err) {
      console.error(err);
      displayMensajeToast('<p class="error">Error de conexión al eliminar el permiso.</p>');
    } finally {
      setLoadingTabla(button, false);
    }
  }


  //==================LISTENER PARA LA TABLA==================
  tabla.addEventListener('click', (ev) => {
    const btnEditar = ev.target.closest('.btn-editar-permiso');
    if (btnEditar) {
      editarPermiso(btnEditar.dataset.id);
      return;
    }

    const btnEliminar = ev.target.closest('.btn-eliminar-permiso');
    if (btnEliminar) {
      eliminarPermiso(btnEliminar.dataset.id, btnEliminar);
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

      const url = new URL(cfgTabPermisos.urlExportarPermisosExcel, window.location.origin);
      const buscar = inputBuscar.value.trim();

      if (buscar) {
        url.searchParams.set('buscar', buscar);
      }

      window.location.href = url.toString();

    } catch (error) {
      console.error(error);
      displayMensajeToast('<p class="error">No se pudo iniciar la exportación del Excel de permisos.</p>');
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