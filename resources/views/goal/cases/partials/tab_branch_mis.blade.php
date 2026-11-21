@php
    $mis = old('branch_mis', $case->branch_mis ?? []) ?: [];
    $ro = $canEdit ? '' : 'readonly';
    $dis = $canEdit ? '' : 'disabled';
    $pct = fn ($k) => $mis[$k] ?? '';

    $toYmd = function ($v) {
        if ($v === null || $v === '') {
            return '';
        }
        $v = trim((string) $v);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v;
        }
        try {
            return \Illuminate\Support\Carbon::parse(str_replace('.', '-', $v))->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    };
    $dispDate = function ($v) {
        $ymd = null;
        if ($v instanceof \DateTimeInterface) {
            return $v->format('d-m-Y');
        }
        if ($v === null || $v === '') {
            return '';
        }
        try {
            return \Illuminate\Support\Carbon::parse(str_replace('.', '-', (string) $v))->format('d-m-Y');
        } catch (\Throwable $e) {
            return (string) $v;
        }
    };
    $dispAmt = function ($v) {
        if ($v === null || $v === '') {
            return '';
        }
        if (! is_numeric($v)) {
            return (string) $v;
        }

        return number_format((float) $v, 2, '.', '');
    };

    $loginDate = $toYmd($mis['login_date'] ?? null);
    $dobApplicant = $toYmd($mis['dob_applicant'] ?? null);
    $sancDateDisp = $case->sanction_date
        ? $case->sanction_date->format('d-m-Y')
        : $dispDate($mis['sanction_date'] ?? $mis['sanction_dt'] ?? null);
    $disbDateDisp = $dispDate($mis['disb_date'] ?? null);
    $sancAmtDisp = $dispAmt($case->sanction_amount ?? ($mis['sanction_amount'] ?? $mis['sanc_amt'] ?? null));
    $disbAmtDisp = $dispAmt($mis['disb_amount'] ?? $mis['disb_amt'] ?? null);
    $loginAmtDisp = $dispAmt($mis['login_amount'] ?? null);

    $monthOptions = [];
    $cursor = now()->startOfMonth();
    for ($i = 0; $i < 18; $i++) {
        $monthOptions[] = $cursor->copy()->subMonths($i)->format('M-Y');
    }
    $currentMonth = $mis['month'] ?? '';
    $currentPayoutMonth = $mis['payout_month'] ?? '';
    if ($currentMonth !== '' && ! in_array($currentMonth, $monthOptions, true)) {
        array_unshift($monthOptions, $currentMonth);
    }
    if ($currentPayoutMonth !== '' && ! in_array($currentPayoutMonth, $monthOptions, true)) {
        array_unshift($monthOptions, $currentPayoutMonth);
    }
@endphp

