<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ProductTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function array(): array
    {
        // Lignes d'exemple
        return [
            [
                'Savon Palmolive 250ml',
                '6001234567890',
                'Savon liquide pour les mains',
                750,
                1200,
                10,
                5,
                'pièce',
                18,
                18,
                1000,
                6,
                'Fournisseur ABC',
                'Oui',
            ],
            [
                'Huile Végétale 1L',
                '6009876543210',
                'Huile de cuisine',
                1500,
                2500,
                25,
                10,
                'bouteille',
                18,
                18,
                2000,
                12,
                '',
                'Oui',
            ],
            [
                'Riz Long Grain 5kg',
                '',
                '',
                3000,
                4500,
                50,
                20,
                'sac',
                0,
                0,
                '',
                '',
                'Grossiste Riz',
                'Oui',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'nom',
            'code_barre',
            'description',
            'prix_achat',
            'prix_vente',
            'stock',
            'stock_min',
            'unite',
            'tva_achat',
            'tva_vente',
            'prix_gros',
            'qte_min_gros',
            'fournisseur',
            'prix_ttc',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,  // nom
            'B' => 18,  // code_barre
            'C' => 35,  // description
            'D' => 12,  // prix_achat
            'E' => 12,  // prix_vente
            'F' => 10,  // stock
            'G' => 10,  // stock_min
            'H' => 12,  // unite
            'I' => 12,  // tva_achat
            'J' => 12,  // tva_vente
            'K' => 12,  // prix_gros
            'L' => 14,  // qte_min_gros
            'M' => 20,  // fournisseur
            'N' => 10,  // prix_ttc
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Style pour l'en-tête
            1 => [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Produits';
    }
}
