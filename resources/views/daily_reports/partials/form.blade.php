@php
    $report = $report ?? null;
    $readOnly = $readOnly ?? false;
    $isEdit = $isEdit ?? false;
    $formAction = $formAction ?? route('daily_reports.store');
    $method = $method ?? 'POST';
    $oldOutreaches = old('outreaches');
    if ($oldOutreaches === null && $report) {
        $oldOutreaches = $report->outreaches->map(fn ($o) => [
            'outreach_type_id' => $o->outreach_type_id,
            'count' => $o->count,
        ])->values()->all();
    }
    if (empty($oldOutreaches)) {
        $oldOutreaches = [['outreach_type_id' => '', 'count' => 0]];
    }
@endphp

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Today's Activities</div>
            <div class="card-body p-0" style="max-height:280px;overflow:auto;">
                @forelse($todayActivities as $activity)
                    <div class="px-3 py-2 border-bottom">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="badge bg-light text-dark border">{{ $activity['type'] }}</span>
                            <small class="text-muted">{{ optional($activity['at'])->format('h:i A') }}</small>
                        </div>
                        <div class="fw-semibold small mt-1">{{ $activity['title'] }}</div>
                        @if(!empty($activity['related']))
                            <div class="text-muted small">{{ $activity['related'] }}</div>
                        @endif
                    </div>
                @empty
                    <div class="p-3 text-muted small">No activities created today.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Today's Leads</div>
            <div class="card-body p-0" style="max-height:280px;overflow:auto;">
                @forelse($todayLeads as $lead)
                    <div class="px-3 py-2 border-bottom">
                        <a href="{{ route('leads.show', $lead->id) }}" class="fw-semibold text-decoration-none">{{ $lead->title ?: ('Lead #'.$lead->id) }}</a>
                        <div class="text-muted small">{{ optional($lead->organization)->name }}</div>
                    </div>
                @empty
                    <div class="p-3 text-muted small">No leads created today.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Today's Deals</div>
            <div class="card-body p-0" style="max-height:280px;overflow:auto;">
                @forelse($todayDeals as $deal)
                    <div class="px-3 py-2 border-bottom">
                        <a href="{{ route('deals.show', $deal->id) }}" class="fw-semibold text-decoration-none">{{ $deal->title ?: ('Deal #'.$deal->id) }}</a>
                        <div class="text-muted small">{{ optional($deal->organization)->name }}</div>
                    </div>
                @empty
                    <div class="p-3 text-muted small">No deals created today.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ $formAction }}" id="dailyReportForm">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold">Daily Report — {{ $day->format('d M Y') }}</span>
            <input type="hidden" name="report_date" value="{{ old('report_date', $day->toDateString()) }}">
        </div>
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label fw-semibold">Other related activity</label>
                <div class="qt-rich-wrap">
                    <textarea name="other_activity" id="other_activity" class="form-control d-none"
                        placeholder="Enter other related activity...">{{ old('other_activity', optional($report)->other_activity) }}</textarea>
                </div>
            </div>

            <div class="mb-2 d-flex justify-content-between align-items-center">
                <label class="form-label fw-semibold mb-0">Outreach</label>
                @unless($readOnly)
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addOutreachRow">
                        <i class="bi bi-plus-lg"></i> Add outreach
                    </button>
                @endunless
            </div>
            <div id="outreachRows">
                @foreach($oldOutreaches as $idx => $row)
                    <div class="row g-2 align-items-end mb-2 outreach-row">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Type</label>
                            <select name="outreaches[{{ $idx }}][outreach_type_id]" class="form-select" {{ $readOnly ? 'disabled' : 'required' }}>
                                <option value="">Select outreach</option>
                                @foreach($outreachTypes as $type)
                                    <option value="{{ $type->id }}" @selected((string)($row['outreach_type_id'] ?? '') === (string)$type->id)>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Count</label>
                            <input type="number" min="0" max="99999" class="form-control"
                                name="outreaches[{{ $idx }}][count]"
                                value="{{ $row['count'] ?? 0 }}"
                                {{ $readOnly ? 'readonly' : 'required' }}>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            @unless($readOnly)
                                <button type="button" class="btn btn-outline-primary add-outreach-btn" title="Add row">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger remove-outreach-btn" title="Remove row">
                                    <i class="bi bi-dash-lg"></i>
                                </button>
                            @else
                                <span class="text-muted small align-self-center">
                                    {{ optional($outreachTypes->firstWhere('id', $row['outreach_type_id'] ?? null))->name }}
                                </span>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>
            @if($outreachTypes->isEmpty())
                <div class="alert alert-warning mt-2 mb-0">
                    No outreach types configured. Ask an admin to add them under Settings → Outreach Types.
                </div>
            @endif
        </div>
        @unless($readOnly)
            <div class="card-footer bg-white d-flex gap-2 justify-content-end">
                <a href="{{ route('daily_reports.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" name="action_type" value="draft" class="btn btn-outline-primary" @disabled($outreachTypes->isEmpty())>
                    <i class="bi bi-save me-1"></i> Save Draft
                </button>
                <button type="submit" name="action_type" value="submit" class="btn btn-custom" @disabled($outreachTypes->isEmpty())>
                    <i class="bi bi-send me-1"></i> Final Submit
                </button>
            </div>
        @endunless
    </div>
