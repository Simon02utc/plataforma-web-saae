<!--=============== HEADER ===============-->
<header class="header barra_navegacion_principal">
    <nav class="nav container">
        <div class="nav__data">
            <a href="{{ route('name_inicio') }}" class="nav__logo">
                <!--<i class="ri-planet-line"></i> Company-->
                <img src="{{ asset('img_plataforma/tec.jpg') }}" alt="Placa tecnologico">
                <img src="{{ asset('img_plataforma/placa_cenidet_2.jpg') }}" alt="Placa cenidet">
            </a>
        
            <div class="nav__toggle" id="nav-toggle">
                <i class="ri-menu-line nav__burger"></i>
                <i class="ri-close-line nav__close"></i>
            </div>
        </div>

        <!--=============== NAV MENU ===============-->
        <div class="nav__menu" id="nav-menu">
            <ul class="nav__list">
                <li>
                    <!--DEBEN DE CREARSE LAS RUTAS PARA SU FUNCIONAMIENTO EN LA BARRA DE NAVEGACION EN: routes/web.php -->
                    <!--Pagina o ruta {{ route('name_inicio') }} --> <!-- Para agregar la clase de "active" al estar en la pagina {{ request()->routeIs('name_inicio') ? 'active' : '' }} -->
                    <a href="{{ route('name_inicio') }}" class="nav__link {{ request()->routeIs('name_inicio') ? 'active' : '' }}">
                        <span>Inicio</span>
                    </a>
                </li>

                <!--=============== DROPDOWN 1 ===============-->
                <li class="dropdown__item">
                    <a href="#" class="nav__link {{ request()->routeIs('grup_estudiante.*') ? 'active' : '' }}">
                        <span>Estudiantes</span>
                        <i class="ri-arrow-down-s-line dropdown__arrow"></i>
                    </a>

                    <ul class="dropdown__menu">
                        <li>
                            <a href="{{ route('grup_estudiante.name_informacion_inicial_estudiantes') }}" class="dropdown__link primerlink  {{ request()->routeIs('grup_estudiante.name_informacion_inicial_estudiantes') ? 'active' : '' }}">
                                <i class="ri-info-card-line"></i>
                                <span>Información inicial</span>
                            </a>                          
                        </li>

                        <li>
                            <a href="{{ route('grup_estudiante.name_login_estudiante') }}" class="dropdown__link ultimolink {{ request()->routeIs('grup_estudiante.name_login_estudiante') ? 'active' : '' }}">
                                <i class="ri-user-line"></i>
                                <span>Login de estudiante</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!--=============== DROPDOWN 2 ===============-->
                <li class="dropdown__item">
                    <a href="#" class="nav__link {{ request()->routeIs('grup_personal.*') ? 'active' : '' }}">
                        <span>Personal</span>
                        <i class="ri-arrow-down-s-line dropdown__arrow"></i>
                    </a>

                    <ul class="dropdown__menu">
                        <li>
                            <a href="{{ route('grup_personal.name_informacion_inicial_personal') }}" class="dropdown__link primerlin {{ request()->routeIs('grup_personal.name_informacion_inicial_personal') ? 'active' : '' }}">
                                <i class="ri-info-card-line"></i>
                                <span>Informacion inicial</span>
                            </a>                          
                        </li>

                        <li>
                            <a href="{{ route('grup_personal.name_login_personal') }}" class="dropdown__link ultimolink {{ request()->routeIs('grup_personal.name_login_personal') ? 'active' : '' }}">
                                <i class="ri-user-2-line"></i>
                                <span>Login del personal</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li>
                    <a href="{{ route('name_alertas') }}" class="nav__link {{ request()->routeIs('name_alertas') ? 'active' : '' }}">
                        <span>Alertas</span>
                    </a>
                </li>

                <!--<li>
                    <a href="{{ route('name_seguridad') }}" class="nav__link {{ request()->routeIs('name_seguridad') ? 'active' : '' }}" class="nav__link">
                        <span>Seguridad</span>
                    </a>
                </li>-->

                <li>
                    <a href="{{ route('name_contacto') }}" class="nav__link {{ request()->routeIs('name_contacto') ? 'active' : '' }}">
                        <span>Contacto</span>
                    </a>
                </li>

                <!--=============== DROPDOWN 3 ===============-->
                <li class="dropdown__item">
                    <a href="#" class="nav__link {{ request()->routeIs('grup_sobre_saae.*') ? 'active' : '' }}">
                        <span>Sobre SAAE</span>
                        <i class="ri-arrow-down-s-line dropdown__arrow"></i>
                    </a>

                    <ul class="dropdown__menu">
                        <li>
                            <a href="{{ route('grup_sobre_saae.name_descripcion_general') }}" class="dropdown__link primerlink {{ request()->routeIs('grup_sobre_saae.name_descripcion_general') ? 'active' : '' }}">
                                <i class="ri-article-line"></i>
                                <span>Descripción general</span>
                            </a>                          
                        </li>

                        <li>
                            <a href="{{ route('grup_sobre_saae.name_objetivo_academico') }}" class="dropdown__link ultimolink {{ request()->routeIs('grup_sobre_saae.name_objetivo_academico') ? 'active' : '' }}">
                                <i class="ri-graduation-cap-line"></i>
                                <span>Objetivo académico</span>
                            </a>
                        </li>

                        <!--=============== DROPDOWN SUBMENU ===============->
                        <li class="dropdown__subitem">
                            <div class="dropdown__link {{ request()->routeIs('grup_sobre_saae.sub_grup_tecnologias_utilizadas.*') ? 'active' : '' }}">
                                <i class="ri-instance-line"></i>
                                <span>Tecnologías utilizadas</span>
                                <i class="ri-add-line dropdown__add"></i>
                            </div>

                            <ul class="dropdown__submenu">
                                <li>
                                    <a href="{{ route('grup_sobre_saae.sub_grup_tecnologias_utilizadas.name_backend_frontend_bd') }}" class="dropdown__sublink primersublink {{ request()->routeIs('grup_sobre_saae.sub_grup_tecnologias_utilizadas.name_backend_frontend_bd') ? 'active' : '' }}">
                                        <i class="ri-code-s-slash-line"></i>
                                        <span>Backend / Frontend / BD</span>
                                    </a>
                                </li>
        
                                <li>
                                    <a href="{{ route('grup_sobre_saae.sub_grup_tecnologias_utilizadas.name_reloj_checador') }}" class="dropdown__sublink {{ request()->routeIs('grup_sobre_saae.sub_grup_tecnologias_utilizadas.name_reloj_checador') ? 'active' : '' }}">
                                        <i class="ri-fingerprint-line"></i>
                                        <span>Reloj checador</span>
                                    </a>
                                </li>
        
                                <li>
                                    <a href="{{ route('grup_sobre_saae.sub_grup_tecnologias_utilizadas.name_wifi_institucional') }}" class="dropdown__sublink ultimosublink {{ request()->routeIs('grup_sobre_saae.sub_grup_tecnologias_utilizadas.name_wifi_institucional') ? 'active' : '' }}">
                                        <i class="ri-wifi-line"></i>
                                        <span>WiFi institucional</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li>
                            <a href="{{ route('grup_sobre_saae.name_desarrollador_saae') }}" class="dropdown__link ultimolink {{ request()->routeIs('grup_sobre_saae.name_desarrollador_saae') ? 'active' : '' }}">
                                <i class="ri-id-card-line"></i>
                                <span>Desarrollador de SAAE</span>
                            </a>
                        </li>

                    </ul>
                </li>-->

            </ul>
        </div>
    </nav>
</header>