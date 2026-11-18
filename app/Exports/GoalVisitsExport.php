<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GoalVisitsExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(protected array $rows, protected array $headings)
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return 'Customer Visits';
    }

    public function styles(Worksheet $sheet)
    {
        $last = chr(64 + max(1, count($this->headings)));
        $sheet->getStyle('A1:'.$last.'1')->getFont()->setBold(true);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 18, 'C' => 16, 'D' => 18, 'E' => 16,
            'F' => 16, 'G' => 12, 'H' => 16, 'I' => 16, 'J' => 28,
        ];
    }
}
