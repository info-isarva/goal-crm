<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Consolidated Sales Report Excel Export (Exact Design with Native Charts matching Screenshot):
 * - Top Table: Sales Target & Revenue Status
 * - Bottom Left Table: Consolidated Activity Matrix
 * - Bottom Right: Embedded Bar Chart (Revenue) & Pie Chart (Activity Distribution)
 */
class ConsolidatedSalesReportExport implements FromArray, WithEvents, WithTitle, WithCharts
{
    protected array $data;
    protected array $charts = [];

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
        return 'Consolidated Sales Report';
    }

    public function charts()
    {
        return $this->charts;
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
        $monthLabel = $this->data['monthLabel'];
        $shortMonthLabel = $this->data['shortMonthLabel'];
        $weekLabel = $this->data['weekLabel'];
        $users = $this->data['users'];
        $rows = $this->data['rows'];
        $totals = $this->data['totals'];
        $metricRows = $this->data['metricRows'];

        $userCount = max(1, count($rows));
        $matrixCols = 1 + $userCount + 1; // Team Member + users + Total
        $chartStartColIdx = $matrixCols + 2; // 1 column gap after matrix table
        $maxCol = max(8, $chartStartColIdx + 9);
        $chartStartCol = Coordinate::stringFromColumnIndex($chartStartColIdx);
        $chartEndCol = Coordinate::stringFromColumnIndex($chartStartColIdx + 9);

        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        // ===== 1. Sales Target & Revenue Status Table (Top) =====
        $row = 1;
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->setCellValue("A{$row}", 'Sales Target  & Revenue Status - ' . $monthLabel);
        $this->fill($sheet, 1, $row, 8, $row, '002060', true, 12);
        $sheet->getRowDimension($row)->setRowHeight(26);

        $row = 2;
        $headers = [
            'Team Members',
            'Estimated Revenue Current Week',
            'Estimated Revenue in Pipeline',
            'Month Achieved (INR)',
            'Month Balance',
            $weekLabel . ' Target Amt (INR)',
            $weekLabel . ' Achieved Amt (INR)',
            $weekLabel . ' Balance Amt (INR)',
        ];

        foreach ($headers as $idx => $h) {
            $colIdx = $idx + 1;
            $sheet->setCellValueByColumnAndRow($colIdx, $row, $h);
        }

        $this->fill($sheet, 1, $row, 1, $row, '002060', true);
        $this->fill($sheet, 2, $row, 2, $row, 'C0392B', true);
        $this->fill($sheet, 3, $row, 4, $row, '922B21', true);
        $this->fill($sheet, 5, $row, 5, $row, '002060', true);
        $this->fill($sheet, 6, $row, 8, $row, '922B21', true);
        $sheet->getRowDimension($row)->setRowHeight(30);

        $revStartRow = 3;
        $row = $revStartRow;
        foreach ($rows as $r) {
            $sheet->setCellValueByColumnAndRow(1, $row, $r['user']->name);
            $sheet->getStyleByColumnAndRow(1, $row)->getFont()->setBold(true);

            // Set raw numeric values so Excel Charts can parse them
            $sheet->setCellValueByColumnAndRow(2, $row, (float) $r['est_week']);
            $sheet->setCellValueByColumnAndRow(3, $row, (float) $r['pipeline']);
            $sheet->setCellValueByColumnAndRow(4, $row, (float) $r['month_achieve']);
            $sheet->setCellValueByColumnAndRow(5, $row, (float) $r['month_balance']);
            $sheet->setCellValueByColumnAndRow(6, $row, (float) $r['week_target']);
            $sheet->setCellValueByColumnAndRow(7, $row, (float) $r['week_achieve']);
            $sheet->setCellValueByColumnAndRow(8, $row, (float) $r['week_balance']);

            // Custom Number Format Codes
            $sheet->getStyleByColumnAndRow(2, $row)->getNumberFormat()->setFormatCode('"₹"#,##0');
            $sheet->getStyleByColumnAndRow(3, $row)->getNumberFormat()->setFormatCode('"₹"#,##0');
            $sheet->getStyleByColumnAndRow(4, $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyleByColumnAndRow(5, $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyleByColumnAndRow(6, $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyleByColumnAndRow(7, $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyleByColumnAndRow(8, $row)->getNumberFormat()->setFormatCode('#,##0');

            for ($c = 2; $c <= 8; $c++) {
                $sheet->getStyleByColumnAndRow($c, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }

        $revEndRow = $row - 1;

        // Revenue Total Row
        $revTotalRow = $row;
        $sheet->setCellValueByColumnAndRow(1, $row, 'Total');
        $sheet->setCellValueByColumnAndRow(2, $row, (float) $totals['est_week']);
        $sheet->setCellValueByColumnAndRow(3, $row, (float) $totals['pipeline']);
        $sheet->setCellValueByColumnAndRow(4, $row, (float) $totals['month_achieve']);
        $sheet->setCellValueByColumnAndRow(5, $row, (float) $totals['month_balance']);
        $sheet->setCellValueByColumnAndRow(6, $row, (float) $totals['week_target']);
        $sheet->setCellValueByColumnAndRow(7, $row, (float) $totals['week_achieve']);
        $sheet->setCellValueByColumnAndRow(8, $row, (float) $totals['week_balance']);

        $sheet->getStyleByColumnAndRow(2, $row)->getNumberFormat()->setFormatCode('"₹"#,##0');
        $sheet->getStyleByColumnAndRow(3, $row)->getNumberFormat()->setFormatCode('"₹"#,##0');
        $sheet->getStyleByColumnAndRow(4, $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyleByColumnAndRow(5, $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyleByColumnAndRow(6, $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyleByColumnAndRow(7, $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyleByColumnAndRow(8, $row)->getNumberFormat()->setFormatCode('#,##0');

        $this->fill($sheet, 1, $row, 8, $row, '002060', true);
        for ($c = 2; $c <= 8; $c++) {
            $sheet->getStyleByColumnAndRow($c, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        $sheet->getRowDimension($row)->setRowHeight(24);
        $this->borderRange($sheet, 1, 1, 8, $revTotalRow);

        // ===== 2. Blank Row Gap =====
        $row += 2;
        $matrixStartRow = $row;

        // ===== 3. Table 2: Consolidated Report Activity Matrix (Bottom Left) =====
        $sheet->mergeCellsByColumnAndRow(1, $row, $matrixCols, $row);
        $sheet->setCellValueByColumnAndRow(1, $row, 'Consolidated Report - ' . $shortMonthLabel);
        $this->fill($sheet, 1, $row, $matrixCols, $row, 'F4D03F', false, 12);
        $sheet->getStyleByColumnAndRow(1, $row)->getFont()->getColor()->setRGB('000000');
        $sheet->getRowDimension($row)->setRowHeight(26);

        $row++;
        $sheet->setCellValueByColumnAndRow(1, $row, 'Team Member');
        $this->fill($sheet, 1, $row, 1, $row, '104E5B', true);

        $c = 2;
        foreach ($rows as $r) {
            $sheet->setCellValueByColumnAndRow($c, $row, $r['user']->name);
            $this->fill($sheet, $c, $row, $c, $row, '104E5B', true);
            $c++;
        }
        $sheet->setCellValueByColumnAndRow($c, $row, 'Total');
        $this->fill($sheet, $c, $row, $c, $row, '104E5B', true);
        $sheet->getRowDimension($row)->setRowHeight(24);

        $matrixHeaderRow = $row;
        $row++;

        foreach ($metricRows as $m) {
            $sheet->setCellValueByColumnAndRow(1, $row, $m['label']);
            $this->fill($sheet, 1, $row, 1, $row, '002060', true);
            $sheet->getStyleByColumnAndRow(1, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $c = 2;
            foreach ($rows as $r) {
                $val = (int) ($r['metrics'][$m['key']] ?? 0);
                $sheet->setCellValueByColumnAndRow($c, $row, $val);
                $sheet->getStyleByColumnAndRow($c, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $c++;
            }

            $totVal = (int) ($totals['metrics'][$m['key']] ?? 0);
            $sheet->setCellValueByColumnAndRow($c, $row, $totVal);
            $this->fill($sheet, $c, $row, $c, $row, 'FDE9A9', false);
            $sheet->getStyleByColumnAndRow($c, $row)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($c, $row)->getFont()->getColor()->setRGB('000000');
            $sheet->getStyleByColumnAndRow($c, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }

        $matrixEndRow = $row - 1;
        $this->borderRange($sheet, 1, $matrixStartRow, $matrixCols, $matrixEndRow);

        // ===== 4. Embed Native Excel Analytics Charts (Bottom Right) =====
        try {
            $sheetTitle = "'" . $this->title() . "'";

            // A. Clustered Column Bar Chart (Estimated Revenue Current Week vs Pipeline)
            $xAxisCategories = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $sheetTitle . '!$A$' . $revStartRow . ':$A$' . $revEndRow, null, count($rows)),
            ];

            $barSeriesLabels = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $sheetTitle . '!$B$2', null, 1),
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $sheetTitle . '!$C$2', null, 1),
            ];

            $barSeriesValues = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $sheetTitle . '!$B$' . $revStartRow . ':$B$' . $revEndRow, null, count($rows)),
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $sheetTitle . '!$C$' . $revStartRow . ':$C$' . $revEndRow, null, count($rows)),
            ];

            $barSeries = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_CLUSTERED,
                range(0, count($barSeriesValues) - 1),
                $barSeriesLabels,
                $xAxisCategories,
                $barSeriesValues
            );
            $barSeries->setPlotDirection(DataSeries::DIRECTION_COL);

            $barPlotArea = new PlotArea(null, [$barSeries]);
            $barLegend = new Legend(Legend::POSITION_TOP, null, false);
            $barChart = new Chart('revenue_bar_chart', null, $barLegend, $barPlotArea);
            $barChart->setTopLeftPosition($chartStartCol . $matrixStartRow);
            $barChart->setBottomRightPosition($chartEndCol . ($matrixStartRow + 17));
            $sheet->addChart($barChart);
            $this->charts[] = $barChart;

            // B. Pie Chart (Consolidated Report Activity Distribution)
            $totalColLetter = Coordinate::stringFromColumnIndex($matrixCols);
            $pieCategories = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $sheetTitle . '!$A$' . ($matrixHeaderRow + 1) . ':$A$' . $matrixEndRow, null, count($metricRows)),
            ];

            $pieValues = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $sheetTitle . '!$' . $totalColLetter . '$' . ($matrixHeaderRow + 1) . ':$' . $totalColLetter . '$' . $matrixEndRow, null, count($metricRows)),
            ];

            $pieSeries = new DataSeries(
                DataSeries::TYPE_PIECHART,
                null,
                range(0, count($pieValues) - 1),
                [],
                $pieCategories,
                $pieValues
            );

            $piePlotArea = new PlotArea(null, [$pieSeries]);
            $pieLegend = new Legend(Legend::POSITION_RIGHT, null, false);
            $pieTitle = new Title('Consolidated Report - ' . $shortMonthLabel);
            $pieChart = new Chart('activity_pie_chart', $pieTitle, $pieLegend, $piePlotArea);
            $pieChart->setTopLeftPosition($chartStartCol . ($matrixStartRow + 19));
            $pieChart->setBottomRightPosition($chartEndCol . max($matrixEndRow + 2, $matrixStartRow + 36));
            $sheet->addChart($pieChart);
            $this->charts[] = $pieChart;
        } catch (\Throwable $e) {
            // Fallback gracefully if charts engine encounters environment restrictions
        }

        // ===== 5. Auto-fit Column Widths =====
        for ($i = 1; $i <= $maxCol; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    protected function fill(Worksheet $sheet, int $c1, int $r1, int $c2, int $r2, string $rgb, bool $whiteFont = false, int $size = 10): void
    {
        $range = Coordinate::stringFromColumnIndex($c1) . $r1 . ':' . Coordinate::stringFromColumnIndex($c2) . $r2;
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
        $sheet->getStyle($range)->getFont()->setBold(true)->setName('Arial')->setSize($size);
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
}
