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
        @if($case->bankApplications->isEmpty())
            <div class="cw-empty-inline">
                <span>No lender applications yet. Add one to proceed to Bank Login.</span>
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
                                <td data-label="App Ref">{{ $ba->application_ref ?: '—' }}</td>
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
