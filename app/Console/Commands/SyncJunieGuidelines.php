<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncJunieGuidelines extends Command
{
    protected $signature = 'junie:sync-guidelines';

    protected $description = 'Regenera .junie/guidelines.md desde CLAUDE.md y AGENTS.md';

    public function handle(): int
    {
        $basePath = base_path();
        $claudePath = $basePath.'/CLAUDE.md';
        $agentsPath = $basePath.'/AGENTS.md';
        $outputPath = $basePath.'/.junie/guidelines.md';

        if (! file_exists($claudePath)) {
            $this->error("No se encontró CLAUDE.md en {$claudePath}");

            return self::FAILURE;
        }

        if (! file_exists($agentsPath)) {
            $this->error("No se encontró AGENTS.md en {$agentsPath}");

            return self::FAILURE;
        }

        $claudeContent = file_get_contents($claudePath);
        $agentsContent = file_get_contents($agentsPath);

        $now = now()->format('Y-m-d H:i');

        $output = <<<MD
        # In-ventra — Junie Guidelines

        > **Auto-generado** el {$now} desde `CLAUDE.md` y `AGENTS.md`.
        > No editar manualmente — los cambios se perderán en la próxima sincronización.
        > Para actualizar: modificar `CLAUDE.md` o `AGENTS.md` y hacer commit (el hook lo regenera automáticamente).

        ---

        {$claudeContent}

        ---

        {$agentsContent}
        MD;

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        file_put_contents($outputPath, $output);

        $this->info('.junie/guidelines.md sincronizado correctamente.');

        return self::SUCCESS;
    }
}
