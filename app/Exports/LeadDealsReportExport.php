<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class LeadDealsReportExport implements FromArray, WithHeadings, WithTitle, WithColumnWidths, WithEvents
{
    protected array $rows;
    protected string $nameLabel;
    protected array $meta;
    protected array $headings;
    protected int $titleRows = 4;
    protected int $colCount;

    public function __construct(array $rows, string $type = 'lead', array $meta = [], array $headings = [])
    {
        $this->rows = $rows;
        $this->nameLabel = $type === 'deal' ? 'Deal Name' : 'Lead Name';
        $this->headings = !empty($headings) ? array_values($headings) : [
            $this->nameLabel,
            'Company',
            'Lead Source',
            'Product/Services',
            'Amount',
        ];
        $this->colCount = max(count($this->headings), 1);
        $this->meta = array_merge([
            'title' => 'Lead Deals Report',
            'date_range' => '',
            'period_label' => '',
            'type_label' => $type === 'deal' ? 'Deals' : 'Leads',
            'category_label' => 'All',
            'source_label' => 'All',
            'owner_label' => 'All',
            'record_count' => count($rows),
            'total_amount' => null,
            'generated_on' => now()->format('d M Y h:i A'),
            'generated_by' => '',
        ], $meta);
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
        return 'Lead Deals Report';
    }

    public function columnWidths(): array
    {
        $widths = [];
        for ($i = 1; $i <= $this->colCount; $i++) {
            $letter = Coordinate::stringFromColumnIndex($i);
            $heading = strtolower((string) ($this->headings[$i - 1] ?? ''));
            if (str_contains($heading, 'amount')) {
                $widths[$letter] = 16;
            } elseif (str_contains($heading, 'name') || str_contains($heading, 'product') || str_contains($heading, 'company')) {
                $widths[$letter] = 32;
            } elseif (str_contains($heading, 'source') || str_contains($heading, 'status') || str_contains($heading, 'owner') || str_contains($heading, 'stage')) {
                $widths[$letter] = 20;
            } else {
                $widths[$letter] = 18;
            }
        }
        return $widths;
    }

    protected function lastColumnLetter(): string
    {
        return Coordinate::stringFromColumnIndex($this->colCount);
    }

    protected function amountColumnIndex(): int
    {
        foreach ($this->headings as $idx => $heading) {
            if (strcasecmp(trim((string) $heading), 'Amount') === 0) {
                return $idx + 1;
            }
        }
        return $this->colCount;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

                $lastCol = $this->lastColumnLetter();
                $amountColIdx = $this->amountColumnIndex();
                $amountCol = Coordinate::stringFromColumnIndex($amountColIdx);
                $beforeAmountCol = $amountColIdx > 1
                    ? Coordinate::stringFromColumnIndex($amountColIdx - 1)
                    : 'A';

                $offset = $this->titleRows;
                $sheet->insertNewRowBefore(1, $offset);

                $headerRow = $offset + 1;
                $dataStart = $headerRow + 1;
                $lastDataRow = $headerRow + max(count($this->rows), 0);
                $hasData = count($this->rows) > 0;
                $tableEndRow = $hasData ? $lastDataRow : $headerRow;
                $tableRange = 'A' . $headerRow . ':' . $lastCol . $tableEndRow;

                // Row 1: Title
                $sheet->mergeCells('A1:' . $lastCol . '1');
                $sheet->setCellValue('A1', $this->meta['title']);
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF'],
                        'name' => 'Calibri',
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

                // Row 2: Period + totals
                $countLabel = ($this->meta['type_label'] ?? 'Leads') === 'Deals' ? 'Total Deals' : 'Total Leads';
                $dateText = 'Report Period: ' . ($this->meta['date_range'] ?: '-');
                $dateText .= '  |  ' . $countLabel . ': ' . (int) $this->meta['record_count'];
                if ($this->meta['total_amount'] !== null && $this->meta['total_amount'] !== '') {
                    $dateText .= '  |  Total Amount: ' . \App\Helpers\MoneyFormatter::formatIndian($this->meta['total_amount']);
                }
                $sheet->mergeCells('A2:' . $lastCol . '2');
                $sheet->setCellValue('A2', $dateText);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
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

                // Row 3: Filters / meta
                $infoParts = [
                    'Type: ' . ($this->meta['type_label'] ?: '-'),
                    'Product/Services: ' . ($this->meta['category_label'] ?: 'All'),
                    'Lead Source: ' . ($this->meta['source_label'] ?: 'All'),
                    'Owner: ' . ($this->meta['owner_label'] ?: 'All'),
                ];
                if (!empty($this->meta['stage_label'])) {
                    $infoParts[] = 'Stage: ' . $this->meta['stage_label'];
                }
                $infoParts = array_merge($infoParts, [
                    'Records: ' . (int) $this->meta['record_count'],
                    'Generated: ' . $this->meta['generated_on'],
                ]);

                if (!empty($this->meta['generated_by'])) {
                    $infoParts[] = 'By: ' . $this->meta['generated_by'];
                }
                $sheet->mergeCells('A3:' . $lastCol . '3');
                $sheet->setCellValue('A3', implode('   |   ', $infoParts));
                $sheet->getRowDimension(3)->setRowHeight(34);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => [
                        'size' => 9,
                        'color' => ['rgb' => '595959'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                // Row 4: spacer
                $sheet->mergeCells('A4:' . $lastCol . '4');
                $sheet->getRowDimension(4)->setRowHeight(8);

                $sheet->getStyle('A1:' . $lastCol . '3')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '1F4E79'],
                        ],
                    ],
                ]);

                // Column headers
                $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2E75B6'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '1F4E79'],
                        ],
                    ],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(24);

                if ($hasData) {
                    $dataRange = 'A' . $dataStart . ':' . $lastCol . $lastDataRow;
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'BDD7EE'],
                            ],
                        ],
                        'font' => [
                            'size' => 10,
                            'color' => ['rgb' => '333333'],
                        ],
                    ]);

                    for ($r = $dataStart; $r <= $lastDataRow; $r++) {
                        if (($r - $dataStart) % 2 === 1) {
                            $sheet->getStyle('A' . $r . ':' . $lastCol . $r)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F2F7FB'],
                                ],
                            ]);
                        }
                        $sheet->getRowDimension($r)->setRowHeight(20);
                    }

                    $indianAmountFormat = \App\Helpers\MoneyFormatter::excelIndianNumberFormat();

                    $sheet->getStyle($amountCol . $dataStart . ':' . $amountCol . $lastDataRow)->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        ],
                        'numberFormat' => [
                            'formatCode' => $indianAmountFormat,
                        ],
                    ]);

                    if ($amountColIdx > 1) {
                        $sheet->getStyle('A' . $dataStart . ':' . $beforeAmountCol . $lastDataRow)->applyFromArray([
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_LEFT,
                            ],
                        ]);
                    }

                    // Totals row
                    $totalRow = $lastDataRow + 1;
                    $countLabel = ($this->meta['type_label'] ?? 'Leads') === 'Deals' ? 'Total Deals' : 'Total Leads';
                    $sheet->setCellValue('A' . $totalRow, $countLabel . ': ' . count($this->rows));
                    if ($amountColIdx > 1) {
                        $sheet->mergeCells('A' . $totalRow . ':' . $beforeAmountCol . $totalRow);
                    }
                    $sheet->setCellValue($amountCol . $totalRow, '=SUM(' . $amountCol . $dataStart . ':' . $amountCol . $lastDataRow . ')');
                    $sheet->getStyle('A' . $totalRow . ':' . $lastCol . $totalRow)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '1F4E79'],
                            'size' => 11,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'D6E3F0'],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '1F4E79'],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                    $sheet->getStyle('A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle($amountCol . $totalRow)->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                        'numberFormat' => ['formatCode' => $indianAmountFormat],
                    ]);
                    $sheet->getRowDimension($totalRow)->setRowHeight(24);
                } else {
                    $emptyRow = $headerRow + 1;
                    $sheet->mergeCells('A' . $emptyRow . ':' . $lastCol . $emptyRow);
                    $sheet->setCellValue('A' . $emptyRow, 'No records found for the selected filters.');
                    $sheet->getStyle('A' . $emptyRow . ':' . $lastCol . $emptyRow)->applyFromArray([
                        'font' => [
                            'italic' => true,
                            'color' => ['rgb' => '666666'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'BDD7EE'],
                            ],
                        ],
                    ]);
                    $sheet->getRowDimension($emptyRow)->setRowHeight(28);
                }

                $sheet->getStyle($tableRange)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
                $sheet->getStyle($tableRange)->getBorders()->getOutline()->getColor()->setRGB('1F4E79');

                $sheet->freezePane('A' . ($headerRow + 1));
                $sheet->setAutoFilter('A' . $headerRow . ':' . $lastCol . $headerRow);
            },
        ];
    }
}
