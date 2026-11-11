<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CallLogsReportsExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
            'Total Calls',
            'Call Logs',
            'Contacts Followup',
            'Follow-up Leads',
            'Follow-up Deals'
        ];
    }

    public function title(): string
    {
        return 'Call Reports';
    }

    public function styles(Worksheet $sheet)
    {
        // Bold header row
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        //Heder background color
        $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');  
        //Border for all cells
        $sheet->getStyle('A1:F' . $sheet->getHighestRow())->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 16,
            'C' => 22,
            'D' => 22,
            'E' => 20,
            'F' => 20,
        ];
    }
}
