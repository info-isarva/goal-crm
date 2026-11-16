<div class="sec-card">
    <div class="sec-head">
        <div class="sec-head-left">
            <span class="sec-ico ico-green"><i class="bi bi-award"></i></span>
            <div>
                <h6>Sanctions</h6>
                <div class="sec-sub">Revisions are preserved — historical sanctions are never overwritten.</div>
            </div>
        </div>
        @if($canEdit && $case->bankApplications->isNotEmpty())
            <button type="button" class="btn-primary-cw" data-bs-toggle="modal" data-bs-target="#cwAddSanctionModal"><i class="bi bi-plus-lg"></i> Add Sanction</button>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Lender</th>
                    <th>App Ref</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Validity</th>
                    <th>Status</th>
                    <th>Rev</th>
                    <th>Recorded By</th>
                    <th>Evidence</th>
                </tr>
            </thead>
            <tbody>
                @forelse($case->sanctions as $s)
                    <tr>
                        <td>{{ $s->application?->lender_name ?? '—' }}</td>
                        <td>{{ $s->application?->application_ref ?? '—' }}</td>
                        <td class="fw-semibold">₹ {{ number_format((float)$s->sanction_amount, 0) }}</td>
                        <td>{{ optional($s->sanction_date)->format('d M Y') }}</td>
                        <td>{{ optional($s->validity_until)->format('d M Y') ?: '—' }}</td>
                        <td><span class="doc-badge {{ $s->status === 'Active' ? 'ok' : 'warn' }}">{{ $s->status }}</span></td>
                        <td>{{ $s->revision }}</td>
                        <td>{{ $s->recorder?->name ?? '—' }}</td>
                        <td>
                            @if($s->evidence_path)
                                <a href="{{ asset('storage/'.$s->evidence_path) }}" target="_blank" class="btn-ghost" style="height:32px;padding:0 8px"><i class="bi bi-download"></i></a>
                            @else — @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No sanctions recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
