<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class ImportDatabase extends Command
{
    protected $signature = 'db:import {--auto : Mode automatique (cron), traite les fichiers dans /import}';
    protected $description = 'Importer la base de données depuis un zip CSV ou un fichier SQL placé dans le dossier /import';

    protected array $tableOrder = [
        'communes',
        'projets',
        'proprietaires',
        'parcelles',
        'imports',
        'import_lignes',
        'mutations',
        'mutation_annulations',
        'document_templates',
        'users',
        'roles',
        'permissions',
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
    ];

    public function handle(): int
    {
        $importDir = base_path('import');
        $files = glob("{$importDir}/*.{zip,sql}", GLOB_BRACE);

        if (empty($files)) {
            if (!$this->option('auto')) {
                $this->info("Aucun fichier à importer dans le dossier /import");
            }
            return Command::SUCCESS;
        }

        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $filename = basename($file);

            $this->info("Traitement de : {$filename}");
            Log::info("[Import DB] Traitement de {$filename}");

            try {
                if ($ext === 'zip') {
                    $this->importCsvZip($file);
                } elseif ($ext === 'sql') {
                    $this->importSql($file);
                }

                // Déplacer le fichier traité
                $processedDir = "{$importDir}/traites";
                if (!is_dir($processedDir)) {
                    mkdir($processedDir, 0755, true);
                }
                $dest = "{$processedDir}/" . now()->format('Y-m-d_H-i-s') . "_{$filename}";
                rename($file, $dest);

                $this->info("Import terminé. Fichier déplacé dans /import/traites/");
                Log::info("[Import DB] {$filename} traité avec succès.");
            } catch (\Exception $e) {
                $this->error("Erreur : {$e->getMessage()}");
                Log::error("[Import DB] Erreur sur {$filename} : {$e->getMessage()}");

                // Déplacer en erreur
                $errorDir = "{$importDir}/erreurs";
                if (!is_dir($errorDir)) {
                    mkdir($errorDir, 0755, true);
                }
                rename($file, "{$errorDir}/" . now()->format('Y-m-d_H-i-s') . "_{$filename}");
            }
        }

        return Command::SUCCESS;
    }

    protected function importCsvZip(string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier zip.");
        }

        $tempDir = sys_get_temp_dir() . '/import_db_' . uniqid();
        $zip->extractTo($tempDir);
        $zip->close();

        DB::statement('PRAGMA foreign_keys = OFF');

        // Importer dans l'ordre des dépendances
        foreach ($this->tableOrder as $table) {
            $csvFile = "{$tempDir}/{$table}.csv";
            if (!file_exists($csvFile)) continue;
            if (!Schema::hasTable($table)) {
                $this->warn("  Table '{$table}' introuvable, ignorée.");
                continue;
            }

            $lines = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (count($lines) < 2) continue;

            // Parser l'en-tête
            $columns = str_getcsv($lines[0], ';');
            $columns = array_map(fn($c) => trim($c, '"'), $columns);

            // Vider la table
            DB::table($table)->truncate();

            // Insérer les lignes
            $count = 0;
            for ($i = 1; $i < count($lines); $i++) {
                $values = str_getcsv($lines[$i], ';', '"');

                $row = [];
                foreach ($columns as $idx => $col) {
                    $val = $values[$idx] ?? null;
                    $row[$col] = ($val === '' || $val === null) ? null : $val;
                }

                DB::table($table)->insert($row);
                $count++;
            }

            $this->info("  {$table} : {$count} ligne(s) importées");
        }

        DB::statement('PRAGMA foreign_keys = ON');

        // Nettoyer
        array_map('unlink', glob("{$tempDir}/*"));
        rmdir($tempDir);
    }

    protected function importSql(string $sqlPath): void
    {
        $sql = file_get_contents($sqlPath);

        // Exécuter chaque instruction
        $statements = array_filter(
            array_map('trim', explode(";\n", $sql)),
            fn($s) => $s && !str_starts_with($s, '--')
        );

        $count = 0;
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            DB::statement($statement);
            $count++;
        }

        $this->info("  {$count} instruction(s) SQL exécutée(s)");
    }
}
