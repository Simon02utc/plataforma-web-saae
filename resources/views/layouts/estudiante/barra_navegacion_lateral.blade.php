@php
    $user = auth('estudiante')->user();
@endphp
<div class="menu">
    <i class="fa-solid fa-bars"></i>
    <i class="fa-solid fa-xmark"></i>
</div>

<div class="barra-lateral">
    <div class="parte-superior-barra">
        <div class="informacion-inicial-barra">
            <img class="logo-barra" id="btn-logo-barra" src="{{ asset('img_plataforma/tecnmicono.ico') }}" alt="">
            <div class="contenedor-titulo-barra">
                <span class="titulo-barra"><a href="{{ route('grup_estudiante.name_panel_estudiante') }}">Panel del estudiante</a></span>
            </div>
        </div>

        <div class="botones-principales-barra-lateral">

        </div>

    </div>

    <nav class="navegacion">
        <ul>

            <li>
                <a class="{{ request()->routeIs('grup_estudiante.name_panel_estudiante') ? 'active' : '' }}" href="{{ route('grup_estudiante.name_panel_estudiante') }}">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="has-submenu {{ request()->routeIs('grup_estudiante.grup_asistencia_estudiante.*') ? 'open' : '' }}">
                <a href="#" class="{{ request()->routeIs('grup_estudiante.grup_asistencia_estudiante.*') ? 'active' : '' }}"
                    data-submenu-toggle
                    aria-expanded="{{ request()->routeIs('grup_estudiante.grup_asistencia_estudiante.*') ? 'true' : 'false' }}">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Mi asistencia</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_estudiante.grup_asistencia_estudiante.name_asistencia_reciente') ? 'active' : '' }}" href="{{ route('grup_estudiante.grup_asistencia_estudiante.name_asistencia_reciente') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Asistencias recientes</span>
                        </a>
                    </li>

                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_estudiante.grup_asistencia_estudiante.name_historial_asistencia_estudiante') ? 'active' : '' }}" href="{{ route('grup_estudiante.grup_asistencia_estudiante.name_historial_asistencia_estudiante') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Historial de asistencia</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="has-submenu {{ request()->routeIs('grup_estudiante.grup_justificantes.*') ? 'open' : '' }}">
                <a href="#" class="{{ request()->routeIs('grup_estudiante.grup_justificantes.*') ? 'active' : '' }}"
                    data-submenu-toggle
                    aria-expanded="{{ request()->routeIs('grup_estudiante.grup_justificantes.*') ? 'true' : 'false' }}">
                    <i class="fa-solid fa-file-circle-check"></i>
                    <span>Justificantes</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_estudiante.grup_justificantes.name_bandeja_justificantes') ? 'active' : '' }}" href="{{ route('grup_estudiante.grup_justificantes.name_bandeja_justificantes') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Bandeja de justificantes</span>
                        </a>
                    </li>

                </ul>
            </li>


            <li class="has-submenu {{ request()->routeIs('grup_estudiante.grup_alertas.*') ? 'open' : '' }}">
                <a href="#" class="{{ request()->routeIs('grup_estudiante.grup_alertas.*') ? 'active' : '' }}"
                    data-submenu-toggle
                    aria-expanded="{{ request()->routeIs('grup_estudiante.grup_alertas.*') ? 'true' : 'false' }}">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Mis alertas</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_estudiante.grup_alertas.name_alertas') ? 'active' : '' }}" href="{{ route('grup_estudiante.grup_alertas.name_alertas') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Alertas de asistencia</span>
                        </a>
                    </li>

                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_estudiante.grup_alertas.name_historial_alertas') ? 'active' : '' }}" href="{{ route('grup_estudiante.grup_alertas.name_historial_alertas') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Historial de alertas de asistencia</span>
                        </a>
                    </li>
                </ul>
            </li>


        </ul>
    </nav>

    <div class="parte-inferior-barra">
        <div class="linea"></div>

        <div class="modo-oscuro">
            <div class="info">
                <i class="fa-solid fa-moon"></i>
                <span>Modo oscuro</span>
            </div>
            <div class="switch">
                <div class="base">
                    <div class="circulo">
                        
                    </div>
                </div>
            </div>
        </div>

        <div class="modo-escritorio">
            <div class="info">
                <i class="fa-solid fa-desktop"></i>
                <span>Vista escritorio</span>
            </div>
            <div class="switch">
                <div class="base">
                    <div class="circulo">
                        
                    </div>
                </div>
            </div>
        </div>


        <div class="usuario">
            <!--<img src="Jhampier.jpg" alt="">-->
            <div class="info-usuario">
                <i class="fas fa-user-circle"></i>
                <div class="username">
                    {{ auth('estudiante')->user()?->nombre_completo ?: auth('estudiante')->user()?->nombre ?: 'Estudiante' }}
                </div>
            </div>

            <div class="content-sesion">
                <form class="form-cerrar" id="logoutForm" method="POST" action="{{ route('grup_estudiante.name_cerrar_sesion_estudiante') }}">
                    @csrf
                    <button class="btn-cerrar" type="button" id="btnLogout">
                        <span><i class="fas fa-sign-out-alt"></i> Cerrar sesión</span>
                    </button>
                </form>

                <!--<div class="nombre-email">    
                    <span class="nombre">Simon</span>
                    <span class="email">nose@gmail.com</span>
                </div>-->
                <i class="fa-solid fa-ellipsis-vertical icon-mas-opciones-user"></i>
            </div>
        </div>
    </div>

</div>