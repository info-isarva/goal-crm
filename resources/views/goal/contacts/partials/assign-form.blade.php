@php
    $showAllMethods = (bool) ($showAllMethods ?? false);
    $defaultMethod = $defaultMethod ?? ($showAllMethods ? 'branch' : 'vrm');
    $validCount = (int) ($validCount ?? 0);
    $embedInNew = (bool) ($embedInNew ?? false);
    $formId = $formId ?? null;
    $formAction = $formAction ?? null;
    $submitLabel = $submitLabel ?? null;
    $uid = $embedInNew ? 'new' : 'asg';
@endphp

@if(!$embedInNew)
<form method="POST" action="{{ $formAction }}" id="{{ $formId }}">
@csrf
@endif

<input type="hidden" name="method" id="{{ $uid }}Method" value="{{ $defaultMethod }}">
<input type="hidden" name="allocation" id="{{ $uid }}Allocation" value="all">

<div class="cg-assign-tabs" role="tablist">
    @if($showAllMethods)
    <label class="{{ $defaultMethod === 'cluster' ? 'on' : '' }}" data-method="cluster">
        <input type="radio" name="{{ $uid }}_method_ui" value="cluster" @checked($defaultMethod === 'cluster')> Cluster
    </label>
    <label class="{{ $defaultMethod === 'branch' ? 'on' : '' }}" data-method="branch">
        <input type="radio" name="{{ $uid }}_method_ui" value="branch" @checked($defaultMethod === 'branch')> Branch (BM)
    </label>
    @endif
    <label class="{{ $defaultMethod === 'vrm' ? 'on' : '' }}" data-method="vrm">
        <input type="radio" name="{{ $uid }}_method_ui" value="vrm" @checked($defaultMethod === 'vrm')> VRM
    </label>
    <label class="{{ $defaultMethod === 'custom' ? 'on' : '' }}" data-method="custom">
        <input type="radio" name="{{ $uid }}_method_ui" value="custom" @checked($defaultMethod === 'custom')> Split VRMs
    </label>
</div>

