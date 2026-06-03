<?php

namespace App\Exports;

use App\Models\Projet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProjetParcellesExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected Projet $projet;
    protected array $data = [];

    public function __construct(Projet $projet)
    {
        $this->projet = $projet;
        $this->buildData();
    }

    public function headings(): array
    {
        return [
            'N°',
            'Lot',
            'Statut',
            'Civilité',
            'Prénom',
            'Nom',
            'CNI / Passeport',
            'NIN',
            'NINEA',
            'Téléphone',
            'Superficie (m²)',
            'Usage',
            'Date attribution',
            'Nb mutations',
        ];
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return $this->projet->nom;
    }

    public function styles(Worksheet $sheet): array
    {
        // En-tête
        $sheet->getStyle('A1:N1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1:N1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF5D4E37');
        $sheet->getStyle('A1:N1')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Auto-width
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Colorer les lignes non attribuées
        $row = 2;
        foreach ($this->data as $d) {
            if ($d[2] === 'Non attribué') {
                $sheet->getStyle("A{$row}:N{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF3E0');
            }
            $row++;
        }

        return [];
    }

    protected function buildData(): void
    {
        $parcelles = $this->projet->parcelles()
            ->with('proprietaire')
            ->withCount('mutations')
            ->orderByRaw("CAST(numero_lot AS INTEGER) ASC, numero_lot ASC")
            ->get();

        $i = 1;
        foreach ($parcelles as $p) {
            $prop = $p->proprietaire;
            $this->data[] = [
                $i++,
                $p->numero_lot,
                $prop ? 'Attribué' : 'Non attribué',
                $prop?->civilite ?? '',
                $prop?->prenom ?? '',
                $prop?->nom ?? '',
                $prop?->cni_passport ?? '',
                $prop?->nin ?? '',
                $prop?->ninea ?? '',
                $prop?->telephone ?? '',
                $p->superficie ?? '',
                $p->usage ? ucfirst($p->usage) : '',
                $p->date_attribution?->format('d/m/Y') ?? '',
                $p->mutations_count,
            ];
        }
    }
}
