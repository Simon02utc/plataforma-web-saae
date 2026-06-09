<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'CENIDET | SAAE')

<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/estilos_inicio.css') }}">
@endpush

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <!--PRESENTACION DE LA PLATAFORMA-->
    <div class="hero">        
        <div class="hero-inner">
        
            <div class="hero-text">
        
                <div class="hero-chip">
                    <div class="chip-dot"></div>
                    <span>SAAE Activo</span>
                </div>
            
                <h1 class="hero-title">
                    Plataforma web de<br>
                    seguimiento y análisis<br>
                    de asistencia escolar (SAAE)</span>
                </h1>
            
                <p class="hero-desc">
                    Centraliza la asistencia académica, gestiona
                    justificantes y da seguimiento a alertas por inasistencias, todo en una sola plataforma.
                </p>
            
                <div class="hero-actions">
                    <a href="{{ route('grup_estudiante.name_login_estudiante') }}" class="btn-primary">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        Login del estudiante
                    </a>

                    <a href="{{ route('grup_personal.name_login_personal') }}" class="btn-primary">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        Login del personal
                    </a>
                </div>
            
                <div class="hero-stats" id="heroStats">
                    <div class="stat"><span class="stat-n">Automatización </span><span class="stat-l">Asistencia · Alertas</span></div>
                    <div class="stat-sep"></div>
                    <div class="stat"><span class="stat-n">Digitalización </span><span class="stat-l">Justificantes</span></div>
                    <div class="stat-sep"></div>
                    <div class="stat"><span class="stat-n">Disponibilidad y acceso </span><span class="stat-l">24/7 · Intuitivo · Adaptable</span></div>
                    <div class="stat-sep"></div>
                    <div class="stat"><span class="stat-n">Seguridad</span><span class="stat-l"> <span class="stat-l">Protección · Respaldo · Inmutable</span></span></div>
                </div>
                <div class="stats-dots" id="statsDots"></div>
        
            </div>
        
            <!--panel visual-->
            <div class="hero-visual">
        
            <div class="float-chip">
                <div class="float-icon">
                    <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                    <div class="float-val">Sincronizado</div>
                    <div class="float-lbl">Reloj checador</div>
                </div>
            </div>
        
            <div class="dash-card">
                <div class="dash-head">
                    <span class="dash-head-title">Asistencia reciente</span>
                    <span class="dash-badge">Periodo actual</span>
                </div>
        
                <div class="dash-row">
                    <div class="avatar av1">AM</div>
                    <div class="d-info"><div class="d-name">Ana Martínez</div><div class="d-sub">Cómputo Inteligente y...</div></div>
                    <span class="d-tag tag-ok">Presente</span>
                </div>
                <div class="dash-row">
                    <div class="avatar av2">LR</div>
                    <div class="d-info"><div class="d-name">Luis Ramírez</div><div class="d-sub">Tecnologías Inteligentes...</div></div>
                    <span class="d-tag tag-warn">Justificante</span>
                </div>
                <div class="dash-row">
                    <div class="avatar av3">CG</div>
                    <div class="d-info"><div class="d-name">Carlos García</div><div class="d-sub">Ingeniría de Software</div></div>
                    <span class="d-tag tag-err">Falta</span>
                </div>
                <div class="dash-row">
                    <div class="avatar av4">SP</div>
                    <div class="d-info"><div class="d-name">Sofía Pérez</div><div class="d-sub">Inteligencia Artificial</div></div>
                    <span class="d-tag tag-ok">Presente</span>
                </div>
        
                <div class="mini-chart">
                    <div class="bar" style="height:55%"></div>
                    <div class="bar" style="height:70%"></div>
                    <div class="bar" style="height:60%"></div>
                    <div class="bar active" style="height:92%"></div>
                    <div class="bar" style="height:76%"></div>
                    <div class="bar" style="height:63%"></div>
                    <div class="bar active" style="height:85%"></div>
                </div>
            </div>
        
            </div>
        </div>
        
        <div class="scroll-hint">
            <div class="scroll-mouse">
                <div class="scroll-wheel"></div>
            </div>
            <span class="scroll-text">Desplazar</span>
        </div>
        
    </div>


    <div class="content-presentacion-plataforma">

        <div class="content-introduccion">

            <div class="intro-div">
                <h3><span>¿Qué es la plataforma SAAE?</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        SAAE es una plataforma web desarrollada para apoyar la gestión, seguimiento y análisis de la asistencia escolar de los estudiantes. 
                        Permite centralizar la información académica, importar registros de asistencia desde el reloj checador institucional, consultar asistencias, gestionar justificantes y dar seguimiento a alertas generadas por inasistencias.
                    </p>

                    <img src="{{ asset('img_plataforma/que_es_la_plataforma_saae.png') }}" alt="que_es_la_plataforma_saae">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>¿Para qué sirve?</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        La plataforma permite al personal autorizado consultar la asistencia reciente e histórica de los estudiantes asignados, revisar justificantes enviados por los alumnos y dar seguimiento a alertas internas generadas por faltas.
                        <br>
                        También facilita la gestión de estudiantes, periodos, catálogos académicos, roles, permisos e importaciones de información escolar, ayudando a mantener un control más ordenado y confiable dentro de la institución.
                    </p>

                    <img src="{{ asset('img_plataforma/para_que_sirve.png') }}" alt="para_que_sirve">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>¿A quién va dirigida?</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        SAAE está dirigida al personal académico y administrativo encargado del seguimiento escolar, así como a los estudiantes que necesitan consultar su asistencia, revisar alertas y enviar justificantes de manera digital.
                        Su objetivo es mejorar la organización de la información y apoyar la toma de decisiones académicas.
                    </p>

                    <img src="{{ asset('img_plataforma/a_quien_ava_dirigido.png') }}" alt="a_quien_ava_dirigido">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Su disponibilidad</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        La plataforma está diseñada para ser utilizada desde un navegador web en computadoras y dispositivos móviles. 
                        Su acceso depende de las credenciales asignadas a cada usuario. Garantizando que cada persona visualice únicamente la información correspondiente a su perfil.
                    </p>

                    <img src="{{ asset('img_plataforma/su_disponibilidad.png') }}" alt="su_disponibilidad">
                </div>
            </div>

        </div>

        <div class="panel">

            <!-- Encabezado -->
            <div class="panel-header">
                <div class="header-dot"></div>
                <p class="header-label">SAAE en números</p>
            </div>
            
            <!-- Grid 2×2 -->
            <div class="stats-grid">
            
                <div class="stat-card c-blue">
                    <div class="stat-icon-wrap">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <p class="stat-value">+500</p>
                    <p class="stat-label">Estudiantes registrados</p>
                </div>
            
                <div class="stat-card c-green">
                    <div class="stat-icon-wrap">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>
                    <p class="stat-value">95%</p>
                    <p class="stat-label">Asistencia promedio</p>
                </div>
            
                <div class="stat-card c-amber">
                    <div class="stat-icon-wrap">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <p class="stat-value">Alertas automáticas</p>
                    <p class="stat-label">46 enviadas</p>
                </div>
            
                <div class="stat-card c-purple">
                    <div class="stat-icon-wrap">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                    <p class="stat-value">Justificantes</p>
                    <p class="stat-label">40 faltas justificadas</p>
                </div>
            
            </div>
            
            <!-- Barra de asistencia general -->
            <div class="progress-card">
                <div class="progress-top">
                    <span class="progress-title">Asistencia institucional general</span>
                    <span class="progress-pct" id="pct">0%</span>
                </div>
                    <div class="progress-track">
                        <div class="progress-fill" id="bar"></div>
                    </div>
                <div class="progress-sub">
                    <span>142 presentes del periodo actual</span>
                    <span>18 faltas</span>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="panel-footer">
                <p class="footer-badge">
                        Inicia sesión para ver la información completa .
                </p>
                <p class="footer-inst">Cenidet / TecNM</p>
            </div>
            
            </div>
            
            <script>
            window.addEventListener('load', () => {
                const target = 88;
                const bar = document.getElementById('bar');
                const pct = document.getElementById('pct');
                setTimeout(() => {
                bar.style.width = target + '%';
                let current = 0;
                const step = () => {
                    if (current < target) {
                    current = Math.min(current + 2, target);
                    pct.textContent = current + '%';
                    requestAnimationFrame(step);
                    }
                };
                requestAnimationFrame(step);
                }, 300);
            });
            </script>


    </div>


    <!-- CONTENIDO DE BENEFICIOS -->
    <div class="seccion-beneficios">
    
        <div class="beneficios-titulo">
            <h2>BENEFICIOS CLAVE</h2>
        </div>
        
        <div class="grid-beneficios-plataforma">
        
            <!-- CARD 1 -->
            <div class="card-beneficio top-left">
                <span class="card-num">01</span>
            
                <div class="card-header">
                    <div class="icono-beneficio">
                    <!-- Ícono: usuarios / gestión -->
                        <i class="fa-solid fa-user-group"></i>
                    </div>
                    <h2>Gestión centralizada de estudiantes y asistencia</h2>
                </div>
            
                <div class="card-divider"></div>
            
                <ul class="lista-beneficios">
                    <li>Centralización de la información de estudiantes registrados en la plataforma.</li>
                    <li>Importación de registros de asistencia provenientes del reloj checador institucional.</li>
                    <li>Consulta de asistencia reciente de los estudiantes asignados al personal.</li>
                    <li>Consulta del historial de asistencia por estudiante y periodo académico.</li>
                    <li>Organización de la información por periodos, estatus escolares y áreas académicas.</li>
                    <li>Acceso a información clara para apoyar el seguimiento escolar.</li>
                </ul>
            </div>
        
            <!-- CARD 2 -->
            <div class="card-beneficio top-right">
                <span class="card-num">02</span>
            
                <div class="card-header">
                    <div class="icono-beneficio">
                    <!-- Ícono: shield / errores -->
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h2>Reducción de errores administrativos</h2>
                </div>
            
                <div class="card-divider"></div>
            
                <ul class="lista-beneficios">
                    <li>Disminución de errores ocasionados por el manejo manual de asistencias.</li>
                    <li>Mayor orden en los registros académicos y administrativos.</li>
                    <li>Evita duplicidad o pérdida de información mediante registros digitales centralizados.</li>
                    <li>Permite validar información antes de realizar importaciones importantes.</li>
                    <li>Mejora la confiabilidad de los datos utilizados para el seguimiento académico.</li>
                </ul>
            </div>
        
            <!-- CARD 3 -->
            <div class="card-beneficio bottom-left">
                <span class="card-num">03</span>
            
                <div class="card-header">
                    <div class="icono-beneficio">
                    <!-- Ícono: bell / alertas -->
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <h2>Seguimiento de justificantes y alertas</h2>
                </div>
            
                <div class="card-divider"></div>
            
                <ul class="lista-beneficios">
                    <li>Los estudiantes pueden enviar justificantes de asistencia de manera digital.</li>
                    <li>El personal puede revisar, aprobar o rechazar justificantes desde la plataforma.</li>
                    <li>Las faltas justificadas pueden reflejarse correctamente en el seguimiento de asistencia.</li>
                    <li>El sistema genera alertas internas cuando se detectan faltas relevantes.</li>
                    <li>Permite dar seguimiento a casos que requieren atención académica o administrativa.</li>
                </ul>
            </div>
        
            <!-- CARD 4 -->
            <div class="card-beneficio bottom-right">
                <span class="card-num">04</span>
            
                <div class="card-header">
                    <div class="icono-beneficio">
                    <!-- Ícono: trending / impacto -->
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h2>Impacto institucional</h2>
                </div>
            
                <div class="card-divider"></div>
            
                <ul class="lista-beneficios">
                    <li>Fortalece el control y seguimiento de la asistencia escolar.</li>
                    <li>Apoya la toma de decisiones con información organizada y actualizada.</li>
                    <li>Mejora la comunicación entre estudiantes, personal académico y responsables administrativos.</li>
                    <li>Facilita la detección temprana de posibles riesgos académicos por inasistencias.</li>
                    <li>Contribuye a la digitalización de procesos escolares dentro de la institución.</li>
                </ul>
            </div>
        
        </div>
    </div>


    <!-- SLIDER DE INICIO
    <div class="content-slider">
        <div class="slides">
            Cada imagen dentro de su contenedor de slide
            <div class="slide">
                <img src="{{ asset('img_plataforma/entrada_cenidet.png') }}" alt="Imagen 1">
            </div>
            <div class="slide">
                <img src="{{ asset('img_plataforma/fachada_cenidet_2.png') }}" alt="Imagen 2">
            </div>
            <div class="slide">
                <img src="{{ asset('img_plataforma/img_2_slider.png') }}" alt="Imagen 3">
            </div>
            <div class="slide">
                <img src="{{ asset('img_plataforma/img_3_slider.png') }}" alt="Imagen 3">
            </div>
            <div class="slide">
                <img src="{{ asset('img_plataforma/placa_cenidet_2.jpg') }}" alt="Imagen 3">
            </div>
            <div class="slide">
                <img src="{{ asset('img_plataforma/tec.jpg') }}" alt="Imagen 3">
            </div>
        </div>
    </div> -->

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/funcion_slider_pagina_inicio.js') }}"></script>
    <script src="{{ asset('js/funcion_hero_stats_pagina_inicio.js') }}"> </script>
@endpush