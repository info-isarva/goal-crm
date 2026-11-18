@php
    $statusLabel = fn ($s) => in_array($s, ['Pending', 'Uploaded'], true) ? 'Pending Verification' : $s;
    $statusClass = fn ($s) => match ($s) {
        'Verified' => 'verified',
        'Rejected' => 'rejected',
        default => 'pending',
    };
    $fileIcon = function ($name) {
        $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'bi-file-earmark-pdf-fill',
            'jpg', 'jpeg', 'png', 'webp' => 'bi-file-earmark-image',
            default => 'bi-file-earmark-text',
        };
    };
@endphp

<style>
.cw-docs-kpi{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;margin-bottom:.85rem}
.cw-docs-kpi .tile{
    background:#fff;border:1px solid #e8edf5;border-radius:12px;padding:.55rem .75rem;
    display:flex;align-items:center;justify-content:space-between;gap:8px;
    box-shadow:0 1px 2px rgba(15,23,42,.04);
}
.cw-docs-kpi .tile .l{font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.02em}
.cw-docs-kpi .tile .n{font-size:1.15rem;font-weight:800;color:#0f172a;line-height:1}
.cw-docs-table .status-pill{
    display:inline-flex;align-items:center;justify-content:center;
    padding:.28rem .7rem;border-radius:999px;font-size:.74rem;font-weight:700;
}
.cw-docs-table .status-pill.pending{background:#ffedd5;color:#c2410c}
.cw-docs-table .status-pill.verified{background:#dcfce7;color:#15803d}
.cw-docs-table .status-pill.rejected{background:#fee2e2;color:#b91c1c}
.cw-docs-table .file-cell{display:inline-flex;align-items:center;gap:8px;max-width:200px}
.cw-docs-table .file-cell i{color:#ef4444}
.cw-docs-table .file-name{overflow:hidden;text-overflow:ellipsis;font-weight:600}
.cw-docs-table .req-pill{
    display:inline-flex;margin-left:6px;padding:.1rem .4rem;border-radius:999px;
    font-size:.65rem;font-weight:700;background:#fee2e2;color:#b91c1c;
}
.cw-docs-actions{display:inline-flex;align-items:center;gap:4px;flex-wrap:wrap}
@media (max-width:991.98px){
    .cw-docs-kpi{grid-template-columns:repeat(2,minmax(0,1fr))}
}
</style>

<div class="cw-docs-kpi">
    <div class="tile"><span class="l">Required</span><span class="n">{{ $docSummary['required'] }}</span></div>
    <div class="tile"><span class="l">Uploaded</span><span class="n">{{ $docSummary['uploaded'] }}</span></div>
    <div class="tile"><span class="l">Pending</span><span class="n">{{ $docSummary['pending'] }}</span></div>
    <div class="tile"><span class="l">Verified</span><span class="n">{{ $docSummary['verified'] }}</span></div>
    <div class="tile"><span class="l">Rejected</span><span class="n">{{ $docSummary['rejected'] }}</span></div>
</div>

<div class="sec-card">
    <div class="sec-head">
        <div class="sec-head-left">
            <span class="sec-ico"><i class="bi bi-file-earmark-text"></i></span>
            <div>
                <h6>Case Documents</h6>
                <div class="sec-sub">
                    This case only — same records as the centralized
                    <a href="{{ route('goal.documents.index', ['case_id' => $case->id]) }}">Documents register</a>.
                    Required documents must be verified before Ready for Login.
                </div>
            </div>
        </div>
        @if($canEdit)
            <button type="button" class="btn-primary-cw" data-bs-toggle="modal" data-bs-target="#cwUploadDocModal">
                <i class="bi bi-upload"></i> Upload
            </button>
        @endif
    </div>
    <div class="table-responsive cw-docs-table">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Document Type</th>
                    <th>Party</th>
                    <th>File Name</th>
                    <th>Uploaded On</th>
                    <th>Uploaded By</th>
                    <th>Verification Status</th>
                    <th>Reviewer</th>
                    <th>Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($docs as $doc)
                    @php
                        $st = $doc['status'] ?? 'Pending';
                        if ($st === 'Uploaded') $st = 'Pending';
                        $path = $doc['path'] ?? ($doc['storage_path'] ?? null);
                        $key = (string) ($doc['key'] ?? '');
                        $party = (string) ($doc['party'] ?? 'Applicant');
                        $hasFile = $path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
                        $previewUrl = $hasFile
                            ? route('goal.documents.file', [$case, 'key' => $key, 'party' => $party, 'disposition' => 'inline'])
                            : null;
                        $downloadUrl = $hasFile
                            ? route('goal.documents.file', [$case, 'key' => $key, 'party' => $party, 'disposition' => 'attachment'])
                            : null;
                        $uploadedOn = null;
                        if (!empty($doc['uploaded_at'])) {
                            try { $uploadedOn = \Carbon\Carbon::parse($doc['uploaded_at'])->format('d M Y'); } catch (\Throwable) { $uploadedOn = $doc['uploaded_at']; }
                        }
                    @endphp
                    <tr>
                        <td>
                            {{ $doc['label'] ?? $doc['key'] ?? 'Document' }}
                            @if(!empty($doc['mandatory'])) <span class="req-pill">Required</span> @endif
                        </td>
                        <td>{{ $party }}</td>
                        <td>
                            @if(!empty($doc['file_name']))
                                <span class="file-cell" title="{{ $doc['file_name'] }}">
                                    <i class="bi {{ $fileIcon($doc['file_name']) }}"></i>
                                    <span class="file-name">{{ $doc['file_name'] }}</span>
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $uploadedOn ?: '—' }}</td>
                        <td>{{ $doc['uploaded_by'] ?? '—' }}</td>
                        <td><span class="status-pill {{ $statusClass($st) }}">{{ $statusLabel($st) }}</span></td>
                        <td>{{ $doc['reviewer'] ?? '—' }}</td>
                        <td class="small">{{ $doc['rejection_reason'] ?? '—' }}</td>
                        <td>
                            <div class="cw-docs-actions">
                                @if($previewUrl)
                                    <a class="btn-ghost" style="height:32px;padding:0 8px" href="{{ $previewUrl }}" target="_blank" title="Preview"><i class="bi bi-eye"></i></a>
                                    <a class="btn-ghost" style="height:32px;padding:0 8px" href="{{ $downloadUrl }}" title="Download"><i class="bi bi-download"></i></a>
                                @endif
                                @if($canVerifyDocs && in_array($st, ['Pending'], true) && $previewUrl)
                                    <form method="POST" action="{{ route('goal.cases.documents.verify', $case) }}">
                                        @csrf
                                        <input type="hidden" name="key" value="{{ $key }}">
                                        <input type="hidden" name="party" value="{{ $party }}">
                                        <input type="hidden" name="action" value="Verified">
                                        <button class="btn-ghost" style="height:32px;padding:0 8px;color:#059669" type="submit" title="Verify"><i class="bi bi-check2"></i></button>
                                    </form>
                                    <button type="button" class="btn-ghost" style="height:32px;padding:0 8px;color:#dc2626"
                                        data-bs-toggle="modal" data-bs-target="#cwRejectDocModal"
                                        data-doc-key="{{ $key }}"
                                        data-doc-party="{{ $party }}"
                                        title="Reject"><i class="bi bi-x-lg"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No documents on this case.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
