@php
    $showAllMethods = (bool) ($showAllMethods ?? false);
    $canAssignRegion = (bool) ($canAssignRegion ?? false);
    $handoffKey = (string) ($handoffKey ?? ($handoff['key'] ?? ''));
    // Own-level targets are hidden: RD ≠ Region, Cluster ≠ Cluster, BM ≠ Branch
    $canAssignCluster = (bool) ($canAssignCluster ?? ($showAllMethods && ! in_array($handoffKey, ['cluster', 'bm'], true)));
    $canAssignBranch = (bool) ($canAssignBranch ?? ($showAllMethods && $handoffKey !== 'bm'));
    // Cascade filters: don't ask RD to re-pick Region; don't ask Cluster to re-pick Cluster
    $showRegionFilter = $canAssignRegion || $handoffKey === 'hq';
    $showClusterFilter = $canAssignCluster || in_array($handoffKey, ['hq', 'rd'], true);
    $lockSelectedOrg = (bool) ($lockSelectedOrg ?? false);
    $hideAllocation = (bool) ($hideAllocation ?? $lockSelectedOrg);
    $preselectedRegionId = (int) ($preselectedRegionId ?? 0);
    $preselectedClusterId = (int) ($preselectedClusterId ?? 0);
    $preselectedBranchId = (int) ($preselectedBranchId ?? 0);
    $preselectedTargetIds = array_values(array_filter(array_map('intval', $preselectedTargetIds ?? [])));
    if ($lockSelectedOrg) {
        $showRegionFilter = false;
        $showClusterFilter = false;
    }
    $defaultMethod = $defaultMethod ?? (
        $canAssignRegion ? 'region' : (
            $canAssignCluster ? 'cluster' : (
                $canAssignBranch ? 'branch' : 'vrm'
            )
        )
    );
    $defaultAlloc = $defaultAlloc ?? ($lockSelectedOrg ? 'equal' : (count($preselectedTargetIds) > 1 ? 'equal' : 'all'));
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
<input type="hidden" name="allocation" id="{{ $uid }}Allocation" value="{{ $defaultAlloc }}">

<div class="cg-assign-tabs" role="tablist">
    @if($canAssignRegion)
    <label class="{{ $defaultMethod === 'region' ? 'on' : '' }}" data-method="region">
        <input type="radio" name="{{ $uid }}_method_ui" value="region" @checked($defaultMethod === 'region')> Region
    </label>
    @endif
    @if($canAssignCluster)
    <label class="{{ $defaultMethod === 'cluster' ? 'on' : '' }}" data-method="cluster">
        <input type="radio" name="{{ $uid }}_method_ui" value="cluster" @checked($defaultMethod === 'cluster')> Cluster
    </label>
    @endif
    @if($canAssignBranch)
    <label class="{{ $defaultMethod === 'branch' ? 'on' : '' }}" data-method="branch">
        <input type="radio" name="{{ $uid }}_method_ui" value="branch" @checked($defaultMethod === 'branch')> Branch (BM)
    </label>
    @endif
    <label class="{{ $defaultMethod === 'vrm' ? 'on' : '' }}" data-method="vrm">
        <input type="radio" name="{{ $uid }}_method_ui" value="vrm" @checked($defaultMethod === 'vrm')> VRM
    </label>
</div>

@if($lockSelectedOrg)
    {{-- Org already chosen on selected contacts — keep as hidden fields --}}
    @if($preselectedRegionId > 0)
        <input type="hidden" name="region_id" id="{{ $uid }}Region" value="{{ $preselectedRegionId }}">
    @endif
    @if($preselectedClusterId > 0)
        <input type="hidden" name="cluster_id" id="{{ $uid }}Cluster" value="{{ $preselectedClusterId }}">
    @endif
    @if($preselectedBranchId > 0)
        <input type="hidden" name="branch_id" id="{{ $uid }}Branch" value="{{ $preselectedBranchId }}">
    @endif
