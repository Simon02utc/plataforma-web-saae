<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta id="meta-viewport" name="viewport" content="width=device-width, initial-scale=1.0"> <!--Sin este meta, el navegador de TELEFONOS pensara que es de escritorio, escala todo como si tuviera ~980px-->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SAAE')</title>

    <link rel="icon" type="image/png" href="{{ asset('img_plataforma/tecnmicono.ico') }}">

    <!-- Color del tema (para la barra superior del navegador) -->
    <meta name="theme-color" content="#1B396A">
    
    <!--Carpeta (libreria) de iconos Font Awesome-->
    <link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}">

    <!--Carpeta (libreria) de Remix Icons-->
    <link rel="stylesheet" href="{{ asset('remixIcon_fonts/fonts/remixicon.css') }}">

    <!--=============== ICONOS ===============-->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.2.0/fonts/remixicon.css" rel="stylesheet">


    <!--=============== CSS ===============-->
    <link rel="stylesheet" href="{{ asset('css/estilos_variables_saae.css') }}">
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_principales_personal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_elementos_contenedores_titulos_texto.css') }}">
    <link rel="stylesheet" href="{{ asset('css/estilos_pantalla_carga.css') }}">
    <link rel="stylesheet" href="{{ asset('css/estilos_mantenimiento_saae.css') }}">
    <link rel="stylesheet" href="{{ asset('css/estilos_modal_cerrar_sesion.css') }}">
    <link rel="stylesheet" href="{{ asset('css/estilos_mensajes_modales.css') }}">
    <link rel="stylesheet" href="{{ asset('css/estilos_mensajes_toast.css') }}">
    <link rel="stylesheet" href="{{ asset('css/estilos_modal_confirmar_accion.css') }}">
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_modal_detalles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">

    <!--CONTENEDOR QUE INDICA QUE LAS PAGINAS TENDRAN O UTILIZARAN CSS PARA ELLA SOLA-->
    @stack('styles')


    <!--Links principales del tipo de letra-->
    <link  rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

</head>
<body>
    
<!--PANTALLA CARGA-->
@include('layouts.pantalla_carga')

<!--BARRA LATERAL DE NAVEGACION PRINCIPAL-->
@include('layouts.personal.barra_navegacion_lateral')

    <main>

        <!--PARA LAS VISTAS HIJAS, ES DECIR EL CONTENIDO DE LAS PAGINAS-->
        @yield('content')
    
    </main>

<!--MODAL DE CIERRE DE SESION-->
@include('layouts.modal_cierre_sesion')

<!--MENSAJES MODALES-->
@include('layouts.mensajes_modales')

<!--MENSAJES TOASTS-->
@include('layouts.mensajes_toast')

<!--MODAL DE DETALLES-->
@include('layouts.personal.modal_detalles')

<!--MODAL DE CONFIRMAR ACCION-->
@include('layouts.modal_confirmar_accion')

<!--MODAL PARA EL FORMULARIO DE EDITAR-->
@include('layouts.personal.modal_formulario_editar')

<!--Boton para subir-->
<div class="contenedor-boton-flotante" id="boton-flotante">
    <div class="boton-flotante" id="btn-subir">
        <i class="icono-flotante ri-arrow-up-double-line"></i>
    </div>
</div>

<!--Algunos navegadores usan una cache especial (bfcache) que puede mostrar la pagina “congelada"-->
<!--Si el usuario intenta volver con “Atrás” a una vista protegida, el navegador recarga y Laravel, al no haber sesión, lo saca-->
<script>
    window.addEventListener('pageshow', function (event) {
        const nav = performance.getEntriesByType?.('navigation')?.[0];
        const isBackForward = nav && nav.type === 'back_forward';
        if (event.persisted || isBackForward) {
        window.location.reload();
        }
    });
</script>

<!--JS principales-->
<script src="{{ asset('js/funcion_lectura_contenido.js') }}"></script>
<script src="{{ asset('js/funcion_boton_subir.js') }}"></script>
<script src="{{ asset('js/funcion_pantalla_carga.js') }}"></script>
<script src="{{ asset('js/personal/funcion_acordeon_texto.js') }}"></script>
<script src="{{ asset('js/funcion_mostrar_mensaje_modal.js') }}"></script>
<script src="{{ asset('js/funcion_mostrar_mensaje_toast.js') }}"></script>
<script src="{{ asset('js/funcion_modal_confirmar_accion.js') }}"></script>
<script src="{{ asset('js/personal/funcion_mostrar_modal_detalles.js') }}"></script>
<script src="{{ asset('js/personal/funcion_mostrar_modal_formulario.js') }}"></script>
<script src="{{ asset('js/personal/funcion_mostrar_modal_formulario_editar.js') }}"></script>
<script src="{{ asset('js/personal/funcion_barra_navegacion_lateral.js') }}"></script>
<script src="{{ asset('js/personal/funcion_cerrar_sesion.js') }}"></script>
<script src="{{ asset('js/mantenimiento_saae/funcion_verificar_mantenimiento.js') }}"></script>

<!--CONTENEDOR QUE INDICA QUE LAS PAGINAS TENDRAN O UTILIZARAN ARCHIVOS JS PARA ELLA SOLA-->
@stack('scripts')

</body>
</html>