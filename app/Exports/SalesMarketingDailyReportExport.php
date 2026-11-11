<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel layout matches the CRM Model sheet:
 * - Top full-width activity matrix with equal narrow columns
 * - Bottom-left: Sales Target + Lead/Deal Details
 * - Bottom-right: Activities Details spanning remaining matrix columns
 */
class SalesMarketingDailyReportExport implements FromArray, WithEvents, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Daily Sales Marketing';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->buildSheet($event->sheet->getDelegate());
            },
        ];
    }

    protected function buildSheet(Worksheet $sheet): void
    {
        $reportDate = $this->data['reportDate'];
        $monthLabel = $this->data['monthLabel'];
        $weekLabel = $this->data['weekLabel'];
        $rows = $this->data['rows'];
        $totals = $this->data['totals'];
        $productGroups = $this->data['productGroups'];
        $marketingTypes = $this->data['marketingTypes'];
        /** @var Collection $leadsByUser */
        $leadsByUser = $this->data['leadsByUser'];
        /** @var Collection $dealsByUser */
        $dealsByUser = $this->data['dealsByUser'];
        $todayDirectDeals = $this->data['todayDirectDeals'];

        $dateLabel = $reportDate->format('d-m-y');
        $mktCount = max(1, $marketingTypes->count());
        $matrixColCount = 1 + 2 + $this->productColumnCount($productGroups) + $mktCount + 1 + 4;

        // Bottom layout: left tables A-F, activities in dedicated wide columns after matrix
        $leftCols = 6;
        $userCount = max(1, count($rows));
        $actStartCol = $matrixColCount + 2; // gap after matrix
        $actEndCol = $actStartCol + $userCount - 1;
        $sheetColCount = $actEndCol;
        $lastColLetter = Coordinate::stringFromColumnIndex(max($matrixColCount, $sheetColCount));

        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        // ===== Titles =====
        $row = 1;
        $sheet->mergeCells("A{$row}:{$lastColLetter}{$row}");
        $sheet->setCellValue("A{$row}", 'ISARVA INFOTECH PRIVATE LIMITED');
        $this->styleTitle($sheet, "A{$row}", 14);
        $sheet->getRowDimension($row)->setRowHeight(22);

        $row = 2;
        $sheet->mergeCells("A{$row}:{$lastColLetter}{$row}");
        $sheet->setCellValue("A{$row}", 'Daily Sales & Marketing Report - ' . $monthLabel);
        $this->styleTitle($sheet, "A{$row}", 12);
        $sheet->getRowDimension($row)->setRowHeight(18);

        // ===== Activity matrix =====
        $catRow = 4;
        $subRow = 5;

        $col = 1;
        $sheet->setCellValueByColumnAndRow($col, $catRow, "Report\n{$dateLabel}");
        $sheet->mergeCellsByColumnAndRow($col, $catRow, $col, $subRow);
        $this->fill($sheet, $col, $catRow, $col, $subRow, 'F4D03F');
        $sheet->getStyleByColumnAndRow($col, $catRow)->getFont()->setBold(true)->getColor()->setRGB('1A1A1A');
        $sheet->getStyleByColumnAndRow($col, $catRow)->getAlignment()->setWrapText(true);
        $col++;

        $start = $col;
        $sheet->setCellValueByColumnAndRow($col, $catRow, 'Cold Calling');
        $sheet->mergeCellsByColumnAndRow($start, $catRow, $start + 1, $catRow);
        $this->fill($sheet, $start, $catRow, $start + 1, $catRow, 'C0392B', true);
        $sheet->setCellValueByColumnAndRow($col++, $subRow, 'Follow ups');
        $sheet->setCellValueByColumnAndRow($col++, $subRow, 'New Calls');
        $this->fill($sheet, $start, $subRow, $start + 1, $subRow, 'F5B7B1');

        foreach ($productGroups as $group) {
            $count = count($group['columns']);
            if ($count < 1) {
                continue;
            }
            $start = $col;
            $end = $col + $count - 1;
            $sheet->setCellValueByColumnAndRow($col, $catRow, $group['label']);
            $sheet->mergeCellsByColumnAndRow($start, $catRow, $end, $catRow);
            $this->fill($sheet, $start, $catRow, $end, $catRow, ltrim($group['header_bg'], '#'), true);
            foreach ($group['columns'] as $c) {
                $sheet->setCellValueByColumnAndRow($col, $subRow, $c['label']);
                $this->fill($sheet, $col, $subRow, $col, $subRow, ltrim($group['cell_bg'], '#'));
                $col++;
            }
        }

        $start = $col;
        $mktSpan = $mktCount + 1;
        $end = $col + $mktSpan - 1;
        $sheet->setCellValueByColumnAndRow($col, $catRow, 'Marketing Activities');
        $sheet->mergeCellsByColumnAndRow($start, $catRow, $end, $catRow);
        $this->fill($sheet, $start, $catRow, $end, $catRow, '196F3D', true);
        if ($marketingTypes->isEmpty()) {
            $sheet->setCellValueByColumnAndRow($col++, $subRow, '—');
        } else {
            foreach ($marketingTypes as $type) {
                $sheet->setCellValueByColumnAndRow($col++, $subRow, $type->name);
            }
        }
        $sheet->setCellValueByColumnAndRow($col++, $subRow, 'Data Pre Analysis');
        $this->fill($sheet, $start, $subRow, $end, $subRow, 'A9DFBF');

        $start = $col;
        $sheet->setCellValueByColumnAndRow($col, $catRow, 'Lead Status');
        $sheet->mergeCellsByColumnAndRow($start, $catRow, $start + 3, $catRow);
        $this->fill($sheet, $start, $catRow, $start + 3, $catRow, '148F77', true);
        $sheet->setCellValueByColumnAndRow($col++, $subRow, 'Leads Gen');
        $sheet->setCellValueByColumnAndRow($col++, $subRow, 'Leads Converted');
        $sheet->setCellValueByColumnAndRow($col++, $subRow, 'Quote Sent');
        $sheet->setCellValueByColumnAndRow($col++, $subRow, 'Invoice Sent');
        $this->fill($sheet, $start, $subRow, $start + 3, $subRow, 'A3E4D7');

        $this->borderRange($sheet, 1, $catRow, $matrixColCount, $subRow);
        $matrixLast = Coordinate::stringFromColumnIndex($matrixColCount);
        $sheet->getStyle("A{$catRow}:{$matrixLast}{$subRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension($catRow)->setRowHeight(20);
        $sheet->getRowDimension($subRow)->setRowHeight(34);

        $dataRow = $subRow + 1;
        foreach ($rows as $r) {
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col, $dataRow, $r['user']->name);
            $this->fill($sheet, $col, $dataRow, $col, $dataRow, 'F8F9F9');
            $sheet->getStyleByColumnAndRow($col, $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $col++;

            $coldStart = $col;
            $sheet->setCellValueByColumnAndRow($col++, $dataRow, $r['follow_ups']);
            $sheet->setCellValueByColumnAndRow($col++, $dataRow, $r['new_calls']);
            $this->fill($sheet, $coldStart, $dataRow, $coldStart + 1, $dataRow, 'FADBD8');

            foreach ($productGroups as $group) {
                foreach ($group['columns'] as $colKey => $c) {
                    $sheet->setCellValueByColumnAndRow($col, $dataRow, $r['products'][$group['key']][$colKey] ?? 0);
                    $this->fill($sheet, $col, $dataRow, $col, $dataRow, ltrim($group['cell_bg'], '#'));
                    $col++;
                }
            }

            $mktStart = $col;
            if ($marketingTypes->isEmpty()) {
                $sheet->setCellValueByColumnAndRow($col++, $dataRow, 0);
            } else {
                foreach ($marketingTypes as $type) {
                    $sheet->setCellValueByColumnAndRow($col++, $dataRow, $r['marketing'][$type->id] ?? 0);
                }
            }
            $sheet->setCellValueByColumnAndRow($col++, $dataRow, $r['data_pre_analysis']);
            $this->fill($sheet, $mktStart, $dataRow, $col - 1, $dataRow, 'D5F5E3');

            $leadStart = $col;
            $sheet->setCellValueByColumnAndRow($col++, $dataRow, $r['leads_gen']);
            $sheet->setCellValueByColumnAndRow($col++, $dataRow, $r['leads_converted']);
            $sheet->setCellValueByColumnAndRow($col++, $dataRow, $r['quote_sent']);
            $sheet->setCellValueByColumnAndRow($col++, $dataRow, $r['invoice_sent']);
            $this->fill($sheet, $leadStart, $dataRow, $leadStart + 3, $dataRow, 'D1F2EB');

            $this->borderRange($sheet, 1, $dataRow, $matrixColCount, $dataRow);
            $sheet->getStyle("B{$dataRow}:{$matrixLast}{$dataRow}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $dataRow++;
        }

        // Totals
        $totalRow = $dataRow;
        $col = 1;
        $sheet->setCellValueByColumnAndRow($col++, $totalRow, 'Total');
        $sheet->setCellValueByColumnAndRow($col++, $totalRow, $totals['follow_ups']);
        $sheet->setCellValueByColumnAndRow($col++, $totalRow, $totals['new_calls']);
        foreach ($productGroups as $group) {
            foreach (array_keys($group['columns']) as $colKey) {
                $sheet->setCellValueByColumnAndRow($col++, $totalRow, $totals['products'][$group['key']][$colKey] ?? 0);
            }
        }
        if ($marketingTypes->isEmpty()) {
            $sheet->setCellValueByColumnAndRow($col++, $totalRow, 0);
        } else {
            foreach ($marketingTypes as $type) {
                $sheet->setCellValueByColumnAndRow($col++, $totalRow, $totals['marketing'][$type->id] ?? 0);
            }
        }
        $sheet->setCellValueByColumnAndRow($col++, $totalRow, $totals['data_pre_analysis']);
        $sheet->setCellValueByColumnAndRow($col++, $totalRow, $totals['leads_gen']);
        $sheet->setCellValueByColumnAndRow($col++, $totalRow, $totals['leads_converted']);
        $sheet->setCellValueByColumnAndRow($col++, $totalRow, $totals['quote_sent']);
        $sheet->setCellValueByColumnAndRow($col++, $totalRow, $totals['invoice_sent']);
        $this->fill($sheet, 1, $totalRow, $matrixColCount, $totalRow, '1E4D8C', true);
        $this->borderRange($sheet, 1, $totalRow, $matrixColCount, $totalRow);

        // ===== Bottom: LEFT sales/leads | RIGHT activities (same column grid) =====
        $bottomStart = $totalRow + 2;

        // --- LEFT: Sales Target (A-F) ---
        $row = $bottomStart;
        $sheet->mergeCellsByColumnAndRow(1, $row, $leftCols, $row);
        $sheet->setCellValueByColumnAndRow(1, $row, 'Sales Target & Revenue Status - ' . $monthLabel);
        $this->fill($sheet, 1, $row, $leftCols, $row, '1E4D8C', true);
        $this->borderRange($sheet, 1, $row, $leftCols, $row);

        $row++;
        $salesHeaders = ['Team Members', 'Month Target', 'Month Achieve', 'Month Balance', $weekLabel . ' Target Amt', $weekLabel . ' Achieved'];
        foreach ($salesHeaders as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, $row, $h);
        }
        $this->fill($sheet, 1, $row, $leftCols, $row, '922B21', true);
        $this->borderRange($sheet, 1, $row, $leftCols, $row);

        foreach ($rows as $r) {
            $row++;
            $sheet->setCellValueByColumnAndRow(1, $row, $r['user']->name);
            $sheet->setCellValueByColumnAndRow(2, $row, $this->formatInr($r['month_target']));
            $sheet->setCellValueByColumnAndRow(3, $row, $this->formatInr($r['month_achieve']));
            $sheet->setCellValueByColumnAndRow(4, $row, $this->formatInr($r['month_balance']));
            $sheet->setCellValueByColumnAndRow(5, $row, $this->formatInr($r['week_target']));
            $sheet->setCellValueByColumnAndRow(6, $row, $this->formatInr($r['week_achieve']));
            $this->borderRange($sheet, 1, $row, $leftCols, $row);
            $sheet->getStyleByColumnAndRow(1, $row)->getFont()->setBold(false);
            $sheet->getStyleByColumnAndRow(1, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            for ($i = 2; $i <= 6; $i++) {
                $sheet->getStyleByColumnAndRow($i, $row)->getFont()->setBold(false);
                $sheet->getStyleByColumnAndRow($i, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }
        $row++;
        $sheet->setCellValueByColumnAndRow(1, $row, 'Total');
        $sheet->setCellValueByColumnAndRow(2, $row, $this->formatInr($totals['month_target']));
        $sheet->setCellValueByColumnAndRow(3, $row, $this->formatInr($totals['month_achieve']));
        $sheet->setCellValueByColumnAndRow(4, $row, $this->formatInr($totals['month_balance']));
        $sheet->setCellValueByColumnAndRow(5, $row, $this->formatInr($totals['week_target']));
        $sheet->setCellValueByColumnAndRow(6, $row, $this->formatInr($totals['week_achieve']));
        $this->fill($sheet, 1, $row, $leftCols, $row, '1E4D8C', true);
        $this->borderRange($sheet, 1, $row, $leftCols, $row);        // --- LEFT: Lead Details (A-F) ---
        $row += 2;
        $sheet->mergeCellsByColumnAndRow(1, $row, 6, $row);
        $sheet->setCellValueByColumnAndRow(1, $row, 'Lead Details');
        $this->fill($sheet, 1, $row, 6, $row, '1E4D8C', true);
        $this->borderRange($sheet, 1, $row, 6, $row);

        $row++;
        foreach (['Team Members', 'Lead Details', 'Product/Services', 'Industries', 'Source', 'Estimated Amt'] as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, $row, $h);
        }
        $this->fill($sheet, 1, $row, 6, $row, '922B21', true);
        $this->borderRange($sheet, 1, $row, 6, $row);

        $hasLeadRows = false;
        foreach ($rows as $r) {
            $userLeads = $leadsByUser->get($r['user']->id) ?? collect();
            if ($userLeads->isEmpty()) {
                $row++;
                $this->writePlainRow($sheet, $row, [
                    $r['user']->name, '—', '—', '—', '—', '—',
                ], 6);
                $hasLeadRows = true;
                continue;
            }
            foreach ($userLeads as $lead) {
                $hasLeadRows = true;
                $personName = trim(implode(' ', array_filter([
                    optional($lead->person)->first_name,
                    optional($lead->person)->last_name,
                ])));
                $detail = $lead->title
                    ?: optional($lead->organization)->name
                    ?: ($personName !== '' ? $personName : null)
                    ?: 'Lead #' . $lead->id;
                $estimatedAmt = ($lead->amount !== null && $lead->amount !== '' && (float)$lead->amount > 0) ? $this->formatInr($lead->amount) : '—';
                $row++;
                $this->writePlainRow($sheet, $row, [
                    $r['user']->name,
                    $detail,
                    $lead->product_services_label ?? '—',
                    $lead->industry_label ?? '—',
                    optional($lead->leadSource)->name ?? '—',
                    $estimatedAmt,
                ], 6);
            }
        }
        if (!$hasLeadRows) {
            $row++;
            $sheet->mergeCellsByColumnAndRow(1, $row, 6, $row);
            $sheet->setCellValueByColumnAndRow(1, $row, 'No leads created on this date.');
            $this->borderRange($sheet, 1, $row, 6, $row);
        }

        if ($todayDirectDeals->isNotEmpty()) {
            $row += 2;
            $sheet->mergeCellsByColumnAndRow(1, $row, 6, $row);
            $sheet->setCellValueByColumnAndRow(1, $row, 'Deal Details');
            $this->fill($sheet, 1, $row, 6, $row, '1A5276', true);
            $this->borderRange($sheet, 1, $row, 6, $row);

            $row++;
            foreach (['Team Members', 'Deal Details', 'Product/Services', 'Industries', 'Source', 'Estimated Amt'] as $i => $h) {
                $sheet->setCellValueByColumnAndRow($i + 1, $row, $h);
            }
            $this->fill($sheet, 1, $row, 6, $row, '922B21', true);
            $this->borderRange($sheet, 1, $row, 6, $row);

            foreach ($rows as $r) {
                $userDeals = $dealsByUser->get($r['user']->id) ?? collect();
                foreach ($userDeals as $deal) {
                    $personName = trim(implode(' ', array_filter([
                        optional($deal->person)->first_name,
                        optional($deal->person)->last_name,
                    ])));
                    $detail = $deal->title
                        ?: optional($deal->organization)->name
                        ?: ($personName !== '' ? $personName : null)
                        ?: 'Deal #' . $deal->id;
                    $estimatedAmt = ($deal->amount !== null && $deal->amount !== '' && (float)$deal->amount > 0) ? $this->formatInr($deal->amount) : '—';
                    $row++;
                    $this->writePlainRow($sheet, $row, [
                        $r['user']->name,
                        $detail,
                        $deal->product_services_label ?? '—',
                        $deal->industry_label ?? '—',
                        optional($deal->dealSource)->name ?? '—',
                        $estimatedAmt,
                    ], 6);
                }
            }
        }

        $leftEndRow = $row;

        // --- RIGHT: Activities Details (one wide column per team member) ---
        $actTitleRow = $bottomStart;
        $actNameRow = $bottomStart + 1;
        $actBodyStart = $bottomStart + 2;
        // Extend below left tables so activity text has proper vertical space
        $actBodyEnd = max($leftEndRow + 8, $actBodyStart + 16);

        $sheet->mergeCellsByColumnAndRow($actStartCol, $actTitleRow, $actEndCol, $actTitleRow);
        $sheet->setCellValueByColumnAndRow($actStartCol, $actTitleRow, 'Sales and Marketing Activities Details');
        $this->fill($sheet, $actStartCol, $actTitleRow, $actEndCol, $actTitleRow, '1E4D8C', true);
        $this->borderRange($sheet, $actStartCol, $actTitleRow, $actEndCol, $actTitleRow);

        $usersForAct = count($rows) > 0 ? array_values($rows) : [null];
        foreach ($usersForAct as $idx => $r) {
            $c = $actStartCol + $idx;
            $name = $r ? $r['user']->name : '—';
            $sheet->setCellValueByColumnAndRow($c, $actNameRow, $name);
            $this->fill($sheet, $c, $actNameRow, $c, $actNameRow, '922B21', true);
            $this->borderRange($sheet, $c, $actNameRow, $c, $actNameRow);

            if ($r) {
                $todayActs = $r['today_activities'] ?? [];
                $hasOther = !empty($r['other_activity']) && trim(strip_tags($r['other_activity'])) !== '';

                $parts = [];
                if (!empty($todayActs)) {
                    $actLines = ["Today's Activities:"];
                    foreach ($todayActs as $i => $act) {
                        $line = ($i + 1) . ". [" . $act['type'] . "] " . $act['title'];
                        if (!empty($act['related'])) {
                            $line .= " (" . $act['related'] . ")";
                        }
                        $actLines[] = $line;
                    }
                    $parts[] = implode("\n", $actLines);
                }

                if ($hasOther) {
                    $otherText = $this->htmlToText($r['other_activity']);
                    if (!empty($todayActs)) {
                        $parts[] = "Other Related Activity:\n" . $otherText;
                    } else {
                        $parts[] = $otherText;
                    }
                }

                $hasText = !empty($parts);
                $text = $hasText
                    ? implode("\n\n", $parts)
                    : ($r['has_report'] ? 'No activity details entered.' : 'Daily report not submitted.');
            } else {
                $hasText = false;
                $text = 'No team members found.';
            }

            $sheet->mergeCellsByColumnAndRow($c, $actBodyStart, $c, $actBodyEnd);
            $sheet->setCellValueByColumnAndRow($c, $actBodyStart, $text);
            $style = $sheet->getStyleByColumnAndRow($c, $actBodyStart);
            $style->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $style->getFont()->setBold(false)->setName('Arial')->setSize(10);
            if (!$hasText) {
                $style->getFont()->setItalic(true)->getColor()->setRGB('7F8C8D');
            }
            $this->borderRange($sheet, $c, $actBodyStart, $c, $actBodyEnd);
        }

        // Extra rows below left content give the activity boxes readable height
        for ($r = $leftEndRow + 1; $r <= $actBodyEnd; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(18);
        }

        // Proper readable column widths — wider for Lead/Deal text columns so data does not overlap
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(26);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(16);
        for ($i = 7; $i <= $matrixColCount; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(14);
        }
        $spacer = $matrixColCount + 1;
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($spacer))->setWidth(4);
        $activityWidth = 36;
        for ($i = $actStartCol; $i <= $actEndCol; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth($activityWidth);
        }
    }

    protected function writePlainRow(Worksheet $sheet, int $row, array $values, int $colCount): void
    {
        $maxLen = 0;
        foreach ($values as $i => $value) {
            $text = (string) $value;
            $maxLen = max($maxLen, strlen($text));
            $sheet->setCellValueByColumnAndRow($i + 1, $row, $text);
            $style = $sheet->getStyleByColumnAndRow($i + 1, $row);
            $style->getFont()->setBold(false)->setName('Arial')->setSize(10);
            $style->getAlignment()
                ->setWrapText(true)
                ->setShrinkToFit(false)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setHorizontal($i === 0 ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_LEFT);
        }
        $this->borderRange($sheet, 1, $row, $colCount, $row);
        // Grow row height so wrapped Lead/Deal text stays inside its cell
        $approxLines = max(1, (int) ceil($maxLen / 28));
        $sheet->getRowDimension($row)->setRowHeight(min(60, 15 + ($approxLines * 12)));
    }

    protected function productColumnCount(array $productGroups): int
    {
        $n = 0;
        foreach ($productGroups as $group) {
            $n += count($group['columns']);
        }
        return $n;
    }

    protected function fill(Worksheet $sheet, int $c1, int $r1, int $c2, int $r2, string $rgb, bool $whiteFont = false): void
    {
        $range = Coordinate::stringFromColumnIndex($c1) . $r1 . ':' . Coordinate::stringFromColumnIndex($c2) . $r2;
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
        $sheet->getStyle($range)->getFont()->setBold(true)->setName('Arial')->setSize(10);
        if ($whiteFont) {
            $sheet->getStyle($range)->getFont()->getColor()->setRGB('FFFFFF');
        }
        $sheet->getStyle($range)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
    }

    protected function borderRange(Worksheet $sheet, int $c1, int $r1, int $c2, int $r2): void
    {
        $range = Coordinate::stringFromColumnIndex($c1) . $r1 . ':' . Coordinate::stringFromColumnIndex($c2) . $r2;
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    protected function styleTitle(Worksheet $sheet, string $cell, int $size): void
    {
        $sheet->getStyle($cell)->getFont()->setBold(true)->setSize($size)->setName('Arial');
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    protected function formatInr($amount): string
    {
        $amount = (float) $amount;
        $negative = $amount < 0;
        $amount = abs($amount);
        $int = (string) (int) round($amount);
        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        if ($rest !== '' && $rest !== false) {
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $formatted = $rest . ',' . $last3;
        } else {
            $formatted = $last3;
        }
        return ($negative ? '-' : '') . $formatted;
    }

    protected function htmlToText(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }
        $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $html);
        $text = preg_replace('/<\/\s*p\s*>/i', "\n", $text);
        $text = preg_replace('/<\/\s*li\s*>/i', "\n", $text);
        $text = strip_tags($text ?? '');
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text ?? '');
    }
}