@elseif($canAssignRegion || $canAssignCluster || $canAssignBranch || $showRegionFilter || $showClusterFilter)
<div class="cg-row2" data-fields="org">
    @if($showRegionFilter || $canAssignRegion)
    <div class="cg-field" data-show="region" id="{{ $uid }}RegionField">
        <label>Region <span class="req" id="{{ $uid }}RegionReq" @if($defaultMethod !== 'region') style="display:none" @endif>*</span></label>
        <select name="region_id" id="{{ $uid }}Region" class="js-select2" data-search="false" data-allow-clear="false">
            <option value="">— Select region —</option>
            @foreach(($orgOptions['regions'] ?? collect()) as $r)
                <option value="{{ $r->id }}" @selected($preselectedRegionId === (int) $r->id)>{{ $r->name }}</option>
            @endforeach
        </select>
    </div>
    @endif
    @if($showClusterFilter || $canAssignCluster)
    <div class="cg-field" data-show="cluster" id="{{ $uid }}ClusterField">
        <label>Cluster <span class="req" id="{{ $uid }}ClusterReq">*</span></label>
        <select name="cluster_id" id="{{ $uid }}Cluster" class="js-select2" data-search="false" data-allow-clear="false">
            <option value="">— Select cluster —</option>
            @foreach(($orgOptions['clusters'] ?? $clusters ?? collect()) as $c)
                <option value="{{ $c->id }}" data-parent="{{ (int)($c->parent_id ?? 0) }}" @selected($preselectedClusterId === (int) $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    @endif
</div>
@if($canAssignBranch)
<div class="cg-field" data-show="branch" id="{{ $uid }}BranchField">
    <label>Branch <span class="req" id="{{ $uid }}BranchReq">*</span></label>
    <select name="branch_id" id="{{ $uid }}Branch" class="js-select2" data-search="false" data-allow-clear="false">
        <option value="">— Select branch —</option>
        @foreach(($orgOptions['branches'] ?? $branches ?? collect()) as $b)
            <option value="{{ $b->id }}" data-parent="{{ (int)($b->parent_id ?? 0) }}" @selected($preselectedBranchId === (int) $b->id)>{{ $b->name }}</option>
        @endforeach
    </select>
</div>
@endif
@endif

@if($lockSelectedOrg && $canAssignBranch && $preselectedBranchId === 0)
<div class="cg-field" data-show="branch" id="{{ $uid }}BranchField">
    <label>Branch <span class="req" id="{{ $uid }}BranchReq">*</span></label>
    <select name="branch_id" id="{{ $uid }}Branch" class="js-select2" data-search="false" data-allow-clear="false">
        <option value="">— Select branch —</option>
        @foreach(($orgOptions['branches'] ?? $branches ?? collect()) as $b)
            @php
                $branchParent = (int) ($b->parent_id ?? 0);
                $showBranch = $preselectedClusterId === 0 || $branchParent === $preselectedClusterId;
            @endphp
            @if($showBranch)
                <option value="{{ $b->id }}" data-parent="{{ $branchParent }}">{{ $b->name }}</option>
            @endif
        @endforeach
    </select>
</div>
@endif

<div class="cg-field" data-show="vrm" id="{{ $uid }}VrmField" @if($defaultMethod !== 'vrm') style="display:none" @endif>
    <label>VRM <span class="req">*</span></label>
    <select name="vrm_id" id="{{ $uid }}Vrm" class="js-select2" data-search="false" data-allow-clear="false">
        <option value="">— Select VRM —</option>
        @foreach($vrms as $vrm)
            <option value="{{ $vrm->id }}" data-branch="{{ (int) ($vrm->goal_org_unit_id ?? 0) }}">{{ $vrm->name }}</option>
        @endforeach
    </select>
</div>

@if(! $hideAllocation)
<div class="cg-alloc">
    <label><input type="radio" name="{{ $uid }}_alloc_ui" value="all" @checked($defaultAlloc === 'all')> All Contacts</label>
    <label><input type="radio" name="{{ $uid }}_alloc_ui" value="equal" @checked($defaultAlloc === 'equal')> Equal Split</label>
    <label><input type="radio" name="{{ $uid }}_alloc_ui" value="percentage"> By %</label>
    <label><input type="radio" name="{{ $uid }}_alloc_ui" value="counts"> Custom count</label>
</div>
<p style="margin:0 0 4px;font-size:.62rem;color:#94a3b8;line-height:1.25" id="{{ $uid }}AllocHint">
    @if($lockSelectedOrg)
        Select branches/VRMs with checkboxes (same as Create Campaign).
    @else
        All contacts go to the selected target.
    @endif
</p>
@else
<p style="margin:0 0 8px;font-size:.75rem;color:#64748b;line-height:1.35" id="{{ $uid }}AllocHint">
    Using already selected org. Choose Branch or VRM to continue.
</p>
@endif

@if($canAssignRegion)
<div class="cg-split" id="{{ $uid }}SplitRegions" data-panel="region">
    <div class="cg-split-head"><span>Select Regions</span><span class="js-split-total">Split total: 0</span></div>
    <div class="cg-split-list">
        <table class="cg-split-table">
            <thead>
                <tr>
                    <th class="col-check"></th>
                    <th>Region</th>
                    <th class="col-pct">%</th>
                    <th class="col-count">Split Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($orgOptions['regions'] ?? collect()) as $r)
                    <tr class="cg-split-row">
                        <td class="col-check"><input type="checkbox" name="region_ids[]" value="{{ $r->id }}" class="js-target" @checked(in_array((int) $r->id, $preselectedTargetIds, true)) disabled></td>
                        <td class="nm">{{ $r->name }}</td>
                        <td class="col-pct"><input type="number" min="0" max="100" step="0.01" class="pct js-pct" name="percentages[{{ $r->id }}]" placeholder="%" disabled></td>
                        <td class="col-count">
                            <span class="js-split-count">—</span>
                            <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" class="cnt js-cnt" name="counts[{{ $r->id }}]" placeholder="Qty" data-valid-total="{{ $validCount }}" disabled>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No regions in scope.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@if($canAssignCluster)
