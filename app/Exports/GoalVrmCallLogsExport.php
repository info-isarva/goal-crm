<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GoalVrmCallLogsExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(
        protected array $rows,
        protected array $headingRow = [
            '#',
            'When',
            'Customer',
            'Mobile',
            'Disposition',
            'Lead',
            'Notes',
            'Branch',
            'Callback At',
        ]
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headingRow;
    }

    public function title(): string
    {
        return 'VRM Call Logs';
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = chr(ord('A') + max(0, count($this->headingRow) - 1));
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);

        return [];
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 6,
            'B' => 20,
            'C' => 22,
            'D' => 16,
            'E' => 16,
            'F' => 10,
            'G' => 36,
            'H' => 20,
            'I' => 20,
            'J' => 22,
        ];

        return array_slice($widths, 0, count($this->headingRow), true);
    }
}
