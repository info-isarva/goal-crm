<div class="kpi-row">
    <div class="kpi"><div class="n">{{ $sanctionCap > 0 ? '₹ '.number_format($sanctionCap, 0) : '—' }}</div><div class="l">Total Sanctioned</div></div>
    <div class="kpi"><div class="n">₹ {{ number_format($verifiedDisbTotal, 0) }}</div><div class="l">Verified Disbursed</div></div>
    <div class="kpi"><div class="n">₹ {{ number_format($pendingDisbTotal, 0) }}</div><div class="l">Pending Verification</div></div>
    <div class="kpi"><div class="n">{{ $sanctionCap > 0 ? '₹ '.number_format(max(0, $sanctionCap - $verifiedDisbTotal - $pendingDisbTotal), 0) : '—' }}</div><div class="l">Remaining Eligible</div></div>
</div>

@if($case->disbursements->contains(fn ($d) => (bool) $d->verified_at))
<div class="sec-card mb-3" style="padding:.85rem 1rem">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <div class="fw-bold" style="font-size:.9rem;color:#0f172a">PD-01 handoff active</div>
            <div class="text-muted" style="font-size:.78rem">Verified disbursement recognized revenue and queued PMS, Accounts, and Payout (On Hold).</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('goal.pms.index', ['q' => $case->case_no]) }}" class="btn-ghost" style="height:34px;padding:0 12px;text-decoration:none">PMS</a>
            <a href="{{ route('goal.accounts.index', ['q' => $case->case_no]) }}" class="btn-ghost" style="height:34px;padding:0 12px;text-decoration:none">Accounts</a>
            <a href="{{ route('goal.payouts.index', ['q' => $case->case_no]) }}" class="btn-ghost" style="height:34px;padding:0 12px;text-decoration:none">Payouts</a>
            <a href="{{ route('goal.revenue-ledger') }}" class="btn-ghost" style="height:34px;padding:0 12px;text-decoration:none">Revenue</a>
        </div>
    </div>
</div>
@endif

<div class="sec-card">
    <div class="sec-head">
        <div class="sec-head-left">
            <span class="sec-ico ico-blue"><i class="bi bi-cash-stack"></i></span>
            <div>
                <h6>Disbursements</h6>
                <div class="sec-sub">Partial disbursements supported. Revenue recognizes only on verification.</div>
            </div>
        </div>
        @if($canEdit && in_array($case->status, ['Sanctioned', 'Disbursed'], true))
            <button type="button" class="btn-primary-cw" data-bs-toggle="modal" data-bs-target="#cwAddDisbModal"><i class="bi bi-plus-lg"></i> Add Disbursement</button>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Reference</th>
                    <th>Lender</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Lender Ref</th>
                    <th>Verification</th>
                    <th>Verified By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($case->disbursements as $d)
                    <tr>
                        <td class="fw-semibold">DISB-{{ $d->id }}</td>
                        <td>{{ $d->application?->lender_name ?? $case->lender_name ?? '—' }}</td>
                        <td>₹ {{ number_format((float)$d->amount, 0) }}</td>
                        <td>{{ optional($d->disbursed_on)->format('d M Y') }}</td>
                        <td>{{ $d->bank_reference }}</td>
                        <td>
                            @if($d->verified_at)
                                <span class="doc-badge ok">Verified</span>
                            @else
                                <span class="doc-badge warn">{{ $d->status ?? 'Pending Verification' }}</span>
                            @endif
                        </td>
                        <td>{{ $d->verifier?->name ?? '—' }}</td>
                        <td>
                            @if(!$d->verified_at && $canDisburse)
                                <form method="POST" action="{{ route('goal.disbursements.verify', $d) }}" onsubmit="return confirm('Verify this disbursement and recognize revenue (PD-01)?')">
                                    @csrf
                                    <button type="submit" class="btn-ghost" style="height:32px;padding:0 10px;color:#059669">Verify</button>
                                </form>
                            @elseif($d->verified_at)
                                <span class="text-muted" style="font-size:.75rem">Queued to PMS / Accounts</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No disbursements recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
