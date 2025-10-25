<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerificarDatos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verificar:datos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar datos en la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ambulancias = \App\Models\Ambulancia::count();
        $personal = \App\Models\Personal::count();
        
        $this->info("✅ Ambulancias: $ambulancias");
        $this->info("✅ Personal: $personal");
        
        if ($ambulancias > 0) {
            $this->info("\n📋 Ambulancias disponibles:");
            \App\Models\Ambulancia::disponibles()->get()->each(function($amb) {
                $this->line("  - {$amb->placa}: {$amb->modelo} ({$amb->tipo_ambulancia})");
            });
        }
        
        if ($personal > 0) {
            $this->info("\n👥 Personal disponible:");
            \App\Models\Personal::disponibles()->get()->each(function($p) {
                $this->line("  - {$p->nombre} {$p->apellido}: {$p->rol}");
            });
        }
        
        return 0;
    }
}
