<?php

namespace App\Http\Controllers\Personal\Estudiantes;

use App\Http\Controllers\Controller;
use App\Jobs\GenerarCorreoInstitucionalYEnviarActivacionEstudianteJob;
use App\Jobs\ReenviarActivacionCuentaEstudianteJob;
use App\Models\EstudiantesSaae;
use App\Services\Estudiantes\GenerarCorreoInstitucionalEstudiantesService;
use Illuminate\Http\Request;


// IMPORTANTE LEER
// - Cuando se realicen cambios con realacion a Jobs/Services/Mails con relacion al metodo "generar_correo_institucional_estudiante" es necesario ejecutar los siguientes comandos:
// - php artisan optimize:clear
// - composer dump-autoload
// - php artisan queue:restart
// - php artisan queue:work --queue=correos_estudiantes,default

class GenerarCorreoInstitucionalEstudianteController extends Controller
{
    //PRIMER ENVIO DE CORREO DE ACTIVACION DE CUENTA
    public function generar_correo_institucional_estudiante(int $id, GenerarCorreoInstitucionalEstudiantesService $correoService)
    {
        $estudiante = EstudiantesSaae::findOrFail($id);

        if (!$correoService->numeroControlEsFormalizado($estudiante->numero_control)) {
            return response()->json([
                'message' => 'El estudiante no tiene un número de control formalizado.',
            ], 422);
        }

        if (filled($estudiante->email)) {
            return response()->json([
                'message' => 'El estudiante ya tiene un correo registrado.',
            ], 422);
        }

        //empiesa a ejecutar el JOB, el cual es el corazón del proceso
        GenerarCorreoInstitucionalYEnviarActivacionEstudianteJob::dispatch(
            $estudiante->id,
            auth('personal')->id()
        )->onQueue('correos_estudiantes');

        return response()->json([
            'message' => 'La generación del correo institucional fue enviada a cola correctamente.',
        ]);
    }


    public function generar_correos_institucionales_pendientes(Request $request, GenerarCorreoInstitucionalEstudiantesService $correoService) 
    {
        $totalDespachados = 0;
        $personalId = auth('personal')->id();

        EstudiantesSaae::query()
            ->select(['id', 'numero_control', 'email'])
            ->whereNull('email')
            ->orderBy('id')
            ->chunkById(200, function ($estudiantes) use (&$totalDespachados, $personalId, $correoService) {
                foreach ($estudiantes as $estudiante) {
                    if (!$correoService->numeroControlEsFormalizado($estudiante->numero_control)) {
                        continue;
                    }

                    //empiesa a ejecutar el JOB, el cual es el corazón del proceso
                    GenerarCorreoInstitucionalYEnviarActivacionEstudianteJob::dispatch(
                        $estudiante->id,
                        $personalId
                    )->onQueue('correos_estudiantes');

                    $totalDespachados++;
                }
            });

        return response()->json([
            'message' => $totalDespachados > 0
                ? "Se enviaron {$totalDespachados} estudiantes a cola para generar correo y mandar activación."
                : 'No se encontraron estudiantes pendientes aptos para generar correo institucional.',
            'total_despachados' => $totalDespachados,
        ]);
    }




    //REENVIO DE CORREO DE ACTIVACION DE CUENTA
    public function reenviar_activacion_cuenta_estudiante(int $id, GenerarCorreoInstitucionalEstudiantesService $correoService) 
    {
        $estudiante = EstudiantesSaae::findOrFail($id);

        if (!$correoService->puedeReenviarActivacion($estudiante)) {
            return response()->json([
                'message' => 'El estudiante no es apto para reenviar activación. Debe tener correo institucional y seguir inactivo.',
            ], 422);
        }

        ReenviarActivacionCuentaEstudianteJob::dispatch(
            $estudiante->id,
            auth('personal')->id()
        )->onQueue('correos_estudiantes');

        return response()->json([
            'message' => 'El reenvío de activación fue enviado a cola correctamente.',
        ]);
    }


    public function reenviar_activaciones_cuentas_pendientes(Request $request, GenerarCorreoInstitucionalEstudiantesService $correoService) 
    {
        $totalDespachados = 0;
        $personalId = auth('personal')->id();

        EstudiantesSaae::query()
            ->select(['id', 'email', 'activo'])
            ->whereNotNull('email')
            ->where('activo', false)
            ->orderBy('id')
            ->chunkById(200, function ($estudiantes) use (&$totalDespachados, $personalId, $correoService) {
                foreach ($estudiantes as $estudiante) {
                    if (!$correoService->puedeReenviarActivacion($estudiante)) {
                        continue;
                    }

                    ReenviarActivacionCuentaEstudianteJob::dispatch(
                        $estudiante->id,
                        $personalId
                    )->onQueue('correos_estudiantes');

                    $totalDespachados++;
                }
            });
        
        return response()->json([
            'message' => $totalDespachados > 0
                ? "Se enviaron {$totalDespachados} estudiantes a cola para reenviar activación."
                : 'No se encontraron estudiantes aptos para reenviar activación.',
            'total_despachados' => $totalDespachados,
        ]);
    }
}