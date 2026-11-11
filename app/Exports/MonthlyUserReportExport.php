<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyUserReportExport implements FromView, WithTitle, WithStyles, WithColumnWidths
{
    protected $results;
    protected $month;
    protected $userId;

    public function __construct($results, $month, $userId)
    {
        $this->results = $results;
        $this->month = $month;
        $this->userId = $userId;
    }

    public function view(): View
    {
        return view('reports.monthly_user_report_excel', [
            'results' => $this->results,
            'month' => $this->month,
            'userId' => $this->userId,
        ]);
    }

    // Set sheet name to month
    public function title(): string
    {
        return date('F_Y', strtotime($this->month . '-01'));
    }

    // Make heading row bold
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    // Set column widths
    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 24,
            'C' => 16,
            'D' => 16,
            'E' => 16,
            'F' => 18,
            'G' => 18,
            'H' => 18,
        ];
    }
}
