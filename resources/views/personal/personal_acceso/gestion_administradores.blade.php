<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Gestión administradores | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Gestión de administradores</h1>
        </div>


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Listado de administradores</span></h2>

            <p class="texto-contenedor">Podras editar los datos de la cuenta.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-administradores">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" id="input-buscar-administrador" class="input-buscar-tabla"
                                placeholder="Buscar por ID, Nombre o Correo">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-administrador">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-listado-administradores"
                                data-url-table-listado-administradores="{{ route('grup_admin.grup_personal_acceso.name_tabla_listado_administradores') }}"
                                
                                data-url-table-ver-roles-administrador="{{ route('grup_admin.grup_personal_acceso.name_ver_roles_administrador', ['id' => '__ID__']) }}"
                                
                                data-url-table-ver-administrador="{{ route('grup_admin.grup_personal_acceso.name_ver_administrador', ['id' => '__ID__']) }}"
                                data-url-table-editar-administrador="{{ route('grup_admin.grup_personal_acceso.name_editar_administrador', ['id' => '__ID__']) }}"
                                data-url-table-eliminar-administrador="{{ route('grup_admin.grup_personal_acceso.name_eliminar_administrador', ['id' => '__ID__']) }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>Nombre y apellidos</th>
                                        <th>Correo</th>
                                        <th>Telefono</th>
                                        <th>Roles asignados</th>
                                        <th>Estado</th>
                                        <th>Ultimo acceso</th>
                                        <th>Registrodo en</th>
                                        <th>Editato en</th>
                                        <th>Acciones</th>
                                        <th class="th-ultimo">Eliminar</th>
                                    </tr>
                                </thead>

                                <tbody>
                                        <tr><td colspan="11" class="td-estado-tabla">Cargando contenido…</td></tr>
                                </tbody>

                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>


    </div>
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/personal_acceso/funcion_gestion_administradores.js') }}"></script>
    <script src="{{ asset('js/personal/ver_verificar_contrasena_formulario.js') }}"></script>
@endpush