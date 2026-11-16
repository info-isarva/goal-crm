@php
    $prefix = $prefix ?? 'add';
    $values = $values ?? [];
    $showErrors = $showErrors ?? false;
    $canApprove = $canApprove ?? false;
    $teamUsers = $teamUsers ?? collect();
    $v = fn ($key, $default = '') => $values[$key] ?? $default;
    $ownerType = $v('owner_type', 'employee');
@endphp

<div class="row g-3">
    <div class="col-12">
        <label class="form-label d-block">Target owner <span class="text-danger">*</span></label>
        <div class="d-flex flex-wrap gap-3">
            <div class="form-check">
                <input class="form-check-input owner-type-radio" type="radio" name="owner_type"
                       id="{{ $prefix }}_owner_employee" value="employee"
                       data-prefix="{{ $prefix }}"
                       @checked($ownerType === 'employee') required>
                <label class="form-check-label" for="{{ $prefix }}_owner_employee">Team member (employee)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input owner-type-radio" type="radio" name="owner_type"
                       id="{{ $prefix }}_owner_org" value="org_unit"
                       data-prefix="{{ $prefix }}"
                       @checked($ownerType === 'org_unit')>
                <label class="form-check-label" for="{{ $prefix }}_owner_org">Branch (org unit)</label>
            </div>
        </div>
        <div class="field-hint">Requirement: exactly one owner — employee <em>or</em> branch, not both.</div>
        @if($showErrors) @error('owner_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
    </div>

    <div class="col-md-4 owner-employee-wrap" data-prefix="{{ $prefix }}" style="{{ $ownerType === 'employee' ? '' : 'display:none' }}">
        <label class="form-label" for="{{ $prefix }}_user_id">Team member <span class="text-danger">*</span></label>
        <select class="form-select @if($showErrors && $errors->has('user_id')) is-invalid @endif"
                id="{{ $prefix }}_user_id" name="user_id">
            <option value="">Select person…</option>
            @foreach($teamUsers as $u)
                <option value="{{ $u->id }}" @selected((string) $v('user_id') === (string) $u->id)>
                    {{ $u->name }}{{ $u->job_role ? ' ('.strtoupper($u->job_role).')' : '' }}
                </option>
            @endforeach
        </select>
        @if($showErrors) @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>

    <div class="col-md-4 owner-org-wrap" data-prefix="{{ $prefix }}" style="{{ $ownerType === 'org_unit' ? '' : 'display:none' }}">
        <label class="form-label" for="{{ $prefix }}_goal_org_unit_id">Branch <span class="text-danger">*</span></label>
        <select class="form-select @if($showErrors && $errors->has('goal_org_unit_id')) is-invalid @endif"
                id="{{ $prefix }}_goal_org_unit_id" name="goal_org_unit_id">
            <option value="">Select branch…</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected((string) $v('goal_org_unit_id') === (string) $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
        @if($showErrors) @error('goal_org_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}_period">Period <span class="text-danger">*</span></label>
        <input type="month" class="form-control @if($showErrors && $errors->has('period')) is-invalid @endif"
               id="{{ $prefix }}_period" name="period" value="{{ $v('period') }}" required>
        <div class="field-hint">Format YYYY-MM</div>
        @if($showErrors) @error('period')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}_metric">Metric <span class="text-danger">*</span></label>
        <select class="form-select @if($showErrors && $errors->has('metric')) is-invalid @endif"
                id="{{ $prefix }}_metric" name="metric" required>
            <option value="revenue" @selected($v('metric') === 'revenue')>Revenue</option>
            <option value="disbursement" @selected($v('metric') === 'disbursement')>Disbursement</option>
        </select>
        @if($showErrors) @error('metric')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>

    <div class="col-md-3">
        <label class="form-label" for="{{ $prefix }}_base_target">Base Target <span class="text-danger">*</span></label>
        <input type="text" inputmode="decimal" data-decimals="2"
               class="form-control js-decimal @if($showErrors && $errors->has('base_target')) is-invalid @endif"
               id="{{ $prefix }}_base_target" name="base_target" value="{{ $v('base_target') }}" placeholder="0.00" required>
        @if($showErrors) @error('base_target')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $prefix }}_opening_shortfall">Opening Shortfall</label>
        <input type="text" inputmode="decimal" data-decimals="2"
               class="form-control js-decimal @if($showErrors && $errors->has('opening_shortfall')) is-invalid @endif"
               id="{{ $prefix }}_opening_shortfall" name="opening_shortfall" value="{{ $v('opening_shortfall', '0') }}" placeholder="0.00">
        <div class="field-hint">Auto-filled from prior closing SF when left 0 on create</div>
        @if($showErrors) @error('opening_shortfall')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $prefix }}_adjustments">Adjustments</label>
        <input type="text" inputmode="decimal" data-decimals="2"
               class="form-control js-decimal @if($showErrors && $errors->has('adjustments')) is-invalid @endif"
               id="{{ $prefix }}_adjustments" name="adjustments" value="{{ $v('adjustments', '0') }}" placeholder="0.00">
        @if($showErrors) @error('adjustments')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $prefix }}_actual">Actual</label>
        <input type="text" inputmode="decimal" data-decimals="2"
               class="form-control js-decimal @if($showErrors && $errors->has('actual')) is-invalid @endif"
               id="{{ $prefix }}_actual" name="actual" value="{{ $v('actual', '0') }}" placeholder="0.00">
        @if($showErrors) @error('actual')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}_status">Status</label>
        @if($canApprove)
            <select class="form-select @if($showErrors && $errors->has('status')) is-invalid @endif"
                    id="{{ $prefix }}_status" name="status">
                <option value="Draft" @selected($v('status', 'Draft') === 'Draft')>Draft</option>
                <option value="Approved" @selected($v('status') === 'Approved')>Approved</option>
            </select>
        @else
            <input type="hidden" name="status" value="Draft">
            <input type="text" class="form-control" id="{{ $prefix }}_status" value="Draft (pending approval)" disabled>
            <div class="field-hint">BM creates/revises as Draft. Cluster+ can approve.</div>
        @endif
        @if($showErrors) @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>
    <div class="col-md-8">
        <label class="form-label d-block">Live calculation</label>
        <div class="calc-box">
            <div>
                <div class="calc-label">Effective Target</div>
                <div class="calc-value" id="{{ $prefix }}_calc_effective">{{ config('app.currency_symbol', '₹') }} 0.00</div>
            </div>
            <div>
                <div class="calc-label">Closing Shortfall</div>
                <div class="calc-value" id="{{ $prefix }}_calc_closing">{{ config('app.currency_symbol', '₹') }} 0.00</div>
            </div>
        </div>
        <div class="field-hint mt-1">Effective = Base + Opening SF + Adjustments · Closing SF = max(0, Effective − Actual)</div>
    </div>
</div>
