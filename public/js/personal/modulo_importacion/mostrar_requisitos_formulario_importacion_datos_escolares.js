document.addEventListener('DOMContentLoaded', () => {
const tipo = document.getElementById('tipo-importacion');
const info1 = document.getElementById('info-requisitos-1');

function actualizarFormulario() {
    const valor = tipo.value;

    if (valor === 'COMPLETA') {

        info1.innerHTML = `
            <p>El archivo Excel debe cumplir estrictamente con la plantilla oficial del Excel.</p>
            <ul>
                <li>
                    Si el archivo es <b>.xls</b> o <b>.xlsx</b>: Debe contener las hojas
                    <b>“MAESTRIA”</b> y/o <b>“DOCTORADO"</b>.
                </li>
                <li>
                    Columnas en posiciones oficiales
                </li>
                <li>
                    Valores de estatus y especialidad exactamente iguales al catálogo académico vigente.
                </li>
                <li>
                    No insertar encabezados alternos.
                </li>
                <li>
                    No usar abreviaturas raras en especialidad ni estatus.
                </li>
                <li>
                    No separar letras con espacios como I N S C R I T O.
                </li>
                <li>
                    No dejar filas de estudiantes sin número de control.
                </li>
                <li>
                    No combinar celdas.
                </li>
                <li>
                    No insertar observaciones dentro de la tabla.
                </li>   
            </ul>
            <p>
                <b>Importante:</b> Usa este modo cuando quieres registrar a estudiantes con sus datos escolares: Año de generación, Mes de ingreso, Numero de control y Nombre. Además de vincularlos automáticamente con su Área de especialidad y Estatus.
            </p>
        `;

    } else if (valor === 'SOLO_ESTUDIANTES') {

        info1.innerHTML = `
            <p><b>Requisitos de la importación de solo estudiantes:</b></p>
            <p>El archivo Excel debe cumplir estrictamente con la plantilla oficial del Excel.</p>
            <ul>
                <li>
                    Si el archivo es <b>.xls</b> o <b>.xlsx</b>: Debe contener las hojas
                    <b>“MAESTRIA”</b> y/o <b>“DOCTORADO"</b>.
                </li>
                <li>
                    Columnas en posiciones oficiales
                </li>
                <li>
                    Valores de estatus y especialidad exactamente iguales al catálogo académico vigente.
                </li>
                <li>
                    No insertar encabezados alternos.
                </li>
                <li>
                    No usar abreviaturas raras en especialidad ni estatus.
                </li>
                <li>
                    No separar letras con espacios como I N S C R I T O.
                </li>
                <li>
                    No dejar filas de estudiantes sin número de control.
                </li>
                <li>
                    No combinar celdas.
                </li>
                <li>
                    No insertar observaciones dentro de la tabla.
                </li>   
            </ul>
            <p>
                <b>Importante:</b> Usa este modo cuando quieres registrar a estudiantes con sus datos escolares: Año de generación, Mes de ingreso, Numero de control y Nombre. Además de vincularlos automáticamente con su Área de especialidad y Estatus.
            </p>
        `;

    } else {
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