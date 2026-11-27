@php
    $brBank = is_array($case->bm_reco ?? null) ? $case->bm_reco : [];
    $recoBankStatus = (string) ($brBank['approval_status'] ?? $brBank['status'] ?? '');
    $showApprovedBank = $recoBankStatus === 'Approved' && filled($brBank['suggested_bank'] ?? null);
    $recoAmountDisp = \App\Helpers\MoneyFormatter::formatIndian(
        $brBank['amount'] ?? $brBank['recommended_amount'] ?? $case->loan_amount,
        2
    );
    $approvedAtDisp = null;
    if (! empty($brBank['approved_at'])) {
        try {
            $approvedAtDisp = \Illuminate\Support\Carbon::parse($brBank['approved_at'])->timezone(config('app.timezone'))->format('d M Y, h:i a');
        } catch (\Throwable $e) {
            $approvedAtDisp = (string) $brBank['approved_at'];
        }
    }
    $linkedBankApp = $case->bankApplications
        ->first(function ($ba) use ($brBank) {
            return mb_strtolower((string) $ba->lender_name) === mb_strtolower((string) ($brBank['suggested_bank'] ?? ''));
        }) ?? $case->bankApplications->first();
    $bankLoginId = $linkedBankApp?->application_ref ?: ($case->application_ref ?? null);
@endphp

@if($showApprovedBank)
<div class="cw-reco-bank-approved is-compact">
    <div class="cw-reco-bank-approved-title">
        <span class="cw-reco-bank-approved-ico" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
        <div class="min-w-0 flex-1">
            <div class="cw-reco-bank-approved-heading">
                Approved BM Recommendation
                <span class="cw-reco-status is-approved">Approved</span>
            </div>
            <div class="cw-reco-bank-approved-sub">
                <span><i class="bi bi-bank2"></i> {{ $brBank['suggested_bank'] }}@if(!empty($brBank['bank_code'])) ({{ $brBank['bank_code'] }})@endif</span>
                <span class="sep">·</span>
                <span class="amt"><i class="bi bi-currency-rupee"></i> {{ $recoAmountDisp !== '' ? $recoAmountDisp : '—' }}</span>
                <span class="sep">·</span>
                <span class="{{ filled($bankLoginId) ? 'login-ok' : 'login-miss' }}">
                    <i class="bi bi-fingerprint"></i>
                    {{ filled($bankLoginId) ? $bankLoginId : 'Login ID not added' }}
                </span>
            </div>
        </div>
    </div>
    <div class="cw-reco-bank-approved-grid">
        <div class="cw-reco-bank-kv">
            <span class="cw-reco-bank-kv-ico ico-product"><i class="bi bi-house-door"></i></span>
            <div><span class="k">Requirement</span><span class="v">{{ $brBank['requirement'] ?? ($case->product ?: '—') }}</span></div>
        </div>
        <div class="cw-reco-bank-kv">
            <span class="cw-reco-bank-kv-ico ico-person"><i class="bi bi-person-badge"></i></span>
            <div><span class="k">Banker</span><span class="v">{{ $brBank['banker_name'] ?? '—' }}</span></div>
        </div>
        <div class="cw-reco-bank-kv">
            <span class="cw-reco-bank-kv-ico ico-phone"><i class="bi bi-telephone"></i></span>
            <div><span class="k">Contact</span><span class="v">{{ $brBank['banker_contact'] ?? '—' }}</span></div>
        </div>
        <div class="cw-reco-bank-kv">
            <span class="cw-reco-bank-kv-ico ico-mail"><i class="bi bi-envelope"></i></span>
            <div><span class="k">Mail</span><span class="v">{{ $brBank['banker_mail'] ?? '—' }}</span></div>
        </div>
        <div class="cw-reco-bank-kv">
            <span class="cw-reco-bank-kv-ico ico-reco"><i class="bi bi-lightbulb"></i></span>
            <div><span class="k">Recommended By</span><span class="v">{{ $brBank['by'] ?? $brBank['recommended_by'] ?? '—' }}</span></div>
        </div>
        <div class="cw-reco-bank-kv">
            <span class="cw-reco-bank-kv-ico ico-approved"><i class="bi bi-person-check"></i></span>
            <div>
                <span class="k">Approved By</span>
                <span class="v">
                    {{ $brBank['approved_by'] ?? '—' }}
                    @if($approvedAtDisp)<span class="cw-reco-bank-meta">{{ $approvedAtDisp }}</span>@endif
                </span>
            </div>
        </div>
    </div>
</div>
@elseif(in_array($recoBankStatus, ['Draft', 'Submitted', 'Rejected', ''], true))
<div class="cw-reco-bank-gate {{ $recoBankStatus === 'Rejected' ? 'is-rejected' : ($recoBankStatus === 'Submitted' ? 'is-submitted' : 'is-draft') }}">
    <span class="cw-reco-bank-gate-ico" aria-hidden="true">
        <i class="bi {{ $recoBankStatus === 'Rejected' ? 'bi-x-octagon' : ($recoBankStatus === 'Submitted' ? 'bi-hourglass-split' : 'bi-info-circle') }}"></i>
    </span>
    <div>
        @if($recoBankStatus === 'Submitted')
            BM Recommendation is <strong>Submitted</strong> — waiting for Cluster / RD / MD approval before bank details appear here.
        @elseif($recoBankStatus === 'Rejected')
            BM Recommendation was <strong>Rejected</strong>. Revise and resubmit from the Recommendation tab.
        @else
            Approve the BM Recommendation first — suggested bank details will appear here after Cluster / RD / MD approval.
        @endif
    </div>
