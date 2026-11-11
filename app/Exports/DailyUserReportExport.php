<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DailyUserReportExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $results;

    public function __construct($results)
    {
        $this->results = $results;
    }

    /**
     * Column formats for date and amount
     */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Date
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Deal Amount
        ];
    }

    public function array(): array
    {
        // Group by user and date for merging
        $grouped = collect($this->results)->groupBy(function($row) {
            return $row['user']->id . '|' . $row['date'];
        });
        $data = [];
        foreach ($grouped as $rows) {
            $rowCount = $rows->count();
            $i = 0;
            foreach ($rows as $row) {
                $data[] = [
                    // Only show user, date, call_count in first row of group, else empty for merging
                    $i === 0 ? $row['user']->name : '',
                    $i === 0 ? $row['date'] : '',
                    $i === 0 ? ($row['call_count'] !== '' ? $row['call_count'] : '-') : '',
                    $row['lead_name'] !== '' ? $row['lead_name'] : '-',
                    $row['lead_source'] !== '' ? $row['lead_source'] : '-',
                    !empty($row['deal_title']) ? $row['deal_title'] : '-',
                    $row['stage'] !== '' ? $row['stage'] : '-',
                    $row['status'] !== '' ? $row['status'] : '-',
                    $row['closed_date'] !== '' ? $row['closed_date'] : '-',
                    $row['deal_amount'] !== '' ? $row['deal_amount'] : '-',
                    $row['loss_reason'] !== '' ? $row['loss_reason'] : '-',
                ];
                $i++;
            }
        }
        return $data;
    }

    public function headings(): array
    {
        return [
            'User Name',
            'Date',
            'Call Count',
            'Lead Name',
            'Lead Source',
            'Deal Title',
            'Stage',
            'Status',
            'Closed Date',
            'Deal Amount',
            'Loss Reason',
        ];
    }

    public function title(): string
    {
        return 'User Daily Report';
    }

    public function styles(Worksheet $sheet)
    {
        // Bold header row
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        // Add borders to all cells with data
        $rowCount = count($this->array()) + 1; // +1 for header
        $cellRange = 'A1:K' . $rowCount;
        $sheet->getStyle($cellRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Merge cells for user, date, call_count columns for same user/date
        $currentRow = 2; // Start after header
        $grouped = collect($this->results)->groupBy(function($row) {
            return $row['user']->id . '|' . $row['date'];
        });
        foreach ($grouped as $rows) {
            $rowCount = $rows->count();
            if ($rowCount > 1) {
                $endRow = $currentRow + $rowCount - 1;
                $sheet->mergeCells("A{$currentRow}:A{$endRow}");
                $sheet->mergeCells("B{$currentRow}:B{$endRow}");
                $sheet->mergeCells("C{$currentRow}:C{$endRow}");
                // Vertically center merged cells
                $sheet->getStyle("A{$currentRow}:A{$endRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle("B{$currentRow}:B{$endRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle("C{$currentRow}:C{$endRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }
            $currentRow += $rowCount;
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 14,
            'C' => 12,
            'D' => 22,
            'E' => 18,
            'F' => 22,
            'G' => 14,
            'H' => 14,
            'I' => 16,
            'J' => 16,
            'K' => 18,
        ];
    }
}
