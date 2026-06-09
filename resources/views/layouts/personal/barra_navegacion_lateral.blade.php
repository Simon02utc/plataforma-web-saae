@php
    $user = auth('personal')->user();
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
                <span class="titulo-barra"><a href="{{ route('grup_personal.name_panel_personal') }}">Panel del personal</a></span>
            </div>
        </div>

        <div class="botones-principales-barra-lateral">
            
            @if($user && $user->hasPermission('modulo_importacion.ver'))<!--Solo el personal-->
            <li class="btn-modulo has-submenu {{ request()->routeIs('grup_personal.grup_modulo_importacion.*') ? 'open' : '' }}">
                <a href="#" class="boton {{ request()->routeIs('grup_personal.grup_modulo_importacion.*') ? 'active' : '' }}"
                    data-submenu-toggle
                    aria-expanded="{{ request()->routeIs('grup_personal.grup_modulo_importacion.*') ? 'true' : 'false' }}">
                    <i class="fa-solid fa-upload"></i>
                    <span>Módulo de importación</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_personal.grup_modulo_importacion.name_importacion_datos_escolares') ? 'active' : '' }}" href="{{ route('grup_personal.grup_modulo_importacion.name_importacion_datos_escolares') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Importación de datos escolares</span>
                        </a>
                    </li>

                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_personal.grup_modulo_importacion.name_importacion_asistencia') ? 'active' : '' }}" href="{{ route('grup_personal.grup_modulo_importacion.name_importacion_asistencia') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Importación de asistencia</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif

        </div>

    </div>

    <nav class="navegacion">
        <ul>

            @if($user && $user->hasPermission('panel_personal.ver'))<!--Solo quien tenga permiso y el administrador pasa-->
            <li>
                <a class="{{ request()->routeIs('grup_personal.name_panel_personal') ? 'active' : '' }}" href="{{ route('grup_personal.name_panel_personal') }}">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            @endif


            @if($user && $user->hasRole('admin'))<!--Solo el administrador pasa-->
            <li class="has-submenu {{ request()->routeIs('grup_admin.grup_personal_acceso.*') ? 'open' : '' }}">
                <a href="#" class="{{ request()->routeIs('grup_admin.grup_personal_acceso.*') ? 'active' : '' }}"
                    data-submenu-toggle
                    aria-expanded="{{ request()->routeIs('grup_admin.grup_personal_acceso.*') ? 'true' : 'false' }}">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Personal y acceso</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_admin.grup_personal_acceso.name_roles_permisos') ? 'active' : '' }}" href="{{ route('grup_admin.grup_personal_acceso.name_roles_permisos') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Roles y permisos</span>
                        </a>
                    </li>

                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_admin.grup_personal_acceso.name_listado_personal') ? 'active' : '' }}" href="{{ route('grup_admin.grup_personal_acceso.name_listado_personal') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Listado del personal</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif


            @if($user && $user->hasPermission('estudiantes.ver'))<!--Solo quien tenga permiso y el administrador pasa-->
            <li class="has-submenu {{ request()->routeIs('grup_personal.grup_estudiantes.*') ? 'open' : '' }}">
                <a href="#" class="{{ request()->routeIs('grup_personal.grup_estudiantes.*') ? 'active' : '' }}"
                    data-submenu-toggle
                    aria-expanded="{{ request()->routeIs('grup_personal.grup_estudiantes.*') ? 'true' : 'false' }}">
                    <i class="fa-solid fa-user-graduate"></i>
                    <span>Estudiantes</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_personal.grup_estudiantes.name_gestion_estudiantes') ? 'active' : '' }}" href="{{ route('grup_personal.grup_estudiantes.name_gestion_estudiantes') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Gestión de estudiantes</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif


            @if($user && $user->hasPermission('asistencia_estudiantes.ver'))<!--Solo quien tenga permiso y el administrador pasa-->
            <li class="has-submenu {{ request()->routeIs('grup_personal.grup_asistencia_estudiantes.*') ? 'open' : '' }}">
                <a href="#" class="{{ request()->routeIs('grup_personal.grup_asistencia_estudiantes.*') ? 'active' : '' }}"
                    data-submenu-toggle
                    aria-expanded="{{ request()->routeIs('grup_personal.grup_asistencia_estudiantes.*') ? 'true' : 'false' }}">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Asistencia de estudiantes</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_personal.grup_asistencia_estudiantes.name_asistencia_reciente') ? 'active' : '' }}" href="{{ route('grup_personal.grup_asistencia_estudiantes.name_asistencia_reciente') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Asistencias recientes</span>
                        </a>
                    </li>

                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_personal.grup_asistencia_estudiantes.name_hitorial_asistencia_estudiante') ? 'active' : '' }}" href="{{ route('grup_personal.grup_asistencia_estudiantes.name_hitorial_asistencia_estudiante') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Historial de asistencia</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif


            @if($user && $user->hasPermission('justificantes.ver'))<!--Solo quien tenga permiso y el administrador pasa-->
            <li class="has-submenu {{ request()->routeIs('grup_personal.grup_justificantes.*') ? 'open' : '' }}">
                <a href="#" class="{{ request()->routeIs('grup_personal.grup_justificantes.*') ? 'active' : '' }}"
                    data-submenu-toggle
                    aria-expanded="{{ request()->routeIs('grup_personal.grup_justificantes.*') ? 'true' : 'false' }}">
                    <i class="fa-solid fa-file-circle-check"></i>
                    <span>Justificantes</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_personal.grup_justificantes.name_bandeja_justificantes') ? 'active' : '' }}" href="{{ route('grup_personal.grup_justificantes.name_bandeja_justificantes') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Bandeja de justificantes</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif


            @if($user && $user->hasPermission('alertas.ver'))<!--Solo quien tenga permiso y el administrador pasa-->
            <li class="has-submenu {{ request()->routeIs('grup_personal.grup_alertas.*') ? 'open' : '' }}">
                <a href="#" class="{{ request()->routeIs('grup_personal.grup_alertas.*') ? 'active' : '' }}"
                    data-submenu-toggle
                    aria-expanded="{{ request()->routeIs('grup_personal.grup_alertas.*') ? 'true' : 'false' }}">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Alertas</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_personal.grup_alertas.name_alertas') ? 'active' : '' }}" href="{{ route('grup_personal.grup_alertas.name_alertas') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Alertas de asistencia</span>
                        </a>
                    </li>

                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_personal.grup_alertas.name_historial_alertas') ? 'active' : '' }}" href="{{ route('grup_personal.grup_alertas.name_historial_alertas') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Historial de alertas de asistencia</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            @if($user && $user->hasRole('admin'))<!--Solo el administrador pasa-->
            <li class="has-submenu {{ request()->routeIs('grup_admin.grup_configuracion.*') ? 'open' : '' }}">
                <a href="#" class="{{ request()->routeIs('grup_admin.grup_configuracion.*') ? 'active' : '' }}"
                    data-submenu-toggle
                    aria-expanded="{{ request()->routeIs('grup_admin.grup_configuracion.*') ? 'true' : 'false' }}">
                    <i class="fa-brands fa-whmcs"></i>
                    <span>Configuración</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_admin.grup_configuracion.grup_catalogos_academicos.*') ? 'active' : '' }}" href="{{ route('grup_admin.grup_configuracion.grup_catalogos_academicos.name_areas_especialidad_estatus_escolar') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Catálogos académicos</span>
                        </a>
                    </li>

                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.*') ? 'active' : '' }}" href="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_relojes_checadores_parsers') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Relojes checadores y parsers</span>
                        </a>
                    </li>

                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_admin.grup_configuracion.grup_fuentes_datos_parsers.*') ? 'active' : '' }}" href="{{ route('grup_admin.grup_configuracion.grup_fuentes_datos_parsers.name_fuentes_datos_parsers') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Fuentes de datos y parsers</span>
                        </a>
                    </li>
                </ul>

                
            </li>
            @endif


            @if($user && $user->hasPermission('auditoria_seguridad.ver'))<!--Solo quien tenga permiso y el administrador pasa-->
            <li class="has-submenu {{ request()->routeIs('grup_personal.grup_auditoria_seguridad.*') ? 'open' : '' }}">
                <a href="#" class="{{ request()->routeIs('grup_personal.grup_auditoria_seguridad.*') ? 'active' : '' }}"
                    data-submenu-toggle 
                    aria-expanded="{{ request()->routeIs('grup_personal.grup_auditoria_seguridad.*') ? 'true' : 'false' }}">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>Auditoría y Seguridad</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_personal.grup_auditoria_seguridad.name_ver_historial_modulo_importaciones') ? 'active' : '' }}" href="{{ route('grup_personal.grup_auditoria_seguridad.name_ver_historial_modulo_importaciones') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Historial módulo de importación</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif


            @if($user && $user->hasPermission('guia_manual_personal.ver'))<!--Solo quien tenga permiso y el administrador pasa-->
            <li class="has-submenu {{ request()->routeIs('grup_personal.grup_guia_manual.*') ? 'open' : '' }}">
                <a href="#" class="{{ request()->routeIs('grup_personal.grup_guia_manual.*') ? 'active' : '' }}"
                    data-submenu-toggle 
                    aria-expanded="{{ request()->routeIs('grup_personal.grup_guia_manual.*') ? 'true' : 'false' }}">
                    <i class="fa-solid fa-book-open-reader"></i>
                    <span>Guía / Manual</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </a>

                <ul class="submenu">
                    @if($user && $user->hasRole('admin'))<!--Solo el administrador-->
                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_personal.grup_guia_manual.name_guia_manual_admin') ? 'active' : '' }}" href="{{ route('grup_personal.grup_guia_manual.name_guia_manual_admin') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Para el Admininistrador</span>
                        </a>
                    </li>
                    @endif

                    <li class="sub-li">
                        <a class="sub-a {{ request()->routeIs('grup_personal.grup_guia_manual.name_guia_manual_personal') ? 'active' : '' }}" href="{{ route('grup_personal.grup_guia_manual.name_guia_manual_personal') }}">
                            <i class="fa-solid fa-caret-right"></i>
                            <span>Para el personal</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif
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
                <span>Modo escritorio</span>
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
                    {{ auth('personal')->user()?->nombre ?? '' }}
                </div>
            </div>

            <div class="content-sesion">
                <form class="form-cerrar" id="logoutForm" method="POST" action="{{ route('grup_personal.name_cerrar_sesion_personal') }}">
                    @csrf
                    <button class="btn-acciones-barra-lateral btn-cerrar" type="button" id="btnLogout">
                        <span><i class="fas fa-sign-out-alt"></i> Cerrar sesión</span>
                    </button>
                </form>

                <!--<div class="nombre-email">    
                    <span class="nombre">Simon</span>
                    <span class="email">nose@gmail.com</span>
                </div>-->
                <!-- <i class="fa-solid fa-ellipsis-vertical icon-mas-opciones-user"></i> -->
            </div>
        </div>
    </div>

</div>