</form>

@unless($readOnly)
<template id="outreachRowTemplate">
    <div class="row g-2 align-items-end mb-2 outreach-row">
        <div class="col-md-6">
            <label class="form-label small mb-1">Type</label>
            <select name="outreaches[__INDEX__][outreach_type_id]" class="form-select" required>
                <option value="">Select outreach</option>
                @foreach($outreachTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Count</label>
            <input type="number" min="0" max="99999" class="form-control" name="outreaches[__INDEX__][count]" value="0" required>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="button" class="btn btn-outline-primary add-outreach-btn" title="Add row">
                <i class="bi bi-plus-lg"></i>
            </button>
            <button type="button" class="btn btn-outline-danger remove-outreach-btn" title="Remove row">
                <i class="bi bi-dash-lg"></i>
            </button>
        </div>
    </div>
</template>
<script>
(function () {
    var wrap = document.getElementById('outreachRows');
    var tpl = document.getElementById('outreachRowTemplate');
    if (!wrap || !tpl) return;
    var index = wrap.querySelectorAll('.outreach-row').length;

    function reindex() {
        wrap.querySelectorAll('.outreach-row').forEach(function (row, i) {
            row.querySelectorAll('select, input').forEach(function (el) {
                if (!el.name) return;
                el.name = el.name.replace(/outreaches\[\d+]/, 'outreaches[' + i + ']');
            });
        });
        index = wrap.querySelectorAll('.outreach-row').length;
    }

    function addRow() {
        var html = tpl.innerHTML.replace(/__INDEX__/g, String(index));
        wrap.insertAdjacentHTML('beforeend', html);
        index += 1;
        reindex();
    }

    document.getElementById('addOutreachRow')?.addEventListener('click', addRow);

    wrap.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.add-outreach-btn');
        if (addBtn) {
            e.preventDefault();
            addRow();
            return;
        }
        var removeBtn = e.target.closest('.remove-outreach-btn');
        if (removeBtn) {
            e.preventDefault();
            var rows = wrap.querySelectorAll('.outreach-row');
            if (rows.length <= 1) return;
            removeBtn.closest('.outreach-row')?.remove();
            reindex();
        }
    });
})();
</script>
@endunless

@include('partials.quill_editor_assets')
<script>
(function () {
    var readOnly = @json((bool) $readOnly);
    var initialHtml = @json(old('other_activity', optional($report)->other_activity ?? ''));

    function initOtherActivityEditor() {
        if (!window.CrmQuill) return;
        var quill = CrmQuill.create('other_activity', {
            sizeClass: 'crm-quill-medium',
            wrapClass: 'qt-rich-quill',
            html: initialHtml || '',
            placeholder: 'Enter other related activity...',
            menubar: false
        });
        if (quill && readOnly) {
            quill.enable(false);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOtherActivityEditor);
    } else {
        initOtherActivityEditor();
    }

    document.getElementById('dailyReportForm')?.addEventListener('submit', function () {
        if (window.CrmQuill) {
            CrmQuill.sync('other_activity');
        }
    });
})();
</script>
