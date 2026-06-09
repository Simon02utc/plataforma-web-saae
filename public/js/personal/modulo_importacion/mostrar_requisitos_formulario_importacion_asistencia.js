document.addEventListener('DOMContentLoaded', () => {
const tipo = document.getElementById('tipo-importacion');
const boxPeriodo = document.getElementById('box-periodo');
const info1 = document.getElementById('info-requisitos-1');
const periodo = document.getElementById('periodo-id');

function actualizarFormulario() {
    const valor = tipo.value;

    if (valor === 'COMPLETA') {
        boxPeriodo.style.display = 'none';
        if (periodo) periodo.value = '';

        info1.innerHTML = `
            <p><b>Requisitos de la importación completa:</b></p>
            <ul>
                <li>
                    Si el archivo es <b>.xls</b> o <b>.xlsx</b>: Debe contener las hojas
                    <b>“Reporte de Turnos”</b> y <b>“Reporte de Asistencia”</b>. El <b>periodo</b> se detecta automáticamente desde la hoja <b>“Reporte de Turnos”</b>.
                </li>
                <li>
                    Si el archivo es <b>.csv</b>, debe contener la información necesaria para identificar
                    el periodo, los días esperados, los alumnos, y las marcaciones de asistencia.
                </li>
                <li>
                    Este modo se recomienda cuando vas a registrar un <b>periodo nuevo</b>
                    o cuando quieres importar tanto la estructura de días esperados como las asistencias, que junto a ello estan los alumnos esperados.
                </li>
            </ul>
            <p>
                <b>Importante:</b> Usa este modo cuando el periodo con sus dias marcados, y las marcaciones de asistencias de los alumnos esperados no existan en la plataforma 
                o cuando deseas hacer una carga completa desde cero.
            </p>
        `;

    } else if (valor === 'SOLO_TURNOS') {
        boxPeriodo.style.display = 'none';
        if (periodo) periodo.value = '';

        info1.innerHTML = `
            <p><b>Requisitos de solo turnos .</b></p>
            <p>
                Este modo permitirá importar únicamente la estructura de turnos o calendario
                sin cargar asistencias.
            </p>

            <ul>
                <li>
                    Si el archivo es <b>.xls</b> / <b>.xlsx</b>: Debe contener la hoja <b>“Reporte de Turnos”</b>.
                </li>
                <li>
                    Si el archivo es <b>.csv</b>: Debe contener el periodo con sus dias marcados y ademas los alumnos esperados. Debe de ser compatible con el parser configurado para el reloj seleccionado.
                </li>
                <li>
                    Este modo solo importa el <b>periodo con sus dias marcados y alumnos esperados.</b> 
                </li>
            </ul>
            <p>
                <b>Importante:</b> Usa solo este modo para el registro de Periodos y sus dias marcados, junta a los alumnos esperados.
            </p>
        `;

    } else if (valor === 'SOLO_ASISTENCIA') {
        boxPeriodo.style.display = '';

        info1.innerHTML = `
            <p><b>Requisitos de solo asistencias:</b></p>
            <ul>
                <li>
                    Si el archivo es <b>.xls</b> / <b>.xlsx</b>: Debe contener la hoja <b>“Reporte de Asistencia”</b>.
                </li>
                <li>
                    Si el archivo es <b>.csv</b>: Debe contener los alumnos esperados con sus marcaciones de asistencia y ser compatible con el parser configurado para el reloj seleccionado.
                </li>
                <li>
                    Debes seleccionar un <b>periodo existente</b>.
                </li>
                <li>
                    Este modo solo importa las <b>marcaciones de asistencia</b> y las relaciona
                    con la estructura que ya existe en la plataforma.
                </li>
            </ul>
            <p>
                <b>Importante:</b> Este modo solo funciona para periodos que ya tienen
                alumnos y fechas previamente registradas. Si el periodo es nuevo, utiliza primero
                <b>Solo turnos/calendario</b> o la <b>Importación completa</b>.
            </p>
        `;

    } else {
        boxPeriodo.style.display = 'none';
        if (periodo) periodo.value = '';

        info1.innerHTML = `
            <p>
                Selecciona un <b>tipo de importación</b> para ver sus requisitos
                y recomendaciones de uso.
            </p>
        `;
    }
}

tipo.addEventListener('change', actualizarFormulario);
actualizarFormulario();
});