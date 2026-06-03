<?php

namespace App\Imports;

use App\Models\Import;
use App\Models\ImportLigne;
use App\Models\Parcelle;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class ParcelleImport implements ToCollection, WithHeadingRow
{
    protected Import $import;
    protected int $matchCount = 0;

    public function __construct(Import $import)
    {
        $this->import = $import;
    }

    public function collection(Collection $rows): void
    {
        $totalLignes = 0;

        foreach ($rows as $row) {
            $numeroLot = $this->cleanValue($row['lot'] ?? $row['LOT'] ?? null);
            if (!$numeroLot) {
                continue;
            }

            $totalLignes++;

            // Chercher la correspondance dans la base
            $parcelle = Parcelle::where('numero_lot', $numeroLot)
                ->where('projet_id', $this->import->projet_id)
                ->first();

            $matched = $parcelle !== null;
            if ($matched) {
                $this->matchCount++;
            }

            ImportLigne::create([
                'import_id' => $this->import->id,
                'numero_ordre' => intval($this->cleanValue($row['no'] ?? $row['n_o'] ?? $row['NO'] ?? $totalLignes)),
                'civilite' => $this->cleanValue($row['civilite'] ?? null),
                'prenom' => $this->cleanValue($row['prenom'] ?? $row['prenom_attributaire'] ?? null),
                'nom' => $this->cleanValue($row['nom'] ?? $row['nom_attributaire'] ?? null),
                'type_piece' => $this->cleanValue($row['type_piece'] ?? null),
                'code_paye' => $this->cleanValue($row['code_paye'] ?? $row['code_pays'] ?? null),
                'cni_passport' => $this->cleanValue($row['n_piece'] ?? $row['no_piece'] ?? $row['numero_piece'] ?? $row['cni_passport'] ?? $row['cni'] ?? null),
                'ninea' => $this->cleanValue($row['ninea'] ?? null),
                'telephone' => $this->cleanValue($row['telephone'] ?? $row['tel'] ?? $row['tel_attributaire'] ?? null),
                'demandeur_prenom' => $this->cleanValue($row['prenom_demandeur'] ?? null),
                'demandeur_nom' => $this->cleanValue($row['nom_demandeur'] ?? null),
                'demandeur_telephone' => $this->cleanValue($row['tel_demandeur'] ?? null),
                'numero_lot' => $numeroLot,
                'ref_lettre' => $this->cleanValue($row['ref_lettre'] ?? null),
                'date_excel' => $this->cleanValue($row['date'] ?? null),
                'observation' => $this->cleanValue($row['observation'] ?? null),
                'precedent_attributaire' => $this->cleanValue($row['precedent_proprietaire'] ?? $row['precedent_attributaire'] ?? null),
                'parcelle_id' => $parcelle?->id,
                'matched' => $matched,
            ]);
        }

        $this->import->update([
            'total_lignes' => $totalLignes,
            'lignes_traitees' => $totalLignes,
            'lignes_matchees' => $this->matchCount,
            'statut' => 'termine',
        ]);
    }

    /**
     * Les headings détaillés sont sur la ligne 2 (la ligne 1 contient les groupes).
     */
    public function headingRow(): int
    {
        return 2;
    }

    protected function cleanValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim((string) $value);
        // Nettoyer les .0 des nombres convertis en string
        if (preg_match('/^\d+\.0$/', $value)) {
            $value = (string) intval($value);
        }
        return $value ?: null;
    }
}
