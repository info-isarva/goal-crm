<div class="sec-card">
    <div class="sec-head">
        <div class="sec-head-left">
            <span class="sec-ico"><i class="bi bi-clock-history"></i></span>
            <div>
                <h6>Activity History</h6>
                <div class="sec-sub">Append-only audit timeline. Entries cannot be edited or deleted.</div>
            </div>
        </div>
        <select id="cwActivityFilter" class="form-select" style="max-width:200px;min-height:38px">
            <option value="all">All Activities</option>
            <option value="updated">Case Updates</option>
            <option value="document">Documents</option>
            <option value="assignment">Assignments</option>
            <option value="application">Lender Applications</option>
            <option value="query">Bank Queries</option>
            <option value="sanction">Sanctions</option>
            <option value="disbursement">Disbursements</option>
            <option value="status">Approvals / Status</option>
            <option value="created">Created</option>
        </select>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date / Time</th>
                    <th>Activity</th>
                    <th>Previous</th>
                    <th>New</th>
                    <th>Performed By</th>
                    <th>Role</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($case->events as $ev)
                    @php
                        $role = match ((int) ($ev->user?->crm_role_type ?? -1)) {
                            8 => 'BM', 10 => 'JSM', 11 => 'SM', 12 => 'SSM',
                            default => strtoupper((string) ($ev->user?->job_role ?: 'User')),
                        };
                    @endphp
                    <tr data-activity-type="{{ $ev->event_type }}">
                        <td>{{ $ev->created_at?->format('d M Y H:i') }}</td>
                        <td class="fw-semibold">{{ $ev->title }}</td>
                        <td class="small text-muted" style="max-width:140px">{{ \Illuminate\Support\Str::limit($ev->from_value, 60) ?: '—' }}</td>
                        <td class="small" style="max-width:140px">{{ \Illuminate\Support\Str::limit($ev->to_value, 60) ?: '—' }}</td>
                        <td>{{ $ev->user?->name ?? 'System' }}</td>
                        <td>{{ $ev->user ? $role : '—' }}</td>
                        <td class="small">{{ $ev->body ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No activity recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
