<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CallLogsExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
            'Name',
            'Mobile Number',
            'Company Name',
            'Email',
            'Call Status',
            'Lead Status',
            'Requirement',
            'Source',
            'Created By',
            'Created At',
        ];
    }

    public function title(): string
    {
        return 'Call Logs';
    }

    public function styles(Worksheet $sheet)
    {
        // Bold header row
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 16,
            'C' => 22,
            'D' => 22,
            'E' => 14,
            'F' => 14,
            'G' => 30,
            'H' => 14,
            'I' => 18,
            'J' => 20,
        ];
    }
}
