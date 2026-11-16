<div class="sec-card">
    <div class="sec-head">
        <div class="sec-head-left">
            <span class="sec-ico ico-amber"><i class="bi bi-chat-left-dots"></i></span>
            <div>
                <h6>Bank Queries</h6>
                <div class="sec-sub">Multiple queries per lender application are supported.</div>
            </div>
        </div>
        @if($canEdit && $case->bankApplications->isNotEmpty())
            <button type="button" class="btn-primary-cw" data-bs-toggle="modal" data-bs-target="#cwAddQueryModal"><i class="bi bi-plus-lg"></i> Add Query</button>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Query Ref</th>
                    <th>Lender</th>
                    <th>App Ref</th>
                    <th>Details</th>
                    <th>Responsible</th>
                    <th>Raised</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th>Resolved</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($case->bankQueries as $q)
                    <tr>
                        <td class="fw-semibold">{{ $q->query_ref }}</td>
                        <td>{{ $q->application?->lender_name ?? '—' }}</td>
                        <td>{{ $q->application?->application_ref ?? '—' }}</td>
                        <td style="max-width:220px">{{ \Illuminate\Support\Str::limit($q->details, 80) }}</td>
                        <td>{{ $q->responsible?->name ?? '—' }}</td>
                        <td>{{ optional($q->raised_at)->format('d M Y') }}</td>
                        <td>{{ optional($q->due_at)->format('d M Y') }}</td>
                        <td>
                            @php $ds = $q->displayStatus(); @endphp
                            <span class="doc-badge {{ $ds === 'Resolved' ? 'ok' : ($ds === 'Overdue' ? 'bad' : 'warn') }}">{{ $ds }}</span>
                        </td>
                        <td>{{ optional($q->resolved_at)->format('d M Y') ?: '—' }}</td>
                        <td>
                            @if($canEdit && $q->status !== 'Resolved')
                                <button type="button" class="btn-ghost" style="height:32px;padding:0 10px"
                                    data-bs-toggle="modal" data-bs-target="#cwResolveQueryModal"
                                    data-query-id="{{ $q->id }}"
                                    data-query-action="{{ route('goal.bank-queries.resolve', $q) }}">Resolve</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No bank queries recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
