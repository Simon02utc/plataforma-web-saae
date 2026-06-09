document.addEventListener('DOMContentLoaded', () => {
  initTablaRelojesChecadores();
  initTablaParsers();
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


//======TABLA DE RELOJES (LISTA, VER, EDITAR Y ELIMINAR)======
function initTablaRelojesChecadores() {
  const tabla = document.getElementById('tabla-listado-relojes');
  const btnRefrescar = document.getElementById('btn-refrescar-tabla-relojes');
  const inputBuscar = document.getElementById('input-buscar-relojes');
  const btnBuscar = document.getElementById('btn-buscar-relojes');

  if (!tabla || !btnRefrescar || !inputBuscar || !btnBuscar) return;

  const tbody = tabla.querySelector('tbody');

  const cfgTabRelojes = {
    urlListadoRelojes: tabla.dataset.urlTablaListadoRelojes,
    urlVerParsersReloj: tabla.dataset.urlTablaVerParsersReloj,
    urlVerReloj: tabla.dataset.urlTablaVerReloj,
    urlEditarReloj: tabla.dataset.urlTablaEditarReloj,
    urlEliminarReloj: tabla.dataset.urlTablaEliminarReloj,
  };

  let aborter = null;


  //PINTADO DE LA TABLA DE RELOJES
  function renderRows(items) {
    if (!items || items.length === 0) {
      const texto = inputBuscar.value.trim()
        ? 'No se encontraron relojes con esa búsqueda.'
        : 'Sin relojes registrados.';

      tbody.innerHTML = `<tr><td colspan="9" class="td-estado-tabla">${texto}</td></tr>`;
      return;
    }

    tbody.innerHTML = items.map((it) => {
      const totalParsers = Number(it.total_parsers ?? 0);
      const estadoTexto = it.estado_reloj ? 
        '<span class="estado-activado"><i class="fa-solid fa-square-check"></i> Activado</span>' :
        '<span class="estado-desactivado"><i class="fa-solid fa-square-xmark"></i> Desactivado</span>';

      return `  
        <tr>
          <td class="td-id">${it.id}</td>
          <td class="td-nombre">${escapeHtml(it.nombre_reloj || '—')}</td>
          <td class="td-descripcion">${escapeHtml(it.ubicacion_reloj || '—')}</td>
          <td class="td-listado-informacion">
            <div class="contenedor-td-listado-informacion">
              ${totalParsers} parser${totalParsers === 1 ? '' : 's'}
            </div>

            <div class="botones-tabla">
              <button type="button" class="btn-ver-detalles-item" data-id="${it.id}">
                <i class="fa-regular fa-eye"></i>
              </button>
            </div>
          </td>
          <td class="td-estado">${estadoTexto}</td>
          <td class="td-fecha">${fmtFecha(it.creado_en)}</td>
          <td class="td-fecha">${fmtFecha(it.editado_en)}</td>
          <td>
            <div class="botones-tabla">
              <button type="button" class="btn-editar-item btn-editar-reloj" data-id="${it.id}">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
            </div>
          </td>
          <td>
            <div class="botones-tabla">
              <button type="button" class="btn-eliminar-item btn-eliminar-reloj" data-id="${it.id}">
                <i class="fa-solid fa-trash"></i>
                <span class="spinner-tabla"></span>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  //CARGA DEL PINTADO DE LA TABLA Y LA FUNCION DE BUSCAR RELOJES
  async function cargar() {
    tbody.innerHTML = `<tr><td colspan="9" class="td-estado-tabla">Cargando contenido…</td></tr>`;

    if (aborter) aborter.abort();
    aborter = new AbortController();

    try {
      const url = new URL(cfgTabRelojes.urlListadoRelojes, window.location.origin);
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
      displayMensajeToast('<p class="error">Error de conexión al cargar los relojes.</p>');
    }
  }


  //VER PARSERS DEL RELOJ
  async function verDetalle(id) {
    try {
      const r = await fetchJson(buildUrl(cfgTabRelojes.urlVerParsersReloj, id));
      if (!r.ok) return;

      const d = r.data?.data;
      if (!d) {
        displayMensajeToast('<p class="error">No se pudo obtener la información de los parsers del reloj.</p>')
        return;
      }

      const parsersHtml = (d.parsers_del_reloj && d.parsers_del_reloj.length)
        ? `<ul>
          ${d.parsers_del_reloj.map(p => `
            <li>${escapeHtml(p.nombre_parser)} <span class="informacion-extra-li-modal-detalles">(${escapeHtml(p.clave)})</span></li>
          `).join('')}
        </ul>`
        : '<p class="info-detalle">Este reloj no tiene un parser asignado.</p>';

      displayModalDetalles(`
        <h3 class="titulo-modal-detalles">Parser del reloj #${d.id}</h3>

          <div class="scroll-contenido-modal-detalles">

              <div class="contenedor-detalles">
                <div class="caja-detalle">
                  <p class="nombre-detalle">Reloj:</p>
                  <p class="info-detalle">${escapeHtml(d.nombre_reloj || '—')}</p>
                </div>

                <div class="caja-detalle">
                  <p class="nombre-detalle">Ubicación:</p>
                  <p class="info-detalle">${escapeHtml(d.ubicacion_reloj || '—')}</p>
                </div>

                <div class="caja-detalle caja-detalle-resultados">
                  <p class="nombre-detalle">Su parser (total de ${d.total_parsers ?? 0}):</p>
                  <p class="info-detalle"></p>
                  <div class="contenedor-listado-informacion-modal-detalles">
                    ${parsersHtml}
                  </div>
                </div>
              </div>

          </div>
      `);

    } catch (err) {
      console.error(err);
      displayMensajeToast('<p class="error">Error de conexión al ver los parsers del reloj.</p>');
    }
  }


  //FORMULARIO DE EDITAR RELOJ
  async function editarReloj(id) {
    try {
      const r = await fetchJson(buildUrl(cfgTabRelojes.urlVerReloj, id));
      if (!r.ok) return;

      const d = r.data?.data;
      if (!d) {
        displayMensajeToast('<p class="error">No se pudo obtener la información del reloj.</p>');
        return;
      }


      const activoChecked = d.estado_reloj ? 'checked' : '';

      const parserSeleccionadoId = Number(d.parser_seleccionado_id ?? 0);

      const opcionesParsers = Array.isArray(d.parsers_disponibles) && d.parsers_disponibles.length
        ? d.parsers_disponibles.map(parser => {
            const parserId = Number(parser.id);
            const selected = parserId === parserSeleccionadoId ? 'selected' : '';
            return `
              <option class="option-input-field-select" value="${parserId}" ${selected}>
                ${escapeHtml(parser.nombre_parser)} <!-- ($ {escapeHtml(parser.clave)})  que sea mas chica la clave-->
              </option>
            `;
          }).join('')
        : '<option class="option-input-field-select" value="">No hay parsers disponibles</option>';

      displayModalFormularioEditar(`
        <form id="form-editar-rol" class="form-editar-contenido">
          
          <div class="form-title">
              <span>Editar reloj #${d.id}</span>
          </div>
          
          <div class="form-inputs">
            <div class="scroll-editar">

              <div class="input-box-editar">
                <label class="nombre-input">Nombre:</label>
                <input class="input-field" type="text" name="nombre" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: Director Tesis" value="${escapeHtml(d.nombre_reloj || '')}" autocomplete="off" autocapitalize="words" disabled  required>
              </div>

              <div class="input-box-editar">
                <label class="nombre-input">Ubicación:</label>
                <textarea class="input-field" name="ubicacion" maxlength="500" title="Solo letras, espacios, y signos de puntuacion" autocomplete="off" disabled>${escapeHtml(d.ubicacion || '')}</textarea>
              </div>

              <div class="input-box-editar">
                <label class="nombre-input" for="parser-id">Parser asignado:</label>
                <select class="input-field" name="parser_id" id="parser-id" required disabled>
                  <option class="option-input-field-select" value="">-- Selecciona un parser --</option>
                  ${opcionesParsers}
                </select>
              </div>

              <div class="input-box">
                  <label class="input-field switch-estado">
                      <input type="checkbox" name="activo" value="1" ${activoChecked} disabled>
                      <span class="slider-switch-estado"></span>
                      <span class="texto-switch-estado">Activar reloj</span>
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

      const form = document.getElementById('form-editar-rol');
      if (!form) return;


      //====================HABILITAR EDICION====================
      //En los <input>, <textarea> y el <input type="checkbox"> tienen "disabled"
      const btnEditarReloj = form.querySelector('.btn-editar-form');
      const btnCancelarEdicionReloj = form.querySelector('.btn-cancelar-borrar');
      const btnGuardarEdicionReloj = form.querySelector('.btn-guardar-enviar');

      btnEditarReloj?.addEventListener('click', () => {
        form.querySelectorAll(`
            input[name="nombre"], 
            textarea[name="ubicacion"],
            select[name="parser_id"],
            input[name="activo"]
          `).forEach(el => el.disabled = false);

        btnEditarReloj.style.display = 'none';
        btnCancelarEdicionReloj.style.display = 'inline-flex';
        btnGuardarEdicionReloj.style.display = 'inline-flex';

        form.querySelector('[name="nombre"]')?.focus();//Enfocarse a ese input
      });

      //=====================CANCELAR EDICION=====================
      btnCancelarEdicionReloj?.addEventListener('click', () => {
          form.reset();

          form.querySelectorAll(`
              input[name="nombre"], 
              textarea[name="ubicacion"],
              select[name="parser_id"],
              input[name="activo"]
          `).forEach(el => el.disabled = true);

          btnEditarReloj.style.display = 'inline-flex';
          btnCancelarEdicionReloj.style.display = 'none';
          btnGuardarEdicionReloj.style.display = 'none';
      });
      //==========================================================


      form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const button = form.querySelector('.btn-guardar-enviar');
        setLoadingFormulario(button, true);

        try {

          const payload = {
            nombre: form.querySelector('[name="nombre"]').value.trim(),
            ubicacion: form.querySelector('[name="ubicacion"]').value.trim(),
            parser_id: form.querySelector('[name="parser_id"]').value.trim(),
            activo: form.querySelector('[name="activo"]').checked ? 1 : 0,
          };

          const rr = await fetchJson(buildUrl(cfgTabRelojes.urlEditarReloj, id), {
            method: 'PUT',
            body: payload,
          });

          if (!rr.ok) return;

          await cargar();
          if (typeof displayMensajeToast === 'function') {
            cerrarModalFormularioEditar();
            displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Reloj actualizado correctamente.')}</p>`);
          }

        } catch (error) {
          console.error(error);
          displayMensajeToast(`<p class="error">Error de conexión al actualizar el reloj. <span class="ext">Revisa tu servidor o recarga la página.</span></p>`);
        } finally {
          setLoadingFormulario(button, false);
        }
      });
    } catch (err) {
      console.error(err);
      displayMensajeToast('<p class="error">Error de conexión al editar el reloj.</p>');
    }
  }


  //ELIMIANR UN ROL
  async function eliminarReloj(id, button) {

    // modal de confirmacion
    const ok = await modalConfirmarAccion({
        titulo: 'Eliminar reloj',
        mensaje: `
            <p>¿Seguro que quieres eliminar el reloj checador #${id}?</p>
            <ul>
                <li>Esta acción eliminará el registro del reloj.</li>
                <li>Si hay personal que ocupa ese reloj, podría causar problemas en el modulo de importación de asistencia.</li>
                <li>Si el reloj tienen registros de importaciones, no se podra eliminar debido a que se utiliza en auditoría.</li>
                <li>Se recomienda el desactivar el reloj.</li>
                <li>Esta acción no se puede deshacer. Por ello, verificala por ultima vez.</li>
            </ul>
        `,
        txtConfirmar: 'Sí, eliminar',
        tipo: 'eliminar-desactivar'
    });

    if (!ok) return;

    try {
      setLoadingTabla(button, true);

      const r = await fetchJson(buildUrl(cfgTabRelojes.urlEliminarReloj, id), {
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

    const btnEditar = ev.target.closest('.btn-editar-reloj');
    if (btnEditar) {
      editarReloj(btnEditar.dataset.id);
      return;
    }

    const btnEliminar = ev.target.closest('.btn-eliminar-reloj');
    if (btnEliminar) {
      eliminarReloj(btnEliminar.dataset.id, btnEliminar);
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



//=====TABLA DE PARSERS (LISTA, VER, EDITAR Y ELIMINAR)=====
function initTablaParsers() {
  const tabla = document.getElementById('tabla-listado-parsers');
  const btnRefrescar = document.getElementById('btn-refrescar-tabla-parsers');
  const inputBuscar = document.getElementById('input-buscar-parsers');
  const btnBuscar = document.getElementById('btn-buscar-parsers');

  if (!tabla || !btnRefrescar || !inputBuscar || !btnBuscar) return;

  const tbody = tabla.querySelector('tbody');

  const cfgTabParsers = {
    urlListadoParsers: tabla.dataset.urlTablaListadoParsers,
    urlVerParser: tabla.dataset.urlTablaVerParser,
    urlEditarParser: tabla.dataset.urlTablaEditarParser,
    urlEliminarParser: tabla.dataset.urlTablaEliminarParser,
  };

  let aborter = null;


  //PINTADO DE LA TABLA DE PARSERS
  function renderRows(items) {
    if (!items || items.length === 0) {
      const texto = inputBuscar.value.trim()
        ? 'No se encontraron parsers con esa búsqueda.'
        : 'Sin parsers registrados.';

      tbody.innerHTML = `<tr><td colspan="9" class="td-estado-tabla">${texto}</td></tr>`;
      return;
    }

    tbody.innerHTML = items.map((it) => {
      const estadoTexto = it.estado_parser ? 
        '<span class="estado-activado"><i class="fa-solid fa-square-check"></i> Activado</span>' :
        '<span class="estado-desactivado"><i class="fa-solid fa-square-xmark"></i> Desactivado</span>';
        return `
        <tr>
          <td class="td-id">${it.id}</td>
          <td class="td-clave">${escapeHtml(it.clave || '—')}</td>
          <td class="td-nombre">${escapeHtml(it.nombre || '—')}</td>
          <td class="td-descripcion">${escapeHtml(it.descripcion || '—')}</td>
          <td class="td-estado">${estadoTexto}</td>
          <td class="td-fecha">${fmtFecha(it.creado_en)}</td>
          <td class="td-fecha">${fmtFecha(it.editado_en)}</td>
          <td>
            <div class="botones-tabla">
              <button type="button" class="btn-editar-item btn-editar-parser" data-id="${it.id}">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
            </div>
          </td>
          <td>
            <div class="botones-tabla">
              <button type="button" class="btn-eliminar-item btn-eliminar-parser" data-id="${it.id}">
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
    tbody.innerHTML = `<tr><td colspan="9" class="td-estado-tabla">Cargando contenido…</td></tr>`;

    if (aborter) aborter.abort();
    aborter = new AbortController();

    try {
      const url = new URL(cfgTabParsers.urlListadoParsers, window.location.origin);
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
      displayMensajeToast('<p class="error">Error de conexión al cargar los parsers.</p>');
    }
  }


  //FORMULARIO DE EDITAR PARSER
  async function editarParser(id) {
    try {
      const r = await fetchJson(buildUrl(cfgTabParsers.urlVerParser, id));
      if (!r.ok) return;

      const d = r.data?.data;
      if (!d) {
        displayMensajeToast('<p class="error">No se pudo obtener la información del parser.</p>');
        return;
      }


      const activoChecked = d.estado_parser ? 'checked' : '';

      displayModalFormularioEditar(`
        <form id="form-editar-parser" class="form-editar-contenido">

          <div class="form-title">
              <span>Editar parser #${d.id}</span>
          </div>

          <div class="form-inputs">
            <div class="scroll-editar">

              <div class="input-box-editar">
                <label class="nombre-input">Clave:</label>
                <input class="input-field" type="text" name="clave" pattern="^[a-z]+(?:_[a-z]+)*$" title="Solo letras minúsculas y guion bajo '_' como separador. Ejemplo: reloj_on_the_minute" value="${escapeHtml(d.clave || '')}" autocomplete="off" autocapitalize="none" disabled required>
              </div>

              <div class="input-box-editar">
                <label class="nombre-input">Nombre:</label>
                <input class="input-field" type="text" name="nombre" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: Parser del Reloj On The Minute" value="${escapeHtml(d.nombre || '')}" autocomplete="off" autocapitalize="words" disabled required>
              </div>

              <div class="input-box-editar">
                <label class="nombre-input">Descripción:</label>
                <textarea class="input-field" maxlength="500" title="Solo letras, espacios, y signos de puntuacion" name="descripcion" autocomplete="off" disabled>${escapeHtml(d.descripcion || '')}</textarea>
              </div>

              <div class="input-box">
                  <label class="input-field switch-estado">
                      <input type="checkbox" name="activo" value="1" ${activoChecked} disabled>
                      <span class="slider-switch-estado"></span>
                      <span class="texto-switch-estado">Activar parser</span>
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

      const form = document.getElementById('form-editar-parser');
      if (!form) return;


      //====================HABILIDAR EDICION====================
      //En los <input>, <textarea> y el <input type="checkbox"> tienen "disabled"
      const btnEditarParser = form.querySelector('.btn-editar-form');
      const btnCancelarEdicionParser = form.querySelector('.btn-cancelar-borrar');
      const btnGuardarEdicionParser = form.querySelector('.btn-guardar-enviar');

      btnEditarParser?.addEventListener('click', () => {
        form.querySelectorAll(`
            input[name="clave"], 
            input[name="nombre"], 
            textarea[name="descripcion"],
            input[name="activo"]
          `).forEach(el => el.disabled = false);

        btnEditarParser.style.display = 'none';
        btnCancelarEdicionParser.style.display = 'inline-flex'
        btnGuardarEdicionParser.style.display = 'inline-flex';

        form.querySelector('[name="clave"]')?.focus();//Enfocarse a ese input
      });

      //=====================CANCELAR EDICION=====================
      btnCancelarEdicionParser?.addEventListener('click', () => {
          form.reset();

          form.querySelectorAll(`
              input[name="clave"], 
              input[name="nombre"], 
              textarea[name="descripcion"],
              input[name="activo"]
          `).forEach(el => el.disabled = true);

          btnEditarParser.style.display = 'inline-flex';
          btnCancelarEdicionParser.style.display = 'none';
          btnGuardarEdicionParser.style.display = 'none';
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

          const rr = await fetchJson(buildUrl(cfgTabParsers.urlEditarParser, id), {
            method: 'PUT',
            body: payload,
          });

          if (!rr.ok) return;

          await cargar();
          if (typeof displayMensajeToast === 'function') {
            cerrarModalFormularioEditar();
            displayMensajeToast(`<p class="exito">${escapeHtml(rr.data?.message || 'Parser actualizado correctamente.')}</p>`);
          }

        } catch (error) {
          console.error(error);
          displayMensajeToast(`<p class="error">Error de conexión al actualizar el parser.</p><p class="ext">Revisa tu servidor o recarga la página.</p>`);
        } finally {
          setLoadingFormulario(button, false);
        }
      });
    } catch (err) {
      console.error(err);
      displayMensajeToast('<p class="error">Error de conexión al editar el parser.</p>');
    }
  }


  //ELIMINAR PERMISO
  async function eliminarParser(id, button) {

    // modal de confirmacion
    const ok = await modalConfirmarAccion({
        titulo: 'Eliminar parser',
        mensaje: `
            <p>¿Seguro que quieres eliminar el parser #${id}?</p>
            <ul>
                <li>Esta acción eliminará el registro del parser.</li>
                <li>Si hay uno o varios relojes usandolo, podría causar graves problemas en el modulo de importación.</li>
                <li>Los parsers son de suma importancia para los relojes checadores, ya que con ellos se extraen los datos de su archivo.</li>
                <li>Se recomienda el desactivarlo.</li>
                <li>Esta acción no se puede deshacer. Por ello, realiza una anotación de los datos del parser a eliminar.</li>
            </ul>
        `,
        txtConfirmar: 'Sí, eliminar',
        tipo: 'eliminar-desactivar'
    });

    if (!ok) return;

    try {
      setLoadingTabla(button, true);

      const r = await fetchJson(buildUrl(cfgTabParsers.urlEliminarParser, id), {
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
    const btnEditar = ev.target.closest('.btn-editar-parser');
    if (btnEditar) {
      editarParser(btnEditar.dataset.id);
      return;
    }

    const btnEliminar = ev.target.closest('.btn-eliminar-parser');
    if (btnEliminar) {
      eliminarParser(btnEliminar.dataset.id, btnEliminar);
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