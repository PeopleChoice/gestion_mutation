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
 * Export Excel pour l'ATTRIBUTION INITIALE de parcelles vierges.
 * Liste tous les lots non attribués du projet, avec colonnes vides à remplir.
 */
class AttributionTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithEvents
{
    protected Projet $projet;

    public function __construct(Projet $projet)
    {
        $this->projet = $projet;
    }

    public function headings(): array
    {
        return [
            'N° O',
            'LOT',
            'Civilité',
            'Prénom',
            'NOM',
            'Type pièce',
            'Code payé',
            'N° pièce',
            'NINEA',
            'Téléphone',
            'Adresse',
            'Date attribution',
            'Observation',
        ];
    }

    public function array(): array
    {
        // Seulement les parcelles SANS propriétaire (vierges)
        $parcelles = Parcelle::where('projet_id', $this->projet->id)
            ->whereNull('proprietaire_id')
            ->orderBy('numero_lot')
            ->get();

        $rows = [];
        $i = 1;
        foreach ($parcelles as $parcelle) {
            $rows[] = [
                $i++,
                $parcelle->numero_lot,
                '', '', '', '', '', '', '', '', '', '', '',
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Attribution';
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
            'K' => 24,  // Adresse
            'L' => 14,  // Date attribution
            'M' => 24,  // Observation
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

                // Ligne de groupes au-dessus
                $sheet->insertNewRowBefore(1, 1);

                $groups = [
                    ['A1:B1', 'LOT',                  'FFE5E7EB', 'FF374151'],
                    ['C1:K1', 'NOUVEAU PROPRIÉTAIRE', 'FFDCFCE7', 'FF166534'],
                    ['L1:M1', 'DÉTAILS',              'FFFCE7F3', 'FF9D174D'],
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
                $sheet->getRowDimension(2)->setRowHeight(28);
                $sheet->getStyle('A2:M2')->applyFromArray([
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