<div class="cg-split" id="{{ $uid }}SplitClusters" data-panel="cluster">
    <div class="cg-split-head"><span>Select Clusters</span><span class="js-split-total">Split total: 0</span></div>
    <div class="cg-split-list">
        <table class="cg-split-table">
            <thead>
                <tr>
                    <th class="col-check"></th>
                    <th>Cluster</th>
                    <th class="col-pct">%</th>
                    <th class="col-count">Split Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($orgOptions['clusters'] ?? $clusters ?? collect()) as $c)
                    <tr class="cg-split-row" data-parent="{{ (int) ($c->parent_id ?? 0) }}">
                        <td class="col-check"><input type="checkbox" name="cluster_ids[]" value="{{ $c->id }}" class="js-target" @checked(in_array((int) $c->id, $preselectedTargetIds, true)) disabled></td>
                        <td class="nm">{{ $c->name }}</td>
                        <td class="col-pct"><input type="number" min="0" max="100" step="0.01" class="pct js-pct" name="percentages[{{ $c->id }}]" placeholder="%" disabled></td>
                        <td class="col-count">
                            <span class="js-split-count">—</span>
                            <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" class="cnt js-cnt" name="counts[{{ $c->id }}]" placeholder="Qty" data-valid-total="{{ $validCount }}" disabled>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No clusters in scope.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@if($canAssignBranch)