</div>
@endif

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
                <div class="sec-sub">Add Bank Login ID when the case is logged with the bank.</div>
            </div>
        </div>
    </div>
    <div class="cw-table-wrap">
        @if($case->bankApplications->isEmpty())
            <div class="cw-empty-inline">
                <span>No lender applications yet.</span>
            </div>
        @else
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
                            <th>Bank Login ID</th>
                            <th>Login Date</th>
                            <th>Status</th>
                            <th class="text-end">Requested</th>
                            <th>Follow-up</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($case->bankApplications as $ba)
                            @php
                                $baStatus = (string) ($ba->status ?? 'Bank Login');
                                $baStatusClass = match ($baStatus) {
                                    'Processing' => 'process',
                                    'Bank Query' => 'query',
                                    'Sanctioned' => 'ok',
                                    'Disbursed' => 'done',
                                    'Rejected' => 'bad',
                                    default => 'login',
                                };
                                $statusOpts = ['Bank Login', 'Processing', 'Sanctioned', 'Rejected'];
                                $canRaiseQuery = !in_array($baStatus, ['Rejected', 'Disbursed'], true);
                                $canRecordSanction = !in_array($baStatus, ['Rejected', 'Disbursed'], true);
                            @endphp
                            <tr>
                                <td data-label="Lender">
                                    <div class="cw-lender-name">{{ $ba->lender_name }}</div>
                                </td>
                                <td data-label="Bank Login ID">
                                    @if(filled($ba->application_ref))
                                        <span class="cw-login-id">{{ $ba->application_ref }}</span>
                                    @else
                                        <span class="cw-login-id is-missing">Not added</span>
                                    @endif
                                </td>
                                <td data-label="Login">{{ optional($ba->login_date)->format('d M Y') ?: '—' }}</td>
                                <td data-label="Status"><span class="status-pill {{ $baStatusClass }}">{{ $baStatus }}</span></td>
                                <td class="text-end cw-lender-amt" data-label="Requested">
                                    {{ $ba->requested_amount !== null ? '₹ '.number_format((float) $ba->requested_amount, 0) : '—' }}
                                </td>
                                <td data-label="Follow-up">{{ optional($ba->follow_up_at)->format('d M Y') ?: '—' }}</td>
                                <td data-label="Updated">{{ optional($ba->updated_at)->format('d M Y H:i') }}</td>
                                <td class="text-end cw-col-actions" data-label="Actions">
                                    @if($canEdit && $baStatus !== 'Disbursed')
                                        <div class="dropdown cw-lender-actions">
                                            <button type="button" class="btn-action" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end cw-lender-menu">
                                                <li>
                                                    <button type="button" class="dropdown-item"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#cwBankLoginIdModal"
                                                            data-login-id-action="{{ route('goal.bank-applications.login-id', $ba) }}"
                                                            data-login-id="{{ $ba->application_ref }}"
                                                            data-lender-name="{{ $ba->lender_name }}">
                                                        <i class="bi bi-fingerprint me-2"></i>{{ filled($ba->application_ref) ? 'Edit Bank Login ID' : 'Add Bank Login ID' }}
                                                    </button>
                                                </li>
                                                @if($canRaiseQuery)
                                                    <li>
                                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#cwAddQueryModal" data-app-id="{{ $ba->id }}">
                                                            <i class="bi bi-chat-dots me-2"></i>Raise Query
                                                        </button>
                                                    </li>
                                                @endif
                                                @if($canRecordSanction)
                                                    <li>
                                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#cwAddSanctionModal" data-app-id="{{ $ba->id }}">
                                                            <i class="bi bi-award me-2"></i>Record Sanction
                                                        </button>
                                                    </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li><h6 class="dropdown-header">Update status</h6></li>
                                                @foreach($statusOpts as $st)
                                                    @if($st === $baStatus)
                                                        <li>
                                                            <span class="dropdown-item disabled text-muted">
                                                                <i class="bi bi-check2 me-2"></i>Mark {{ $st }} <span class="small">(current)</span>
                                                            </span>
                                                        </li>
                                                    @else
                                                        <li>
                                                            <form method="POST" action="{{ route('goal.bank-applications.status', $ba) }}" class="js-cw-ajax-save" data-no-loader data-ajax-reload="1">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="status" value="{{ $st }}">
                                                                @if($st === 'Rejected')
                                                                    <input type="hidden" name="rejection_reason" value="">
                                                                    <button type="submit" class="dropdown-item text-danger"
                                                                        onclick="var r=prompt('Rejection reason'); if(!r){event.preventDefault();return false;} this.form.rejection_reason.value=r;">
                                                                        <i class="bi bi-x-circle me-2"></i>Mark {{ $st }}
                                                                    </button>
                                                                @else
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="bi bi-arrow-right-circle me-2"></i>Mark {{ $st }}
                                                                    </button>
                                                                @endif
                                                            </form>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    @elseif($canEdit)
                                        <span class="small text-muted">Closed</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
