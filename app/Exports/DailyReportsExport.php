<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DailyReportsExport implements FromArray, WithHeadings, WithCustomStartCell, WithTitle, WithEvents
{
    protected const HEADER_ROW = 4;

    protected array $rows;
    protected array $headings;
    protected array $meta;
    protected int $outreachColumnCount;

    public function __construct(array $rows, array $headings, array $meta, int $outreachColumnCount)
    {
        $this->rows = $rows;
        $this->headings = $headings;
        $this->meta = $meta;
        $this->outreachColumnCount = $outreachColumnCount;
    }

    public function array(): array
    {
        if (empty($this->rows)) {
            return [];
        }

        // Totals for outreach columns and Total Outreach (S.No, Date, User come first)
        $totals = array_fill(0, count($this->headings), '');
        $totals[2] = 'Total';
        for ($c = 3; $c <= 3 + $this->outreachColumnCount; $c++) {
            $totals[$c] = array_sum(array_map(fn ($row) => (int) ($row[$c] ?? 0), $this->rows));
        }

        return array_merge($this->rows, [$totals]);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function startCell(): string
    {
        return 'A' . self::HEADER_ROW;
    }

    public function title(): string
    {
        return 'Daily Reports';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $colCount = count($this->headings);
                $lastCol = Coordinate::stringFromColumnIndex($colCount);
                $headerRow = self::HEADER_ROW;
                $dataCount = count($this->rows);
                $lastRow = $headerRow + $dataCount + ($dataCount > 0 ? 1 : 0);

                // Title block
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', ($this->meta['company'] ?? '') . ' - ' . ($this->meta['title'] ?? 'Daily Reports'));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', implode('   |   ', array_filter([
                    'Period: ' . ($this->meta['period_label'] ?? '') . ' (' . ($this->meta['range_text'] ?? '') . ')',
                    'User: ' . ($this->meta['user_label'] ?? 'All Users'),
                    'Records: ' . (int) ($this->meta['record_count'] ?? 0),
                    'Generated: ' . ($this->meta['generated_on'] ?? ''),
                    !empty($this->meta['generated_by']) ? 'By: ' . $this->meta['generated_by'] : null,
                ])));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '595959']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Header row
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(24);

                if ($dataCount === 0) {
                    $sheet->mergeCells('A' . ($headerRow + 1) . ":{$lastCol}" . ($headerRow + 1));
                    $sheet->setCellValue('A' . ($headerRow + 1), 'No daily reports found for the selected filters.');
                    $sheet->getStyle('A' . ($headerRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $lastRow = $headerRow + 1;
                }

                // Borders, alignment, zebra rows
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFBFBF']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
                ]);
                for ($r = $headerRow + 1; $r < $headerRow + 1 + $dataCount; $r++) {
                    if (($r - $headerRow) % 2 === 0) {
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F7FAFD');
                    }
                }

                // Column sizing: S.No, Date, User, outreach counts, Total, Other Activity, Status
                $sheet->getColumnDimension('A')->setWidth(7);
                $sheet->getColumnDimension('B')->setWidth(14);
                $sheet->getColumnDimension('C')->setWidth(24);
                $countFrom = 4;
                $countTo = 4 + $this->outreachColumnCount;
                for ($c = $countFrom; $c <= $countTo; $c++) {
                    $col = Coordinate::stringFromColumnIndex($c);
                    $sheet->getColumnDimension($col)->setWidth(14);
                    $sheet->getStyle("{$col}" . ($headerRow + 1) . ":{$col}{$lastRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                $activityCol = Coordinate::stringFromColumnIndex($colCount - 1);
                $statusCol = Coordinate::stringFromColumnIndex($colCount);
                $sheet->getColumnDimension($activityCol)->setWidth(60);
                $sheet->getStyle("{$activityCol}" . ($headerRow + 1) . ":{$activityCol}{$lastRow}")
                    ->getAlignment()->setWrapText(true);
                $sheet->getColumnDimension($statusCol)->setWidth(12);
                $sheet->getStyle("A" . ($headerRow + 1) . ":A{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$statusCol}" . ($headerRow + 1) . ":{$statusCol}{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Totals row
                if ($dataCount > 0) {
                    $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E75B6']]],
                    ]);
                }

                $sheet->freezePane('A' . ($headerRow + 1));
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
            },
        ];
    }
}
