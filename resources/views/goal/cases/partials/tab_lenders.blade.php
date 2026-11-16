<div class="kpi-row">
    <div class="kpi"><div class="n">{{ $lenderSummary['total'] }}</div><div class="l">Total</div></div>
    <div class="kpi"><div class="n">{{ $lenderSummary['login'] }}</div><div class="l">Bank Login</div></div>
    <div class="kpi"><div class="n">{{ $lenderSummary['processing'] }}</div><div class="l">Processing</div></div>
    <div class="kpi"><div class="n">{{ $lenderSummary['query'] }}</div><div class="l">Query Pending</div></div>
    <div class="kpi"><div class="n">{{ $lenderSummary['sanctioned'] }}</div><div class="l">Sanctioned</div></div>
    <div class="kpi"><div class="n">{{ $lenderSummary['rejected'] }}</div><div class="l">Rejected</div></div>
</div>

<div class="sec-card">
    <div class="sec-head">
        <div class="sec-head-left">
            <span class="sec-ico ico-blue"><i class="bi bi-bank"></i></span>
            <div>
                <h6>Lender Applications</h6>
                <div class="sec-sub">Each application keeps its own status and history.</div>
            </div>
        </div>
        @if($canEdit)
            <button type="button" class="btn-primary-cw" data-bs-toggle="modal" data-bs-target="#cwAddLenderModal"><i class="bi bi-plus-lg"></i> Add Lender Application</button>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Lender</th>
                    <th>Application Ref</th>
                    <th>Login Date</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Follow-up</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($case->bankApplications as $ba)
                    <tr>
                        <td class="fw-semibold">{{ $ba->lender_name }}</td>
                        <td>{{ $ba->application_ref ?: '—' }}</td>
                        <td>{{ optional($ba->login_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-pill login">{{ $ba->status }}</span></td>
                        <td>{{ $ba->requested_amount !== null ? '₹ '.number_format((float)$ba->requested_amount, 0) : '—' }}</td>
                        <td>{{ optional($ba->follow_up_at)->format('d M Y') ?: '—' }}</td>
                        <td>{{ optional($ba->updated_at)->format('d M Y H:i') }}</td>
                        <td>
                            @if($canEdit)
                            <div class="dropdown">
                                <button class="btn-ghost" style="height:32px;padding:0 8px" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#cwAddQueryModal" data-app-id="{{ $ba->id }}">Raise Query</button></li>
                                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#cwAddSanctionModal" data-app-id="{{ $ba->id }}">Record Sanction</button></li>
                                    <li><hr class="dropdown-divider"></li>
                                    @foreach(['Bank Login','Processing','Sanctioned','Rejected'] as $st)
                                        <li>
                                            <form method="POST" action="{{ route('goal.bank-applications.status', $ba) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $st }}">
                                                @if($st === 'Rejected')
                                                    <button type="submit" class="dropdown-item" onclick="var r=prompt('Rejection reason'); if(!r) return false; this.form.rejection_reason.value=r;">Mark {{ $st }}</button>
                                                    <input type="hidden" name="rejection_reason" value="">
                                                @else
                                                    <button type="submit" class="dropdown-item">Mark {{ $st }}</button>
                                                @endif
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No lender applications. Add one to proceed to Bank Login.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
