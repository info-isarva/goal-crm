@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold">Access Control</h4>
                    <small class="text-muted">Super Admin: set user create limit and which modules Admins can assign to other users</small>
                </div>
                <a href="{{ route('settings.index') }}" class="btn btn-light btn-sm">&laquo; Back to Settings</a>
            </div>
        </div>

        <div class="card-body p-4">
            @if(session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 small">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('access.settings.update') }}">
                @csrf

                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="border rounded-3 p-3 mb-3">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-people me-1"></i> User create limit
                            </h6>
                            <div class="mb-3">
                                <label for="max_users" class="form-label">Maximum users</label>
                                <input type="number"
                                       min="1"
                                       class="form-control @error('max_users') is-invalid @enderror"
                                       id="max_users"
                                       name="max_users"
                                       value="{{ old('max_users', $maxUsers) }}"
                                       placeholder="Leave blank for unlimited">
                                @error('max_users')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Current users (excl. Super Admin): <strong>{{ $userCount }}</strong>
                                    @if($maxUsers)
                                        / {{ $maxUsers }}
                                    @else
                                        (no limit)
                                    @endif
                                </div>
                            </div>
                            <p class="small text-muted mb-0">
                                When the limit is reached, Admin/others cannot create new users until Super Admin raises it.
                            </p>
                        </div>

                        <div class="border rounded-3 p-3 mb-3">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-send me-1"></i> Message send access
                            </h6>
                            <p class="small text-muted mb-3">
                                Master switches for quotation Email / WhatsApp send. When off, only Super Admin can send.
                                Also tick the matching permissions below so Admins can assign them to users.
                            </p>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       id="email_send_enabled"
                                       name="email_send_enabled"
                                       value="1"
                                       {{ !empty($emailSendEnabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="email_send_enabled">
                                    <i class="bi bi-envelope me-1"></i> Enable Email Send
                                </label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       id="whatsapp_send_enabled"
                                       name="whatsapp_send_enabled"
                                       value="1"
                                       {{ !empty($whatsappSendEnabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="whatsapp_send_enabled">
                                    <i class="bi bi-whatsapp me-1"></i> Enable WhatsApp Send
                                </label>
                            </div>
                        </div>

                        <div class="border rounded-3 p-3">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-sliders me-1"></i> Module Access (Web Forms & Social Ads)
                            </h6>
                            <p class="small text-muted mb-3">
                                Master permission toggles. When enabled, they will show in the user access permission list and Admins can be assigned them. When disabled, they are hidden from the user access permission list and cannot be assigned to Admin or other users.
                            </p>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input module-master-switch"
                                       type="checkbox"
                                       role="switch"
                                       id="web_forms_enabled"
                                       name="web_forms_enabled"
                                       value="1"
                                       data-target-module="web_forms"
                                       {{ !empty($webFormsEnabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="web_forms_enabled">
                                    <i class="bi bi-ui-checks-grid me-1"></i> Enable Web Forms
                                </label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input module-master-switch"
                                       type="checkbox"
                                       role="switch"
                                       id="social_ads_enabled"
                                       name="social_ads_enabled"
                                       value="1"
                                       data-target-module="social_ads"
                                       {{ !empty($socialAdsEnabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="social_ads_enabled">
                                    <i class="bi bi-megaphone me-1"></i> Enable Social Ads
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="border rounded-3 p-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <h6 class="fw-semibold mb-0">
                                    <i class="bi bi-shield-check me-1"></i> Modules Admins can assign
                                </h6>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllPerms">Select all</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllPerms">Clear</button>
                                </div>
                            </div>
                            <p class="small text-muted mb-3">
                                Checked permissions appear on User Create/Edit when an Admin assigns access to other users.
                                Unchecked permissions stay Super Admin only.
                                Include <strong>Send Quotation Email</strong> and <strong>Send Quotation WhatsApp</strong> here if Admins should assign those to users.
                            </p>

                            @forelse($groupedPermissions as $parentName => $group)
                                @php
                                    $isWfGroup = \Illuminate\Support\Str::contains(strtolower($parentName), 'web form');
                                    $isSaGroup = \Illuminate\Support\Str::contains(strtolower($parentName), 'social ad');
                                    $moduleType = $isWfGroup ? 'web_forms' : ($isSaGroup ? 'social_ads' : '');
                                @endphp
                                <div class="mb-3 border rounded p-2 permission-module-card" @if($moduleType) data-module-type="{{ $moduleType }}" @endif>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong class="small">{{ $parentName }}</strong>
                                            @if($moduleType)
                                                <span class="badge bg-secondary ms-1 module-status-badge d-none">Disabled</span>
                                            @endif
                                        </div>
                                        <button type="button"
                                                class="btn btn-link btn-sm p-0 toggle-group"
                                                data-group="{{ \Illuminate\Support\Str::slug($parentName) }}">
                                            Toggle group
                                        </button>
                                    </div>
                                    <div class="row g-2" data-perm-group="{{ \Illuminate\Support\Str::slug($parentName) }}">
                                        @foreach($group as $permission)
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input assignable-perm"
                                                           type="checkbox"
                                                           name="admin_assignable_permissions[]"
                                                           value="{{ $permission->guard_name }}"
                                                           id="ap_{{ $permission->id }}"
                                                           {{ in_array($permission->guard_name, $selectedGuards, true) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="ap_{{ $permission->id }}">
                                                        {{ $permission->name }}
                                                        <span class="text-muted">({{ $permission->guard_name }})</span>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted">No permissions found. Create permissions first.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-custom">Save Access Settings</button>
                    <a href="{{ route('settings.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAllPerms');
    const clearAll = document.getElementById('clearAllPerms');
    if (selectAll) {
        selectAll.addEventListener('click', function () {
            document.querySelectorAll('.assignable-perm:not(:disabled)').forEach(function (el) { el.checked = true; });
        });
    }
    if (clearAll) {
        clearAll.addEventListener('click', function () {
            document.querySelectorAll('.assignable-perm:not(:disabled)').forEach(function (el) { el.checked = false; });
        });
    }
    document.querySelectorAll('.toggle-group').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const group = btn.getAttribute('data-group');
            const boxes = document.querySelectorAll('[data-perm-group="' + group + '"] .assignable-perm:not(:disabled)');
            if (!boxes.length) return;
            const allChecked = Array.from(boxes).every(function (el) { return el.checked; });
            boxes.forEach(function (el) { el.checked = !allChecked; });
        });
    });

    // Synchronize module master switches with module permission cards
    function syncModule(moduleType, isEnabled) {
        const card = document.querySelector('.permission-module-card[data-module-type="' + moduleType + '"]');
        if (!card) return;
        const boxes = card.querySelectorAll('.assignable-perm');
        const badge = card.querySelector('.module-status-badge');
        const toggleBtn = card.querySelector('.toggle-group');

        if (isEnabled) {
            card.classList.remove('opacity-50');
            if (badge) badge.classList.add('d-none');
            if (toggleBtn) toggleBtn.disabled = false;
            boxes.forEach(function (el) {
                el.disabled = false;
                el.checked = true;
            });
        } else {
            card.classList.add('opacity-50');
            if (badge) badge.classList.remove('d-none');
            if (toggleBtn) toggleBtn.disabled = true;
            boxes.forEach(function (el) {
                el.checked = false;
                el.disabled = true;
            });
        }
    }

    const wfSwitch = document.getElementById('web_forms_enabled');
    const saSwitch = document.getElementById('social_ads_enabled');

    if (wfSwitch) {
        wfSwitch.addEventListener('change', function () {
            syncModule('web_forms', this.checked);
        });
        // Initial state sync
        if (!wfSwitch.checked) {
            syncModule('web_forms', false);
        }
    }

    if (saSwitch) {
        saSwitch.addEventListener('change', function () {
            syncModule('social_ads', this.checked);
        });
        // Initial state sync
        if (!saSwitch.checked) {
            syncModule('social_ads', false);
        }
    }
});
</script>
@endsection
