<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();


        //limitar cuantas veces pueden pegarle a esa ruta (limite de peticiones)
        // en este caso sirve para que no esten pidiendo enlaces de recuperacion una y otra vez, 
        // saturando correos o intentando abusar del formulario. Laravel usa “rate limiting” justamente para restringir trafico en una ruta o grupo de rutas
        RateLimiter::for('recuperar-contrasena-personal', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $ip = $request->ip();

            return [
                Limit::perDay(3)->by('recover-email:' . $email), //solo 3 intentos
                Limit::perDay(10)->by('recover-ip:' . $ip),
            ];
        });

    
        //limitar cuantas veces pueden pegarle a esa ruta (limite de peticiones)
        // en este caso sirve para que no esten pidiendo enlaces de recuperacion una y otra vez, 
        // saturando correos o intentando abusar del formulario. Laravel usa “rate limiting” justamente para restringir trafico en una ruta o grupo de rutas
        RateLimiter::for('recuperar-contrasena-estudiante', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $ip = $request->ip();

            return [
                Limit::perDay(3)->by('recover-email:' . $email), //solo 3 intentos
                Limit::perDay(10)->by('recover-ip:' . $ip),
            ];
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
