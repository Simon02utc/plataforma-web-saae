<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_panel_personal_estudiante.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Inicio del administrador | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')
    <div class="contenedor-principal-seccion">

        <div class="dashboard-personal-estudiante">

            <div class="dashboard-bienvenida">
                <div class="">
                    <h2>Bienvenido, {{ $personal->nombre }} {{ $personal->apellidos }}</h2>
                    <p>
                        @if ($esAdmin)
                            Este panel muestra el panorama general de asistencia, alertas y justificantes.
                        @elseif ($rolIdSeleccionado && $rolesConEstudiantes->isNotEmpty())
                            Mostrando estudiantes del rol:
                            <strong>{{ $rolesConEstudiantes->firstWhere('id', $rolIdSeleccionado)?->nombre ?? '—' }}</strong>
                        @else
                            Este panel muestra la información de todos tus estudiantes asignados.
                        @endif
                    </p>
                </div>

                <div class="botones-accion-dashboard">
                    <!-- { { (!$hayPeriodos || !$tieneEstudiantesConDatos) ? 'disabled' : '' } } -->
                    <button type="button" class="btn-accion-dashboard btn-exportar-datos-excel"  id="btn-exportar-datos-excel">
                        <span><i class="fa-solid fa-file-excel"></i> Exportar Excel</span>
                        <span class="spinner-dashboard-botones"></span>
                        <span class="texto-spinner-dashboard-botones">Exportando</span>
                    </button>
                </div>

            </div>


            <div class="dashboard-filtros">
                <form method="GET" action="{{ route('grup_personal.name_panel_personal') }}" id="form-filtros-dashboard">
                    
                    <div class="contenedor-filtros">
                            <!-- selector del periodo -->
                        <div class="dashboard-filtro-grupo">
                            <label>Periodo</label>
                            <select name="periodo_id" id="periodo_id" class="select-filtro-dashboard">
                                <option class="option-input-field-select" value="" @selected(!$periodoIdSeleccionado)>
                                    Todos los periodos
                                </option>
                                @forelse ($periodos as $itemPeriodo)
                                    <option class="option-input-field-select" value="{{ $itemPeriodo->id }}" @selected((int) $periodoIdSeleccionado === (int) $itemPeriodo->id)>
                                        {{ $itemPeriodo->nombre }}
                                    </option>
                                @empty
                                    <option value="">Sin periodos</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="dashboard-filtro-grupo ultimo">
                            <label>Mi rol</label>

                            <!-- Desabilitar si es administrador -->
                            <select name="rol_id" id="rol_id" class="select-filtro-dashboard" @disabled($esAdmin || $rolesConEstudiantes->isEmpty())>

                                @if ($esAdmin)
                                    <option value="">
                                        Administrador (todos los roles)
                                    </option>
                                @elseif ($rolesConEstudiantes->isEmpty())
                                    <option value="">
                                        Sin roles asignados
                                    </option>
                                @else

                                    <option class="option-input-field-select" value="" @selected(!$rolIdSeleccionado)>
                                        Todos mis estudiantes
                                    </option>

                                    @foreach ($rolesConEstudiantes as $rol)

                                        <option class="option-input-field-select" value="{{ $rol->id }}" @selected((int) $rolIdSeleccionado === (int) $rol->id)>
                                            {{ $rol->nombre }}
                                        </option>

                                    @endforeach

                                @endif

                            </select>
                        </div>

                        <div class="dashboard-filtro-grupo">
                            <label>Especialidad</label>
                            <select name="area_id" class="select-filtro-dashboard">
                                <option class="option-input-field-select" value="">Todas</option>

                                @foreach($areasEspecialidad as $area)
                                    <option class="option-input-field-select" value="{{ $area->id }}"
                                        @selected((int) $areaIdSeleccionada === (int) $area->id)>

                                        {{ $area->nombre }}

                                    </option>
                                @endforeach
                            </select>
                        </div>


                        <div class="dashboard-filtro-grupo">
                            <label>Estatus</label>
                            <select name="estatus_id" class="select-filtro-dashboard">

                                <option class="option-input-field-select" value="">Todos</option>
                                @foreach($estatusEscolares as $estatus)
                                    <option class="option-input-field-select" value="{{ $estatus->id }}"
                                        @selected((int) $estatusIdSeleccionado === (int) $estatus->id)>

                                        {{ $estatus->nombre }}

                                    </option>
                                @endforeach
                            </select>
                        </div>


                        <div class="dashboard-filtro-grupo ultimo-filtro-grupo">
                            <label>Estudiante</label>
                            <div class="contenedor-buscador-informacion">
                                <input  type="text" id="input-buscar-estudiante" class="select-filtro-dashboard filtro-input-field-select" value="{{ $nombreEstudianteSeleccionado }}" placeholder="Buscar por No. de control o Nombre" autocomplete="off">

                                <input type="hidden" id="filtro-estudiante" name="estudiante_id" value="{{ $estudianteId }}">

                                <div id="resultados-busqueda-estudiante" class="contenedor-resultados-busqueda" style="display: none;"></div>
                            </div>

                        </div>

                    </div>
                    
                    <div class="dashboard-filtro-botones">
                        <button type="button" class="btn-limpiar-filtros-dashboard" id="btn-limpiar-filtros-dashboard">
                            <span>Limpiar</span>
                            <span class="spinner-dashboard-botones"></span>
                            <span class="texto-spinner-dashboard-botones">Espera</span>
                        </button>

                        <button type="submit" class="btn-aplicar-filtros-dashboard" id="btn-aplicar-filtros-dashboard">
                            <span>Aplicar</span>
                            <span class="spinner-dashboard-botones"></span>
                            <span class="texto-spinner-dashboard-botones">Espera</span>
                        </button>
                    </div>

                </form>
            </div>
    

            @if (!$hayPeriodos)
                <div class="dashboard-sin-datos">
                    <h3><i class="ri-error-warning-line icono-advertencia"></i> Sin información disponible</h3>
                    <p>No hay periodos registrados para mostrar información.</p>
                </div>
            
            @elseif (!$tieneEstudiantesConDatos)
                <div class="dashboard-sin-datos">
                    <h3><i class="ri-user-search-line icono-advertencia"></i> Sin estudiantes con información escolar</h3>
                    <p>No hay estudiantes con datos escolares registrados para mostrar el dashboard.</p>
                </div>
            @else


                <div class="dashboard-tarjetas">
                    <div class="tarjeta-dashboard">
                        <span>Estudiantes visibles</span>
                        <strong>{{ $resumenGeneral['total_estudiantes'] }}</strong>
                    </div>

                    <div class="tarjeta-dashboard">
                        <span>Días esperados</span>
                        <strong>{{ $resumenGeneral['total_dias'] }}</strong>
                    </div>

                    <div class="tarjeta-dashboard tarjeta-presente">
                        <span>Días presentes</span>
                        <strong>{{ $resumenGeneral['presentes'] }}</strong>
                    </div>

                    <div class="tarjeta-dashboard tarjeta-falta">
                        <span>Faltas</span>
                        <strong>{{ $resumenGeneral['faltas'] }}</strong>
                    </div>

                    <div class="tarjeta-dashboard tarjeta-justificada">
                        <span>Faltas justificadas</span>
                        <strong>{{ $resumenGeneral['faltas_justificadas'] }}</strong>
                    </div>

                    <div class="tarjeta-dashboard tarjeta-no-aplica">
                        <span>No aplica</span>
                        <strong>{{ $resumenGeneral['no_aplica'] }}</strong>
                    </div>

                    <div class="tarjeta-dashboard tarjeta-porcentaje">
                        <span>Asistencia general</span>
                        <strong>{{ $resumenGeneral['porcentaje'] }}%</strong>
                    </div>
                </div>


                <!-- GRAFICAS -->
                <div class="dashboard-grid-graficas">
                    <div class="contenedor-grafica-dashboard">
                        <h3>Resumen de asistencia</h3>
                        <canvas id="graficaResumenAsistencia"></canvas>
                    </div>

                    <div class="contenedor-grafica-dashboard">
                        <h3>Asistencia por día</h3>
                        <canvas id="graficaAsistenciaDiasEstudiante"></canvas>
                    </div>

                    <div class="contenedor-grafica-dashboard ultimo">
                        <h3>Faltas por día</h3>
                        <canvas id="graficaFaltasDiasEstudiante"></canvas>
                    </div>
                </div>


                <div class="monitoreo-general">
                    <!-- <h3 class="titulo-monitoreo-general"><span>MONITOREO GENERAL</span></h3> -->

                    <div class="dashboard-grid-info">

                        <div class="contenedor-info-dashboard">
                            <h3>Alertas de asistencia</h3>

                            <div class="mini-resumen-dashboard">
                                <div class="caja-mini-resumen">
                                    <span>Pendientes</span>
                                    <strong>{{ $resumenAlertas['pendientes'] }}</strong>
                                </div>
                                <div class="caja-mini-resumen">
                                    <span>Atendidas</span>
                                    <strong>{{ $resumenAlertas['atendidas'] }}</strong>
                                </div>
                                <div class="caja-mini-resumen">
                                    <span>Cerradas</span>
                                    <strong>{{ $resumenAlertas['cerradas'] }}</strong>
                                </div>
                            </div>

                            <div class="scroll-contenedor-info-dashboard">
                                <div class="lista-dashboard">

                                    @forelse ($ultimasAlertas as $alerta)

                                        <div class="item-lista-dashboard">
                                            <div class="datos-item">
                                                <p>{{ $alerta['estudiante']?->nombre_completo ?? 'Sin nombre' }} 
                                                    <span>- {{ $alerta['estudiante']?->numero_control ?? 'N/A' }}</span>
                                                </p>

                                                <div class="mini-scroll">
                                                    <span>{{ $alerta['tipo_alerta_texto'] }}: {{ $alerta['fechas'] }}.</span>
                                                </div>
                                            </div>

                                            <div class="contenedor-badges-estado">

                                                @foreach ($alerta['estados'] as $estado)
                                                    <span class="badge-estado badge-{{ strtolower($estado['estado']) }}">
                                                        {{ $estado['total'] }}
                                                        {{ ucfirst(strtolower($estado['estado'])) }}(s)
                                                        <!-- ucfirst() primera letras en mayuscular, strtolower() convierte todo en minusculas-->
                                                    </span>
                                                @endforeach

                                            </div>
                                        </div>

                                    @empty
                                        <p class="texto-vacio-item-lista-dashboard">
                                            No hay alertas registradas.
                                        </p>
                                    @endforelse

                                </div>
                            </div>

                        </div>

                        <div class="contenedor-info-dashboard">
                            <h3>Justificantes</h3>

                            <div class="mini-resumen-dashboard">
                                <div class="caja-mini-resumen">
                                    <span>Pendientes</span>
                                    <strong>{{ $resumenJustificantes['pendientes'] }}</strong>
                                </div>
                                <div class="caja-mini-resumen">
                                    <span>Aprobados</span>
                                    <strong>{{ $resumenJustificantes['aprobados'] }}</strong>
                                </div>
                                <div class="caja-mini-resumen">
                                    <span>Rechazados</span>
                                    <strong>{{ $resumenJustificantes['rechazados'] }}</strong>
                                </div>
                            </div>

                            <div class="scroll-contenedor-info-dashboard">
                                <div class="lista-dashboard">
                                    @forelse ($ultimosJustificantes as $justificante)
                                        <div class="item-lista-dashboard">
                                            <div class="datos-item">
                                                <p>{{ $justificante->estudiante?->nombre_completo ?? 'Nombre no disponible' }} <span>- {{ $justificante->estudiante?->numero_control ?? '—' }}</span>
                                                </p>
                                                <span>
                                                    Folio: {{ $justificante->folio ?? 'Sin folio' }}
                                                    - {{ $justificante->created_at?->format('d/m/Y') }}
                                                </span>
                                            </div>
                                            
                                            <div class="contenedor-badges-estado">
                                                <span class="badge-estado badge-{{ strtolower($justificante->estado) }}">
                                                    {{ ucfirst( strtolower($justificante->estado)) }}
                                                    <!-- ucfirst() primera letras en mayuscular, strtolower() convierte todo en minusculas-->
                                                </span>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="texto-vacio-item-lista-dashboard">No hay justificantes registrados.</p>
                                    @endforelse
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="contenedor-info-dashboard">
                        <h3>Estudiantes con más faltas</h3>

                        <div class="scroll-contenedor-info-dashboard">
                            <div class="lista-dashboard">
                
                                @forelse ($topEstudiantesFaltas as $registro)
                                    <div class="item-lista-dashboard">
                                        <div class="datos-item">
                                            <p>{{ $registro->estudiante?->nombre_completo ?? 'Nombre no disponible' }} <span>- {{ $registro->estudiante?->numero_control ?? 'N/A' }}</span></p>
                                            <span>
                                                Área de especialidad:
                                                {{
                                                    ucfirst(strtolower(
                                                        $registro->estudiante
                                                        ?->estudiantesConDatosEscolares
                                                        ?->datoEscolarDeAreaEspecialidad
                                                        ?->nombre
                                                        ?? 'Sin área'
                                                    ))
                                                }}

                                                <!-- ucfirst() primera letras en mayuscular, strtolower() convierte todo en minusculas-->
                                            </span>
                                        </div>

                                        <div class="contenedor-badges-estado">
                                            <span class="badge-estado badge-pendiente">
                                                {{ $registro->total_faltas }} Falta(s)
                                            </span>
                                        </div>    
                                    </div>
                                @empty
                                    <p class="texto-vacio-item-lista-dashboard">No hay faltas registradas en este periodo.</p>
                                @endforelse

                            </div>
                        </div>

                    </div>

                </div>

            @endif
        </div>

    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script type="application/json" id="dashboard-data-json">
        {!! json_encode([
            'graficaResumenAsistencia' => $graficaResumenAsistencia ?? [],
            'graficaAsistenciaDias' => $graficaAsistenciaDias ?? [],
            'graficaFaltasDias' => $graficaFaltasDias ?? [],
        ]) !!}
    </script>

    <script type="application/json" id="estudiantes-dashboard-json">
        @json($estudiantesBusqueda)
    </script>

    <script src="{{ asset('js/personal/panel_personal/funcion_panel_personal.js') }}"></script>
@endpush