@php
    $auditLabel = function (string $key): string {
        return match ($key) {
            'customer_name' => 'Customer',
            'mobile' => 'Mobile',
            'product' => 'Product',
            'loan_amount' => 'Loan amount',
            'lender_name' => 'Lender',
            'application_ref' => 'App ref',
            'status' => 'Status',
            'owner_id' => 'Owner',
            'from' => 'From',
            'to' => 'To',
            'body' => 'Note',
            default => str_replace('_', ' ', ucfirst($key)),
        };
    };

    $parseAuditValue = function (?string $raw) use ($auditLabel): array {
        if ($raw === null || trim($raw) === '') {
            return [];
        }
        $trim = trim($raw);
        $decoded = json_decode($trim, true);
        if (is_array($decoded)) {
            $rows = [];
            foreach ($decoded as $k => $v) {
                if (is_array($v) || is_object($v)) {
                    $v = json_encode($v, JSON_UNESCAPED_UNICODE);
                }
                $rows[] = [
                    'label' => $auditLabel((string) $k),
                    'value' => $v === null || $v === '' ? '—' : (string) $v,
                ];
            }

            return $rows;
        }

        return [['label' => '', 'value' => $trim]];
    };

    $buildChanges = function (?string $fromRaw, ?string $toRaw) use ($parseAuditValue): array {
        $fromRows = $parseAuditValue($fromRaw);
        $toRows = $parseAuditValue($toRaw);
        if ($fromRows === [] && $toRows === []) {
            return [];
        }

        $fromMap = [];
        foreach ($fromRows as $r) {
            if ($r['label'] !== '') {
                $fromMap[$r['label']] = $r['value'];
            }
        }
        $toMap = [];
        foreach ($toRows as $r) {
            if ($r['label'] !== '') {
                $toMap[$r['label']] = $r['value'];
            }
        }

        $labels = array_unique(array_merge(array_keys($fromMap), array_keys($toMap)));
        if ($labels !== []) {
            $out = [];
            foreach ($labels as $label) {
                $out[] = [
                    'label' => $label,
                    'from' => $fromMap[$label] ?? null,
                    'to' => $toMap[$label] ?? null,
                ];
            }

            return $out;
        }

        // Plain string values (not JSON objects)
        return [[
            'label' => 'Value',
            'from' => $fromRows[0]['value'] ?? null,
            'to' => $toRows[0]['value'] ?? null,
        ]];
    };

    $typeMeta = function (?string $type): array {
        return match ($type) {
            'created' => ['Created', 'ok', 'bi-plus-lg'],
            'updated' => ['Updated', 'info', 'bi-pencil-square'],
            'document' => ['Document', 'teal', 'bi-cloud-upload'],
            'assignment' => ['Assignment', 'warn', 'bi-person-check'],
            'application' => ['Application', 'info', 'bi-layers'],
            'query' => ['Query', 'warn', 'bi-chat-left-text'],
            'sanction' => ['Sanction', 'ok', 'bi-award'],
            'disbursement' => ['Disbursement', 'ok', 'bi-cash-coin'],
            'status' => ['Status', 'info', 'bi-arrow-right-circle'],
            'bm_reco' => ['BM Reco', 'warn', 'bi-lightbulb'],
            'revenue' => ['Revenue', 'ok', 'bi-currency-rupee'],
            'visit' => ['Visit', 'teal', 'bi-geo-alt'],
            default => [ucfirst((string) ($type ?: 'Event')), 'slate', 'bi-dot'],
        };
    };
@endphp

