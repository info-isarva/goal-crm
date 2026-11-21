<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Library\MpdfDocument;
use App\Services\Goal\GoalAnalyticsMisService;
use App\Services\Goal\GoalDashboardService;
use App\Services\Goal\GoalMisReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GoalMisReportController extends Controller
{
    public function __construct(
        protected GoalMisReportService $reports,
        protected GoalAnalyticsMisService $analytics
    ) {}

    public function show(string $report)
    {
        $user = auth()->user();
        $this->authorizeMis($user);

        if (! isset(GoalMisReportService::REPORTS[$report])) {
            abort(404, 'Unknown MIS report.');
        }

        $filters = $this->requestFilters();
        $reportData = $this->safeBuild($user, $report, $filters);
        $scopeIds = $this->analytics->resolveScopeIds($user, $filters);
        $filterOptions = $this->analytics->filterOptions($user, $scopeIds);
        $fyOptions = $this->analytics->fyOptions();

        return view('goal.mis-reports.show', [
            'report' => $reportData,
            'user' => $user,
            'filterOptions' => $filterOptions,
            'fyOptions' => $fyOptions,
            'filters' => $reportData['filters'] ?? $filters,
            'catalog' => $this->reports->catalog(),
        ]);
    }

    public function export(Request $request, string $report)
    {
        $user = auth()->user();
        $this->authorizeMis($user);

        if (! isset(GoalMisReportService::REPORTS[$report])) {
            abort(404, 'Unknown MIS report.');
        }

        $format = strtolower((string) $request->input('format', 'xlsx'));
        if (! in_array($format, ['pdf', 'excel', 'xlsx', 'csv'], true)) {
            $format = 'xlsx';
        }
        if ($format === 'excel') {
            $format = 'xlsx';
        }

        $filters = $this->requestFilters();
        $reportData = $this->safeBuild($user, $report, $filters);
        $stamp = now()->format('Ymd-His');
        $slug = str_replace('_', '-', $report);
        $title = ($reportData['title'] ?? 'MIS Report').' Export';

        if ($format === 'pdf') {
            $download = ! $request->boolean('inline');

            return MpdfDocument::fromView(
                'goal.mis-reports.export-pdf',
                [
                    'title' => $title,
                    'report' => $reportData,
                    'owner' => $user,
                    'generatedAt' => now(),
                ],
                $slug.'-'.$stamp.'.pdf',
                $download,
                'A4-L'
            );
        }

        $rows = $this->exportRows($reportData, $title, $user);

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, $slug.'-'.$stamp.'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return $this->streamXlsx($rows, $slug.'-'.$stamp.'.xlsx', $reportData['title'] ?? 'MIS');
    }

    protected function exportRows(array $report, string $title, $user): array
    {
        $rows = [];
        $rows[] = ['Report', $title];
        $rows[] = ['Generated At', now()->format('d M Y H:i')];
        $rows[] = ['Generated For', $user->name];
        $rows[] = ['Scope', $report['scope_label'] ?? '—'];
        $rows[] = ['Period', $report['period_label'] ?? ''];
        $rows[] = [];
        $rows[] = ['KPI', 'Value'];
        foreach (($report['kpis'] ?? []) as $kpi) {
            $rows[] = [$kpi['label'] ?? '', $kpi['value'] ?? ''];
        }
        $rows[] = [];

        $columns = $report['columns'] ?? [];
        $header = array_map(fn ($c) => $c['label'] ?? ($c['key'] ?? ''), $columns);
        if ($header !== []) {
            $rows[] = $header;
        }
        foreach (($report['rows'] ?? []) as $row) {
            $line = [];
            foreach ($columns as $col) {
                $key = $col['key'] ?? '';
                if ($key === '' || str_starts_with($key, '_')) {
                    continue;
                }
                $line[] = $row[$key] ?? '';
            }
            $rows[] = $line;
        }

        return $rows;
    }

    protected function streamXlsx(array $rows, string $filename, string $sheetName = 'MIS'): StreamedResponse
    {
        $sheetName = substr(preg_replace('/[^\w\s\-]/', '', $sheetName) ?: 'MIS', 0, 31);

        return response()->streamDownload(function () use ($rows, $sheetName) {
            $sheet = '';
            foreach ($rows as $rIdx => $cols) {
                $sheet .= '<row r="'.($rIdx + 1).'">';
                foreach (array_values($cols) as $cIdx => $val) {
                    $cell = $this->xlsxCellRef($cIdx, $rIdx + 1);
                    $safe = htmlspecialchars((string) $val, ENT_QUOTES | ENT_XML1);
                    $sheet .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.$safe.'</t></is></c>';
                }
                $sheet .= '</row>';
            }

            $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
                .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="'.htmlspecialchars($sheetName, ENT_QUOTES | ENT_XML1).'" sheetId="1" r:id="rId1"/></sheets></workbook>';
            $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
                .$sheet.'</sheetData></worksheet>';
            $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'</Types>';
            $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>';
            $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'</Relationships>';

            $tmp = tempnam(sys_get_temp_dir(), 'mis_xlsx_');
            $zip = new \ZipArchive;
            $zip->open($tmp, \ZipArchive::OVERWRITE);
            $zip->addFromString('[Content_Types].xml', $contentTypes);
            $zip->addFromString('_rels/.rels', $rels);
            $zip->addFromString('xl/workbook.xml', $workbook);
            $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
            $zip->close();
            readfile($tmp);
            @unlink($tmp);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function xlsxCellRef(int $col, int $row): string
    {
        $letters = '';
        $n = $col;
        do {
            $letters = chr(65 + ($n % 26)).$letters;
            $n = intdiv($n, 26) - 1;
        } while ($n >= 0);

        return $letters.$row;
    }

    protected function authorizeMis($user): void
    {
        if (! GoalDashboardService::userUsesGoalDashboard($user)) {
            abort(403, 'Unauthorized action.');
        }

        $can = $user->isSuperAdmin()
            || $user->hasCrmPermission('view_targets_guard')
            || $user->hasCrmPermission('manage_targets_guard')
            || $user->hasCrmPermission('branch_mis_guard')
            || $user->hasCrmPermission('revenue_ledger_guard');
        if (! $can) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function requestFilters(): array
    {
        $fyDefaults = app(\App\Services\FinancialYearService::class)->defaultDateFilters();

        return [
            'fy' => (string) (request()->filled('fy') ? request('fy') : $fyDefaults['fy']),
            'date_from' => (string) (request()->filled('date_from') ? request('date_from') : $fyDefaults['date_from']),
            'date_to' => (string) (request()->filled('date_to') ? request('date_to') : $fyDefaults['date_to']),
            'region' => request('region'),
            'cluster' => request('cluster'),
            'branch' => request('branch'),
            'product' => request('product'),
            'owner' => request('owner'),
            'lead_source' => request('lead_source'),
            'lead_status' => request('lead_status'),
            'q' => (string) request('q', ''),
        ];
    }

    protected function safeBuild($user, string $report, array $filters): array
    {
        try {
            return $this->reports->build($user, $report, $filters);
        } catch (Throwable $e) {
            report($e);

            [$title, $group, $description] = GoalMisReportService::REPORTS[$report]
                ?? ['MIS Report', 'operational', ''];

            return [
                'key' => $report,
                'title' => $title,
                'description' => $description,
                'group' => $group,
                'filters' => $filters,
                'scope_label' => '—',
                'period_label' => '',
                'fy' => $filters['fy'] ?? '',
                'generated_at' => now(),
                'kpis' => [],
                'charts' => [],
                'columns' => [],
                'rows' => [],
                'search_keys' => [],
                'load_error' => 'Unable to load this report. Please try again.',
            ];
        }
    }
}