<div class="cg-split" id="{{ $uid }}SplitBranches" data-panel="branch">
    <div class="cg-split-head"><span>Select Branches (BM)</span><span class="js-split-total">Split total: 0</span></div>
    <div class="cg-split-list">
        <table class="cg-split-table">
            <thead>
                <tr>
                    <th class="col-check"></th>
                    <th>Branch</th>
                    <th class="col-pct">%</th>
                    <th class="col-count">Split Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($orgOptions['branches'] ?? $branches ?? collect()) as $b)
                    <tr class="cg-split-row" data-parent="{{ (int) ($b->parent_id ?? 0) }}" data-branch="{{ (int) $b->id }}">
                        <td class="col-check"><input type="checkbox" name="branch_ids[]" value="{{ $b->id }}" class="js-target" @checked(in_array((int) $b->id, $preselectedTargetIds, true)) disabled></td>
                        <td class="nm">{{ $b->name }}</td>
                        <td class="col-pct"><input type="number" min="0" max="100" step="0.01" class="pct js-pct" name="percentages[{{ $b->id }}]" placeholder="%" disabled></td>
                        <td class="col-count">
                            <span class="js-split-count">—</span>
                            <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" class="cnt js-cnt" name="counts[{{ $b->id }}]" placeholder="Qty" data-valid-total="{{ $validCount }}" disabled>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No branches in scope.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="cg-split" id="{{ $uid }}SplitVrms" data-panel="vrm">
    <div class="cg-split-head"><span>Select VRMs</span><span class="js-split-total">Split total: 0</span></div>
    <div class="cg-split-list">
        <table class="cg-split-table">
            <thead>
                <tr>
                    <th class="col-check"></th>
                    <th>VRM</th>
                    <th class="col-pct">%</th>
                    <th class="col-count">Split Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vrms as $vrm)
                    <tr class="cg-split-row" data-branch="{{ (int) ($vrm->goal_org_unit_id ?? 0) }}">
                        <td class="col-check"><input type="checkbox" name="vrm_ids[]" value="{{ $vrm->id }}" class="js-target" disabled></td>
                        <td class="nm">{{ $vrm->name }}</td>
                        <td class="col-pct"><input type="number" min="0" max="100" step="0.01" class="pct js-pct" name="percentages[{{ $vrm->id }}]" placeholder="%" disabled></td>
                        <td class="col-count">
                            <span class="js-split-count">—</span>
                            <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" class="cnt js-cnt" name="counts[{{ $vrm->id }}]" placeholder="Qty" data-valid-total="{{ $validCount }}" disabled>
                        </td>
                    </tr>
                @empty
                    <tr class="js-vrm-empty"><td colspan="4" class="text-muted">No VRMs in scope.</td></tr>
                @endforelse
                <tr class="js-vrm-none" hidden><td colspan="4" class="text-muted">No VRMs for the selected cluster / branch.</td></tr>
            </tbody>
        </table>
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
window.cgAssignPerms = window.cgAssignPerms || {};
window.cgAssignPerms[@json($uid)] = {
    canRegion: @json($canAssignRegion),
    canCluster: @json($canAssignCluster),
    canBranch: @json($canAssignBranch),
    showRegion: @json($showRegionFilter),
    showCluster: @json($showClusterFilter),
    lockOrg: @json($lockSelectedOrg),
    hideAlloc: @json($hideAllocation),
    handoff: @json($handoffKey),
    preselectedTargets: @json($preselectedTargetIds),
    defaultMethod: @json($defaultMethod),
    defaultAlloc: @json($defaultAlloc)
};
document.addEventListener('DOMContentLoaded', function () {
    (window.cgAssignBoot || []).forEach(function (uid) {
        if (window['cgAssignInit_' + uid]) return;
        window['cgAssignInit_' + uid] = true;
        initAssign(uid);
    });

    function cgAssignAlert(message, title) {
        title = title || 'Check assignment';
        if (window.toastr && typeof toastr.error === 'function') { toastr.error(message); return; }
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({ icon: 'warning', title: title, text: message, confirmButtonColor: '#16a34a' });
            return;
        }
        alert(message);
    }

    function initAssign(uid) {
        var methodInput = document.getElementById(uid + 'Method');
        var allocInput = document.getElementById(uid + 'Allocation');
        if (!methodInput || !allocInput) return;
        var perms = window.cgAssignPerms[uid] || {};

        var regionSel = document.getElementById(uid + 'Region');
        var clusterSel = document.getElementById(uid + 'Cluster');
        var branchSel = document.getElementById(uid + 'Branch');
        var vrmSel = document.getElementById(uid + 'Vrm');
        var regionField = document.getElementById(uid + 'RegionField');
        var clusterField = document.getElementById(uid + 'ClusterField');
        var branchField = document.getElementById(uid + 'BranchField');
        var vrmField = document.getElementById(uid + 'VrmField');
        var regionReq = document.getElementById(uid + 'RegionReq');
        var clusterReq = document.getElementById(uid + 'ClusterReq');
        var branchReq = document.getElementById(uid + 'BranchReq');
        var splitRegions = document.getElementById(uid + 'SplitRegions');
        var splitClusters = document.getElementById(uid + 'SplitClusters');
        var splitBranches = document.getElementById(uid + 'SplitBranches');
        var splitVrms = document.getElementById(uid + 'SplitVrms');
        var meter = document.getElementById(uid + 'Meter');
        var hint = document.getElementById(uid + 'AllocHint');

        function isSplit(a) { return a === 'equal' || a === 'percentage' || a === 'counts'; }

        function activePanel() {
            var m = methodInput.value, a = allocInput.value;
            if (m === 'vrm' && isSplit(a)) return 'vrm';
            if (m === 'region' && isSplit(a)) return 'region';
            if (m === 'cluster' && isSplit(a)) return 'cluster';
            if (m === 'branch' && isSplit(a)) return 'branch';
            return null;
        }

        function panelEl(key) {
            if (key === 'region') return splitRegions;
            if (key === 'cluster') return splitClusters;
            if (key === 'branch') return splitBranches;
            if (key === 'vrm') return splitVrms;
            return null;
        }

        function setPanel(panel, on) {
            if (!panel) return;
            panel.classList.toggle('show', !!on);
            panel.querySelectorAll('input').forEach(function (inp) { inp.disabled = !on; });
        }

        function parentOf(sel, id) {
            if (!sel || !id) return '0';
            var opt = sel.querySelector('option[value="' + id + '"]');
            return opt ? (opt.getAttribute('data-parent') || '0') : '0';
        }

        function branchAllowed(branchId) {
            var bid = String(branchId || '0');
            if (!bid || bid === '0') return false;
            var branch = branchSel ? branchSel.value : '';
            var cluster = clusterSel ? clusterSel.value : '';
            var region = regionSel ? regionSel.value : '';
            if (branch) return bid === branch;
            var clusterOfBranch = parentOf(branchSel, bid);
            if (cluster) return clusterOfBranch === cluster;
            if (region) {
                var regionOfCluster = parentOf(clusterSel, clusterOfBranch);
                return regionOfCluster === region || clusterOfBranch === region;
            }
            return true;
        }

        function filterSplitRows(box, attr, allowedFn) {
            if (!box) return;
            box.querySelectorAll('tr.cg-split-row').forEach(function (row) {
                var id = row.getAttribute(attr) || '0';
                var show = allowedFn(id);
                row.hidden = !show;
                if (!show) {
                    var cb = row.querySelector('.js-target');
                    var pct = row.querySelector('.js-pct');
                    var cnt = row.querySelector('.js-cnt');
                    if (cb) cb.checked = false;
                    if (pct) pct.value = '';
                    if (cnt) cnt.value = '';
                }
            });
        }

        function isSelect(el) { return el && el.tagName === 'SELECT'; }

        function syncCascade() {
            if (perms.lockOrg) {
                // Org already locked from selected contacts — only filter VRMs if needed
                if (isSelect(vrmSel) || splitVrms) {
                    // fall through to VRM filter using locked branch/cluster values
                } else {
                    return;
                }
            }
            var region = regionSel ? regionSel.value : '';
            if (isSelect(clusterSel)) {
                Array.prototype.forEach.call(clusterSel.options, function (opt) {
                    if (!opt.value) return;
                    var p = opt.getAttribute('data-parent') || '0';
                    var show = !region || p === region || p === '0';
                    opt.hidden = !show;
                    opt.disabled = !show;
                    if (!show && opt.value === clusterSel.value) clusterSel.value = '';
                });
            }
            var cluster = clusterSel ? clusterSel.value : '';
            if (isSelect(branchSel)) {
                Array.prototype.forEach.call(branchSel.options, function (opt) {
                    if (!opt.value) return;
                    var p = opt.getAttribute('data-parent') || '0';
                    var show = (!cluster && !region)
                        || (cluster && (p === cluster || p === '0'))
                        || (!cluster && region && (parentOf(clusterSel, p) === region || p === region || p === '0'));
                    opt.hidden = !show;
                    opt.disabled = !show;
                    if (!show && opt.value === branchSel.value) branchSel.value = '';
                });
            }

            // Filter cluster/branch split tables by selected region/cluster
            if (splitClusters && region) {
                filterSplitRows(splitClusters, 'data-parent', function (pid) {
                    return !pid || pid === '0' || pid === region;
                });
            } else if (splitClusters) {
                splitClusters.querySelectorAll('tr.cg-split-row').forEach(function (row) { row.hidden = false; });
            }
            if (splitBranches) {
                filterSplitRows(splitBranches, 'data-parent', function (pid) {
                    if (cluster) return !pid || pid === '0' || pid === cluster;
                    if (region) return !pid || pid === '0' || parentOf(clusterSel, pid) === region || pid === region;
                    return true;
                });
            }

            // Filter VRMs by selected branch → cluster → region
            if (vrmSel) {
                Array.prototype.forEach.call(vrmSel.options, function (opt) {
                    if (!opt.value) return;
                    var show = branchAllowed(opt.getAttribute('data-branch') || '0');
                    opt.hidden = !show;
                    opt.disabled = !show;
                    if (!show && opt.value === vrmSel.value) vrmSel.value = '';
                });
            }
            if (splitVrms) {
                var visibleVrms = 0;
                splitVrms.querySelectorAll('tr.cg-split-row').forEach(function (row) {
                    var show = branchAllowed(row.getAttribute('data-branch') || '0');
                    row.hidden = !show;
                    if (!show) {
                        var cb = row.querySelector('.js-target');
                        var pct = row.querySelector('.js-pct');
                        var cnt = row.querySelector('.js-cnt');
                        if (cb) cb.checked = false;
                        if (pct) pct.value = '';
                        if (cnt) cnt.value = '';
                    } else {
                        visibleVrms++;
                    }
                });
                var noneRow = splitVrms.querySelector('tr.js-vrm-none');
                if (noneRow) noneRow.hidden = visibleVrms > 0;
            }
        }

        function validTotal() {
            return meter ? (parseInt(meter.getAttribute('data-total') || '0', 10) || 0) : (window.__cgValidEstimate || 0);
        }

        function refreshSplitCounts() {
            var a = allocInput.value;
            var total = validTotal();
            [splitRegions, splitClusters, splitBranches, splitVrms].forEach(function (box) {
                if (!box || !box.classList.contains('show')) return;
                var rows = Array.prototype.slice.call(box.querySelectorAll('tr.cg-split-row'));
                var checked = rows.filter(function (row) {
                    if (row.hidden) return false;
                    var cb = row.querySelector('.js-target');
                    return cb && cb.checked && !cb.disabled;
                });
                var n = checked.length;
                var sum = 0;

                rows.forEach(function (row) {
                    var cb = row.querySelector('.js-target');
                    var pct = row.querySelector('.js-pct');
                    var cnt = row.querySelector('.js-cnt');
                    var show = row.querySelector('.js-split-count');
                    var on = cb && cb.checked && !cb.disabled;

                    if (a === 'counts') {
                        if (show) show.classList.add('d-none');
                        if (cnt) cnt.classList.remove('hidden');
                        if (on && cnt) sum += parseInt(cnt.value || '0', 10) || 0;
                        else if (show) show.textContent = '—';
                        return;
                    }

                    if (cnt) cnt.classList.add('hidden');
                    if (show) show.classList.remove('d-none');

                    if (!on) {
                        if (show) show.textContent = '—';
                        return;
                    }

                    var share = 0;
                    if (a === 'equal') {
                        share = n > 0 ? Math.floor(total / n) : 0;
                    } else if (a === 'percentage') {
                        var p = pct ? (parseFloat(pct.value) || 0) : 0;
                        share = Math.floor(total * (p / 100));
                    }
                    if (show) show.textContent = String(share);
                    sum += share;
                });

                // Remainder for equal split goes to first selected
                if (a === 'equal' && n > 0 && total > 0) {
                    var base = Math.floor(total / n);
                    var rem = total - (base * n);
                    if (rem > 0 && checked[0]) {
                        var firstShow = checked[0].querySelector('.js-split-count');
                        if (firstShow) firstShow.textContent = String(base + rem);
                        sum = total;
                    }
                }

                var totEl = box.querySelector('.js-split-total');
                if (totEl) {
                    totEl.textContent = total > 0
                        ? ('Split: ' + sum + ' / ' + total)
                        : ('Split count (upload file to preview)');
                }
            });
        }

        function updateMeter() {
            refreshSplitCounts();
            if (!meter) return;
            var total = validTotal();
            var panelKey = activePanel();
            var panel = panelEl(panelKey);
            var sum = 0;
            if (panel && allocInput.value === 'counts') {
                panel.querySelectorAll('.js-target:checked:not(:disabled)').forEach(function (cb) {
                    var cnt = cb.closest('tr').querySelector('.js-cnt');
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
            document.querySelectorAll('input[name="' + uid + '_method_ui"]').forEach(function (r) {
                var lab = r.closest('label');
                if (lab) lab.classList.toggle('on', r.value === m);
                r.checked = r.value === m;
            });

            if (vrmField) vrmField.style.display = (m === 'vrm' && !isSplit(a)) ? '' : 'none';
            if (regionField) {
                regionField.style.display = (m === 'region' || m === 'cluster' || m === 'branch' || m === 'vrm') ? '' : 'none';
                if (m === 'region' && isSplit(a)) regionField.style.display = 'none';
            }
            if (clusterField) {
                clusterField.style.display = (m === 'cluster' || m === 'branch' || m === 'vrm') ? '' : 'none';
                if (m === 'cluster' && isSplit(a)) clusterField.style.display = 'none';
            }
            if (branchField) {
                branchField.style.display = (m === 'branch' || m === 'vrm') ? '' : 'none';
                if (m === 'branch' && isSplit(a)) branchField.style.display = 'none';
            }
            if (regionReq) regionReq.style.display = (m === 'region' && !isSplit(a)) ? '' : 'none';
            if (clusterReq) clusterReq.style.display = (m === 'cluster' && !isSplit(a)) ? '' : 'none';
            if (branchReq) branchReq.style.display = (m === 'branch' && !isSplit(a)) ? '' : 'none';

            var panel = activePanel();
            setPanel(splitRegions, panel === 'region');
            setPanel(splitClusters, panel === 'cluster');
            setPanel(splitBranches, panel === 'branch');
            setPanel(splitVrms, panel === 'vrm');

            [splitRegions, splitClusters, splitBranches, splitVrms].forEach(function (box) {
                if (!box) return;
                box.classList.toggle('mode-pct', a === 'percentage');
                box.classList.toggle('mode-counts', a === 'counts');
                box.classList.toggle('mode-equal', a === 'equal');
                box.querySelectorAll('.js-pct').forEach(function (el) { el.classList.toggle('hidden', a !== 'percentage'); });
                box.querySelectorAll('.js-cnt').forEach(function (el) { el.classList.toggle('hidden', a !== 'counts'); });
            });

            if (hint) {
                var unit = panel === 'region' ? 'regions' : (panel === 'cluster' ? 'clusters' : (panel === 'branch' ? 'BMs' : (panel === 'vrm' ? 'VRMs' : 'targets')));
                hint.textContent = isSplit(a)
                    ? ('Split across selected ' + unit + '. Split Count updates per row.')
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
                document.dispatchEvent(new CustomEvent('cg-assign-method', { detail: { uid: uid, method: r.value } }));
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
        [splitRegions, splitClusters, splitBranches, splitVrms].forEach(function (box) {
            if (!box) return;
            box.querySelectorAll('input').forEach(function (el) {
                el.addEventListener('change', updateMeter);
                el.addEventListener('input', updateMeter);
            });
            box.querySelectorAll('.js-cnt').forEach(function (el) {
                el.addEventListener('keydown', function (e) {
                    if (e.key === '.' || e.key === ',' || e.key === 'e' || e.key === 'E' || e.key === '+' || e.key === '-') {
                        e.preventDefault();
                    }
                });
                el.addEventListener('input', function () {
                    var raw = String(el.value || '').replace(/[^\d]/g, '');
                    if (el.value !== raw) el.value = raw;
                });
                el.addEventListener('blur', function () {
                    if (el.value === '') return;
                    var n = parseInt(el.value, 10);
                    el.value = isNaN(n) || n < 0 ? '0' : String(n);
                    updateMeter();
                });
            });
        });

        window.cgRefreshSplitHints = function (valid) {
            window.__cgValidEstimate = valid;
            if (meter) {
                meter.setAttribute('data-total', String(valid));
                var t = meter.querySelector('.js-total');
                if (t) t.textContent = String(valid);
            }
            updateMeter();
        };

        window.cgValidateAssign = function (e, soft) {
            if (e && e.__cgAssignChecked) return !!e.__cgAssignOk;
            function fail(msg) {
                if (e && e.preventDefault) e.preventDefault();
                if (e) { e.__cgAssignChecked = true; e.__cgAssignOk = false; }
                cgAssignAlert(msg);
                return false;
            }
            function ok() {
                if (e) { e.__cgAssignChecked = true; e.__cgAssignOk = true; }
                return true;
            }
            var m = methodInput.value;
            var a = allocInput.value;
            var panel = activePanel();

            if (m === 'region' && !perms.canRegion) {
                return fail('You do not have permission to assign to a Region.');
            }
            if (m === 'cluster' && !perms.canCluster) {
                return fail('You do not have permission to assign to a Cluster. Choose Branch (BM) or VRM.');
            }
            if (m === 'branch' && !perms.canBranch) {
                return fail('You do not have permission to assign to a Branch. Choose VRM in your branch.');
            }

            // Cascade required when those filters are visible (single-target modes)
            if (!perms.lockOrg) {
                if (!isSplit(a) && perms.showRegion && regionSel && regionField && regionField.style.display !== 'none' && !regionSel.value) {
                    if (m === 'cluster' || m === 'branch' || m === 'vrm') {
                        return fail('Select a Region first.');
                    }
                }
                if (!isSplit(a) && perms.showCluster && clusterSel && clusterField && clusterField.style.display !== 'none' && !clusterSel.value) {
                    if (m === 'branch' || m === 'vrm') {
                        return fail('Select a Cluster first.');
                    }
                }
                if (m === 'region' && !isSplit(a) && regionSel && !regionSel.value) {
                    return fail('Select a region.');
                }
                if (m === 'cluster' && !isSplit(a) && clusterSel && !clusterSel.value) {
                    return fail('Select a cluster.');
                }
            }
            if (m === 'branch' && !isSplit(a)) {
                if (branchSel && !branchSel.value) return fail('Select a branch (BM).');
            }
            if (m === 'vrm' && !isSplit(a)) {
                if (!perms.lockOrg && branchField && branchField.style.display !== 'none' && branchSel && !branchSel.value) {
                    return fail('Select a Branch before choosing a VRM.');
                }
                if (vrmSel && !vrmSel.value) return fail('Select a VRM.');
            }
            if (panel) {
                var box = panelEl(panel);
                var checked = [];
                if (box) {
                    box.querySelectorAll('tr.cg-split-row').forEach(function (row) {
                        if (row.hidden) return;
                        var cb = row.querySelector('.js-target');
                        if (cb && cb.checked && !cb.disabled) checked.push(cb);
                    });
                }
                var unit = panel === 'region' ? 'region' : (panel === 'cluster' ? 'cluster' : (panel === 'branch' ? 'branch (BM)' : 'VRM'));
                if (!checked.length) {
                    return fail('Select at least one ' + unit + ' for the split.');
                }
                if (a === 'percentage') {
                    var pct = 0;
                    checked.forEach(function (cb) {
                        var inp = cb.closest('tr').querySelector('.js-pct');
                        pct += inp ? (parseFloat(inp.value) || 0) : 0;
                    });
                    if (Math.abs(pct - 100) > 0.5) {
                        return fail('Percentages must total 100%. Currently ' + pct.toFixed(1) + '%.');
                    }
                }
                if (a === 'counts') {
                    var total = validTotal();
                    var sum = 0;
                    checked.forEach(function (cb) {
                        var inp = cb.closest('tr').querySelector('.js-cnt');
                        sum += inp ? (parseInt(inp.value || '0', 10) || 0) : 0;
                    });
                    if (total > 0 && sum !== total) {
                        return fail('Custom split must total exactly ' + total + ' (currently ' + sum + ').');
                    }
                    if (total <= 0) {
                        return fail('Upload a file with valid contacts before using custom count.');
                    }
                }
            }
            return ok();
        };

        var form = methodInput.closest('form');
        if (form && !form.__cgAssignBound) {
            form.__cgAssignBound = true;
            form.addEventListener('submit', function (e) {
                var submitter = e.submitter;
                if (submitter && submitter.getAttribute('formaction') && submitter.getAttribute('formaction').indexOf('/draft') !== -1) return;

                // Create/Edit page owns Swal confirm on #cgCampaignForm
                if (form.id === 'cgCampaignForm') {
                    if (!form.__cgAllowSubmit) return;
                    if (!window.cgValidateAssign(e, false)) {
                        form.__cgAllowSubmit = false;
                        var btn = document.getElementById('cgSubmitBtn');
                        var lbl = document.getElementById('cgSubmitLabel');
                        if (btn) btn.disabled = false;
                        if (lbl) lbl.textContent = form.getAttribute('data-edit') === '1' ? 'Save & Assign' : 'Import & Assign';
                    }
                    return;
                }

                // Standalone assign form — validate then Swal confirm
                if (form.__cgAllowSubmit) {
                    window.setTimeout(function () { form.__cgAllowSubmit = false; }, 0);
                    return;
                }
                e.preventDefault();
                if (!window.cgValidateAssign({ preventDefault: function () {}, __cgAssignChecked: false, __cgAssignOk: false }, true)) {
                    return;
                }
                function proceedAssign() {
                    form.__cgAllowSubmit = true;
                    if (typeof form.requestSubmit === 'function') form.requestSubmit(submitter || undefined);
                    else HTMLFormElement.prototype.submit.call(form);
                }
                if (typeof Swal !== 'undefined' && Swal.fire) {
                    Swal.fire({
                        icon: 'question',
                        title: 'Assign contacts?',
                        text: 'This will assign the selected contacts as configured.',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, assign',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#16a34a',
                        cancelButtonColor: '#94a3b8',
                        reverseButtons: true,
                        focusCancel: true
                    }).then(function (result) {
                        if (result.isConfirmed) proceedAssign();
                    });
                    return;
                }
                if (window.confirm('Assign contacts?')) proceedAssign();
            });
        }

        // Restore prior assignment: method + allocation + checked targets
        if (perms.defaultMethod) {
            methodInput.value = perms.defaultMethod;
        }
        if (perms.defaultAlloc) {
            allocInput.value = perms.defaultAlloc;
            document.querySelectorAll('input[name="' + uid + '_alloc_ui"]').forEach(function (r) {
                r.checked = r.value === perms.defaultAlloc;
            });
        }
        var cgSelectBinding = false;
        function bindCgSelects() {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
            if (cgSelectBinding) return;
            cgSelectBinding = true;
            var $ = window.jQuery;
            try {
                [regionSel, clusterSel, branchSel, vrmSel].forEach(function (el) {
                    if (!el || el.tagName !== 'SELECT') return;
                    var $el = $(el);
                    var $parent = $el.closest('.cg-field');
                    if (!$parent.length) $parent = $el.parent();
                    if ($el.hasClass('select2-hidden-accessible')) {
                        try { $el.select2('destroy'); } catch (e) {}
                    }
                    $el.select2({
                        width: '100%',
                        dropdownParent: $parent,
                        dropdownCssClass: 'cg-select2-drop',
                        minimumResultsForSearch: Infinity,
                        allowClear: false
                    });
                    $el.off('change.cgSel').on('change.cgSel', function () {
                        if (cgSelectBinding) return;
                        syncCascade();
                        bindCgSelects();
                        updateMeter();
                    });
                });
            } finally {
                cgSelectBinding = false;
            }
        }

        sync();
        var targets = perms.preselectedTargets || [];
        if (targets.length) {
            var box = panelEl(activePanel());
            if (box) {
                box.querySelectorAll('tr.cg-split-row').forEach(function (row) {
                    var cb = row.querySelector('.js-target');
                    if (!cb) return;
                    var id = parseInt(cb.value, 10) || 0;
                    if (targets.indexOf(id) !== -1) {
                        cb.checked = true;
                        cb.disabled = false;
                    }
                });
                updateMeter();
            }
        }
        bindCgSelects();
        // Global select2-init may race; re-bind once more after paint
        window.setTimeout(bindCgSelects, 0);

        var _syncCore = sync;
        sync = function () {
            _syncCore();
            bindCgSelects();
        };
    }
});
</script>
@endpush
@endonce
