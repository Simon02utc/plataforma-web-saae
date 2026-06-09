<?php

namespace App\Jobs;

use App\Models\ImportacionAsistencia;
use App\Services\ModuloImportacionAsistencia\ImportarAsistenciaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcesarImportacionAsistenciaJob implements ShouldQueue
{
    use Queueable;

    public function __construct(

    ) {}

    public function handle(): void
    {

    }

    public function failed(?Throwable $e): void
    {

    }
}