<style>
.cw-page .act-wrap{padding:0 0 4px}
.cw-page .act-toolbar{
    display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
    padding:12px 16px 0;
}
.cw-page .act-list{list-style:none;margin:0;padding:8px 16px 16px;display:flex;flex-direction:column;gap:10px}
.cw-page .act-item{
    display:grid;grid-template-columns:44px minmax(0,1fr);gap:12px;
    padding:12px 14px;border:1px solid #e8edf5;border-radius:14px;background:#fff;
}
.cw-page .act-item:hover{border-color:#dbe4f0;background:#fbfdff}
.cw-page .act-ico{
    width:40px;height:40px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;
    font-size:1rem;flex-shrink:0;
}
.cw-page .act-ico.ok{background:#dcfce7;color:#15803d}
.cw-page .act-ico.info{background:#dbeafe;color:#1d4ed8}
.cw-page .act-ico.warn{background:#ffedd5;color:#c2410c}
.cw-page .act-ico.teal{background:#ccfbf1;color:#0f766e}
.cw-page .act-ico.slate{background:#f1f5f9;color:#475569}
.cw-page .act-main{min-width:0}
.cw-page .act-top{
    display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;
}
.cw-page .act-title{margin:0;font-size:.92rem;font-weight:700;color:#0f172a;line-height:1.3}
.cw-page .act-meta{
    display:flex;flex-wrap:wrap;gap:6px 10px;align-items:center;
    margin-top:4px;font-size:.76rem;color:#64748b;
}
.cw-page .act-meta .sep{color:#cbd5e1}
.cw-page .act-pill{
    display:inline-flex;align-items:center;padding:.12rem .5rem;border-radius:999px;
    font-size:.68rem;font-weight:700;letter-spacing:.02em;text-transform:uppercase;
}
.cw-page .act-pill.ok{background:#dcfce7;color:#166534}
.cw-page .act-pill.info{background:#dbeafe;color:#1e40af}
.cw-page .act-pill.warn{background:#ffedd5;color:#9a3412}
.cw-page .act-pill.teal{background:#ccfbf1;color:#115e59}
.cw-page .act-pill.slate{background:#f1f5f9;color:#475569}
.cw-page .act-when{white-space:nowrap;font-variant-numeric:tabular-nums;color:#64748b;font-size:.78rem;font-weight:600}
.cw-page .act-changes{
    margin-top:10px;display:grid;gap:6px;
    background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:8px 10px;
}
.cw-page .act-change{
    display:grid;grid-template-columns:120px minmax(0,1fr);gap:8px;align-items:start;
    font-size:.8rem;
}
.cw-page .act-change .k{color:#64748b;font-weight:600}
.cw-page .act-change .v{min-width:0;color:#0f172a;word-break:break-word}
.cw-page .act-change .from{color:#94a3b8;text-decoration:line-through;margin-right:6px}
.cw-page .act-change .arrow{color:#94a3b8;margin:0 4px}
.cw-page .act-note{
    margin-top:8px;font-size:.8rem;color:#475569;background:#fff7ed;border:1px solid #fed7aa;
    border-radius:8px;padding:6px 10px;
}
.cw-page .act-empty{text-align:center;padding:28px 12px;color:#94a3b8}
.cw-page .act-empty i{font-size:1.6rem;display:block;margin-bottom:6px;opacity:.5}
@media (max-width:767.98px){
    .cw-page .act-change{grid-template-columns:1fr}
    .cw-page .act-item{grid-template-columns:36px minmax(0,1fr);padding:10px}
    .cw-page .act-ico{width:36px;height:36px;border-radius:10px}
}
</style>

<div class="sec-card act-wrap">
    <div class="act-toolbar">
        <div class="sec-head-left" style="padding:0">
            <span class="sec-ico"><i class="bi bi-clock-history"></i></span>
            <div>
                <h6 style="margin:0;font-size:1rem;font-weight:700">Activity History</h6>
                <div class="sec-sub">Case audit trail · newest first</div>
            </div>
        </div>
        <select id="cwActivityFilter" class="form-select" style="max-width:220px;min-height:40px;border-radius:10px">
            <option value="all">All activities</option>
            <option value="updated">Case updates</option>
            <option value="document">Documents</option>
            <option value="assignment">Assignments</option>
            <option value="application">Lender applications</option>
            <option value="query">Bank queries</option>
            <option value="sanction">Sanctions</option>
            <option value="disbursement">Disbursements</option>
            <option value="status">Approvals / status</option>
            <option value="created">Created</option>
            <option value="visit">Visits / Follow-ups</option>
        </select>
    </div>

    <ul class="act-list">
        @forelse($case->events as $ev)
            @php
                [$typeLabel, $tone, $ico] = $typeMeta($ev->event_type);
                $evMeta = is_array($ev->meta ?? null) ? $ev->meta : [];
                $isRecoRejected = $ev->event_type === 'bm_reco'
                    && (($evMeta['action'] ?? null) === 'Rejected' || str_contains((string) $ev->title, 'Rejected'));
                if ($isRecoRejected) {
                    $tone = 'bad';
                    $ico = 'bi-x-circle';
                    $typeLabel = 'Rejected';
                } elseif ($ev->event_type === 'bm_reco' && (($evMeta['action'] ?? null) === 'Approved' || str_contains((string) $ev->title, 'Approved'))) {
                    $tone = 'ok';
                    $ico = 'bi-check2-circle';
                    $typeLabel = 'Approved';
                }
                $rejectDetail = filled($evMeta['rejection_reason'] ?? null)
                    ? (string) $evMeta['rejection_reason']
                    : null;
                $role = match ((int) ($ev->user?->crm_role_type ?? -1)) {
                    8 => 'BM', 10 => 'JSM', 11 => 'SM', 12 => 'SSM',
                    default => strtoupper((string) ($ev->user?->job_role ?: 'User')),
                };
                $changes = $buildChanges($ev->from_value, $ev->to_value);
            @endphp
            <li class="act-item" data-activity-type="{{ $ev->event_type }}">
                <span class="act-ico {{ $tone }}"><i class="bi {{ $ico }}"></i></span>
                <div class="act-main">
                    <div class="act-top">
                        <div class="min-w-0">
                            <p class="act-title">{{ $ev->title }}</p>
                            <div class="act-meta">
                                <span class="act-pill {{ $tone }}">{{ $typeLabel }}</span>
                                <span>{{ $ev->user?->name ?? 'System' }}</span>
                                @if($ev->user)
                                    <span class="sep">·</span>
                                    <span>{{ $role }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="act-when">{{ $ev->created_at?->format('d M Y, h:i A') }}</div>
                    </div>

                    @if($changes !== [])
                        <div class="act-changes">
                            @foreach($changes as $ch)
                                <div class="act-change">
                                    <div class="k">{{ $ch['label'] }}</div>
                                    <div class="v">
                                        @if(($ch['from'] ?? null) !== null && ($ch['to'] ?? null) !== null && (string) $ch['from'] !== (string) $ch['to'])
                                            <span class="from">{{ \Illuminate\Support\Str::limit((string) $ch['from'], 80) }}</span>
                                            <span class="arrow">→</span>
                                            <strong>{{ \Illuminate\Support\Str::limit((string) $ch['to'], 80) }}</strong>
                                        @elseif(($ch['to'] ?? null) !== null)
                                            {{ \Illuminate\Support\Str::limit((string) $ch['to'], 120) }}
                                        @elseif(($ch['from'] ?? null) !== null)
                                            {{ \Illuminate\Support\Str::limit((string) $ch['from'], 120) }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($rejectDetail)
                        <div class="act-note" style="color:#be123c;font-weight:600">Reason: {{ $rejectDetail }}</div>
                    @elseif(filled($ev->body))
                        <div class="act-note">{{ $ev->body }}</div>
                    @endif
                </div>
            </li>
        @empty
            <li class="act-empty">
                <i class="bi bi-clock-history"></i>
                No activity recorded yet.
            </li>
        @endforelse
    </ul>
</div>
