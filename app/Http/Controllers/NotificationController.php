<?php

namespace App\Http\Controllers;

use App\Services\Goal\GoalNotify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Mark a notification as read (single id) or mark all as read.
     */
    public function markRead(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $id = $request->input('id');
        $markAll = $request->input('all');

        if ($markAll) {
            $user->unreadNotifications->markAsRead();
            return response()->json(['success' => true, 'unread' => 0]);
        }

        if ($id) {
            $notif = $user->unreadNotifications->where('id', $id)->first();
            if ($notif) {
                $notif->markAsRead();
                $unreadCount = $user->unreadNotifications()->count();
                return response()->json(['success' => true, 'unread' => $unreadCount]);
            }
            return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
        }

        return response()->json(['success' => false, 'message' => 'Invalid request'], 400);
    }

    /**
     * Show paginated Goal-style notifications inbox for the current user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $filters = $request->validate([
            'status' => 'nullable|in:all,unread,read',
            'type' => 'nullable|string|max:40',
            'q' => 'nullable|string|max:150',
        ]);

        $status = (string) ($filters['status'] ?? 'unread');
        $type = (string) ($filters['type'] ?? 'all');
        $q = trim((string) ($filters['q'] ?? ''));

        if ($type !== 'all' && ! in_array($type, GoalNotify::TYPES, true)) {
            $type = 'all';
        }

        $base = $user->notifications()->orderBy('created_at', 'desc');

        // KPIs from recent window (last 500) to avoid heavy full-table scans.
        $recentForKpi = (clone $base)->limit(500)->get();
        $kpis = [
            'unread' => $user->unreadNotifications()->count(),
            'today' => $recentForKpi->filter(fn ($n) => $n->created_at && $n->created_at->isToday())->count(),
            'action' => $recentForKpi->filter(function ($n) {
                if ($n->read_at) {
                    return false;
                }
                $data = $n->data ?? [];
                if (array_key_exists('action', $data)) {
                    return (bool) $data['action'];
                }

                // Legacy Goal alerts without action flag count as actionable.
                return ($data['url'] ?? $data['related_link'] ?? null) !== null;
            })->count(),
            'ageing' => $recentForKpi->filter(fn ($n) => GoalNotify::inferType($n->data ?? []) === 'payout')->count(),
        ];

        $query = $user->notifications()->orderBy('created_at', 'desc');
        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }
        if ($type !== 'all') {
            $query->where(function ($inner) use ($type) {
                $inner->where('data->type', $type);
                // Legacy rows without type: best-effort event key match
                if ($type === 'assign') {
                    $inner->orWhere('data->event', 'like', '%assign%');
                } elseif ($type === 'task') {
                    $inner->orWhere('data->event', 'like', 'bq-%');
                } elseif ($type === 'visit') {
                    $inner->orWhere('data->event', 'like', '%visit%')
                        ->orWhere('data->event', 'like', '%escalat%');
                } elseif ($type === 'payout') {
                    $inner->orWhere('data->event', 'like', '%payout%');
                }
            });
        }
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('data->title', 'like', $like)
                    ->orWhere('data->message', 'like', $like)
                    ->orWhere('data->ref', 'like', $like)
                    ->orWhere('data->event', 'like', $like);
            });
        }

        $notifications = $query->paginate(20)->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'filters' => [
                'status' => $status,
                'type' => $type,
                'q' => $q,
            ],
            'kpis' => $kpis,
            'typeOptions' => GoalNotify::TYPES,
        ]);
    }

    /**
     * Return recent notifications and unread count as JSON for AJAX polling.
     */
    public function recent(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        $notes = $user->notifications()->orderBy('created_at', 'desc')->limit(10)->get();
        $items = $notes->map(function ($n) {
            $data = $n->data ?? [];
            $type = GoalNotify::inferType($data);
            $link = GoalNotify::linkFromData($data);

            return [
                'id' => $n->id,
                'title' => $data['title'] ?? null,
                'message' => $data['message'] ?? ($data['title'] ?? 'Notification'),
                'type' => $type,
                'event' => $data['event'] ?? null,
                'ref' => $data['ref'] ?? null,
                'created_at' => $n->created_at->diffForHumans(),
                'read_at' => (bool) $n->read_at,
                'url' => $link,
                'task_link' => $link,
            ];
        });

        return response()->json([
            'success' => true,
            'unread' => $user->unreadNotifications()->count(),
            'items' => $items,
        ]);
    }

    /**
     * Weekly assigned target alert for employee/manager (monthly target / 4).
     * Shows when behind target and current time has reached an admin-configured
     * morning / afternoon / evening slot.
     *
     * Query: force=1 returns details even for header click (when behind target).
     */
    public function weeklyTargetAlert(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['show' => false, 'available' => false], 401);
        }

        // Only employee (3) and manager (2)
        if (!in_array((int) $user->crm_role_type, [2, 3], true)) {
            return response()->json(['show' => false, 'available' => false]);
        }

        $company = \App\Models\Company::query()->first();
        if (!$company || !$company->isWeeklyTargetAlertEnabled()) {
            return response()->json(['show' => false, 'available' => false, 'reason' => 'disabled']);
        }

        $force = $request->boolean('force');
        $times = $company->weeklyTargetAlertTimes();
        $now = now();
        $currentHm = $now->format('H:i');
        $slot = null;
        $slotLabels = [
            'morning' => 'Morning',
            'afternoon' => 'Afternoon',
            'evening' => 'Evening',
        ];

        // Latest slot whose scheduled time has passed today
        foreach (['morning', 'afternoon', 'evening'] as $key) {
            if ($currentHm >= $times[$key]) {
                $slot = $key;
            }
        }

        $monthTarget = (float) ($user->sales_target ?? 0);
        if ($monthTarget <= 0) {
            return response()->json([
                'show' => false,
                'available' => false,
                'reason' => 'no_target',
                'slot' => $slot,
                'times' => $times,
            ]);
        }

        $weeklyTarget = round($monthTarget / 4, 2);
        $weekMeta = $this->weekOfMonth($now);
        $weekStart = $weekMeta['start'];
        $weekEnd = $weekMeta['end'];

        $weeklyAchieved = (float) \App\Models\Deal::query()
            ->where('user_owner_id', $user->id)
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
            ->sum('amount');

        $percentage = $weeklyTarget > 0
            ? round(($weeklyAchieved / $weeklyTarget) * 100, 1)
            : 0;

        $payloadBase = [
            'slot' => $slot,
            'slot_label' => $slot ? ($slotLabels[$slot] ?? null) : null,
            'slot_time' => $slot ? ($times[$slot] ?? null) : null,
            'times' => $times,
            'server_time' => $now->format('Y-m-d H:i:s'),
            'user_name' => $user->name,
            'week_label' => 'Week ' . $weekMeta['number'],
            'week_range' => $weekStart->format('d M Y') . ' – ' . $weekEnd->format('d M Y'),
            'month_target' => $monthTarget,
            'month_target_formatted' => \App\Helpers\MoneyFormatter::formatIndian($monthTarget),
            'weekly_target' => $weeklyTarget,
            'weekly_target_formatted' => \App\Helpers\MoneyFormatter::formatIndian($weeklyTarget),
            'weekly_achieved' => $weeklyAchieved,
            'weekly_achieved_formatted' => \App\Helpers\MoneyFormatter::formatIndian($weeklyAchieved),
            'percentage' => $percentage,
            'currency_symbol' => optional($company)->currency_symbol ?: '₹',
        ];

        if ($weeklyAchieved >= $weeklyTarget) {
            return response()->json(array_merge($payloadBase, [
                'show' => false,
                'available' => false,
                'reached' => true,
                'percentage' => min(100, $percentage),
            ]));
        }

        $shortfall = max(0, $weeklyTarget - $weeklyAchieved);
        $payload = array_merge($payloadBase, [
            'reached' => false,
            'available' => true,
            'shortfall' => $shortfall,
            'shortfall_formatted' => \App\Helpers\MoneyFormatter::formatIndian($shortfall),
            'percentage' => $percentage,
        ]);

        // Auto popup only after first configured slot time has passed
        $autoShow = $slot !== null;
        if (!$autoShow && !$force) {
            return response()->json(array_merge($payload, [
                'show' => false,
                'reason' => 'before_first_slot',
            ]));
        }

        // Header force-open can show even before first slot (still behind)
        if ($force && $slot === null) {
            $payload['slot'] = 'preview';
            $payload['slot_label'] = 'Weekly Target';
            $payload['slot_time'] = null;
        }

        return response()->json(array_merge($payload, [
            'show' => $autoShow || $force,
        ]));
    }

    /**
     * Week buckets within a month (same logic as sales daily report).
     */
    private function weekOfMonth(\Carbon\Carbon $date): array
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

        return [
            'number' => $weekNumber,
            'start' => $weekStart,
            'end' => $weekEnd,
        ];
    }
}
