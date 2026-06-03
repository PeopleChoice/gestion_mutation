<?php

namespace App\Exports;

use App\Models\Projet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Template Excel pour l'attribution GLOBALE multi-projets.
 * Vide, sauf 1 ligne d'exemple par projet existant (pour rappel des codes).
 */
class AttributionGlobalTemplate implements FromArray, WithHeadings, WithEvents, WithTitle
{
    public function headings(): array
    {
        return [
            'Code projet',
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
            'Observation',
        ];
    }

    public function array(): array
    {
        $rows = [];
        // Une ligne d'exemple vide par projet existant (juste le code, pour repère)
        foreach (Projet::orderBy('code')->get() as $p) {
            $rows[] = [$p->code, '', '', '', '', '', '', '', '', '', '', '— Projet : ' . $p->nom];
        }
        // 20 lignes vides supplémentaires
        for ($i = 0; $i < 20; $i++) {
            $rows[] = ['', '', '', '', '', '', '', '', '', '', '', ''];
        }
        return $rows;
    }

    public function title(): string
    {
        return 'Attribution Globale';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $widths = ['A'=>14,'B'=>10,'C'=>12,'D'=>16,'E'=>16,'F'=>14,'G'=>12,'H'=>16,'I'=>14,'J'=>16,'K'=>20,'L'=>26];
                foreach ($widths as $c => $w) $sheet->getColumnDimension($c)->setWidth($w);

                $sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF166534']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCFCE7']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBBBBBB']]],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(32);

                // Code projet en gras et couleur marron
                $sheet->getStyle('A2:A100')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF5D4E37'], 'name' => 'Courier New'],
                ]);
                $sheet->freezePane('A2');
            },
        ];
    }
}
