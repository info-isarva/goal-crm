<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Library\MpdfDocument;
use App\Services\Goal\GoalAnalyticsMisService;
use App\Services\Goal\GoalDashboardService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GoalAnalyticsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $this->authorizeAnalytics($user);

        $filters = $this->requestFilters();
        $dash = $this->safeBuild($user, $filters);

        if (request()->wantsJson() || request('format') === 'json') {
            return response()->json([
                'ok' => empty($dash['load_error']),
                'error' => $dash['load_error'] ?? null,
                'filters' => $dash['filters'] ?? $filters,
                'kpis' => $dash['kpis'] ?? [],
                'funnel' => $dash['funnel'] ?? [],
                'trend' => $dash['trend'] ?? [],
                'targets' => $dash['targets'] ?? [],
                'branches' => $dash['branches'] ?? [],
                'case_status' => $dash['case_status'] ?? [],
                'alerts' => $dash['alerts'] ?? [],
            ]);
        }

        if (request()->boolean('partial') || request()->ajax()) {
            return response()
                ->view('goal.partials.analytics_results', compact('dash'))
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return view('goal.analytics', compact('dash', 'user'));
    }

    public function misReports()
    {
        $user = auth()->user();
        $this->authorizeAnalytics($user);

        $filters = $this->requestFilters();
        $filters['section'] = 'overview';
        $dash = $this->safeBuild($user, $filters);

        return view('goal.mis-reports', compact('dash', 'user'));
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $this->authorizeAnalytics($user);

        $format = strtolower((string) $request->input('format', 'pdf'));
        if (! in_array($format, ['pdf', 'excel', 'xlsx', 'csv'], true)) {
            $format = 'pdf';
        }
        if ($format === 'excel') {
            $format = 'xlsx';
        }

        $filters = $this->requestFilters();
        $dash = $this->safeBuild($user, $filters);
        $stamp = now()->format('Ymd-His');
        $section = $filters['section'] ?? 'overview';
        $sectionLabels = [
            'overview' => 'Analytics Dashboard (Summary)',
            'leads' => 'Lead Analytics',
            'processing' => 'Processing',
            'disbursement' => 'Disbursement',
            'revenue' => 'Revenue & Payout',
            'performance' => 'Performance',
        ];
        $title = 'Analytics Export — '.($sectionLabels[$section] ?? 'Overview');

        if ($format === 'pdf') {
            return MpdfDocument::fromView(
                'goal.analytics.export-pdf',
                [
                    'title' => $title,
                    'dash' => $dash,
                    'owner' => $user,
                    'generatedAt' => now(),
                    'sectionLabel' => $sectionLabels[$section] ?? 'Overview',
                ],
                'analytics-'.$stamp.'.pdf',
                true,
                'A4'
            );
        }

        $rows = $this->exportRows($dash, $title, $user);

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, 'analytics-'.$stamp.'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return $this->streamXlsx($rows, 'analytics-'.$stamp.'.xlsx');
    }

    protected function exportRows(array $dash, string $title, $user): array
    {
        $rows = [];
        $rows[] = ['Report', $title];
        $rows[] = ['Generated At', now()->format('d M Y H:i')];
        $rows[] = ['Generated For', $user->name];
        $rows[] = ['Scope', $dash['scope_label'] ?? '—'];
        $rows[] = ['Period', ($dash['filters']['date_from'] ?? '').' to '.($dash['filters']['date_to'] ?? '')];
        $rows[] = [];
        $rows[] = ['KPI', 'Value', 'Delta %'];
        foreach (($dash['kpis'] ?? []) as $kpi) {
            $rows[] = [$kpi['label'] ?? '', $kpi['value'] ?? '', $kpi['delta'] ?? ''];
        }
        $rows[] = [];
        $rows[] = ['Funnel Stage', 'Count', 'From Prev %'];
        foreach (($dash['funnel']['stages'] ?? []) as $st) {
            $rows[] = [$st['label'] ?? '', $st['count'] ?? 0, $st['from_prev_pct'] ?? 0];
        }
        $rows[] = [];
        $rows[] = ['Branch', 'Disbursement (₹ Lac)'];
        $labels = $dash['branches']['labels'] ?? [];
        $vals = $dash['branches']['disbursement_lac'] ?? [];
        foreach ($labels as $i => $name) {
            $rows[] = [$name, number_format((float) ($vals[$i] ?? 0), 2, '.', '')];
        }

        return $rows;
    }

    protected function streamXlsx(array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
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
                .'<sheets><sheet name="Analytics" sheetId="1" r:id="rId1"/></sheets></workbook>';
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

            $tmp = tempnam(sys_get_temp_dir(), 'ga_xlsx_');
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

    protected function authorizeAnalytics($user): void
    {
        if (! GoalDashboardService::userUsesGoalDashboard($user)) {
            abort(403, 'Unauthorized action.');
        }

        $canAnalytics = $user->isSuperAdmin()
            || $user->hasCrmPermission('view_targets_guard')
            || $user->hasCrmPermission('manage_targets_guard')
            || $user->hasCrmPermission('branch_mis_guard')
            || $user->hasCrmPermission('revenue_ledger_guard');
        if (! $canAnalytics) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function requestFilters(): array
    {
        $section = request('section', 'overview');
        if (! in_array($section, ['overview', 'leads', 'processing', 'disbursement', 'revenue', 'performance'], true)) {
            $section = 'overview';
        }

        $fyDefaults = app(\App\Services\FinancialYearService::class)->defaultDateFilters();

        return [
            'section' => $section,
            'fy' => (string) (request()->filled('fy') ? request('fy') : $fyDefaults['fy']),
            'date_from' => (string) (request()->filled('date_from') ? request('date_from') : $fyDefaults['date_from']),
            'date_to' => (string) (request()->filled('date_to') ? request('date_to') : $fyDefaults['date_to']),
            'region' => request('region'),
            'cluster' => request('cluster'),
            'branch' => request('branch'),
            'product' => request('product'),
            'owner' => request('owner'),
            'target_tab' => in_array(request('target_tab'), ['employee', 'branch', 'cluster', 'region'], true)
                ? request('target_tab')
                : 'employee',
        ];
    }

    protected function safeBuild($user, array $filters): array
    {
        try {
            $dash = app(GoalAnalyticsMisService::class)->build($user, $filters);
            $dash['load_error'] = null;

            return $dash;
        } catch (Throwable $e) {
            report($e);

            return [
                'role_key' => 'bm',
                'role_label' => 'User',
                'scope_label' => '—',
                'filters' => $filters,
                'filter_options' => [
                    'regions' => collect(),
                    'clusters' => collect(),
                    'branches' => collect(),
                    'products' => collect(),
                    'owners' => collect(),
                ],
                'fy_options' => [],
                'kpis' => [],
                'funnel' => ['stages' => [], 'overall_conversion' => 0],
                'trend' => ['labels' => [], 'disbursement_cr' => [], 'revenue_lac' => []],
                'targets' => ['employee' => [], 'branch' => [], 'cluster' => [], 'region' => []],
                'branches' => ['labels' => [], 'ids' => [], 'cluster_ids' => [], 'disbursement_lac' => [], 'revenue_lac' => [], 'drill_base' => route('goal.analytics')],
                'case_status' => ['total' => 0, 'items' => []],
                'alerts' => [],
                'mis_register' => [],
                'load_error' => 'Unable to load analytics data. Please try again.',
            ];
        }
    }
}
