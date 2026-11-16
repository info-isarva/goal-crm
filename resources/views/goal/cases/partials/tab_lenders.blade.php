<div class="kpi-row">
    <div class="kpi"><div class="n">{{ $lenderSummary['total'] }}</div><div class="l">Total</div></div>
    <div class="kpi"><div class="n">{{ $lenderSummary['login'] }}</div><div class="l">Bank Login</div></div>
    <div class="kpi"><div class="n">{{ $lenderSummary['processing'] }}</div><div class="l">Processing</div></div>
    <div class="kpi"><div class="n">{{ $lenderSummary['query'] }}</div><div class="l">Query Pending</div></div>
    <div class="kpi"><div class="n">{{ $lenderSummary['sanctioned'] }}</div><div class="l">Sanctioned</div></div>
    <div class="kpi"><div class="n">{{ $lenderSummary['rejected'] }}</div><div class="l">Rejected</div></div>
</div>

<div class="sec-card cw-table-card">
    <div class="sec-head has-line">
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
    <div class="cw-table-wrap">
        <div class="table-responsive">
            <table class="table mb-0 align-middle cw-lender-table">
                <colgroup>
                    <col style="width:16%">
                    <col style="width:14%">
                    <col style="width:12%">
                    <col style="width:12%">
                    <col style="width:12%">
                    <col style="width:12%">
                    <col style="width:14%">
                    <col style="width:8%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Lender</th>
                        <th>Application Ref</th>
                        <th>Login Date</th>
                        <th>Status</th>
                        <th class="text-end">Requested</th>
                        <th>Follow-up</th>
                        <th>Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($case->bankApplications as $ba)
                        @php
                            $baStatusClass = match ($ba->status) {
                                'Processing' => 'process',
                                'Bank Query' => 'query',
                                'Sanctioned' => 'ok',
                                'Disbursed' => 'done',
                                'Rejected' => 'bad',
                                default => 'login',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="cw-lender-name">{{ $ba->lender_name }}</div>
                            </td>
                            <td>{{ $ba->application_ref ?: '—' }}</td>
                            <td>{{ optional($ba->login_date)->format('d M Y') ?: '—' }}</td>
                            <td><span class="status-pill {{ $baStatusClass }}">{{ $ba->status }}</span></td>
                            <td class="text-end cw-lender-amt">
                                {{ $ba->requested_amount !== null ? '₹ '.number_format((float) $ba->requested_amount, 0) : '—' }}
                            </td>
                            <td>{{ optional($ba->follow_up_at)->format('d M Y') ?: '—' }}</td>
                            <td>{{ optional($ba->updated_at)->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                @if($canEdit)
                                    <div class="dropdown">
                                        <button type="button" class="btn-action" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#cwAddQueryModal" data-app-id="{{ $ba->id }}">Raise Query</button></li>
                                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#cwAddSanctionModal" data-app-id="{{ $ba->id }}">Record Sanction</button></li>
                                            <li><hr class="dropdown-divider"></li>
                                            @foreach(['Bank Login', 'Processing', 'Sanctioned', 'Rejected'] as $st)
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
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No lender applications. Add one to proceed to Bank Login.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
