<?php

namespace App\Http\Controllers\Personal\Estudiantes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AreasEspecialidadEstudiantesSaae;
use App\Models\EstatusEscolaresEstudiantesSaae;
use App\Models\EstudiantesSaae;
use App\Models\EstudianteConDatosEscolares;
use App\Services\Estudiantes\NotificacionesEstudiantesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegistrarEstudiantesSaaeController extends Controller
{
    public function cargar_area_especialidad_estatus_personal_estudiante()
    {
        $areaEspecialidad = AreasEspecialidadEstudiantesSaae::query()
            ->where('activo', true)
            ->orderByDesc('created_at')
            ->get(['id', 'nombre']);

        $estatusEscolares = EstatusEscolaresEstudiantesSaae::query()
            ->where('activo', true)
            ->orderByDesc('created_at')
            ->get(['id', 'nombre']);

        return view('personal.estudiantes.gestion_estudiantes', compact('areaEspecialidad', 'estatusEscolares'));
    }


    public function registrar_estudiante(Request $request, NotificacionesEstudiantesService $notificacionesEstudiantesService)
    {
        $request->merge([
            'numero_control' => Str::of($request->input('numero_control', ''))
                ->upper()
                ->replaceMatches('/\s+/', '')
                ->replaceMatches('/[^A-Z0-9]/', '')
                ->trim()
                ->toString(),

            'nombre' => Str::of($request->input('nombre', ''))
                ->replaceMatches('/\s+/', ' ')
                ->replaceMatches('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/u', '')
                ->trim()
                ->title()
                ->toString(),

            'apellidos' => Str::of($request->input('apellidos', ''))
                ->replaceMatches('/\s+/', ' ')
                ->replaceMatches('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/u', '')
                ->trim()
                ->title()
                ->toString(),

            'email' => Str::of($request->input('email', ''))
                ->lower()
                ->replaceMatches('/\s+/', '')
                ->trim()
                ->toString(),

            'telefono' => Str::of($request->input('telefono', ''))
                ->replaceMatches('/\D+/', '')
                ->trim()
                ->toString(),
            'anio_ingreso' => (int) $request->input('anio_ingreso'),
            'mes_ingreso' => (int) $request->input('mes_ingreso'),
            'activo' => $request->boolean('activo'),
        ]);

        $data = $request->validate(
            [
                'numero_control' => [
                    'required',
                    'string',
                    'size:8',
                    'regex:/^[A-Z][0-9]{2}[A-Z]{2}[0-9]{3}$/',
                    'unique:estudiantes_saae,numero_control',
                ],
                'nombre' => [
                    'required',
                    'string',
                    'max:120',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u',
                ],
                'apellidos' => [
                    'required',
                    'string',
                    'max:120',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u',
                ],
                'email' => [
                    'required',
                    'email',
                    'max:190',
                    'unique:estudiantes_saae,email',
                ],
                'telefono' => [
                    'required',
                    'regex:/^\d{10}$/',
                    'unique:estudiantes_saae,telefono',
                ],
                'password' => [
                    'required',
                    'string',
                    'min:6',
                    'confirmed',
                    'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$/',
                ],
                'anio_ingreso' => [
                    'required',
                    'integer',
                    'min:2000',
                    'max:' . now()->year,
                ],
                'mes_ingreso' => [
                    'required',
                    'integer',
                    'between:1,12',
                ],
                'area_id' => [
                    'required',
                    'exists:areas_especialidad_estudiantes_saae,id',
                ],
                'estatus_id' => [
                    'required',
                    'exists:estatus_escolares_estudiantes_saae,id',
                ],
                'activo' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'numero_control.required' => 'El número de control es obligatorio.',
                'numero_control.size' => 'El número de control debe tener exactamente 8 caracteres.',
                'numero_control.regex' => 'El número de control debe tener un formato válido. Ejemplo: M01CE001.',
                'numero_control.unique' => 'Ese número de control ya se encuentra en uso.',

                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',

                'apellidos.required' => 'Los apellidos son obligatorios.',
                'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios.',

                'email.required' => 'El correo es obligatorio.',
                'email.email' => 'El correo no tiene un formato válido.',
                'email.unique' => 'Ese correo electrónico ya está registrado.',

                'telefono.required' => 'El número de teléfono es obligatorio.',
                'telefono.regex' => 'El número de teléfono debe tener exactamente 10 dígitos.',
                'telefono.unique' => 'Ese número de teléfono ya está registrado.',

                'password.required' => 'La contraseña es obligatoria.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
                'password.regex' => 'La contraseña debe contener al menos 6 caracteres e incluir 1 mayúscula, 1 número y 1 símbolo (!@#$%^&*).',

                'anio_ingreso.required' => 'El año de ingreso es obligatorio.',
                'anio_ingreso.integer' => 'El año de ingreso debe ser numérico.',
                'anio_ingreso.min' => 'El año de ingreso no es válido.',
                'anio_ingreso.max' => 'El año de ingreso no puede ser mayor al año actual.',

                'mes_ingreso.required' => 'El mes de ingreso es obligatorio.',
                'mes_ingreso.integer' => 'El mes de ingreso debe ser numérico.',
                'mes_ingreso.between' => 'El mes de ingreso debe estar entre 1 y 12.',

                'area_id.required' => 'La especialidad es obligatoria.',
                'area_id.exists' => 'La especialidad seleccionada no es válida.',

                'estatus_id.required' => 'El estatus escolar es obligatorio.',
                'estatus_id.exists' => 'El estatus escolar seleccionado no es válido.',
            ]
        );

        $passwordPlano = $data['password']; //para enviar la contra

        $estudiante = DB::transaction(function () use ($data) {

            $estudiante = EstudiantesSaae::create([
                'numero_control' => $data['numero_control'],
                'nombre_completo' => trim($data['nombre'] . ' ' . $data['apellidos']),
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'email' => $data['email'],
                'telefono' => $data['telefono'],
                'password' => Hash::make($data['password']),
                'activo' => (bool) $data['activo'],
            ]);

            EstudianteConDatosEscolares::create([
                'estudiante_id' => $estudiante->id,
                'anio_ingreso' => $data['anio_ingreso'],
                'mes_ingreso' => $data['mes_ingreso'],
                'especialidad_id' => $data['area_id'],
                'estatus_escolar_id' => $data['estatus_id'],
                'periodo_ingreso_texto' => null,
                'ultima_importacion_id' => null,
            ]);

            return $estudiante;
        });

        $correoEnviado = $notificacionesEstudiantesService->enviarCorreoRegistroEstudiante($estudiante, $passwordPlano);

        return response()->json([
            'status' => 'success',
            'message' => $correoEnviado
                ? 'Estudiante registrado correctamente.'
                : 'Estudiante registrado correctamente, pero no se pudo enviar el correo.',
            'id' => $estudiante->id,
        ], 201);
    }
}