@if($showAllMethods)
<div class="cg-row2" data-fields="org">
    <div class="cg-field" data-show="region">
        <label>Region</label>
        <select name="region_id" id="{{ $uid }}Region">
            <option value="">— Select region —</option>
            @foreach(($orgOptions['regions'] ?? collect()) as $r)
                <option value="{{ $r->id }}">{{ $r->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="cg-field" data-show="cluster" id="{{ $uid }}ClusterField">
        <label>Cluster <span class="req" id="{{ $uid }}ClusterReq">*</span></label>
        <select name="cluster_id" id="{{ $uid }}Cluster">
            <option value="">— Select cluster —</option>
            @foreach(($orgOptions['clusters'] ?? $clusters ?? collect()) as $c)
                <option value="{{ $c->id }}" data-parent="{{ (int)($c->parent_id ?? 0) }}">{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="cg-field" data-show="branch" id="{{ $uid }}BranchField">
    <label>Branch <span class="req" id="{{ $uid }}BranchReq">*</span></label>
    <select name="branch_id" id="{{ $uid }}Branch">
        <option value="">— Select branch —</option>
        @foreach(($orgOptions['branches'] ?? $branches ?? collect()) as $b)
            <option value="{{ $b->id }}" data-parent="{{ (int)($b->parent_id ?? 0) }}">{{ $b->name }}</option>
        @endforeach
    </select>
</div>
@endif

<div class="cg-field" data-show="vrm" id="{{ $uid }}VrmField" @if($defaultMethod !== 'vrm') style="display:none" @endif>
    <label>VRM <span class="req">*</span></label>
    <select name="vrm_id" id="{{ $uid }}Vrm">
        <option value="">— Select VRM —</option>
        @foreach($vrms as $vrm)
            <option value="{{ $vrm->id }}">{{ $vrm->name }}</option>
        @endforeach
    </select>
</div>

<div class="cg-alloc">
    <label><input type="radio" name="{{ $uid }}_alloc_ui" value="all" checked> All Contacts</label>
    <label><input type="radio" name="{{ $uid }}_alloc_ui" value="equal"> Equal Split</label>
    <label><input type="radio" name="{{ $uid }}_alloc_ui" value="percentage"> By %</label>
    <label><input type="radio" name="{{ $uid }}_alloc_ui" value="counts"> Custom count</label>
</div>
<p style="margin:0 0 4px;font-size:.62rem;color:#94a3b8;line-height:1.25" id="{{ $uid }}AllocHint">Split across clusters, BMs, or VRMs.</p>

@if($showAllMethods)
<div class="cg-split" id="{{ $uid }}SplitClusters" data-panel="cluster">
    <div class="cg-split-head"><span>Select Clusters</span><span class="js-head-right"></span></div>
    <div class="cg-split-list">
        @forelse(($orgOptions['clusters'] ?? $clusters ?? collect()) as $c)
            <label class="cg-split-row">
                <input type="checkbox" name="cluster_ids[]" value="{{ $c->id }}" class="js-target" disabled>
                <span class="nm">{{ $c->name }}</span>
                <input type="number" min="0" max="100" class="pct hidden js-pct" name="percentages[{{ $c->id }}]" placeholder="%" disabled>
                <input type="number" min="0" class="cnt hidden js-cnt" name="counts[{{ $c->id }}]" placeholder="Qty" data-valid-total="{{ $validCount }}" disabled>
            </label>
        @empty
            <div class="cg-split-row">No clusters in scope.</div>
        @endforelse
    </div>
</div>
<div class="cg-split" id="{{ $uid }}SplitBranches" data-panel="branch">
    <div class="cg-split-head"><span>Select Branches (BM)</span><span class="js-head-right"></span></div>
    <div class="cg-split-list">
        @forelse(($orgOptions['branches'] ?? $branches ?? collect()) as $b)
            <label class="cg-split-row">
                <input type="checkbox" name="branch_ids[]" value="{{ $b->id }}" class="js-target" disabled>
                <span class="nm">{{ $b->name }}</span>
                <input type="number" min="0" max="100" class="pct hidden js-pct" name="percentages[{{ $b->id }}]" placeholder="%" disabled>
                <input type="number" min="0" class="cnt hidden js-cnt" name="counts[{{ $b->id }}]" placeholder="Qty" data-valid-total="{{ $validCount }}" disabled>
            </label>
        @empty
            <div class="cg-split-row">No branches in scope.</div>
        @endforelse
    </div>
</div>
@endif

<div class="cg-split {{ $defaultMethod === 'custom' ? 'show' : '' }}" id="{{ $uid }}SplitVrms" data-panel="vrm">
    <div class="cg-split-head"><span>Select VRMs</span><span class="js-head-right"></span></div>
    <div class="cg-split-list">
        @forelse($vrms as $vrm)
            <label class="cg-split-row">
                <input type="checkbox" name="vrm_ids[]" value="{{ $vrm->id }}" class="js-target" @disabled($defaultMethod !== 'custom')>
                <span class="nm">{{ $vrm->name }}</span>
                <input type="number" min="0" max="100" class="pct hidden js-pct" name="percentages[{{ $vrm->id }}]" placeholder="%" disabled>
                <input type="number" min="0" class="cnt hidden js-cnt" name="counts[{{ $vrm->id }}]" placeholder="Qty" data-valid-total="{{ $validCount }}" disabled>
            </label>
        @empty
            <div class="cg-split-row">No VRMs in scope.</div>
        @endforelse
    </div>
</div>

<div class="cg-meter d-none" id="{{ $uid }}Meter" data-total="{{ $validCount }}" style="display:none">
    <span>Allocated: <strong class="js-alloc">0</strong> / <span class="js-total">{{ $validCount }}</span></span>
    <span class="js-hint">Must total exactly</span>
</div>

@if(!$embedInNew && $submitLabel)
<div class="cg-actions" style="padding-top:14px;margin:0">
    <button type="submit" class="cg-btn cg-btn-primary"><i class="bi bi-people-fill"></i> {{ $submitLabel }}</button>
</div>
</form>
@endif

@once
@push('scripts')
<script>
window.cgAssignBoot = window.cgAssignBoot || [];
window.cgAssignBoot.push(@json($uid));
document.addEventListener('DOMContentLoaded', function () {
    (window.cgAssignBoot || []).forEach(function (uid) {
        if (window['cgAssignInit_' + uid]) return;
        window['cgAssignInit_' + uid] = true;
        initAssign(uid);
    });

    function initAssign(uid) {
        var methodInput = document.getElementById(uid + 'Method');
        var allocInput = document.getElementById(uid + 'Allocation');
        if (!methodInput || !allocInput) return;

        var regionSel = document.getElementById(uid + 'Region');
        var clusterSel = document.getElementById(uid + 'Cluster');
        var branchSel = document.getElementById(uid + 'Branch');
        var vrmSel = document.getElementById(uid + 'Vrm');
        var clusterField = document.getElementById(uid + 'ClusterField');
        var branchField = document.getElementById(uid + 'BranchField');
        var vrmField = document.getElementById(uid + 'VrmField');
        var clusterReq = document.getElementById(uid + 'ClusterReq');
        var branchReq = document.getElementById(uid + 'BranchReq');
        var splitClusters = document.getElementById(uid + 'SplitClusters');
        var splitBranches = document.getElementById(uid + 'SplitBranches');
        var splitVrms = document.getElementById(uid + 'SplitVrms');
        var meter = document.getElementById(uid + 'Meter');
        var hint = document.getElementById(uid + 'AllocHint');

        function isSplit(a) { return a === 'equal' || a === 'percentage' || a === 'counts'; }

        function activePanel() {
            var m = methodInput.value, a = allocInput.value;
            if (m === 'custom' || (m === 'vrm' && isSplit(a))) return 'vrm';
            if (m === 'cluster' && isSplit(a)) return 'cluster';
            if (m === 'branch' && isSplit(a)) return 'branch';
            return null;
        }

        function setPanel(panel, on) {
            if (!panel) return;
            panel.classList.toggle('show', !!on);
            panel.querySelectorAll('input').forEach(function (inp) { inp.disabled = !on; });
        }

        function syncCascade() {
            if (!regionSel || !clusterSel || !branchSel) return;
            var region = regionSel.value;
            Array.prototype.forEach.call(clusterSel.options, function (opt) {
                if (!opt.value) return;
                var p = opt.getAttribute('data-parent') || '0';
                var show = !region || p === region || p === '0';
                opt.hidden = !show;
                if (!show && opt.value === clusterSel.value) clusterSel.value = '';
            });
            var cluster = clusterSel.value;
            Array.prototype.forEach.call(branchSel.options, function (opt) {
                if (!opt.value) return;
                var p = opt.getAttribute('data-parent') || '0';
                var show = !cluster || p === cluster || p === '0';
                opt.hidden = !show;
                if (!show && opt.value === branchSel.value) branchSel.value = '';
            });
        }

        function updateMeter() {
            if (!meter) return;
            var total = parseInt(meter.getAttribute('data-total') || '0', 10) || 0;
            var panelKey = activePanel();
            var panel = panelKey === 'cluster' ? splitClusters : (panelKey === 'branch' ? splitBranches : splitVrms);
            var sum = 0;
            if (panel) {
                panel.querySelectorAll('.js-target:checked:not(:disabled)').forEach(function (cb) {
                    var cnt = cb.closest('.cg-split-row').querySelector('.js-cnt');
                    sum += cnt && !cnt.disabled ? (parseInt(cnt.value || '0', 10) || 0) : 0;
                });
            }
            var allocEl = meter.querySelector('.js-alloc');
            var hintEl = meter.querySelector('.js-hint');
            if (allocEl) allocEl.textContent = String(sum);
            if (allocInput.value !== 'counts' || !panelKey) {
                meter.style.display = 'none';
                return;
            }
            meter.style.display = '';
            meter.classList.toggle('ok', sum === total && total > 0);
            meter.classList.toggle('bad', !(sum === total && total > 0));
            if (hintEl) hintEl.textContent = sum === total ? 'Ready' : ('Must total ' + total + ' (remaining ' + (total - sum) + ')');
        }

        function sync() {
            var m = methodInput.value;
            var a = allocInput.value;
            if (m === 'vrm' && isSplit(a)) {
                m = 'custom';
                methodInput.value = 'custom';
            }
            document.querySelectorAll('input[name="' + uid + '_method_ui"]').forEach(function (r) {
                var lab = r.closest('label');
                if (lab) lab.classList.toggle('on', r.value === methodInput.value);
                r.checked = r.value === methodInput.value;
            });

            if (vrmField) vrmField.style.display = (methodInput.value === 'vrm') ? '' : 'none';
            if (clusterField) clusterField.style.display = '';
            if (branchField) {
                branchField.style.display = (methodInput.value === 'branch' || methodInput.value === 'vrm' || methodInput.value === 'custom') ? '' : 'none';
            }
            if (clusterReq) clusterReq.style.display = (methodInput.value === 'cluster' && !isSplit(a)) ? '' : 'none';
            if (branchReq) branchReq.style.display = (methodInput.value === 'branch' && !isSplit(a)) ? '' : 'none';
            if (methodInput.value === 'cluster' && isSplit(a) && clusterField) clusterField.style.display = 'none';
            if (methodInput.value === 'branch' && isSplit(a) && branchField) branchField.style.display = 'none';

            var panel = activePanel();
            setPanel(splitClusters, panel === 'cluster');
            setPanel(splitBranches, panel === 'branch');
            setPanel(splitVrms, panel === 'vrm');

            document.querySelectorAll('#' + uid + 'SplitClusters .js-pct, #' + uid + 'SplitBranches .js-pct, #' + uid + 'SplitVrms .js-pct').forEach(function (el) {
                el.classList.toggle('hidden', a !== 'percentage');
            });
            document.querySelectorAll('#' + uid + 'SplitClusters .js-cnt, #' + uid + 'SplitBranches .js-cnt, #' + uid + 'SplitVrms .js-cnt').forEach(function (el) {
                el.classList.toggle('hidden', a !== 'counts');
            });
            document.querySelectorAll('#' + uid + 'SplitClusters .js-head-right, #' + uid + 'SplitBranches .js-head-right, #' + uid + 'SplitVrms .js-head-right').forEach(function (el) {
                el.textContent = a === 'percentage' ? '%' : (a === 'counts' ? 'Qty' : '');
            });
            if (hint) {
                var unit = panel === 'cluster' ? 'clusters' : (panel === 'branch' ? 'BMs' : (panel === 'vrm' ? 'VRMs' : 'targets'));
                hint.textContent = isSplit(a)
                    ? ('Equal / % / custom count split across selected ' + unit + '.')
                    : 'All contacts go to the selected target.';
            }
            syncCascade();
            updateMeter();
        }

        document.querySelectorAll('input[name="' + uid + '_method_ui"]').forEach(function (r) {
            r.addEventListener('change', function () {
                if (!r.checked) return;
                methodInput.value = r.value;
                sync();
            });
        });
        document.querySelectorAll('input[name="' + uid + '_alloc_ui"]').forEach(function (r) {
            r.addEventListener('change', function () {
                if (!r.checked) return;
                allocInput.value = r.value;
                sync();
            });
        });
        [regionSel, clusterSel, branchSel, vrmSel].forEach(function (el) {
            if (el) el.addEventListener('change', function () { syncCascade(); updateMeter(); });
        });
        document.querySelectorAll('#' + uid + 'SplitClusters input, #' + uid + 'SplitBranches input, #' + uid + 'SplitVrms input').forEach(function (el) {
            el.addEventListener('change', updateMeter);
            el.addEventListener('input', updateMeter);
        });

        window.cgRefreshSplitHints = function (valid) {
            if (meter) {
                meter.setAttribute('data-total', String(valid));
                var t = meter.querySelector('.js-total');
                if (t) t.textContent = String(valid);
            }
            updateMeter();
        };

        window.cgValidateAssign = function (e, soft) {
            var m = methodInput.value;
            var a = allocInput.value;
            var panel = activePanel();
            if (m === 'cluster' && !isSplit(a) && clusterSel && !clusterSel.value) {
                e.preventDefault();
                alert('Select a cluster.');
                return false;
            }
            if (m === 'branch' && !isSplit(a)) {
                if (branchSel && !branchSel.value) { e.preventDefault(); alert('Select a branch (BM).'); return false; }
            }
            if (m === 'vrm' && vrmSel && !vrmSel.value) {
                e.preventDefault();
                alert('Select a VRM.');
                return false;
            }
            if (panel) {
                var box = panel === 'cluster' ? splitClusters : (panel === 'branch' ? splitBranches : splitVrms);
                var checked = box ? box.querySelectorAll('.js-target:checked:not(:disabled)') : [];
                var unit = panel === 'cluster' ? 'cluster' : (panel === 'branch' ? 'branch (BM)' : 'VRM');
                if (!checked.length) {
                    e.preventDefault();
                    alert('Select at least one ' + unit + ' for the split.');
                    return false;
                }
                if (a === 'percentage') {
                    var pct = 0;
                    checked.forEach(function (cb) {
                        var inp = cb.closest('.cg-split-row').querySelector('.js-pct');
                        pct += inp ? (parseFloat(inp.value) || 0) : 0;
                    });
                    if (Math.abs(pct - 100) > 0.5) {
                        e.preventDefault();
                        alert('Percentages must total 100%.');
                        return false;
                    }
                }
                if (a === 'counts') {
                    var total = meter ? (parseInt(meter.getAttribute('data-total') || '0', 10) || 0) : 0;
                    var sum = 0;
                    checked.forEach(function (cb) {
                        var inp = cb.closest('.cg-split-row').querySelector('.js-cnt');
                        sum += inp ? (parseInt(inp.value || '0', 10) || 0) : 0;
                    });
                    if (total > 0 && sum !== total) {
                        e.preventDefault();
                        alert('Custom split must total exactly ' + total + ' (currently ' + sum + ').');
                        return false;
                    }
                }
            }
            return true;
        };

        var form = methodInput.closest('form');
        if (form && !form.__cgAssignBound) {
            form.__cgAssignBound = true;
            form.addEventListener('submit', function (e) {
                var submitter = e.submitter;
                if (submitter && submitter.getAttribute('formaction') && submitter.getAttribute('formaction').indexOf('/draft') !== -1) return;
                window.cgValidateAssign(e, false);
            });
        }

        sync();
    }
});
</script>
@endpush
@endonce
