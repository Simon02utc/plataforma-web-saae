<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pie_pagina/estilos_sobre_nosotros.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Nuestra historia | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="content-sobre-nosotros-pie-pagina">
        <div class="sobre-nosotros">
            <h1>Nuestra historia</h1>
            <p class="ultima-actualizacion"><span>Última actualización:</span> 03 de febrero del 2026</p>

            <div class="div-sobre-nost">
                <p>
                El Centro Nacional de Investigación y Desarrollo Tecnológico (CENIDET) se creó en enero de 1987, en el marco de una alianza estratégica con el Instituto de Investigaciones Eléctricas (IIE), con el fin de ser un centro de excelencia del Sistema Nacional de Institutos Tecnológicos. En mayo de ese año inició sus actividades con la primera generación de alumnos del programa de Maestría en Ciencias en Ingeniería Electrónica y, al siguiente, los programas de Maestría en Ciencias en Ciencias de la Computación, al comenzar el año, y de Maestría en Ciencias en Ingeniería Mecánica, al final del mismo.
                </p>
                
                <p>
                Cabe señalar y reconocer que, inicialmente, la alianza con el IIE permitió aprovechar la infraestructura y la experiencia de sus investigadores, así como su interés en participar en la integración de un centro de formación de recursos humanos altamente calificados en las áreas de electrónica, mecánica y computación. Pero, paulatinamente y con el apoyo de la Subsecretaría de Educación e Investigación Tecnológicas (SEIT), la Dirección General de Institutos Tecnológicos (DGIT), el Consejo del Sistema Nacional de Educación Tecnológica (COSNET) y del Consejo Nacional de Ciencia y Tecnología (CONACYT), el CENIDET fue creando su propia infraestructura y su capital intelectual siguiendo la estrategia de enviar personal con grado de maestría a efectuar estudios doctorales en temas y áreas de interés para los fines académicos del CENIDET, en instituciones de educación superior de prestigio nacionales y extranjeras.
                </p>

                <p>
                Para el 2007, 32 profesores del Centro tienen el Perfil Deseable, de estos, 11 son del Departamento de Ciencias Computacionales, 11 del de Ingeniería Mecánica, 9 del de Ingeniería Electrónica y 1 del de Ingeniería Mecatrónica.
                </p>

                <p>
                En los inicios del Centro, con la finalidad de elevar la eficiencia terminal, la estrategia consistió en formular y establecer políticas para asegurar la correspondencia entre la infraestructura física, los recursos humanos y los alumnos aceptados.
                </p>

                <p>
                En 1995 se creó el programa de Doctorado en Ciencias en Ingeniería Electrónica y en 1996, el Doctorado en Ciencias en Ingeniería Mecánica. A raíz de un replanteamiento estratégico institucional, se concluyó que era necesario consolidar los posgrados de manera que, por un lado, se fortalecieron sus líneas de investigación y con base en nuestras fortalezas y amplitud disciplinaria se incursionó en posgrados multidisciplinarios; así fue como nació y se fortaleció, a mediados de la década de los noventa, la oferta de programas de doctorado, como un mecanismo natural de continuidad y de consolidación de los proyectos académicos.
                </p>

                <p>
                En 2000 se inició el Doctorado en Ciencias en Ciencias de la Computación, y apoyados por todos los programas existentes, la Maestría y Doctorado en Ciencias en Ingeniería Mecatrónica, con el propósito de realizar investigación y desarrollo tecnológico multidisciplinario pero con mayor impacto industrial.
                </p>

                <p>
                Mientras que en 2005 se reestructuró el Sistema Educativo Nacional por niveles, lo que trajo como resultado la integración de los Institutos Tecnológicos a la Subsecretaría de Educación Superior (SES), transformando a la Dirección General de Institutos Tecnológicos (DGIT) en Dirección General de Educación Superior Tecnológica (DGEST).
                </p>

                <p>
                El 23 de julio de 2014, fue publicado en el Diario Oficial de la Federación, el Decreto Presidencial por el que se crea la institución de educación superior tecnológica más grande de nuestro país, el Tecnológico Nacional de México (TecNM). De acuerdo con el Decreto citado, el TecNM se funda como un órgano desconcentrado de la Secretaría de Educación Pública, que sustituye a la unidad administrativa que se hacía cargo de coordinar este importante subsistema de educación superior.
                </p>

                <p>
                Actualmente, el TecNM/CENIDET cuenta con 9 programas de posgrado, cinco Maestrías en Ciencias: de la Ingeniería, en Ingeniería Electrónica, en Ingeniería Mecatrónica, en Ingeniería Mecánica y de la Computación, así como con cuatro Doctorados en Ciencias: de la Ingeniería, en Ingeniería Electrónica, en Ingeniería Mecánica y de la Computación.
                </p>
            </div>

            <div class="div-sobre-nost">
                <h3 class="sub-titulo">Galería de los inicios del CENIDET</h3>
                <p><a href="https://cenidet.tecnm.mx/historia_galeria.php" target="_blank" rel="noopener noreferrer"><i class="ri-multi-image-line"></i> 
                Ver galería</a></p>
            </div>

            <div class="div-sobre-nost-content-img">
                <img class="img-ilustrativa" src="{{ asset('img_plataforma/nuestra_historia.png') }}" alt="">
            </div>

        </div>
    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->