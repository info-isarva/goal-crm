<?php

namespace App\Services\Goal;

use App\Library\MpdfDocument;
use App\Mail\GoalScheduledReportMail;
use App\Models\EmailSetting;
use App\Models\GoalScheduledReport;
use App\Models\GoalScheduledReportRun;
use App\Models\User;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class GoalScheduledReportService
{
    public function __construct(
        protected GoalAnalyticsMisService $analytics,
        protected GoalMisReportService $misReports
    ) {}

    public function computeNextRun(GoalScheduledReport $schedule, ?Carbon $from = null): ?Carbon
    {
        $from = ($from ?? now())->copy();
        $time = $this->parseRunTime($schedule->run_time);
        $cursor = $from->copy()->setTime($time[0], $time[1], 0);

        if ($cursor->lte($from)) {
            $cursor->addDay();
        }

        $start = Carbon::parse($schedule->start_date)->startOfDay();
        if ($cursor->lt($start)) {
            $cursor = $start->copy()->setTime($time[0], $time[1], 0);
            if ($cursor->lt($from)) {
                $cursor->addDay();
            }
        }

        if (! $schedule->no_end_date && $schedule->end_date) {
            $end = Carbon::parse($schedule->end_date)->endOfDay();
            if ($cursor->gt($end)) {
                return null;
            }
        }

        $cfg = $schedule->frequency_config ?? [];
        $guard = 0;
        while ($guard++ < 800) {
            if (! $schedule->no_end_date && $schedule->end_date && $cursor->gt(Carbon::parse($schedule->end_date)->endOfDay())) {
                return null;
            }

            $ok = match ($schedule->frequency) {
                'daily' => true,
                'weekly' => $this->matchesWeekdays($cursor, $cfg['weekdays'] ?? [1]),
                'monthly' => (int) $cursor->day === (int) ($cfg['day_of_month'] ?? 1),
                'custom' => $this->matchesCustomInterval($cursor, $schedule, $cfg),
                default => false,
            };

            if ($ok && $cursor->gt($from)) {
                return $cursor;
            }

            $cursor->addDay()->setTime($time[0], $time[1], 0);
        }

        return null;
    }

    public function createForUser(User $user, array $data): GoalScheduledReport
    {
        $schedule = new GoalScheduledReport([
            'user_id' => $user->id,
            'name' => $data['name'],
            'report_type' => $data['report_type'],
            'frequency' => $data['frequency'],
            'frequency_config' => $data['frequency_config'] ?? [],
            'run_time' => $data['run_time'],
            'start_date' => $data['start_date'],
            'end_date' => ($data['no_end_date'] ?? false) ? null : ($data['end_date'] ?? null),
            'no_end_date' => (bool) ($data['no_end_date'] ?? false),
            'format' => $data['format'],
            'recipients' => $data['recipients'],
            'filters' => $data['filters'] ?? [],
            'status' => 'active',
            'last_error' => null,
        ]);
        $schedule->next_run_at = $this->computeNextRun($schedule, now()->subSecond());
        $schedule->save();

        return $schedule;
    }

    public function updateSchedule(GoalScheduledReport $schedule, array $data): GoalScheduledReport
    {
        $schedule->fill([
            'name' => $data['name'],
            'report_type' => $data['report_type'],
            'frequency' => $data['frequency'],
            'frequency_config' => $data['frequency_config'] ?? [],
            'run_time' => $data['run_time'],
            'start_date' => $data['start_date'],
            'end_date' => ($data['no_end_date'] ?? false) ? null : ($data['end_date'] ?? null),
            'no_end_date' => (bool) ($data['no_end_date'] ?? false),
            'format' => $data['format'],
            'recipients' => $data['recipients'],
            'filters' => $data['filters'] ?? $schedule->filters,
            'status' => $schedule->status === 'failed' ? 'active' : $schedule->status,
            'last_error' => $schedule->status === 'failed' ? null : $schedule->last_error,
        ]);
        $schedule->next_run_at = $this->computeNextRun($schedule, now()->subSecond());
        $schedule->save();

        return $schedule;
    }

    public function pause(GoalScheduledReport $schedule): void
    {
        $schedule->status = 'paused';
        $schedule->next_run_at = null;
        $schedule->save();
    }

    public function resume(GoalScheduledReport $schedule): void
    {
        $schedule->status = 'active';
        $schedule->last_error = null;
        $schedule->next_run_at = $this->computeNextRun($schedule, now()->subSecond());
        $schedule->save();
    }

    /**
     * Execute one schedule using the owner's role-scoped analytics data.
     */
    public function run(GoalScheduledReport $schedule, bool $manual = false): GoalScheduledReportRun
    {
        $run = GoalScheduledReportRun::create([
            'goal_scheduled_report_id' => $schedule->id,
            'status' => 'failed',
            'started_at' => now(),
        ]);

        $tmpPath = null;

        try {
            $owner = $schedule->user;
            if (! $owner) {
                throw new \RuntimeException('Schedule owner not found.');
            }

            $filters = $schedule->filters ?? [];
            if (isset(GoalMisReportService::REPORTS[$schedule->report_type])) {
                $report = $this->misReports->build($owner, $schedule->report_type, $filters);
                $dash = [
                    'scope_label' => $report['scope_label'] ?? '—',
                    'kpis' => $report['kpis'] ?? [],
                    'filters' => $report['filters'] ?? $filters,
                    'funnel' => ['stages' => []],
                    'mis_report' => $report,
                ];
                [$absolute, $fileName, $mime] = $this->buildMisAttachment($schedule, $report, $owner);
            } else {
                $filters['section'] = $schedule->report_type === 'overview' ? 'overview' : $schedule->report_type;
                $dash = $this->analytics->build($owner, $filters);
                [$absolute, $fileName, $mime] = $this->buildAttachment($schedule, $dash, $owner);
            }
            $tmpPath = $absolute;

            $this->prepareMailer();
            $mailable = new GoalScheduledReportMail($schedule, $dash, $absolute, $fileName, $mime);
            Mail::to($schedule->recipientsList())->send($mailable);

            $run->update([
                'status' => 'success',
                'finished_at' => now(),
                'recipients_sent' => $schedule->recipientsList(),
                'file_name' => $fileName,
                'error_message' => null,
            ]);

            $schedule->last_run_at = now();
            $schedule->last_error = null;
            if ($schedule->status === 'failed') {
                $schedule->status = 'active';
            }
            if (! $manual && $schedule->status === 'active') {
                $schedule->next_run_at = $this->computeNextRun($schedule, now());
            } elseif ($schedule->status === 'active' && ! $schedule->next_run_at) {
                $schedule->next_run_at = $this->computeNextRun($schedule, now());
            }
            $schedule->save();
        } catch (Throwable $e) {
            report($e);
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => Str::limit($e->getMessage(), 2000),
            ]);
            $schedule->last_run_at = now();
            $schedule->last_error = Str::limit($e->getMessage(), 2000);
            $schedule->status = 'failed';
            $schedule->next_run_at = $manual
                ? $schedule->next_run_at
                : $this->computeNextRun($schedule, now());
            $schedule->save();
        } finally {
            if ($tmpPath && is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }

        return $run->fresh();
    }

    public function processDue(int $limit = 25): int
    {
        $due = GoalScheduledReport::query()
            ->with('user')
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get();

        $count = 0;
        foreach ($due as $schedule) {
            $this->run($schedule, false);
            $count++;
        }

        return $count;
    }

    /**
     * @return array{0:string,1:string,2:string} absolute path, filename, mime
     */
    protected function buildMisAttachment(GoalScheduledReport $schedule, array $report, User $owner): array
    {
        $slug = Str::slug($schedule->name ?: ($report['title'] ?? 'mis-report'));
        $stamp = now()->format('Ymd_His');
        $dir = storage_path('app/tmp/goal-scheduled-reports');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        if ($schedule->format === 'csv') {
            $file = "{$slug}_{$stamp}.csv";
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            $out = fopen($path, 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Report', $schedule->name]);
            fputcsv($out, ['Type', $schedule->typeLabel()]);
            fputcsv($out, ['Generated At', now()->format('d M Y H:i')]);
            fputcsv($out, ['Generated For', $owner->name]);
            fputcsv($out, ['Scope', $report['scope_label'] ?? '—']);
            fputcsv($out, ['Period', $report['period_label'] ?? '']);
            fputcsv($out, []);
            fputcsv($out, ['KPI', 'Value']);
            foreach (($report['kpis'] ?? []) as $kpi) {
                fputcsv($out, [$kpi['label'] ?? '', $kpi['value'] ?? '']);
            }
            fputcsv($out, []);
            $columns = $report['columns'] ?? [];
            fputcsv($out, array_map(fn ($c) => $c['label'] ?? '', $columns));
            foreach (($report['rows'] ?? []) as $row) {
                $line = [];
                foreach ($columns as $col) {
                    $ck = $col['key'] ?? '';
                    if ($ck === '' || str_starts_with($ck, '_')) {
                        continue;
                    }
                    $line[] = $row[$ck] ?? '';
                }
                fputcsv($out, $line);
            }
            fclose($out);

            return [$path, $file, 'text/csv'];
        }

        if ($schedule->format === 'xlsx') {
            $file = "{$slug}_{$stamp}.xlsx";
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            $rows = [];
            $rows[] = ['Report', $schedule->name];
            $rows[] = ['Type', $schedule->typeLabel()];
            $rows[] = ['Generated At', now()->format('d M Y H:i')];
            $rows[] = ['Scope', $report['scope_label'] ?? '—'];
            $rows[] = [];
            $rows[] = ['KPI', 'Value'];
            foreach (($report['kpis'] ?? []) as $kpi) {
                $rows[] = [$kpi['label'] ?? '', $kpi['value'] ?? ''];
            }
            $rows[] = [];
            $columns = $report['columns'] ?? [];
            $rows[] = array_values(array_filter(array_map(function ($c) {
                $ck = $c['key'] ?? '';
                if ($ck === '' || str_starts_with($ck, '_')) {
                    return null;
                }

                return $c['label'] ?? '';
            }, $columns)));
            foreach (($report['rows'] ?? []) as $row) {
                $line = [];
                foreach ($columns as $col) {
                    $ck = $col['key'] ?? '';
                    if ($ck === '' || str_starts_with($ck, '_')) {
                        continue;
                    }
                    $line[] = $row[$ck] ?? '';
                }
                $rows[] = $line;
            }
            $this->writeXlsxRows($path, $rows);

            return [$path, $file, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        }

        $file = "{$slug}_{$stamp}.pdf";
        $path = $dir.DIRECTORY_SEPARATOR.$file;
        $html = view('goal.mis-reports.export-pdf', [
            'title' => $schedule->name ?: ($report['title'] ?? 'MIS Report'),
            'report' => $report,
            'owner' => $owner,
            'generatedAt' => now(),
        ])->render();
        file_put_contents($path, MpdfDocument::binary($html));

        return [$path, $file, 'application/pdf'];
    }

    /**
     * @return array{0:string,1:string,2:string} absolute path, filename, mime
     */
    protected function buildAttachment(GoalScheduledReport $schedule, array $dash, User $owner): array
    {
        $slug = Str::slug($schedule->name ?: 'analytics-report');
        $stamp = now()->format('Ymd_His');
        $dir = storage_path('app/tmp/goal-scheduled-reports');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        if ($schedule->format === 'csv') {
            $file = "{$slug}_{$stamp}.csv";
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            $this->writeCsv($path, $dash, $schedule, $owner);

            return [$path, $file, 'text/csv'];
        }

        if ($schedule->format === 'xlsx') {
            $file = "{$slug}_{$stamp}.xlsx";
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            $this->writeXlsx($path, $dash, $schedule, $owner);

            return [$path, $file, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        }

        $file = "{$slug}_{$stamp}.pdf";
        $path = $dir.DIRECTORY_SEPARATOR.$file;
        $html = view('goal.scheduled-reports.export-pdf', [
            'schedule' => $schedule,
            'dash' => $dash,
            'owner' => $owner,
            'generatedAt' => now(),
        ])->render();
        file_put_contents($path, MpdfDocument::binary($html));

        return [$path, $file, 'application/pdf'];
    }

    protected function writeCsv(string $path, array $dash, GoalScheduledReport $schedule, User $owner): void
    {
        $out = fopen($path, 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Report', $schedule->name]);
        fputcsv($out, ['Type', $schedule->typeLabel()]);
        fputcsv($out, ['Generated At', now()->format('d M Y H:i')]);
        fputcsv($out, ['Generated For', $owner->name.' <'.$owner->email.'>']);
        fputcsv($out, ['Scope', $dash['scope_label'] ?? '—']);
        fputcsv($out, []);
        fputcsv($out, ['KPI', 'Value', 'Delta %']);
        foreach (($dash['kpis'] ?? []) as $kpi) {
            fputcsv($out, [$kpi['label'] ?? '', $kpi['value'] ?? '', $kpi['delta'] ?? '']);
        }
        fputcsv($out, []);
        fputcsv($out, ['Funnel Stage', 'Count', 'From Prev %']);
        foreach (($dash['funnel']['stages'] ?? []) as $st) {
            fputcsv($out, [$st['label'] ?? '', $st['count'] ?? 0, $st['from_prev_pct'] ?? 0]);
        }
        fclose($out);
    }

    protected function writeXlsx(string $path, array $dash, GoalScheduledReport $schedule, User $owner): void
    {
        // Lightweight XML spreadsheet (Excel-compatible) without requiring Maatwebsite for this summary.
        $rows = [];
        $rows[] = ['Report', $schedule->name];
        $rows[] = ['Type', $schedule->typeLabel()];
        $rows[] = ['Generated At', now()->format('d M Y H:i')];
        $rows[] = ['Generated For', $owner->name];
        $rows[] = ['Scope', $dash['scope_label'] ?? '—'];
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

        $this->writeXlsxRows($path, $rows);
    }

    protected function writeXlsxRows(string $path, array $rows): void
    {
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
            .'<sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets></workbook>';
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

        $zip = new \ZipArchive;
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create Excel file.');
        }
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();
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

    protected function parseRunTime(string $runTime): array
    {
        $parts = explode(':', $runTime);
        $h = max(0, min(23, (int) ($parts[0] ?? 9)));
        $m = max(0, min(59, (int) ($parts[1] ?? 0)));

        return [$h, $m];
    }

    protected function matchesWeekdays(Carbon $cursor, array $weekdays): bool
    {
        $days = array_map('intval', $weekdays);
        // ISO: 1=Mon … 7=Sun
        return in_array((int) $cursor->dayOfWeekIso, $days, true);
    }

    protected function matchesCustomInterval(Carbon $cursor, GoalScheduledReport $schedule, array $cfg): bool
    {
        $interval = max(1, (int) ($cfg['interval_days'] ?? 7));
        $start = Carbon::parse($schedule->start_date)->startOfDay();
        $diff = $start->diffInDays($cursor->copy()->startOfDay());

        return $diff >= 0 && ($diff % $interval) === 0;
    }

    /**
     * Apply Email Settings mailer. If SMTP/Sendmail rows exist but none are enabled,
     * fail clearly instead of silently falling back to stale .env credentials.
     */
    protected function prepareMailer(): void
    {
        EmailService::applyConfiguration();

        try {
            if (! Schema::hasTable('email_settings')) {
                return;
            }
        } catch (Throwable $e) {
            return;
        }

        if (EmailSetting::query()->exists() && ! EmailSetting::where('is_active', true)->exists()) {
            throw new \RuntimeException(
                'Outbound email is disabled. Open Settings → Email Settings, turn on “Enable Integration for Selected Provider”, save, then try Run Now again.'
            );
        }
    }
}
