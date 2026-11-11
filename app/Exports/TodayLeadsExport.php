<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TodayLeadsExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Title',
            // 'Company Owner',
            'Company Name',
            'Contact Person',
            'Lead Owner',
            'Category',
            'Status',
            
            'Created At',
        ];
    }

    public function title(): string
    {
        return 'Today Leads';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,
            'B' => 30,
            'C' => 30,
            'D' => 30,
            'E' => 25,
            'F' => 18,
            'G' => 20,
        ];
    }
}
