<?php

namespace App\Exports;

use App\Models\Parcelle;
use App\Models\Projet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template Excel pour les MUTATIONS.
 * Liste uniquement les parcelles déjà attribuées (on ne mute pas une parcelle vierge).
 * Le précédent propriétaire est en base, donc absent du fichier.
 * À remplir : nouveau propriétaire + demandeur.
 */
class TemplateImportExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithEvents
{
    protected Projet $projet;
    protected bool $avecLots;

    public function __construct(Projet $projet, bool $avecLots = true)
    {
        $this->projet = $projet;
        $this->avecLots = $avecLots;
    }

    public function headings(): array
    {
        return [
            'N° O',
            'LOT',
            // Nouveau propriétaire (à remplir)
            'Civilité',
            'Prénom',
            'NOM',
            'Type pièce',
            'Code payé',
            'N° pièce',
            'NINEA',
            'Téléphone',
            // Demandeur (à remplir)
            'Prénom demandeur',
            'NOM demandeur',
            'Tél demandeur',
            // Détails mutation (à remplir)
            'Réf. Lettre',
            'Date',
            'Observation',
        ];
    }

    public function array(): array
    {
        if (!$this->avecLots) {
            // Mode template vide générique : 40 lignes vides
            $rows = [];
            for ($i = 1; $i <= 40; $i++) {
                $rows[] = array_merge([$i], array_fill(0, 15, ''));
            }
            return $rows;
        }

        // Uniquement les parcelles ATTRIBUÉES (proprietaire_id NOT NULL)
        $parcelles = Parcelle::where('projet_id', $this->projet->id)
            ->whereNotNull('proprietaire_id')
            ->orderBy('numero_lot')
            ->get();

        $rows = [];
        $i = 1;
        foreach ($parcelles as $parcelle) {
            $rows[] = [
                $i++,
                $parcelle->numero_lot,
                // Nouveau propriétaire (8 colonnes vides)
                '', '', '', '', '', '', '', '',
                // Demandeur (3 colonnes vides)
                '', '', '',
                // Détails (3 colonnes vides)
                '', '', '',
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Mutations';
    }

    public function styles(Worksheet $sheet): array
    {
        $widths = [
            'A' => 6,   // N° O
            'B' => 10,  // LOT
            'C' => 12,  // Civilité
            'D' => 16,  // Prénom
            'E' => 16,  // NOM
            'F' => 14,  // Type pièce
            'G' => 12,  // Code payé
            'H' => 18,  // N° pièce
            'I' => 16,  // NINEA
            'J' => 16,  // Téléphone
            'K' => 18,  // Prénom demandeur
            'L' => 18,  // NOM demandeur
            'M' => 16,  // Tél demandeur
            'N' => 18,  // Réf. Lettre
            'O' => 12,  // Date
            'P' => 24,  // Observation
        ];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->insertNewRowBefore(1, 1);

                $groups = [
                    ['A1:B1', 'LOT',                  'FFE5E7EB', 'FF374151'],
                    ['C1:J1', 'NOUVEAU PROPRIÉTAIRE', 'FFDCFCE7', 'FF166534'],
                    ['K1:M1', 'DEMANDEUR',            'FFDBEAFE', 'FF1E40AF'],
                    ['N1:P1', 'DÉTAILS MUTATION',     'FFFCE7F3', 'FF9D174D'],
                ];

                foreach ($groups as [$range, $label, $bg, $fg]) {
                    $sheet->mergeCells($range);
                    [$cell] = explode(':', $range);
                    $sheet->setCellValue($cell, $label);
                    $sheet->getStyle($range)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => $fg]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBBBBBB']],
                        ],
                    ]);
                }

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(32);
                $sheet->getStyle('A2:P2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']],
                    ],
                ]);

                $sheet->freezePane('A3');
            },
        ];
    }
}
