<div class="kpi-row">
    <div class="kpi"><div class="n">{{ $docSummary['required'] }}</div><div class="l">Required</div></div>
    <div class="kpi"><div class="n">{{ $docSummary['uploaded'] }}</div><div class="l">Uploaded</div></div>
    <div class="kpi"><div class="n">{{ $docSummary['pending'] }}</div><div class="l">Pending Verification</div></div>
    <div class="kpi"><div class="n">{{ $docSummary['verified'] }}</div><div class="l">Verified</div></div>
    <div class="kpi"><div class="n">{{ $docSummary['rejected'] }}</div><div class="l">Rejected</div></div>
</div>

<div class="sec-card">
    <div class="sec-head">
        <div class="sec-head-left">
            <span class="sec-ico"><i class="bi bi-file-earmark-text"></i></span>
            <div>
                <h6>Case Documents</h6>
                <div class="sec-sub">Mandatory types come from product configuration. Uploads start as Pending.</div>
            </div>
        </div>
        @if($canEdit)
            <button type="button" class="btn-primary-cw" data-bs-toggle="modal" data-bs-target="#cwUploadDocModal"><i class="bi bi-upload"></i> Upload</button>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Document Type</th>
                    <th>Party</th>
                    <th>File</th>
                    <th>Uploaded</th>
                    <th>By</th>
                    <th>Status</th>
                    <th>Reviewer</th>
                    <th>Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($docs as $doc)
                    @php
                        $st = $doc['status'] ?? 'Pending';
                        $badge = match($st){ 'Verified'=>'ok', 'Rejected'=>'bad', default=>'warn' };
                        $path = $doc['path'] ?? ($doc['storage_path'] ?? null);
                    @endphp
                    <tr>
                        <td>
                            {{ $doc['label'] ?? $doc['key'] ?? 'Document' }}
                            @if(!empty($doc['mandatory'])) <span class="doc-badge bad">Required</span> @endif
                        </td>
                        <td>{{ $doc['party'] ?? 'Applicant' }}</td>
                        <td>{{ $doc['file_name'] ?? '—' }}</td>
                        <td>{{ $doc['uploaded_at'] ?? '—' }}</td>
                        <td>{{ $doc['uploaded_by'] ?? '—' }}</td>
                        <td><span class="doc-badge {{ $badge }}">{{ $st }}</span></td>
                        <td>{{ $doc['reviewer'] ?? '—' }}</td>
                        <td class="small">{{ $doc['rejection_reason'] ?? '—' }}</td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                @if($path)
                                    <a class="btn-ghost" style="height:32px;padding:0 8px" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->exists($path) ? asset('storage/'.$path) : '#' }}" target="_blank" title="View"><i class="bi bi-eye"></i></a>
                                @endif
                                @if($canVerifyDocs && in_array($st, ['Pending', 'Uploaded'], true) && $path)
                                    <form method="POST" action="{{ route('goal.cases.documents.verify', $case) }}">
                                        @csrf
                                        <input type="hidden" name="key" value="{{ $doc['key'] ?? '' }}">
                                        <input type="hidden" name="party" value="{{ $doc['party'] ?? 'Applicant' }}">
                                        <input type="hidden" name="action" value="Verified">
                                        <button class="btn-ghost" style="height:32px;padding:0 8px;color:#059669" type="submit" title="Verify"><i class="bi bi-check2"></i></button>
                                    </form>
                                    <button type="button" class="btn-ghost" style="height:32px;padding:0 8px;color:#dc2626"
                                        data-bs-toggle="modal" data-bs-target="#cwRejectDocModal"
                                        data-doc-key="{{ $doc['key'] ?? '' }}"
                                        data-doc-party="{{ $doc['party'] ?? 'Applicant' }}"
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
