<div class="modal-confirmacion" id="modalConfirmacion">
    <div class="contenedor-modal-confirmacion">

        <span class="btn-cerrar-modal-confirmacion" id="btnCerrarModalConfirmacion">&times;</span>

        <h2 id="modalConfirmacionTitulo">Confirmar acción</h2>
        <div class="mensaje" id="modalConfirmacionMensaje"></div>

        <div class="input-box" id="campoObservacionesModal" style="display:none;">
            <input  id="inputObservacionesModal" class="input-field" rows="3"  placeholder="Escribe una observación..."maxlength="500" autocomplete="off" require></input>
        </div>

        <div class="botones-modal-confirmacion">
            <button type="button" class="btn-cancelar-confirmacion" id="btnCancelarConfirmacion">
                Cancelar
            </button>
            <button type="button" class="btn-confirmar-accion" id="btnConfirmarAccion">
                Confirmar
            </button>
        </div>

    </div>
</div>