<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Notificación SAAE')</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <style>
        .body-gmail {
            margin:0; 
            padding:0; 
            background: rgb(244, 246, 247); 
            font-family: "Roboto", sans-serif;
        }

        .contenedor-gmail {
            max-width: 600px; 
            margin: 30px auto; 
            background: #ffffff; 
            border-radius: 10px; 
            overflow: hidden; 
            border: 1px solid rgb(208, 211, 212);
        }

        .head-gmail {
            background: #1B396A; 
            padding: 10px; 
            text-align:center;
        }

        .head-gmail h1{
            margin: 0; 
            color: #ffffff; 
            font-size: 18px;
            font-weight: 900;
        }

        .contenido-gmail {
            padding: 24px;
        }

        .contenido-gmail h2 {
            margin-top: 0;
            font-size: 16px; 
            color: #1B396A;
            font-weight: 700;
        }

        .contenido-gmail p {
            color: #374151;
            font-size: 14px;
            line-height: 1.6;
        }

        .contenedor-credenciales {
            background: #f8fafc; 
            border: 1px solid #D0D3D4; 
            border-radius: 16px; 
            padding: 10px; 
            margin: 20px 0;
        }

        .separador-contenedor-credenciale {
            border: solid 1px #D0D3D4;
            margin: 10px;
        }

        .titulo-contenedor-credenciales {
            color: #111827;
            font-size: 15px;
            font-weight: 700;
            text-align: center;
        }

        .contenedor-credenciales p {
            color: #111827;
            font-size: 14px;
        }

        .informacion-extra-dato {
            font-size: 10px;
            color: #111827;
        }

        .contenedor-botones {
            text-align:center; 
            margin:25px 0;
        }

        .contenedor-botones a {
            display: inline-block; 
            background: #1B396A; 
            color: #ffffff; 
            text-decoration: none; 
            padding: 10px 20px; 
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .informacion-final {
            color: #6b7280; 
            font-size: 12px; 
            line-height: 1.6;
        }


        .informacion-final a {
            text-decoration: none;
            font-weight: 600;
            color: #1B396A;
        }

        .footer-gmail {
            position: relative;
            background: #f8fafc; 
            text-align: center; 
            font-size: 10px; 
            color: #6b7280; 
            border-top: 1px solid #e5e7eb;
        }

        .footer-gmail img {
            width: 100px;
            height: 50px;
            border-radius: 6px;
        }

        .footer-gmail .img-placa-tec {
            margin: 15px;
        }

        .footer-gmail .img-placa-cenidet {
            margin: 15px;
        }
        
        /*--CONTENEDOR QUE INDICA QUE LAS VISTAS DE LOS CORREOS TENDRAN CSS PARA ELLAS SOLAS*/
    </style>
    @yield('extra_styles')

</head>
<body class="body-gmail">
    <div class="contenedor-gmail">
        
        <div class="head-gmail">
            <h1>Plataforma SAAE</h1>
        </div>

        <div class="contenido-gmail">
            @yield('content_email')
        </div>

        <div  class="footer-gmail">
            <p>{{ config('app.name') }} - &copy; 2026 a TecNM/CENIDET</p>
            <p>Todos los derechos reservados</p>
            <img class="img-placa-tec" src="{{ $message->embed(public_path('img_plataforma/tec.jpg')) }}" alt="TecNM">
            <img class="img-placa-cenidet" src="{{ $message->embed(public_path('img_plataforma/placa_cenidet_2.jpg')) }}" alt="CENIDET">
        </div>
    </div>
</body>
</html>