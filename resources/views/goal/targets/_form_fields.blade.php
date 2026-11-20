@php
    $prefix = $prefix ?? 'add';
    $values = $values ?? [];
    $showErrors = $showErrors ?? false;
    $canApprove = $canApprove ?? false;
    $teamUsers = $teamUsers ?? collect();
    $v = fn ($key, $default = '') => $values[$key] ?? $default;
    $ownerType = $v('owner_type', 'employee');
    $currency = config('app.currency_symbol', '₹');
@endphp

<div class="gt-form">
    <div class="section-bar"><i class="bi bi-person-badge"></i> Owner</div>
    <div class="gt-owner-toggle" role="radiogroup" aria-label="Target owner type">
        <label class="gt-owner-opt">
            <input class="owner-type-radio" type="radio" name="owner_type"
                   id="{{ $prefix }}_owner_employee" value="employee"
                   data-prefix="{{ $prefix }}"
                   @checked($ownerType === 'employee') required>
            <span><i class="bi bi-person"></i> Team member</span>
        </label>
        <label class="gt-owner-opt">
            <input class="owner-type-radio" type="radio" name="owner_type"
                   id="{{ $prefix }}_owner_org" value="org_unit"
                   data-prefix="{{ $prefix }}"
                   @checked($ownerType === 'org_unit')>
            <span><i class="bi bi-building"></i> Org unit</span>
        </label>
    </div>
    @if($showErrors) @error('owner_type')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror @endif

    <div class="row g-3 mb-3">
        <div class="col-12 owner-employee-wrap" data-prefix="{{ $prefix }}" style="{{ $ownerType === 'employee' ? '' : 'display:none' }}">
            <label class="form-label" for="{{ $prefix }}_user_id">Team member <span class="req">*</span></label>
            <select class="form-select @if($showErrors && $errors->has('user_id')) is-invalid @endif"
                    id="{{ $prefix }}_user_id" name="user_id"
                    data-search="true" data-allow-clear="true" data-placeholder="Select person…">
                <option value="">Select person…</option>
                @foreach($teamUsers as $u)
                    <option value="{{ $u->id }}" @selected((string) $v('user_id') === (string) $u->id)>
                        {{ $u->name }}{{ $u->job_role ? ' ('.strtoupper($u->job_role).')' : '' }}
                    </option>
                @endforeach
            </select>
            @if($showErrors) @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>

        <div class="col-12 owner-org-wrap" data-prefix="{{ $prefix }}" style="{{ $ownerType === 'org_unit' ? '' : 'display:none' }}">
            <label class="form-label" for="{{ $prefix }}_goal_org_unit_id">Org unit <span class="req">*</span></label>
            <select class="form-select @if($showErrors && $errors->has('goal_org_unit_id')) is-invalid @endif"
                    id="{{ $prefix }}_goal_org_unit_id" name="goal_org_unit_id"
                    data-search="true" data-allow-clear="true" data-placeholder="Select region / cluster / branch…">
                <option value="">Select region / cluster / branch…</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" @selected((string) $v('goal_org_unit_id') === (string) $b->id)>
                        {{ strtoupper($b->type) }} · {{ $b->name }}
                    </option>
                @endforeach
            </select>
            @if($showErrors) @error('goal_org_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
    </div>

    <div class="section-bar"><i class="bi bi-calendar3"></i> Period & metric</div>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label" for="{{ $prefix }}_period">Period <span class="req">*</span></label>
            <input type="text" class="form-control js-monthpicker @if($showErrors && $errors->has('period')) is-invalid @endif"
                   id="{{ $prefix }}_period" name="period" value="{{ $v('period') }}"
                   placeholder="Select month" autocomplete="off" required>
            @if($showErrors) @error('period')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
        <div class="col-md-4">
            <label class="form-label" for="{{ $prefix }}_metric">Metric <span class="req">*</span></label>
            <select class="form-select @if($showErrors && $errors->has('metric')) is-invalid @endif"
                    id="{{ $prefix }}_metric" name="metric" required
                    data-search="false" data-allow-clear="false" data-placeholder="Select metric">
                <option value="revenue" @selected($v('metric') === 'revenue')>Revenue</option>
                <option value="disbursement" @selected($v('metric') === 'disbursement')>Disbursement</option>
            </select>
            @if($showErrors) @error('metric')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
        <div class="col-md-4">
            <label class="form-label" for="{{ $prefix }}_status">Status</label>
            @if($canApprove)
                <select class="form-select @if($showErrors && $errors->has('status')) is-invalid @endif"
                        id="{{ $prefix }}_status" name="status"
                        data-search="false" data-allow-clear="false">
                    <option value="Draft" @selected($v('status', 'Draft') === 'Draft')>Draft</option>
                    <option value="Approved" @selected($v('status') === 'Approved')>Approved</option>
                </select>
            @else
                <input type="hidden" name="status" value="Draft">
                <input type="text" class="form-control" id="{{ $prefix }}_status" value="Draft" disabled
                       title="BM creates as Draft. Cluster+ can approve.">
            @endif
            @if($showErrors) @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
    </div>

    <div class="section-bar"><i class="bi bi-currency-rupee"></i> Amounts</div>
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label" for="{{ $prefix }}_base_target">Base Target <span class="req">*</span></label>
            <div class="input-icon">
                <span class="rupee">{{ $currency }}</span>
                <input type="text" inputmode="decimal" data-decimals="2"
                       class="form-control js-decimal @if($showErrors && $errors->has('base_target')) is-invalid @endif"
                       id="{{ $prefix }}_base_target" name="base_target" value="{{ $v('base_target') }}" placeholder="0.00" required>
            </div>
            @if($showErrors) @error('base_target')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
        </div>
        <div class="col-md-3">
            <label class="form-label" for="{{ $prefix }}_opening_shortfall">
                Opening Shortfall
                <i class="bi bi-info-circle gt-tip" title="Auto-filled from prior closing shortfall when left 0 on create"></i>
            </label>
            <div class="input-icon">
                <span class="rupee">{{ $currency }}</span>
                <input type="text" inputmode="decimal" data-decimals="2"
                       class="form-control js-decimal @if($showErrors && $errors->has('opening_shortfall')) is-invalid @endif"
                       id="{{ $prefix }}_opening_shortfall" name="opening_shortfall" value="{{ $v('opening_shortfall', '0') }}" placeholder="0.00">
            </div>
            @if($showErrors) @error('opening_shortfall')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
        </div>
        <div class="col-md-3">
            <label class="form-label" for="{{ $prefix }}_adjustments">Adjustments</label>
            <div class="input-icon">
                <span class="rupee">{{ $currency }}</span>
                <input type="text" inputmode="decimal" data-decimals="2"
                       class="form-control js-decimal @if($showErrors && $errors->has('adjustments')) is-invalid @endif"
                       id="{{ $prefix }}_adjustments" name="adjustments" value="{{ $v('adjustments', '0') }}" placeholder="0.00">
            </div>
            @if($showErrors) @error('adjustments')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
        </div>
        <div class="col-md-3">
            <label class="form-label" for="{{ $prefix }}_actual">Actual</label>
            <div class="input-icon">
                <span class="rupee">{{ $currency }}</span>
                <input type="text" inputmode="decimal" data-decimals="2"
                       class="form-control js-decimal @if($showErrors && $errors->has('actual')) is-invalid @endif"
                       id="{{ $prefix }}_actual" name="actual" value="{{ $v('actual', '0') }}" placeholder="0.00">
            </div>
            @if($showErrors) @error('actual')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
        </div>
    </div>

    <div class="section-bar"><i class="bi bi-calculator"></i> Live calculation</div>
    <div class="calc-box" title="Effective = Base + Opening SF + Adjustments · Closing SF = max(0, Effective − Actual)">
        <div class="calc-item">
            <div class="calc-label">Effective Target</div>
            <div class="calc-value" id="{{ $prefix }}_calc_effective">{{ $currency }} 0.00</div>
        </div>
        <div class="calc-item">
            <div class="calc-label">Closing Shortfall</div>
            <div class="calc-value is-sf" id="{{ $prefix }}_calc_closing">{{ $currency }} 0.00</div>
        </div>
    </div>
</div>
