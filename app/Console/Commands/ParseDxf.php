<?php

namespace App\Console\Commands;

use App\Models\Parcelle;
use App\Models\Projet;
use Illuminate\Console\Command;

class ParseDxf extends Command
{
    protected $signature = 'dxf:parse {projet_id} {fichier} {--lat=14.95} {--lng=-16.82} {--scale=0.00001}';
    protected $description = 'Parser un fichier DXF et lier les parcelles au projet';

    public function handle(): int
    {
        $projet = Projet::find($this->argument('projet_id'));
        if (!$projet) {
            $this->error("Projet introuvable.");
            return Command::FAILURE;
        }

        $fichier = $this->argument('fichier');
        if (!file_exists($fichier)) {
            $this->error("Fichier introuvable : {$fichier}");
            return Command::FAILURE;
        }

        $this->info("Parsing DXF pour le projet : {$projet->nom}");

        // Appeler le script Python
        $outputPath = storage_path('app/dxf_output_' . $projet->id . '.json');
        $scriptPath = base_path('scripts/parse_dxf.py');

        $lat = $this->option('lat');
        $lng = $this->option('lng');
        $scale = $this->option('scale');

        $cmd = sprintf(
            'python3 %s %s %s --lat=%s --lng=%s --scale=%s 2>&1',
            escapeshellarg($scriptPath),
            escapeshellarg($fichier),
            escapeshellarg($outputPath),
            $lat, $lng, $scale
        );

        $output = shell_exec($cmd);
        $result = json_decode($output, true);

        if (!$result || isset($result['error'])) {
            $this->error("Erreur Python : " . ($result['error'] ?? $output));
            return Command::FAILURE;
        }

        $this->info("Parcelles trouvées : {$result['parcelles']}");
        $this->info("Labels trouvés : {$result['labels']}");

        // Charger le GeoJSON
        $geojson = json_decode(file_get_contents($outputPath), true);

        // Sauvegarder le GeoJSON global sur le projet
        $projet->update(['geojson' => $geojson]);

        // Lier les polygones aux parcelles existantes
        $matched = 0;
        $created = 0;

        foreach ($geojson['features'] as $feature) {
            if ($feature['properties']['type'] !== 'parcelle') continue;

            $lot = $feature['properties']['lot'] ?? null;
            $centroid = $feature['properties']['centroid'] ?? null;
            $geometrie = $feature['geometry'];

            if (!$lot) continue;

            // Chercher la parcelle existante
            $parcelle = Parcelle::where('numero_lot', $lot)
                ->where('projet_id', $projet->id)
                ->first();

            if ($parcelle) {
                $parcelle->update([
                    'geometrie' => $geometrie,
                    'centroid_lat' => $centroid ? $centroid[1] : null,
                    'centroid_lng' => $centroid ? $centroid[0] : null,
                ]);
                $matched++;
                $this->info("  Lot {$lot} -> lié à parcelle #{$parcelle->id}");
            } else {
                // Créer la parcelle si elle n'existe pas
                Parcelle::create([
                    'numero_lot' => $lot,
                    'projet_id' => $projet->id,
                    'geometrie' => $geometrie,
                    'centroid_lat' => $centroid ? $centroid[1] : null,
                    'centroid_lng' => $centroid ? $centroid[0] : null,
                    'date_attribution' => now(),
                ]);
                $created++;
                $this->info("  Lot {$lot} -> nouvelle parcelle créée");
            }
        }

        // Nettoyer
        @unlink($outputPath);

        $this->info("Terminé : {$matched} liée(s), {$created} créée(s)");
        return Command::SUCCESS;
    }
}
