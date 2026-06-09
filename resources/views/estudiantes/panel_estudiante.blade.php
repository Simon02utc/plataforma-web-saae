<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.estudiante.estructura_web_estudiante')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_panel_personal_estudiante.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Panel del estudiante | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="dashboard-personal-estudiante">

            <div class="dashboard-bienvenida">
                <div>
                    <h2>Bienvenido, {{ ucfirst(strtolower($estudiante->nombre_completo ?? 'Estudiante')) }}</h2>
                    <p>
                        Este panel resume tu asistencia, alertas y justificantes del periodo actual.
                    </p>
                </div>
            </div>

            <div class="dashboard-filtros">
                <form method="GET" action="{{ route('grup_estudiante.name_panel_estudiante') }}" id="form-filtros-dashboard">
                    
                    <div class="contenedor-filtros">
                            <!-- selector del periodo -->
                        <div class="dashboard-filtro-grupo ultimo-filtro-grupo">
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

                    </div>
                    
                    <!-- Botones -->
                    
                </form>
            </div>

            @if ($periodos->isEmpty())
                <div class="dashboard-sin-datos">
                    <h3><i class="ri-error-warning-line icono-advertencia"></i> Sin información disponible.</h3>
                    <p>Actualmente no tienes un periodo activo o asignado para mostrar estadísticas.</p>
                </div>
            @else

                <div class="dashboard-tarjetas">
                    <div class="tarjeta-dashboard">
                        <span>Días esperados:</span>
                        <strong>{{ $resumenAsistencia['total_dias'] }}</strong>
                    </div>

                    <div class="tarjeta-dashboard tarjeta-presente">
                        <span>Presentes:</span>
                        <strong>{{ $resumenAsistencia['presentes'] }}</strong>
                    </div>

                    <div class="tarjeta-dashboard tarjeta-falta">
                        <span>Faltas:</span>
                        <strong>{{ $resumenAsistencia['faltas'] }}</strong>
                    </div>

                    <div class="tarjeta-dashboard tarjeta-justificada">
                        <span>Justificadas:</span>
                        <strong>{{ $resumenAsistencia['faltas_justificadas'] }}</strong>
                    </div>

                    <div class="tarjeta-dashboard tarjeta-no-aplica">
                        <span>No aplica:</span>
                        <strong>{{ $resumenAsistencia['no_aplica'] }}</strong>
                    </div>

                    <div class="tarjeta-dashboard tarjeta-porcentaje">
                        <span>Asistencia:</span>
                        <strong>{{ $resumenAsistencia['porcentaje'] }}%</strong>
                    </div>
                </div>


                <!-- GRAFICAS -->
                <div class="dashboard-grid-graficas">
                    <div class="contenedor-grafica-dashboard">
                        <h3>Resumen de asistencia</h3>
                        <canvas id="graficaResumenAsistenciaEstudiante"></canvas>
                    </div>

                    <div class="contenedor-grafica-dashboard">
                        <h3>Asistencia por día</h3>
                            <canvas id="graficaAsistenciaDiasEstudiante"></canvas>
                    </div>
                </div>


                <!-- MONITOREO GENERAL -->
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
                                            <div class="mini-scroll">
                                                <p>{{ $alerta['tipo_alerta_texto'] }}:
                                                    <span>{{ optional($alerta->fecha_referencia)->format('d/m/Y') }}</span>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="contenedor-badges-estado">
                                            <span class="badge-estado badge-{{ strtolower($alerta->estado) }}">
                                                {{ ucfirst(strtolower($alerta->estado)) }}
                                            </span>
                                        </div>
                                    </div>

                                @empty
                                    <p class="texto-vacio-item-lista-dashboard"> No tienes alertas registradas.</p>
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
                                                <p>Folio: {{ $justificante->folio ?? 'Sin folio' }}</p>
                                                <span>Enviado el: {{ $justificante->created_at?->format('d/m/Y') }}</span>
                                            </div>

                                            <div class="contenedor-badges-estado">
                                                <span class="badge-estado badge-{{ strtolower($justificante->estado) }}">
                                                    {{ $justificante->estado }}
                                                </span>
                                            </div>

                                        </div>
                                    @empty
                                        <p class="texto-vacio-item-lista-dashboard">No tienes justificantes registrados.</p>
                                    @endforelse
                                </div>
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
        {!! @json_encode([
            'graficaAsistencia' => $graficaAsistencia ?? [],
            'graficaAsistenciaDias' => $graficaAsistenciaDias ?? [],
        ]) !!}
    </script>

    <script src="{{ asset('js/estudiantes/panel_estudiante/funcion_panel_estudiante.js') }}"></script>
@endpush