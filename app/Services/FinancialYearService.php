<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FinancialYear;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class FinancialYearService
{
    /**
     * Ensure active + previous FY rows exist and session has a selection.
     */
    public function bootstrapSession(): FinancialYear
    {
        $this->ensureRecentYears();
        $fy = $this->current();
        session(['selected_financial_year' => $fy->id]);

        return $fy;
    }

    /**
     * Ensure at least one active financial year exists (Apr–Mar by default / company settings).
     * Also creates the previous FY (inactive) so the header selector is usable.
     */
    public function ensureActiveExists(): FinancialYear
    {
        $this->ensureRecentYears();

        return FinancialYear::query()->where('active', 1)->first()
            ?? FinancialYear::query()->orderByDesc('from_date')->first()
            ?? $this->createYearForDate(now(), true);
    }

    /**
     * Create current + previous FY rows when missing (does not flip an existing active FY).
     */
    public function ensureRecentYears(): void
    {
        $active = FinancialYear::query()->where('active', 1)->first();
        if (! $active) {
            $active = $this->findOrCreateYearForDate(now(), true);
        }

        $prevAnchor = Carbon::parse($active->from_date)->subDay();
        $this->findOrCreateYearForDate($prevAnchor, false);

        // If today falls in a newer FY window than the active row, create that row (inactive)
        // so it can be closed/activated via Company close-year flow later.
        [$todayFrom] = $this->computeContainingRange(now());
        if ($todayFrom->gt(Carbon::parse($active->from_date))) {
            $this->findOrCreateYearForDate(now(), false);
        }
    }

    /**
     * Selected FY (session) or active FY. Creates active FY when missing.
     */
    public function current(?int $preferId = null): FinancialYear
    {
        $this->ensureRecentYears();

        $id = $preferId ?: session('selected_financial_year');
        if ($id) {
            $fy = FinancialYear::query()->find($id);
            if ($fy) {
                return $fy;
            }
        }

        $active = FinancialYear::query()->where('active', 1)->first();
        if ($active) {
            return $active;
        }

        return $this->ensureActiveExists();
    }

    protected function findOrCreateYearForDate(Carbon $date, bool $makeActive): FinancialYear
    {
        [$from, $to] = $this->computeContainingRange($date);
        $existing = FinancialYear::query()
            ->whereDate('from_date', $from->toDateString())
            ->whereDate('to_date', $to->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->createYearForDate($date, $makeActive);
    }

    protected function createYearForDate(Carbon $date, bool $active): FinancialYear
    {
        [$from, $to] = $this->computeContainingRange($date);

        return FinancialYear::query()->create([
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'fin_key' => $this->finKeyFor($from, $to),
            'status' => $active ? 'running' : 'closed',
            'active' => $active ? 1 : 0,
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function range(?FinancialYear $fy = null): array
    {
        $fy ??= $this->current();
        $tz = config('app.timezone', 'Asia/Kolkata');

        return [
            Carbon::parse($fy->from_date, $tz)->startOfDay(),
            Carbon::parse($fy->to_date, $tz)->endOfDay(),
        ];
    }

    /**
     * Human label e.g. "Apr, 2025 - Mar, 2026".
     */
    public function label(?FinancialYear $fy = null): string
    {
        $fy ??= $this->current();
        $tz = config('app.timezone', 'Asia/Kolkata');

        return Carbon::parse($fy->from_date, $tz)->format('M, Y')
            .' - '
            .Carbon::parse($fy->to_date, $tz)->format('M, Y');
    }

    /**
     * FY key used by Goal analytics filters, e.g. "2025-2026".
     */
    public function analyticsKey(?FinancialYear $fy = null): string
    {
        $fy ??= $this->current();
        $tz = config('app.timezone', 'Asia/Kolkata');
        $from = Carbon::parse($fy->from_date, $tz);
        $to = Carbon::parse($fy->to_date, $tz);

        return $from->year.'-'.$to->year;
    }

    /**
     * Restrict a query to the selected/active financial year on a date column.
     */
    public function applyToQuery(Builder $query, string $column = 'created_at', ?FinancialYear $fy = null): Builder
    {
        [$from, $to] = $this->range($fy);

        return $query->whereBetween($column, [$from, $to]);
    }

    /**
     * Default date_from / date_to for report UIs when request does not override.
     *
     * @return array{date_from: string, date_to: string, fy: string, financial_year_id: int}
     */
    public function defaultDateFilters(?FinancialYear $fy = null): array
    {
        $fy ??= $this->current();
        [$from, $to] = $this->range($fy);
        $today = now()->endOfDay();
        $end = $to->lt($today) ? $to : $today;

        return [
            'date_from' => $from->toDateString(),
            'date_to' => $end->toDateString(),
            'fy' => $this->analyticsKey($fy),
            'financial_year_id' => (int) $fy->id,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function computeContainingRange(Carbon $date): array
    {
        $company = Company::query()->first();
        $startMonth = (int) ($company?->fy_start_month ?: 4);
        $startDay = (int) ($company?->fy_start_day ?: 1);
        if ($startMonth < 1 || $startMonth > 12) {
            $startMonth = 4;
        }
        if ($startDay < 1 || $startDay > 28) {
            $startDay = 1;
        }

        $year = (int) $date->year;
        $fyStart = Carbon::create($year, $startMonth, $startDay)->startOfDay();
        if ($date->lt($fyStart)) {
            $fyStart->subYear();
        }
        $fyEnd = $fyStart->copy()->addYear()->subDay()->endOfDay();

        return [$fyStart, $fyEnd];
    }

    protected function finKeyFor(Carbon $from, Carbon $to): string
    {
        // Legacy CEM used 2-digit end year; keep compatible + readable.
        return $from->format('Y').'-'.$to->format('y');
    }
}
