<?php

namespace App\Imports;

use App\Models\Import;
use App\Models\ImportLigne;
use App\Models\Parcelle;
use App\Models\Projet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Import global de mutations multi-projets.
 * Crée un Import par projet trouvé dans le fichier (basé sur "Code projet").
 */
class MutationGlobalImport implements ToCollection
{
    public array $importsParProjet = []; // ['CODE' => Import]
    public array $resultats = [];
    public array $errors = [];

    protected int $userId;
    protected string $fichierNom;
    protected string $fichierPath;

    public function __construct(int $userId, string $fichierNom, string $fichierPath = '')
    {
        $this->userId = $userId;
        $this->fichierNom = $fichierNom;
        $this->fichierPath = $fichierPath;
    }

    public function collection(Collection $rows): void
    {
        if ($rows->count() < 2) return;

        $headerRowIdx = 0;
        $maxCols = 0;
        for ($r = 0; $r < min(3, $rows->count()); $r++) {
            $cells = $rows->get($r)->values()->filter(fn($v) => trim((string) $v) !== '')->count();
            if ($cells > $maxCols) { $maxCols = $cells; $headerRowIdx = $r; }
        }
        $headers = $rows->get($headerRowIdx)->values()->map(fn($v) => strtolower(trim((string) $v)))->toArray();

        $codeIdx     = $this->findCol($headers, ['code projet', 'code_projet', 'projet']);
        $lotIdx      = $this->findCol($headers, ['lot']);
        $civIdx      = $this->findCol($headers, ['civilité', 'civilite']);
        $prenomIdx   = $this->findCol($headers, ['prénom', 'prenom']);
        $nomIdx      = $this->findCol($headers, ['nom']);
        $typePIdx    = $this->findCol($headers, ['type pièce', 'type piece', 'type_piece']);
        $codePayeIdx = $this->findCol($headers, ['code payé', 'code paye', 'code_paye']);
        $cniIdx      = $this->findCol($headers, ['n° pièce', 'no piece', 'numero piece', 'cni']);
        $nineaIdx    = $this->findCol($headers, ['ninea']);
        $telIdx      = $this->findCol($headers, ['téléphone', 'telephone', 'tel']);
        $demPIdx     = $this->findCol($headers, ['prénom demandeur', 'prenom demandeur']);
        $demNIdx     = $this->findCol($headers, ['nom demandeur']);
        $demTIdx     = $this->findCol($headers, ['tél demandeur', 'tel demandeur']);
        $refIdx      = $this->findCol($headers, ['réf. lettre', 'ref lettre', 'ref_lettre']);
        $dateIdx     = $this->findCol($headers, ['date']);
        $obsIdx      = $this->findCol($headers, ['observation']);

        if ($codeIdx === null) { $this->errors[] = "Colonne 'Code projet' introuvable."; return; }
        if ($lotIdx === null)  { $this->errors[] = "Colonne 'LOT' introuvable.";        return; }

        $projetsCache = [];
        $compteursParCode = [];
        $matchCountParCode = [];

        foreach ($rows->slice($headerRowIdx + 1) as $row) {
            $vals = $row->values()->toArray();
            $code = strtoupper(trim((string) ($vals[$codeIdx] ?? '')));
            $lot  = $this->clean($vals[$lotIdx] ?? null);
            if (!$code || !$lot) continue;

            // Charger le projet
            if (!isset($projetsCache[$code])) {
                $projetsCache[$code] = Projet::where('code', $code)->first();
            }
            $projet = $projetsCache[$code];
            if (!$projet) {
                $this->errors[] = "Code projet « {$code} » introuvable (lot {$lot}).";
                continue;
            }

            // Créer un Import pour ce projet (1 par projet)
            if (!isset($this->importsParProjet[$code])) {
                $this->importsParProjet[$code] = Import::create([
                    'projet_id'    => $projet->id,
                    'nom_fichier'  => $this->fichierNom . ' — ' . $code,
                    'fichier_path' => $this->fichierPath ?: 'global-import',
                    'imported_by'  => $this->userId,
                    'total_lignes' => 0,
                    'lignes_traitees' => 0,
                    'lignes_matchees' => 0,
                    'statut'       => 'en_cours',
                ]);
                $compteursParCode[$code] = 0;
                $matchCountParCode[$code] = 0;
            }
            $import = $this->importsParProjet[$code];
            $compteursParCode[$code]++;

            $parcelle = Parcelle::where('numero_lot', $lot)->where('projet_id', $projet->id)->first();
            $matched = $parcelle !== null;
            if ($matched) $matchCountParCode[$code]++;

            ImportLigne::create([
                'import_id'           => $import->id,
                'numero_ordre'        => $compteursParCode[$code],
                'civilite'            => $this->clean($vals[$civIdx ?? -1] ?? null),
                'prenom'              => $this->clean($vals[$prenomIdx ?? -1] ?? null),
                'nom'                 => $this->clean($vals[$nomIdx ?? -1] ?? null),
                'type_piece'          => $this->clean($vals[$typePIdx ?? -1] ?? null),
                'code_paye'           => $this->clean($vals[$codePayeIdx ?? -1] ?? null),
                'cni_passport'        => $this->clean($vals[$cniIdx ?? -1] ?? null),
                'ninea'               => $this->clean($vals[$nineaIdx ?? -1] ?? null),
                'telephone'           => $this->clean($vals[$telIdx ?? -1] ?? null),
                'demandeur_prenom'    => $this->clean($vals[$demPIdx ?? -1] ?? null),
                'demandeur_nom'       => $this->clean($vals[$demNIdx ?? -1] ?? null),
                'demandeur_telephone' => $this->clean($vals[$demTIdx ?? -1] ?? null),
                'numero_lot'          => $lot,
                'ref_lettre'          => $this->clean($vals[$refIdx ?? -1] ?? null),
                'date_excel'          => $this->clean($vals[$dateIdx ?? -1] ?? null),
                'observation'         => $this->clean($vals[$obsIdx ?? -1] ?? null),
                'parcelle_id'         => $parcelle?->id,
                'matched'             => $matched,
            ]);
        }

        // Mettre à jour les compteurs des Imports
        foreach ($this->importsParProjet as $code => $imp) {
            $imp->update([
                'total_lignes'    => $compteursParCode[$code],
                'lignes_traitees' => $compteursParCode[$code],
                'lignes_matchees' => $matchCountParCode[$code],
                'statut'          => 'termine',
            ]);
            $this->resultats[$code] = [
                'projet'   => $imp->projet_id,
                'import_id'=> $imp->id,
                'total'    => $compteursParCode[$code],
                'matched'  => $matchCountParCode[$code],
            ];
        }
    }

    protected function findCol(array $headers, array $names): ?int
    {
        foreach ($names as $name) {
            $idx = array_search($name, $headers);
            if ($idx !== false) return $idx;
        }
        return null;
    }

    protected function clean($value): ?string
    {
        if ($value === null || $value === '') return null;
        $value = trim((string) $value);
        if (preg_match('/^\d+\.0$/', $value)) $value = (string) intval($value);
        return $value ?: null;
    }
}
