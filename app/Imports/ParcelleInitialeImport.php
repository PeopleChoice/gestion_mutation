<?php

namespace App\Imports;

use App\Models\Parcelle;
use App\Models\Projet;
use App\Models\Proprietaire;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ParcelleInitialeImport implements ToCollection
{
    protected Projet $projet;
    protected int $created = 0;
    protected int $skipped = 0;
    protected array $errors = [];

    public function __construct(Projet $projet)
    {
        $this->projet = $projet;
    }

    public function collection(Collection $rows): void
    {
        if ($rows->count() < 2) return;

        // Détecter la ligne des vrais en-têtes : la ligne avec le plus grand nombre
        // de cellules non vides (la ligne 1 peut contenir des titres de groupes fusionnés
        // qui n'ont qu'une seule cellule visible).
        $headerRowIdx = 0;
        $maxCols = 0;
        $maxScan = min(3, $rows->count());
        for ($r = 0; $r < $maxScan; $r++) {
            $cells = $rows->get($r)->values()->filter(fn($v) => trim((string) $v) !== '')->count();
            if ($cells > $maxCols) {
                $maxCols = $cells;
                $headerRowIdx = $r;
            }
        }

        $headers = $rows->get($headerRowIdx)->values()->map(fn($v) => strtolower(trim((string) $v)))->toArray();

        $lotIdx       = $this->findColumn($headers, ['lot']);
        $civiliteIdx  = $this->findColumn($headers, ['civilité', 'civilite']);
        $prenomIdx    = $this->findColumn($headers, ['prénom', 'prenom']);
        $nomIdx       = $this->findColumn($headers, ['nom']);
        $typePieceIdx = $this->findColumn($headers, ['type pièce', 'type piece', 'type_piece']);
        $codePayeIdx  = $this->findColumn($headers, ['code payé', 'code paye', 'code_paye', 'code pays']);
        $cniIdx       = $this->findColumn($headers, ['n° pièce', 'no piece', 'n piece', 'numero piece', 'cni / passeport', 'cni_passport', 'cni']);
        $nineaIdx     = $this->findColumn($headers, ['ninea']);
        $telIdx       = $this->findColumn($headers, ['téléphone', 'telephone', 'tel']);
        $adresseIdx   = $this->findColumn($headers, ['adresse']);
        $dateIdx      = $this->findColumn($headers, ['date attribution', 'date']);
        $refIdx       = $this->findColumn($headers, ['réf. lettre', 'ref_lettre', 'ref lettre', 'ref']);
        $obsIdx       = $this->findColumn($headers, ['observation']);

        if ($lotIdx === null) {
            $this->errors[] = "Colonne 'LOT' introuvable.";
            return;
        }

        // Parcourir les données (lignes après l'en-tête)
        foreach ($rows->slice($headerRowIdx + 1) as $row) {
            $vals = $row->values()->toArray();
            $lot = $this->clean($vals[$lotIdx] ?? null);

            if (!$lot) continue;

            if (Parcelle::where('numero_lot', $lot)->where('projet_id', $this->projet->id)->exists()) {
                $this->skipped++;
                continue;
            }

            $proprietaireId = null;
            $nom = $this->clean($vals[$nomIdx ?? -1] ?? null);
            $prenom = $this->clean($vals[$prenomIdx ?? -1] ?? null);
            $cni = $this->clean($vals[$cniIdx ?? -1] ?? null);

            if ($nom || $prenom || $cni) {
                $proprietaire = Proprietaire::create([
                    'civilite' => $this->clean($vals[$civiliteIdx ?? -1] ?? null) ?: 'Monsieur',
                    'prenom' => $prenom ?: 'N/A',
                    'nom' => $nom ?: 'N/A',
                    'type_piece' => $this->clean($vals[$typePieceIdx ?? -1] ?? null),
                    'cni_passport' => $cni,
                    'ninea' => $this->clean($vals[$nineaIdx ?? -1] ?? null),
                    'telephone' => $this->clean($vals[$telIdx ?? -1] ?? null),
                    'adresse' => $this->clean($vals[$adresseIdx ?? -1] ?? null),
                ]);
                $proprietaireId = $proprietaire->id;
            }

            $dateAttr = $this->parseDate($this->clean($vals[$dateIdx ?? -1] ?? null));
            Parcelle::create([
                'numero_lot' => $lot,
                'projet_id' => $this->projet->id,
                'proprietaire_id' => $proprietaireId,
                'ref_lettre' => $this->clean($vals[$refIdx ?? -1] ?? null),
                'date_attribution' => $proprietaireId ? ($dateAttr ?: now()) : null,
                'observation' => $this->clean($vals[$obsIdx ?? -1] ?? null),
            ]);

            $this->created++;
        }
    }

    protected function findColumn(array $headers, array $names): ?int
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

    /**
     * Parse une date au format d/m/Y, Y-m-d ou serial Excel. Retourne null si invalide.
     */
    protected function parseDate(?string $value): ?\Carbon\Carbon
    {
        if (!$value) return null;
        // Serial Excel (nombre)
        if (is_numeric($value)) {
            try {
                return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value));
            } catch (\Throwable $e) { return null; }
        }
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $fmt) {
            try {
                return \Carbon\Carbon::createFromFormat($fmt, $value);
            } catch (\Throwable $e) { /* try next */ }
        }
        return null;
    }

    public function getCreated(): int { return $this->created; }
    public function getSkipped(): int { return $this->skipped; }
    public function getErrors(): array { return $this->errors; }
}
