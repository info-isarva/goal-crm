<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\CallLog;
use App\Models\DailyReport;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Note;
use App\Models\OutreachType;
use App\Models\ProductCategory;
use App\Models\ProductCategoryType;
use App\Models\StageHistory;
use App\Models\Task;
use App\Models\User;
use App\Models\UserMonthlySales;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SalesMarketingDailyReportController extends Controller
{
    protected function typeColorPalette(): array
    {
        return [
            ['header_bg' => '#1e4d8c', 'cell_bg' => '#cfe2f3'],
            ['header_bg' => '#5b2c6f', 'cell_bg' => '#d7bde2'],
            ['header_bg' => '#1a5276', 'cell_bg' => '#a9cce3'],
            ['header_bg' => '#9a7d0a', 'cell_bg' => '#f9e79f'],
            ['header_bg' => '#117a65', 'cell_bg' => '#d5f5e3'],
            ['header_bg' => '#922b21', 'cell_bg' => '#f5b7b1'],
            ['header_bg' => '#1c2833', 'cell_bg' => '#d5d8dc'],
            ['header_bg' => '#6c3483', 'cell_bg' => '#e8daef'],
        ];
    }

    /**
     * Build product matrix groups from Product Category Types + Product Categories.
     */
    protected function buildProductGroups(): array
    {
        $palette = $this->typeColorPalette();
        $types = ProductCategoryType::query()->active()->ordered()->get();
        $categories = ProductCategory::query()
            ->where('status', 1)
            ->orderBy('category_name')
            ->get(['id', 'category_name', 'type']);

        $byType = $categories->groupBy(function ($cat) {
            return strtolower(trim((string) $cat->type));
        });

        $groups = [];
        $colorIndex = 0;
        foreach ($types as $type) {
            $typeKey = (string) $type->id;
            $typeName = trim((string) $type->name);
            $typeCats = $byType->get(strtolower($typeName), collect());
            if ($typeCats->isEmpty()) {
                continue;
            }

            $colors = $palette[$colorIndex % count($palette)];
            $colorIndex++;

            $columns = [];
            foreach ($typeCats as $cat) {
                $columns[(string) $cat->id] = [
                    'id' => (int) $cat->id,
                    'label' => $cat->category_name,
                    'match_name' => $this->normalizeMatchText($cat->category_name),
                ];
            }

            $groups[] = [
                'key' => $typeKey,
                'label' => ucwords(str_replace(['_', '-'], ' ', $typeName)),
                'type_name' => $typeName,
                'header_bg' => $colors['header_bg'],
                'cell_bg' => $colors['cell_bg'],
                'columns' => $columns,
            ];
        }

        return $groups;
    }

    /**
     * Keep only categories that have a non-zero count for at least one user.
     * Type groups with no remaining categories are removed.
     */
    protected function filterProductGroupsByCounts(array $productGroups, array $productCountsByUser): array
    {
        $filtered = [];
        foreach ($productGroups as $group) {
            $visibleColumns = [];
            foreach ($group['columns'] as $colKey => $col) {
                $hasData = false;
                foreach ($productCountsByUser as $userCounts) {
                    if (($userCounts[$group['key']][$colKey] ?? 0) > 0) {
                        $hasData = true;
                        break;
                    }
                }
                if ($hasData) {
                    $visibleColumns[$colKey] = $col;
                }
            }
            if (!empty($visibleColumns)) {
                $group['columns'] = $visibleColumns;
                $filtered[] = $group;
            }
        }

        return $filtered;
    }

    public function index(Request $request)
    {
        $data = $this->buildReportData($request);

        return view('reports.sales_marketing_daily', $data);
    }

    public function excel(Request $request)
    {
        $data = $this->buildReportData($request);
        $fileName = 'Daily_Sales_Marketing_Report_' . $data['reportDate']->format('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SalesMarketingDailyReportExport($data),
            $fileName
        );
    }

    protected function buildReportData(Request $request): array
    {
        $user = auth()->user();
        $canAccess = (int) ($user->crm_role_type ?? -1) === 0
            || $user->hasCrmPermission('daily_sales_and_marketing_report_guard')
            || $user->hasCrmPermission('sales_marketing_daily_report_guard');

        if (!$canAccess) {
            abort(403, 'Unauthorized action.');
        }

        $reportDate = $request->filled('report_date')
            ? Carbon::parse($request->query('report_date'))->startOfDay()
            : Carbon::today();

        $dayStart = $reportDate->copy()->startOfDay();
        $dayEnd = $reportDate->copy()->endOfDay();
        $year = (int) $reportDate->year;
        $month = (int) $reportDate->month;
        $monthLabel = $reportDate->format('F - Y');

        $users = $this->resolveUsers($user, $request->query('user_id'));
        $userIds = $users->pluck('id')->all();

        $allProductGroups = $this->buildProductGroups();
        $outreachTypes = OutreachType::query()->active()->ordered()->get();

        // Marketing activity columns from outreach types (Data Pre Analysis shown under Marketing Activities in the UI)
        $marketingTypes = $outreachTypes->filter(function ($type) {
            return !preg_match('/data\s*pre\s*analysis/i', (string) $type->name);
        })->values();

        $dataPreType = $outreachTypes->first(function ($type) {
            return preg_match('/data\s*pre\s*analysis/i', (string) $type->name);
        });

        $dailyReports = DailyReport::query()
            ->with(['outreaches.outreachType', 'user'])
            ->whereDate('report_date', $reportDate->toDateString())
            ->where(function ($q) {
                $q->where('status', 'submitted')->orWhereNull('status');
            })
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->get()
            ->keyBy('user_id');

        // Users without a submitted daily report for this date are left out of every section
        $users = $users->filter(fn ($member) => $dailyReports->has($member->id))->values();
        $userIds = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (empty($userIds)) {
            // ->when($userIds, ...) skips the filter on an empty array, which would return everyone's data
            $userIds = [0];
        }

        $newCallsByUser = CallLog::query()
            ->selectRaw('created_by, COUNT(*) as cnt')
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->when($userIds, fn ($q) => $q->whereIn('created_by', $userIds))
            ->groupBy('created_by')
            ->pluck('cnt', 'created_by');

        $leadsGenByUser = Lead::query()
            ->selectRaw('user_owner_id, COUNT(*) as cnt')
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Junk Lead');
            })
            ->when($userIds, fn ($q) => $q->whereIn('user_owner_id', $userIds))
            ->groupBy('user_owner_id')
            ->pluck('cnt', 'user_owner_id');

        $convertedByUser = Lead::query()
            ->selectRaw('user_owner_id, COUNT(*) as cnt')
            ->whereNotNull('converted_at')
            ->whereBetween('converted_at', [$dayStart, $dayEnd])
            ->when($userIds, fn ($q) => $q->whereIn('user_owner_id', $userIds))
            ->groupBy('user_owner_id')
            ->pluck('cnt', 'user_owner_id');

        $followUpsByUser = $this->followUpCounts($userIds, $dayStart, $dayEnd);

        $quoteSentByUser = [];
        $invoiceSentByUser = [];
        if (Schema::hasTable('stage_history')) {
            // Get the latest stage history record ID for each deal updated on this report date
            $latestStageHistoryIds = StageHistory::query()
                ->selectRaw('MAX(id) as max_id')
                ->whereBetween('modified_time', [$dayStart, $dayEnd])
                ->groupBy('deal_id')
                ->pluck('max_id')
                ->filter()
                ->all();

            if (!empty($latestStageHistoryIds)) {
                // If latest stage transition on this date was Proposal/Price Quote
                $quoteSentByUser = StageHistory::query()
                    ->join('deals', 'stage_history.deal_id', '=', 'deals.id')
                    ->selectRaw('COALESCE(stage_history.modified_by, deals.user_owner_id) as uid, COUNT(DISTINCT stage_history.deal_id) as cnt')
                    ->whereIn('stage_history.id', $latestStageHistoryIds)
                    ->where(function ($q) {
                        $q->whereRaw('LOWER(stage_history.stage_name) = ?', ['proposal/price quote'])
                            ->orWhereRaw('LOWER(stage_history.stage_name) LIKE ?', ['%proposal%'])
                            ->orWhereRaw('LOWER(stage_history.stage_name) LIKE ?', ['%price quote%']);
                    })
                    ->when($userIds, function ($q) use ($userIds) {
                        $q->where(function ($inner) use ($userIds) {
                            $inner->whereIn('stage_history.modified_by', $userIds)
                                ->orWhereIn('deals.user_owner_id', $userIds);
                        });
                    })
                    ->groupBy('uid')
                    ->pluck('cnt', 'uid')
                    ->all();

                // If latest stage transition on this date was Negotiation/Invoice
                $invoiceSentByUser = StageHistory::query()
                    ->join('deals', 'stage_history.deal_id', '=', 'deals.id')
                    ->selectRaw('COALESCE(stage_history.modified_by, deals.user_owner_id) as uid, COUNT(DISTINCT stage_history.deal_id) as cnt')
                    ->whereIn('stage_history.id', $latestStageHistoryIds)
                    ->where(function ($q) {
                        $q->whereRaw('LOWER(stage_history.stage_name) = ?', ['negotiation/invoice'])
                            ->orWhereRaw('LOWER(stage_history.stage_name) = ?', ['negotiation/review'])
                            ->orWhereRaw('LOWER(stage_history.stage_name) LIKE ?', ['%negotiation%'])
                            ->orWhereRaw('LOWER(stage_history.stage_name) LIKE ?', ['%invoice%']);
                    })
                    ->when($userIds, function ($q) use ($userIds) {
                        $q->where(function ($inner) use ($userIds) {
                            $inner->whereIn('stage_history.modified_by', $userIds)
                                ->orWhereIn('deals.user_owner_id', $userIds);
                        });
                    })
                    ->groupBy('uid')
                    ->pluck('cnt', 'uid')
                    ->all();
            }
        }

        // Marked product columns: only newly created call_logs for the report date, matched by type only
        $callLogsToday = CallLog::query()
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->when($userIds, fn ($q) => $q->whereIn('created_by', $userIds))
            ->get(['id', 'created_by', 'type']);

        $productCountsByUser = [];
        foreach ($userIds as $uid) {
            $productCountsByUser[$uid] = $this->emptyProductCounts($allProductGroups);
        }

        foreach ($callLogsToday as $log) {
            $uid = (int) $log->created_by;
            if (!isset($productCountsByUser[$uid])) {
                continue;
            }
            $haystack = $this->normalizeMatchText((string) ($log->type ?? ''));
            $matched = $this->matchProductColumn($haystack, $allProductGroups);
            if ($matched) {
                [$groupKey, $colKey] = $matched;
                $productCountsByUser[$uid][$groupKey][$colKey]++;
            }
        }

        // Only show categories that have data for at least one user
        $productGroups = $this->filterProductGroupsByCounts($allProductGroups, $productCountsByUser);

        // Slim per-user counts to visible columns only
        foreach ($productCountsByUser as $uid => $counts) {
            $slim = $this->emptyProductCounts($productGroups);
            foreach ($productGroups as $group) {
                foreach (array_keys($group['columns']) as $colKey) {
                    $slim[$group['key']][$colKey] = $counts[$group['key']][$colKey] ?? 0;
                }
            }
            $productCountsByUser[$uid] = $slim;
        }

        $weekMeta = $this->weekOfMonth($reportDate);
        $weekStart = $weekMeta['start'];
        $weekEnd = $weekMeta['end'];
        $weekLabel = 'Week ' . $weekMeta['number'];

        $monthlySales = UserMonthlySales::query()
            ->where('year', $year)
            ->where('month', $month)
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->get()
            ->keyBy('user_id');

        $weekAchievedByUser = Deal::query()
            ->selectRaw('user_owner_id, COALESCE(SUM(amount), 0) as total')
            ->where(function ($q) {
                $q->where('stage_id', \App\Models\Stage::closedWonId())
                    ->orWhereRaw('LOWER(status) = ?', ['closed won']);
            })
            ->where(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('close_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                    ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                        $q2->whereNull('close_date')
                            ->whereBetween('updated_at', [$weekStart, $weekEnd]);
                    });
            })
            ->when($userIds, fn ($q) => $q->whereIn('user_owner_id', $userIds))
            ->groupBy('user_owner_id')
            ->pluck('total', 'user_owner_id');

        $todayLeads = Lead::query()
            ->with(['owner', 'leadSource', 'industry', 'organization.industry', 'person'])
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Junk Lead');
            })
            ->when($userIds, fn ($q) => $q->whereIn('user_owner_id', $userIds))
            ->orderBy('user_owner_id')
            ->orderByDesc('id')
            ->get();
        $this->attachProductAndIndustryLabels($todayLeads);

        // Direct deals only (created without converting a lead)
        $todayDirectDeals = Deal::query()
            ->with(['owner', 'dealSource', 'industry', 'organization.industry', 'person'])
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->where(function ($q) {
                $q->whereNull('converted_lead_id')
                    ->orWhere('converted_lead_id', 0);
            })
            ->when(
                Schema::hasColumn('deals', 'deleted_at'),
                fn ($q) => $q->whereNull('deleted_at')
            )
            ->when($userIds, fn ($q) => $q->whereIn('user_owner_id', $userIds))
            ->orderBy('user_owner_id')
            ->orderByDesc('id')
            ->get();
        $this->attachProductAndIndustryLabels($todayDirectDeals);

        $rows = [];
        $totals = [
            'follow_ups' => 0,
            'new_calls' => 0,
            'leads_gen' => 0,
            'leads_converted' => 0,
            'quote_sent' => 0,
            'invoice_sent' => 0,
            'data_pre_analysis' => 0,
            'products' => $this->emptyProductCounts($productGroups),
            'marketing' => [],
            'month_target' => 0,
            'month_achieve' => 0,
            'month_balance' => 0,
            'week_target' => 0,
            'week_achieve' => 0,
        ];
        foreach ($marketingTypes as $type) {
            $totals['marketing'][$type->id] = 0;
        }

        $todayActivitiesByUser = $this->getTodayActivitiesByUsers($userIds, $dayStart, $dayEnd);

        foreach ($users as $member) {
            $uid = (int) $member->id;
            $report = $dailyReports->get($uid);
            $outreachMap = [];
            if ($report) {
                foreach ($report->outreaches as $row) {
                    $outreachMap[(int) $row->outreach_type_id] = (int) $row->count;
                }
            }

            $marketingCounts = [];
            foreach ($marketingTypes as $type) {
                $cnt = (int) ($outreachMap[$type->id] ?? 0);
                $marketingCounts[$type->id] = $cnt;
                $totals['marketing'][$type->id] += $cnt;
            }

            $dataPre = $dataPreType ? (int) ($outreachMap[$dataPreType->id] ?? 0) : 0;
            $followUps = (int) ($followUpsByUser[$uid] ?? 0);
            $newCalls = (int) ($newCallsByUser[$uid] ?? 0);
            $leadsGen = (int) ($leadsGenByUser[$uid] ?? 0);
            $converted = (int) ($convertedByUser[$uid] ?? 0);
            $quoteSent = (int) ($quoteSentByUser[$uid] ?? 0);
            $invoiceSent = (int) ($invoiceSentByUser[$uid] ?? 0);
            $products = $productCountsByUser[$uid] ?? $this->emptyProductCounts($productGroups);

            $monthTarget = (float) ($member->sales_target ?? 0);
            $monthAchieve = (float) ($monthlySales->get($uid)->achieved_sales ?? 0);
            $monthBalance = max(0, $monthTarget - $monthAchieve);
            $weekTarget = $monthTarget > 0 ? round($monthTarget / 4, 2) : 0;
            $weekAchieve = (float) ($weekAchievedByUser[$uid] ?? 0);

            $totals['follow_ups'] += $followUps;
            $totals['new_calls'] += $newCalls;
            $totals['leads_gen'] += $leadsGen;
            $totals['leads_converted'] += $converted;
            $totals['quote_sent'] += $quoteSent;
            $totals['invoice_sent'] += $invoiceSent;
            $totals['data_pre_analysis'] += $dataPre;
            $totals['month_target'] += $monthTarget;
            $totals['month_achieve'] += $monthAchieve;
            $totals['month_balance'] += $monthBalance;
            $totals['week_target'] += $weekTarget;
            $totals['week_achieve'] += $weekAchieve;
            foreach ($productGroups as $group) {
                foreach (array_keys($group['columns']) as $colKey) {
                    $totals['products'][$group['key']][$colKey] += $products[$group['key']][$colKey] ?? 0;
                }
            }

            $rows[] = [
                'user' => $member,
                'follow_ups' => $followUps,
                'new_calls' => $newCalls,
                'leads_gen' => $leadsGen,
                'products' => $products,
                'marketing' => $marketingCounts,
                'leads_converted' => $converted,
                'quote_sent' => $quoteSent,
                'invoice_sent' => $invoiceSent,
                'data_pre_analysis' => $dataPre,
                'month_target' => $monthTarget,
                'month_achieve' => $monthAchieve,
                'month_balance' => $monthBalance,
                'week_target' => $weekTarget,
                'week_achieve' => $weekAchieve,
                'other_activity' => $report->other_activity ?? null,
                'today_activities' => $todayActivitiesByUser[$uid] ?? [],
                'has_report' => (bool) $report,
            ];
        }

        $leadsByUser = $todayLeads->groupBy('user_owner_id');
        $dealsByUser = $todayDirectDeals->groupBy('user_owner_id');

        return [
            'reportDate' => $reportDate,
            'monthLabel' => $monthLabel,
            'weekLabel' => $weekLabel,
            'users' => $users,
            'rows' => $rows,
            'totals' => $totals,
            'productGroups' => $productGroups,
            'marketingTypes' => $marketingTypes,
            'leadsByUser' => $leadsByUser,
            'todayLeads' => $todayLeads,
            'dealsByUser' => $dealsByUser,
            'todayDirectDeals' => $todayDirectDeals,
        ];
    }

    protected function getTodayActivitiesByUsers(array $userIds, Carbon $dayStart, Carbon $dayEnd): array
    {
        if (empty($userIds)) {
            return [];
        }

        $activitiesByUser = [];
        foreach ($userIds as $uid) {
            $activitiesByUser[$uid] = [];
        }

        $rawItems = [];

        // 1. Notes
        if (class_exists(Note::class) && Schema::hasTable('notes')) {
            $notes = Note::query()
                ->whereIn('created_by', $userIds)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->where(function ($q) {
                    $q->whereRaw('LOWER(related_type) LIKE ?', ['%lead%'])
                        ->orWhereRaw('LOWER(related_type) LIKE ?', ['%deal%']);
                })
                ->orderByDesc('id')
                ->get();
            foreach ($notes as $note) {
                $rawItems[] = [
                    'uid' => (int) $note->created_by,
                    'type' => 'Note',
                    'title' => \Illuminate\Support\Str::limit(strip_tags((string) $note->content), 80),
                    'related_type' => $note->related_type,
                    'related_id' => $note->related_id,
                    'at' => $note->created_at,
                ];
            }
        }

        // 2. Tasks
        if (class_exists(Task::class) && Schema::hasTable('tasks')) {
            $tasks = Task::query()
                ->where(function ($q) use ($userIds) {
                    $q->whereIn('created_by', $userIds)
                        ->orWhereIn('user_owner_id', $userIds);
                })
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->where(function ($q) {
                    $q->whereRaw('LOWER(related_type) LIKE ?', ['%lead%'])
                        ->orWhereRaw('LOWER(related_type) LIKE ?', ['%deal%']);
                })
                ->orderByDesc('id')
                ->get();
            foreach ($tasks as $task) {
                $uid = (int) ($task->created_by ?: $task->user_owner_id);
                $rawItems[] = [
                    'uid' => $uid,
                    'type' => 'Task',
                    'title' => $task->name ?? 'Task',
                    'related_type' => $task->related_type,
                    'related_id' => $task->related_id,
                    'at' => $task->created_at,
                ];
            }
        }

        // 3. Meetings
        if (class_exists(Meeting::class) && Schema::hasTable('meetings')) {
            $meetings = Meeting::query()
                ->where(function ($q) use ($userIds) {
                    $q->whereIn('created_by', $userIds)
                        ->orWhereIn('user_owner_id', $userIds);
                })
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->where(function ($q) {
                    $q->whereRaw('LOWER(related_type) LIKE ?', ['%lead%'])
                        ->orWhereRaw('LOWER(related_type) LIKE ?', ['%deal%']);
                })
                ->orderByDesc('id')
                ->get();
            foreach ($meetings as $meeting) {
                $uid = (int) ($meeting->created_by ?: $meeting->user_owner_id);
                $rawItems[] = [
                    'uid' => $uid,
                    'type' => 'Meeting',
                    'title' => $meeting->name ?? 'Meeting',
                    'related_type' => $meeting->related_type,
                    'related_id' => $meeting->related_id,
                    'at' => $meeting->created_at,
                ];
            }
        }

        // 4. Calls
        if (class_exists(Call::class) && Schema::hasTable('calls')) {
            $calls = Call::query()
                ->where(function ($q) use ($userIds) {
                    $q->whereIn('created_by', $userIds)
                        ->orWhereIn('user_owner_id', $userIds);
                })
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->where(function ($q) {
                    $q->whereRaw('LOWER(related_type) LIKE ?', ['%lead%'])
                        ->orWhereRaw('LOWER(related_type) LIKE ?', ['%deal%']);
                })
                ->orderByDesc('id')
                ->get();
            foreach ($calls as $call) {
                $uid = (int) ($call->created_by ?: $call->user_owner_id);
                $rawItems[] = [
                    'uid' => $uid,
                    'type' => 'Call',
                    'title' => $call->name ?? $call->subject ?? 'Call',
                    'related_type' => $call->related_type,
                    'related_id' => $call->related_id,
                    'at' => $call->created_at,
                ];
            }
        }

        // 5. Emails
        if (class_exists(\App\Models\QuotationEmailSendLog::class) && Schema::hasTable('quotation_email_send_logs')) {
            $emailLogs = \App\Models\QuotationEmailSendLog::query()
                ->with(['quotation.deal'])
                ->whereIn('sent_by', $userIds)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->orderByDesc('id')
                ->get();
            foreach ($emailLogs as $emailLog) {
                $deal = optional($emailLog->quotation)->deal;
                if ($deal) {
                    $rawItems[] = [
                        'uid' => (int) $emailLog->sent_by,
                        'type' => 'Email',
                        'title' => $emailLog->subject ?? 'Quotation Email Sent',
                        'related_label' => 'Deal: ' . ($deal->title ?? ('#' . $deal->id)),
                        'at' => $emailLog->created_at,
                    ];
                }
            }
        }

        // Bulk resolve Lead / Deal titles
        $leadIds = [];
        $dealIds = [];
        foreach ($rawItems as $item) {
            if (!empty($item['related_type']) && !empty($item['related_id'])) {
                $rel = strtolower((string) $item['related_type']);
                if (str_contains($rel, 'lead')) {
                    $leadIds[] = (int) $item['related_id'];
                } elseif (str_contains($rel, 'deal')) {
                    $dealIds[] = (int) $item['related_id'];
                }
            }
        }

        $leadTitles = !empty($leadIds) ? Lead::whereIn('id', array_unique($leadIds))->pluck('title', 'id')->all() : [];
        $dealTitles = !empty($dealIds) ? Deal::whereIn('id', array_unique($dealIds))->pluck('title', 'id')->all() : [];

        foreach ($rawItems as $item) {
            $uid = $item['uid'];
            if (!isset($activitiesByUser[$uid])) {
                continue;
            }

            $relatedLabel = $item['related_label'] ?? '';
            if ($relatedLabel === '' && !empty($item['related_type']) && !empty($item['related_id'])) {
                $rel = strtolower((string) $item['related_type']);
                $id = (int) $item['related_id'];
                if (str_contains($rel, 'lead')) {
                    $t = $leadTitles[$id] ?? null;
                    $relatedLabel = 'Lead: ' . ($t ?: ('#' . $id));
                } elseif (str_contains($rel, 'deal')) {
                    $t = $dealTitles[$id] ?? null;
                    $relatedLabel = 'Deal: ' . ($t ?: ('#' . $id));
                }
            }

            $activitiesByUser[$uid][] = [
                'type' => $item['type'],
                'title' => $item['title'],
                'related' => $relatedLabel,
                'at' => $item['at'],
            ];
        }

        foreach ($activitiesByUser as $uid => $acts) {
            usort($activitiesByUser[$uid], fn ($a, $b) => strcmp((string) ($b['at'] ?? ''), (string) ($a['at'] ?? '')));
        }

        return $activitiesByUser;
    }

    protected function resolveUsers(User $currentUser, $filterUserId = null)
    {
        $roleType = (int) ($currentUser->crm_role_type ?? -1);
        $query = User::query()
            ->where('status', 1)
            ->whereNotIn('crm_role_type', [0, 1])
            ->orderBy('name');

        if ($roleType === 2) {
            $query->where(function ($q) use ($currentUser) {
                $q->where('assign_manager', $currentUser->id)
                    ->orWhere('id', $currentUser->id);
            });
        } elseif ($roleType === 3) {
            $query->where('id', $currentUser->id);
        }

        if ($filterUserId && in_array($roleType, [0, 1, 2], true)) {
            $query->where('id', (int) $filterUserId);
        }

        return $query->get();
    }

    /**
     * Map of user_id => [Y-m-d => true] for days with a submitted daily report.
     */
    protected function submittedReportDates($dailyReports): array
    {
        $dates = [];
        foreach ($dailyReports as $report) {
            if (!$report->report_date) {
                continue;
            }
            $dates[(int) $report->user_id][Carbon::parse($report->report_date)->toDateString()] = true;
        }

        return $dates;
    }

    /**
     * Keep only rows whose user submitted a daily report on the row's date.
     */
    protected function scopeToSubmittedReportDays($query, string $userColumn, string $dateExpression): void
    {
        $query->whereExists(function ($sub) use ($userColumn, $dateExpression) {
            $sub->selectRaw('1')
                ->from('daily_reports as dr_scope')
                ->whereColumn('dr_scope.user_id', $userColumn)
                ->whereRaw('dr_scope.report_date = DATE(' . $dateExpression . ')')
                ->whereNull('dr_scope.deleted_at')
                ->where(function ($q) {
                    $q->where('dr_scope.status', 'submitted')->orWhereNull('dr_scope.status');
                });
        });
    }

    protected function followUpCounts(array $userIds, Carbon $dayStart, Carbon $dayEnd, ?array $submittedDates = null): array
    {
        $counts = array_fill_keys($userIds, 0);

        if (Schema::hasTable('calls')) {
            $relatedCalls = Call::query()
                ->where(function ($q) use ($dayStart, $dayEnd) {
                    $q->whereBetween('start_at', [$dayStart, $dayEnd])
                        ->orWhere(function ($q2) use ($dayStart, $dayEnd) {
                            $q2->whereNull('start_at')
                                ->whereBetween('created_at', [$dayStart, $dayEnd]);
                        });
                })
                ->where(function ($q) {
                    $q->where('related_type', 'like', '%Lead%')
                        ->orWhere('related_type', 'like', '%Deal%');
                })
                ->when($userIds, function ($q) use ($userIds) {
                    $q->where(function ($inner) use ($userIds) {
                        $inner->whereIn('user_owner_id', $userIds)
                            ->orWhereIn('created_by', $userIds);
                    });
                })
                ->get(['id', 'user_owner_id', 'created_by', 'start_at', 'created_at']);

            foreach ($relatedCalls as $call) {
                $uid = (int) ($call->user_owner_id ?: $call->created_by);
                if (!isset($counts[$uid])) {
                    continue;
                }
                if ($submittedDates !== null) {
                    $at = $call->start_at ?: $call->created_at;
                    if (!$at || !isset($submittedDates[$uid][Carbon::parse($at)->toDateString()])) {
                        continue;
                    }
                }
                $counts[$uid]++;
            }
        }

        // Existing call logs touched today (follow-up updates), not newly created today
        $existingTouched = CallLog::query()
            ->whereBetween('updated_at', [$dayStart, $dayEnd])
            ->where('created_at', '<', $dayStart)
            ->when($userIds, fn ($q) => $q->whereIn('created_by', $userIds))
            ->get(['created_by', 'updated_at']);

        foreach ($existingTouched as $log) {
            $uid = (int) $log->created_by;
            if (!isset($counts[$uid])) {
                continue;
            }
            if ($submittedDates !== null
                && (!$log->updated_at || !isset($submittedDates[$uid][Carbon::parse($log->updated_at)->toDateString()]))) {
                continue;
            }
            $counts[$uid]++;
        }

        return $counts;
    }

    protected function emptyProductCounts(array $productGroups): array
    {
        $out = [];
        foreach ($productGroups as $group) {
            $out[$group['key']] = [];
            foreach (array_keys($group['columns']) as $colKey) {
                $out[$group['key']][$colKey] = 0;
            }
        }

        return $out;
    }

    protected function normalizeMatchText(string $text): string
    {
        $text = strtolower(trim($text));
        $text = str_replace(['/', '\\', '-', '_', '&'], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Match call_logs.type only to the best product category name.
     */
    protected function matchProductColumn(string $haystack, array $productGroups): ?array
    {
        if ($haystack === '') {
            return null;
        }

        $candidates = [];
        foreach ($productGroups as $group) {
            foreach ($group['columns'] as $colKey => $col) {
                $name = $col['match_name'] ?? $this->normalizeMatchText($col['label'] ?? '');
                if ($name === '') {
                    continue;
                }
                $score = 0;
                if ($haystack === $name) {
                    $score = 1000 + strlen($name);
                } elseif (str_contains($haystack, $name)) {
                    $score = 500 + strlen($name);
                } elseif (str_contains($name, $haystack) && strlen($haystack) >= 3) {
                    $score = 200 + strlen($haystack);
                } else {
                    $hayTokens = array_filter(explode(' ', $haystack), fn ($t) => strlen($t) >= 3);
                    $nameTokens = array_filter(explode(' ', $name), fn ($t) => strlen($t) >= 3);
                    $overlap = array_intersect($hayTokens, $nameTokens);
                    if (!empty($overlap)) {
                        $score = 50 + (count($overlap) * 20) + strlen(implode('', $overlap));
                    }
                }
                if ($score > 0) {
                    $candidates[] = [
                        'score' => $score,
                        'group' => $group['key'],
                        'col' => $colKey,
                    ];
                }
            }
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return [$candidates[0]['group'], $candidates[0]['col']];
    }

    /**
     * Resolve Product/Services names from comma-separated category IDs,
     * and Industries from industry_id (fallback: company industry).
     */
    protected function attachProductAndIndustryLabels($items): void
    {
        $allCategoryIds = [];
        foreach ($items as $item) {
            $raw = (string) ($item->getAttributes()['category'] ?? '');
            $ids = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $raw))));
            $item->setAttribute('category_id_list', $ids);
            foreach ($ids as $id) {
                $allCategoryIds[$id] = true;
            }
        }

        $namesById = empty($allCategoryIds)
            ? collect()
            : ProductCategory::whereIn('id', array_keys($allCategoryIds))->pluck('category_name', 'id');

        foreach ($items as $item) {
            $names = [];
            foreach (($item->category_id_list ?? []) as $id) {
                if (isset($namesById[$id])) {
                    $names[] = $namesById[$id];
                }
            }
            $item->category_names = $names;
            $item->product_services_label = !empty($names) ? implode(', ', $names) : '—';

            $industryName = optional($item->industry)->name;
            if (!$industryName && $item->relationLoaded('organization') && $item->organization) {
                $industryName = optional($item->organization->industry)->name;
            }
            $item->industry_label = $industryName ?: '—';
        }
    }

    protected function weekOfMonth(Carbon $date): array
    {
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();
        $day = (int) $date->day;
        $weekNumber = (int) ceil($day / 7);
        $weekStart = $monthStart->copy()->addDays(($weekNumber - 1) * 7)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
        if ($weekEnd->gt($monthEnd)) {
            $weekEnd = $monthEnd->copy()->endOfDay();
        }
        $weeksInMonth = (int) ceil(((int) $monthEnd->day) / 7);

        return [
            'number' => $weekNumber,
            'start' => $weekStart,
            'end' => $weekEnd,
            'weeks_in_month' => max(1, $weeksInMonth),
        ];
    }
}
