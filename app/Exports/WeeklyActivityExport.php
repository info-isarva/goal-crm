<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class WeeklyActivityExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    /** @var array Flat Excel data rows */
    protected $rows;

    /**
     * Group sizes for merging lead/deal columns.
     * Each item: ['start' => excelRowNumber (data sheet before title insert), 'count' => activityRowCount]
     * After title rows are inserted, these are offset by $titleRows.
     */
    protected $groups;

    /** @var array Report heading meta */
    protected $meta;

    /** Number of title/info rows inserted above column headers */
    protected $titleRows = 4;

    public function __construct(array $rows, array $groups = [], array $meta = [])
    {
        $this->rows = $rows;
        $this->groups = $groups;
        $this->meta = array_merge([
            'title' => 'Weekly Activity Report',
            'date_range' => '',
            'period_label' => '',
            'type_label' => '',
            'user_label' => '',
            'generated_on' => now()->format('d M Y h:i A'),
        ], $meta);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Type',
            'Lead / Deal',
            'Status',
            'Stage',
            'Lead Owner',
            'Amount',
            'Activity Type',
            'Activity Title',
            'Activity Description',
            'Activity Status',
            'Created At',
            'Activity By',
        ];
    }

    public function title(): string
    {
        return 'Weekly Activity';
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 36,
            'C' => 16,
            'D' => 18,
            'E' => 22,
            'F' => 14,
            'G' => 14,
            'H' => 35,
            'I' => 45,
            'J' => 16,
            'K' => 18,
            'L' => 22,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $offset = $this->titleRows;

                // Insert professional heading rows above column headers
                $sheet->insertNewRowBefore(1, $offset);

                $headerRow = $offset + 1; // column headings row
                $dataStart = $headerRow + 1;
                $lastDataRow = $headerRow + count($this->rows);
                $tableRange = 'A' . $headerRow . ':L' . max($lastDataRow, $headerRow);

                // Row 1: Report title
                $sheet->mergeCells('A1:L1');
                $sheet->setCellValue('A1', $this->meta['title']);
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F4E79'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Row 2: Date range
                $dateText = 'Report Period: ' . ($this->meta['date_range'] ?: '-');
                if (!empty($this->meta['period_label'])) {
                    $dateText .= '  |  ' . $this->meta['period_label'];
                }
                $sheet->mergeCells('A2:L2');
                $sheet->setCellValue('A2', $dateText);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => '1F4E79'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D6E3F0'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Row 3: Filters / generated info
                $infoParts = [];
                if (!empty($this->meta['type_label'])) {
                    $infoParts[] = 'Type: ' . $this->meta['type_label'];
                }
                if (!empty($this->meta['user_label'])) {
                    $infoParts[] = 'User: ' . $this->meta['user_label'];
                }
                $infoParts[] = 'Generated On: ' . $this->meta['generated_on'];
                $sheet->mergeCells('A3:L3');
                $sheet->setCellValue('A3', implode('   |   ', $infoParts));
                $sheet->getRowDimension(3)->setRowHeight(20);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => [
                        'size' => 10,
                        'color' => ['rgb' => '595959'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Row 4: spacer
                $sheet->mergeCells('A4:L4');
                $sheet->getRowDimension(4)->setRowHeight(8);
                $sheet->getStyle('A4:L4')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFFFF'],
                    ],
                ]);

                // Title block outer border
                $sheet->getStyle('A1:L3')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '1F4E79'],
                        ],
                    ],
                ]);

                // Column header style
                $sheet->getStyle('A' . $headerRow . ':L' . $headerRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F4E79'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(24);

                // Base alignment for table
                $sheet->getStyle($tableRange)->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                // Freeze below title + column header
                $sheet->freezePane('A' . ($headerRow + 1));

                // Merge lead/deal identity columns per group
                foreach ($this->groups as $index => $group) {
                    $start = (int) $group['start'] + $offset;
                    $count = (int) $group['count'];
                    $end = $start + $count - 1;

                    if ($count > 1) {
                        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
                            $sheet->mergeCells("{$col}{$start}:{$col}{$end}");
                            $sheet->getStyle("{$col}{$start}:{$col}{$end}")
                                ->getAlignment()
                                ->setVertical(Alignment::VERTICAL_CENTER)
                                ->setHorizontal($col === 'B' || $col === 'E' ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_CENTER);
                        }
                    }

                    $fillColor = ($index % 2 === 0) ? 'EAF2F8' : 'F5F5F5';
                    $sheet->getStyle("A{$start}:F{$end}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $fillColor],
                        ],
                        'font' => [
                            'bold' => true,
                        ],
                    ]);

                    $sheet->getStyle("G{$start}:G{$end}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);
                }

                // Table borders
                $sheet->getStyle($tableRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Medium outline around each lead/deal block
                foreach ($this->groups as $group) {
                    $start = (int) $group['start'] + $offset;
                    $end = $start + (int) $group['count'] - 1;
                    $sheet->getStyle("A{$start}:L{$end}")->applyFromArray([
                        'borders' => [
                            'outline' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color' => ['rgb' => '1F4E79'],
                            ],
                        ],
                    ]);
                }

                // Auto-filter on data table only
                if (count($this->rows) >= 0) {
                    $sheet->setAutoFilter('A' . $headerRow . ':L' . max($lastDataRow, $headerRow));
                }
            },
        ];
    }
}
