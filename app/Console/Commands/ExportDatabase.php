<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class ExportDatabase extends Command
{
    protected $signature = 'db:export {--format=both : csv, sql, or both}';
    protected $description = 'Exporter toute la base de données en CSV (zip) et/ou SQL dans le dossier /export';

    protected array $tables = [
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
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
    ];

    public function handle(): int
    {
        $format = $this->option('format');
        $exportDir = base_path('export');
        $timestamp = now()->format('Y-m-d_H-i-s');

        $this->info("Export de la base de données...");

        if (in_array($format, ['csv', 'both'])) {
            $this->exportCsv($exportDir, $timestamp);
        }

        if (in_array($format, ['sql', 'both'])) {
            $this->exportSql($exportDir, $timestamp);
        }

        $this->info("Export terminé dans le dossier /export");
        return Command::SUCCESS;
    }

    protected function exportCsv(string $dir, string $timestamp): void
    {
        $zipPath = "{$dir}/export_csv_{$timestamp}.zip";
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Impossible de créer le fichier zip.");
            return;
        }

        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("  Table '{$table}' introuvable, ignorée.");
                continue;
            }

            $rows = DB::table($table)->get();
            if ($rows->isEmpty()) {
                $this->line("  {$table} : vide");
                continue;
            }

            // Générer le CSV
            $csv = '';
            $columns = array_keys((array) $rows->first());
            $csv .= implode(';', $columns) . "\n";

            foreach ($rows as $row) {
                $values = array_map(function ($v) {
                    if ($v === null) return '';
                    $v = str_replace('"', '""', (string) $v);
                    return '"' . $v . '"';
                }, (array) $row);
                $csv .= implode(';', $values) . "\n";
            }

            $zip->addFromString("{$table}.csv", $csv);
            $this->info("  {$table} : {$rows->count()} ligne(s)");
        }

        $zip->close();
        $this->info("CSV zip créé : {$zipPath}");
    }

    protected function exportSql(string $dir, string $timestamp): void
    {
        $sqlPath = "{$dir}/export_sql_{$timestamp}.sql";
        $sql = "-- Export base de données Gestion des Mutations\n";
        $sql .= "-- Date : " . now()->format('d/m/Y H:i:s') . "\n";
        $sql .= "-- =============================================\n\n";

        $sql .= "PRAGMA foreign_keys = OFF;\n\n";

        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) continue;

            $rows = DB::table($table)->get();
            if ($rows->isEmpty()) continue;

            $sql .= "-- Table: {$table} ({$rows->count()} lignes)\n";
            $sql .= "DELETE FROM `{$table}`;\n";

            foreach ($rows as $row) {
                $values = (array) $row;
                $columns = array_keys($values);

                $escapedValues = array_map(function ($v) {
                    if ($v === null) return 'NULL';
                    return "'" . str_replace("'", "''", (string) $v) . "'";
                }, $values);

                $sql .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $escapedValues) . ");\n";
            }

            $sql .= "\n";
            $this->info("  {$table} : {$rows->count()} ligne(s)");
        }

        $sql .= "PRAGMA foreign_keys = ON;\n";

        file_put_contents($sqlPath, $sql);
        $this->info("SQL créé : {$sqlPath}");
    }
}