<style>
.cw-page .mis-tab .mis-field-ico{
    position:relative;
}
.cw-page .mis-tab .mis-field-ico > i{
    position:absolute;left:12px;top:50%;transform:translateY(-50%);
    color:#64748b;font-size:.9rem;pointer-events:none;z-index:2;
}
.cw-page .mis-tab .mis-field-ico .form-control,
.cw-page .mis-tab .mis-field-ico .flatpickr-alt{
    padding-left:36px !important;
}
.cw-page .mis-tab .mis-comm-card{
    background:linear-gradient(180deg,#f8fafc 0%,#fff 48%);
    border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;margin:0 16px 14px;
}
.cw-page .mis-tab .mis-net{
    background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;padding:10px 12px;
}
.cw-page .mis-tab .mis-net .form-label{color:#047857}
.cw-page .mis-tab .mis-net .form-control[readonly]{
    background:#fff !important;color:#047857;font-weight:700;border-color:#6ee7b7 !important;
}
.cw-page .mis-tab .form-control:not(.form-control-sm),
.cw-page .mis-tab .form-select:not(.form-select-sm){
    min-height:40px;border-radius:10px;
}
.cw-page .mis-tab .mis-readonly-hint{
    font-size:.68rem;font-weight:600;color:#94a3b8;margin-top:4px;
}
</style>

<form method="POST" action="{{ route('goal.cases.workspace', $case) }}" id="cwBranchMisForm" class="mis-tab js-cw-ajax-save" data-no-loader novalidate>
    @csrf
    <input type="hidden" name="cw_tab" value="mis" data-cw-fixed-tab="1">
    <input type="hidden" name="customer_name" value="{{ old('customer_name', $case->customer_name) }}">
    <input type="hidden" name="mobile" value="{{ old('mobile', $case->mobile) }}">

    <div class="sec-card">
        <div class="sec-head has-line">
            <div class="sec-head-left">
                <span class="sec-ico ico-amber"><i class="bi bi-clipboard-data"></i></span>
                <div>
                    <h6>Branch MIS</h6>
                    <div class="sec-sub">Case-detail branch updation</div>
                </div>
            </div>
            <a href="{{ route('goal.branch-mis') }}" class="btn-ghost"><i class="bi bi-grid"></i> MIS grid</a>
        </div>

        {{-- Case identity --}}
        <div class="ws-section-title">Case identity</div>
        <div class="sec-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Ref Number</label>
                    <input class="form-control" value="{{ $case->case_no }}" readonly tabindex="-1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Customer Name</label>
                    <input class="form-control" value="{{ $case->customer_name }}" readonly tabindex="-1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Customer Contact</label>
                    <input class="form-control" value="{{ $case->mobile }}" readonly tabindex="-1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Location / Branch</label>
                    <input class="form-control" value="{{ $case->orgUnit?->name }}" readonly tabindex="-1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Product</label>
                    <input class="form-control" value="{{ $case->product }}" readonly tabindex="-1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <input class="form-control" value="{{ $case->status }}" readonly tabindex="-1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">LAN Number <span class="text-danger">*</span></label>
                    <input class="form-control" name="branch_mis[lan_number]" value="{{ $mis['lan_number'] ?? $case->application_ref }}" {{ $ro }} placeholder="Lender LAN / app no" @if($canEdit) required @endif>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Code</label>
                    <input class="form-control" name="branch_mis[code]" value="{{ $mis['code'] ?? '' }}" {{ $ro }} placeholder="Internal code">
                </div>
            </div>
        </div>

        {{-- Login / Sanction / Disb --}}
        <div class="ws-section-title">Login · Sanction · Disbursement</div>
        <div class="sec-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Login Date</label>
                    <div class="mis-field-ico">
                        <i class="bi bi-calendar3"></i>
                        <input type="text"
                               class="form-control js-datepicker"
                               name="branch_mis[login_date]"
                               value="{{ $loginDate }}"
                               placeholder="dd-mm-yyyy"
                               autocomplete="off"
                               {{ $ro }}
                               @if(! $canEdit) disabled @endif>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Login Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input class="form-control js-mis-num" name="branch_mis[login_amount]" value="{{ $loginAmtDisp }}" {{ $ro }} inputmode="decimal" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sanction Dt</label>
                    <div class="mis-field-ico">
                        <i class="bi bi-calendar3"></i>
                        <input class="form-control" value="{{ $sancDateDisp }}" readonly tabindex="-1" placeholder="—">
                    </div>
                    <div class="mis-readonly-hint">From sanction</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sanc Amt</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input class="form-control" value="{{ $sancAmtDisp }}" readonly tabindex="-1" placeholder="—">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Disb Date</label>
                    <div class="mis-field-ico">
                        <i class="bi bi-calendar3"></i>
                        <input class="form-control" id="mis_disb_date" value="{{ $disbDateDisp }}" readonly tabindex="-1" placeholder="—">
                    </div>
                    <div class="mis-readonly-hint">From verified disbursement</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Disb amt</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input class="form-control" id="mis_disb_amount" name="branch_mis[disb_amount]" value="{{ $disbAmtDisp }}" readonly tabindex="-1" placeholder="—">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Month</label>
                    <select class="form-select" name="branch_mis[month]" {{ $dis }}>
                        <option value="">Select month</option>
                        @foreach($monthOptions as $mo)
                            <option value="{{ $mo }}" @selected($currentMonth === $mo)>{{ $mo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sub Product</label>
                    <input class="form-control" name="branch_mis[sub_product]" value="{{ $mis['sub_product'] ?? '' }}" {{ $ro }} placeholder="e.g. HL / LAP">
                </div>
                <div class="col-md-3">
                    <label class="form-label">OTC / PDD Status</label>
                    <select class="form-select" name="branch_mis[otc_pdd]" {{ $dis }}>
                        @foreach(['' => 'Select status', 'OTC Cleared' => 'OTC Cleared', 'Pending PDD' => 'Pending PDD', 'PDD Cleared' => 'PDD Cleared'] as $val => $label)
                            <option value="{{ $val }}" @selected(($mis['otc_pdd'] ?? $mis['otc_pdd_status'] ?? '') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Source · Banker --}}
        <div class="ws-section-title">Source · Banker</div>
        <div class="sec-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Source</label>
                    <input class="form-control" name="branch_mis[source]" value="{{ $mis['source'] ?? '' }}" {{ $ro }} placeholder="VRM / Channel / Referral">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Source Name</label>
                    <input class="form-control" name="branch_mis[source_name]" value="{{ $mis['source_name'] ?? '' }}" {{ $ro }} placeholder="Partner / referrer name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">GSM Name</label>
                    <input class="form-control" name="branch_mis[gsm_name]" value="{{ $mis['gsm_name'] ?? $case->owner?->name }}" {{ $ro }}>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bank Name</label>
                    <input class="form-control" value="{{ $case->lender_name ?: ($mis['bank_name'] ?? '') }}" readonly tabindex="-1">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Banker Name</label>
                    <input class="form-control" name="branch_mis[banker_name]" value="{{ $mis['banker_name'] ?? '' }}" {{ $ro }} placeholder="Bank contact name">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Banker Contact</label>
                    <input class="form-control" name="branch_mis[banker_contact]" value="{{ $mis['banker_contact'] ?? '' }}" {{ $ro }} placeholder="Mobile" inputmode="tel">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Banker Mail ID</label>
                    <input type="email" class="form-control" name="branch_mis[banker_mail]" value="{{ $mis['banker_mail'] ?? $mis['banker_email'] ?? '' }}" {{ $ro }} placeholder="name@bank.com">
                </div>
            </div>
        </div>

        {{-- Commercials --}}
        <div class="ws-section-title">Commercials</div>
        <div class="mis-comm-card">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">PayIn %</label>
                    <input class="form-control js-mis-rate" id="mis_payin_pct" name="branch_mis[payin_pct]" value="{{ $pct('payin_pct') }}" {{ $ro }} inputmode="decimal" placeholder="0.00">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payin In Amt</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input class="form-control" id="mis_payin_amt" name="branch_mis[payin_amt]" value="{{ $dispAmt($pct('payin_amt')) }}" readonly tabindex="-1">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payout %</label>
                    <input class="form-control js-mis-rate" id="mis_payout_pct" name="branch_mis[payout_pct]" value="{{ $pct('payout_pct') }}" {{ $ro }} inputmode="decimal" placeholder="0.00">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payout Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input class="form-control" id="mis_payout_amount" name="branch_mis[payout_amount]" value="{{ $dispAmt($pct('payout_amount')) }}" readonly tabindex="-1">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mis-net">
                        <label class="form-label mb-1">Net Revenue</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input class="form-control" id="mis_net_revenue" name="branch_mis[net_revenue]" value="{{ $dispAmt($pct('net_revenue')) }}" readonly tabindex="-1">
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payout Month</label>
                    <select class="form-select" name="branch_mis[payout_month]" {{ $dis }}>
                        <option value="">Select month</option>
                        @foreach($monthOptions as $mo)
                            <option value="{{ $mo }}" @selected($currentPayoutMonth === $mo)>{{ $mo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">SM Remark</label>
                    <input class="form-control" name="branch_mis[sm_remark]" value="{{ $mis['sm_remark'] ?? '' }}" {{ $ro }} placeholder="Optional note for branch MIS">
                </div>
            </div>
        </div>

        {{-- References --}}
        <div class="ws-section-title">References · Applicant</div>
        <div class="sec-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Ref - 1 Name</label>
                    <input class="form-control" name="branch_mis[ref1_name]" value="{{ $mis['ref1_name'] ?? '' }}" {{ $ro }}>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ref - 1 Contact</label>
                    <input class="form-control" name="branch_mis[ref1_contact]" value="{{ $mis['ref1_contact'] ?? '' }}" {{ $ro }} inputmode="tel">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ref - 2 Name</label>
                    <input class="form-control" name="branch_mis[ref2_name]" value="{{ $mis['ref2_name'] ?? '' }}" {{ $ro }}>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ref - 2 Contact</label>
                    <input class="form-control" name="branch_mis[ref2_contact]" value="{{ $mis['ref2_contact'] ?? '' }}" {{ $ro }} inputmode="tel">
                </div>
                <div class="col-md-3">
                    <label class="form-label">DOB Applicant</label>
                    <div class="mis-field-ico">
                        <i class="bi bi-calendar3"></i>
                        <input type="text"
                               class="form-control js-datepicker"
                               name="branch_mis[dob_applicant]"
                               value="{{ $dobApplicant }}"
                               placeholder="dd-mm-yyyy"
                               autocomplete="off"
                               {{ $ro }}
                               @if(! $canEdit) disabled @endif>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Co-Applicant</label>
                    <input class="form-control" name="branch_mis[co_applicant]" value="{{ $mis['co_applicant'] ?? '' }}" {{ $ro }} placeholder="Name">
                </div>
            </div>
        </div>
    </div>

    @if($canEdit)
    <div class="cw-footer">
        <div class="hint mb-0">Pay-in, payout and net update when you change rates or when disbursement is verified.</div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('goal.cases.show', ['case' => $case, 'tab' => 'overview']) }}" class="btn btn-outline-secondary" style="height:42px;border-radius:10px;display:inline-flex;align-items:center;">Cancel</a>
            <button type="submit" class="btn-save"><i class="bi bi-check2"></i> Save Branch MIS</button>
        </div>
    </div>
    @endif
</form>

@once
@push('scripts')
<script>
(function () {
    function num(v) {
        if (v === null || v === undefined) return null;
        var s = String(v).replace(/[^\d.\-]/g, '');
        if (!s || s === '.' || s === '-') return null;
        var n = parseFloat(s);
        return isNaN(n) ? null : n;
    }
    function money(n) {
        if (n === null || n === undefined || isNaN(n)) return '';
        return (Math.round(n * 100) / 100).toFixed(2);
    }
    function recalcMis() {
        var disb = num(document.getElementById('mis_disb_amount') && document.getElementById('mis_disb_amount').value);
        var payinPct = num(document.getElementById('mis_payin_pct') && document.getElementById('mis_payin_pct').value);
        var payoutPct = num(document.getElementById('mis_payout_pct') && document.getElementById('mis_payout_pct').value);
        var payin = (disb !== null && payinPct !== null) ? disb * (payinPct / 100) : null;
        var payout = (disb !== null && payoutPct !== null) ? disb * (payoutPct / 100) : null;
        var net = (payin !== null && payout !== null) ? payin - payout : null;
        var a = document.getElementById('mis_payin_amt');
        var b = document.getElementById('mis_payout_amount');
        var c = document.getElementById('mis_net_revenue');
        if (a) a.value = money(payin);
        if (b) b.value = money(payout);
        if (c) c.value = money(net);
    }
    document.querySelectorAll('#cwBranchMisForm .js-mis-rate').forEach(function (el) {
        el.addEventListener('input', recalcMis);
        el.addEventListener('change', recalcMis);
    });
    if (window.IsarvaDatepicker && typeof window.IsarvaDatepicker.init === 'function') {
        window.IsarvaDatepicker.init(document.getElementById('cwBranchMisForm'));
    }
    recalcMis();
})();
</script>
@endpush
@endonce
