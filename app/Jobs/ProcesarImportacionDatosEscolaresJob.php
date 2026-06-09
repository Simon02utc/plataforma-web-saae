<?php

namespace App\Jobs;

use App\Models\ImportacionDatosEscolares;
use App\Services\ModuloImportacionDatosEscolares\ImportarDatosEscolaresService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcesarImportacionDatosEscolaresJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
