@php
    $entityType = $entityType ?? 'lead'; // lead|deal
    $entity = $entity ?? null;
    $isHistorical = $isHistorical ?? false;
    $gmailEnabled = !$isHistorical && (
        auth()->user()->canSendQuotationEmail()
        || (
            \App\Models\Company::current()->isEmailSendEnabled()
            && ($entityType === 'lead'
                ? auth()->user()->hasCrmPermission('edit_crm_leads_guard')
                : auth()->user()->hasCrmPermission('edit_crm_deals_guard'))
        )
    );

    $userEmail = trim((string) (auth()->user()->email ?? ''));
    $userName = trim((string) (auth()->user()->name ?? '')) ?: config('app.name');
    $userInitial = strtoupper(mb_substr($userName !== '' ? $userName : 'U', 0, 1));
    $person = optional($entity)->person;
    $defaultToEmail = trim((string) optional($person)->email);
    $defaultToName = trim((optional($person)->first_name ?? '') . ' ' . (optional($person)->last_name ?? ''));
    $entityTitle = (string) (optional($entity)->title ?? ucfirst($entityType));
    $entityId = (int) (optional($entity)->id ?? 0);

    $composeUrl = $entityType === 'lead'
        ? route('leads.gmail_compose', $entityId)
        : route('deals.gmail_compose', $entityId);
    $sendUrl = $entityType === 'lead'
        ? route('leads.send_gmail', $entityId)
        : route('deals.send_gmail', $entityId);
    $connectUrl = route('google.gmail.connect', [
        'return' => ($entityType === 'lead' ? route('leads.show', $entityId) : route('deals.show', $entityId)) . '?open_entity_gmail=1',
    ]);
    $recipientSearchUrl = route('gmail.email_recipients');

    $emailNotes = \App\Models\Note::where('related_type', $entityType)
        ->where('related_id', $entityId)
        ->where('content', 'like', '[Gmail Email]%')
        ->orderByDesc('noted_at')
        ->orderByDesc('created_at')
        ->get();

    $defaultBody = app(\App\Services\EmailComposeSettingsService::class)
        ->buildGreetingBody(auth()->user(), $defaultToName !== '' ? $defaultToName : 'Customer');
    $composeEditorStyle = app(\App\Services\EmailComposeSettingsService::class)
        ->editorContentStyle(auth()->user());
@endphp

<style>
.egm-compose-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border-radius: 0.5rem;
    padding: 0.55rem 1rem;
    font-weight: 600;
    background: #ea4335 !important;
    border-color: #ea4335 !important;
}
.egm-compose-btn:hover {
    background: #d93025 !important;
    border-color: #d93025 !important;
}
/* Gmail-style Sent list */
.egm-sent-list {
    border: 1px solid #e0e0e0;
    border-radius: 0.35rem;
    background: #fff;
    overflow: hidden;
}
.egm-sent-row {
    display: grid;
    grid-template-columns: minmax(120px, 180px) minmax(0, 1fr) auto;
    align-items: center;
    gap: 0.5rem 0.85rem;
    padding: 0.7rem 0.85rem;
    border-bottom: 1px solid #f1f3f4;
    background: #fff;
    cursor: default;
    position: relative;
    transition: background 0.12s ease;
    min-height: 52px;
}
.egm-sent-row:last-child { border-bottom: 0; }
.egm-sent-row:hover { background: #f2f6fc; }
.egm-sent-to {
    font-size: 0.875rem;
    color: #202124;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.egm-sent-to .egm-to-prefix {
    color: #5f6368;
    font-weight: 400;
}
.egm-sent-main { min-width: 0; }
.egm-sent-line {
    font-size: 0.875rem;
    color: #202124;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.egm-sent-line .egm-subject { font-weight: 600; }
.egm-sent-line .egm-sep { color: #5f6368; margin: 0 0.25rem; }
.egm-sent-line .egm-snippet { color: #5f6368; font-weight: 400; }
.egm-sent-atts {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.35rem;
}
.egm-sent-att {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    max-width: 180px;
    padding: 0.15rem 0.55rem 0.15rem 0.35rem;
    border: 1px solid #dadce0;
    border-radius: 999px;
    background: #fff;
    font-size: 0.75rem;
    color: #3c4043;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-decoration: none;
}
a.egm-sent-att {
    cursor: pointer;
}
a.egm-sent-att:hover {
    background: #f8fafc;
    border-color: #c5221f;
    color: #c5221f;
    text-decoration: none;
}
.egm-sent-att i { color: #ea4335; flex-shrink: 0; }
.egm-sent-att.is-disabled {
    opacity: 0.75;
    cursor: default;
    pointer-events: none;
}
.egm-sent-meta {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    min-width: 88px;
}
.egm-sent-time {
    font-size: 0.8rem;
    color: #5f6368;
    font-weight: 500;
    white-space: nowrap;
}
.egm-sent-actions {
    display: none;
    align-items: center;
    gap: 0.15rem;
}
.egm-sent-row:hover .egm-sent-time { display: none; }
.egm-sent-row:hover .egm-sent-actions { display: inline-flex; }
.egm-sent-actions button {
    border: 0;
    background: transparent;
    color: #5f6368;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.egm-sent-actions button:hover { background: #e8eaed; color: #202124; }
.egm-sent-actions .egm-view-detail-btn:hover {
    background: #fce8e6;
    color: #c5221f;
}
.egm-sent-empty {
    padding: 1.25rem 1rem;
    color: #5f6368;
    font-size: 0.9rem;
}

/* Email detail (Gmail-like read view) */
.egm-detail-modal .modal-dialog {
    max-width: min(860px, 96vw);
}
.egm-detail-modal .modal-content {
    border: 0;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 18px 48px rgba(15, 23, 42, 0.22);
}
.egm-detail-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid #e8eaed;
    background: #fff;
}
.egm-detail-top h5 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 500;
    color: #202124;
    line-height: 1.35;
}
.egm-detail-top .btn-close { opacity: 0.65; }
.egm-detail-sender {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem 1.1rem 0.65rem;
}
.egm-detail-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ea4335;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}
.egm-detail-sender-meta { min-width: 0; flex: 1; }
.egm-detail-sender-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #202124;
}
.egm-detail-sender-name span {
    font-weight: 400;
    color: #5f6368;
    font-size: 0.82rem;
}
.egm-detail-to-line {
    font-size: 0.82rem;
    color: #5f6368;
    margin-top: 0.15rem;
}
.egm-detail-when {
    font-size: 0.8rem;
    color: #5f6368;
    white-space: nowrap;
    padding-top: 0.15rem;
}
.egm-detail-body {
    padding: 0.5rem 1.1rem 1.25rem 4.1rem;
    font-size: 0.95rem;
    color: #202124;
    white-space: pre-wrap;
    line-height: 1.6;
    min-height: 140px;
}
.egm-detail-atts {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    padding: 0 1.1rem 1.25rem 4.1rem;
}
.egm-detail-atts .egm-sent-att {
    max-width: 260px;
    padding: 0.35rem 0.7rem 0.35rem 0.45rem;
    font-size: 0.8rem;
}
@media (max-width: 768px) {
    .egm-sent-row {
        grid-template-columns: 1fr auto;
        grid-template-areas:
            "to meta"
            "main meta";
    }
    .egm-sent-to { grid-area: to; min-width: 0; }
    .egm-sent-main { grid-area: main; }
    .egm-sent-meta { grid-area: meta; align-self: start; }
    .egm-detail-body,
    .egm-detail-atts { padding-left: 1.1rem; }
}

.egm-modal .modal-dialog {
    max-width: min(1180px, 96vw);
    width: 96vw;
    margin: 1rem auto;
}
.egm-modal .modal-content {
    border: 0;
    border-radius: 0.75rem;
    overflow: visible;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
    min-height: min(92vh, 900px);
}
.egm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #c5221f;
    color: #fff;
    padding: 0.55rem 0.85rem;
}
.egm-header .egm-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: #ea4335;
    border-radius: 0.4rem 0.4rem 0 0;
    padding: 0.35rem 0.75rem;
    font-size: 0.9rem;
    font-weight: 600;
}
.egm-header .egm-actions button {
    border: 0;
    background: transparent;
    color: #fff;
    opacity: 0.9;
    width: 30px;
    height: 30px;
}
.egm-from-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    background: #fff;
}
.egm-from-user {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
}
.egm-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #f472b6;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}
.egm-from-meta {
    font-size: 0.9rem;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.egm-from-meta span { color: #64748b; }
.egm-notice {
    background: #fff8db;
    color: #92400e;
    font-size: 0.8rem;
    padding: 0.4rem 1rem;
    border-bottom: 1px solid #fde68a;
}
.egm-row {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.55rem 1rem;
    border-bottom: 1px solid #eef2f7;
    position: relative;
    overflow: visible;
}
.egm-row .field { flex: 1; min-width: 0; position: relative; overflow: visible; }
.egm-row .label {
    width: 42px;
    flex-shrink: 0;
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 600;
    padding-top: 0.35rem;
}
.egm-row-extra {
    display: flex;
    gap: 0.75rem;
    padding-top: 0.35rem;
}
.egm-row-extra a {
    font-size: 0.82rem;
    color: #ea4335;
    text-decoration: none;
    font-weight: 600;
}
.egm-chip-box {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: center;
    min-height: 32px;
}
.egm-chip-box input {
    border: 0;
    outline: none;
    flex: 1;
    min-width: 140px;
    font-size: 0.9rem;
    background: transparent;
    padding: 0.25rem 0;
}
.egm-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-radius: 999px;
    padding: 0.18rem 0.55rem;
    font-size: 0.8rem;
    max-width: 100%;
}
.egm-chip i { color: #64748b; }
.egm-chip-remove {
    border: 0;
    background: transparent;
    color: #64748b;
    line-height: 1;
    padding: 0;
}
.egm-suggest {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 2px);
    z-index: 1080;
    background: #fff;
    border: 1px solid #dbe3ee;
    border-radius: 0.5rem;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
    max-height: 280px;
    overflow-y: auto;
    display: none;
}
.egm-suggest.is-open { display: block; }
.egm-suggest-item {
    display: block;
    width: 100%;
    text-align: left;
    border: 0;
    background: #fff;
    padding: 0.55rem 0.75rem;
    font-size: 0.86rem;
}
.egm-suggest-item:hover,
.egm-suggest-item.is-active {
    background: #fef2f2;
}
.egm-suggest-item .email {
    color: #64748b;
    font-size: 0.78rem;
}
.egm-subject-input {
    width: 100%;
    border: 0;
    outline: none;
    font-size: 0.95rem;
    padding: 0.3rem 0;
    background: transparent;
}
.egm-editor-wrap {
    padding: 0.65rem 1rem 0.35rem;
    min-height: 420px;
    width: 100%;
}
.egm-editor-wrap .crm-quill-wrap {
    width: 100%;
}
.egm-files {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    padding: 0 1rem 0.75rem;
}
.egm-file-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.45rem;
    padding: 0.3rem 0.55rem;
    font-size: 0.8rem;
    color: #334155;
}
.egm-file-chip button {
    border: 0;
    background: transparent;
    color: #94a3b8;
    line-height: 1;
}
.egm-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
}
.egm-attach-btn {
    width: 38px;
    height: 38px;
    border-radius: 0.45rem;
    border: 0;
    background: #ea4335;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.egm-send-btn {
    min-width: 110px;
    font-weight: 600;
    background: #ea4335 !important;
    border-color: #ea4335 !important;
}
.egm-send-btn:hover {
    background: #d93025 !important;
    border-color: #d93025 !important;
}
.egm-connect-box {
    text-align: center;
    padding: 2rem 1rem;
}
.egm-connect-box .icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 0.75rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(234, 67, 53, 0.12);
    color: #ea4335;
    font-size: 1.5rem;
}
</style>

<div class="egm-wrap"
     id="entityGmailWrap"
     data-compose-url="{{ $composeUrl }}"
     data-send-url="{{ $sendUrl }}"
     data-connect-url="{{ $connectUrl }}"
     data-recipient-url="{{ $recipientSearchUrl }}"
     data-crm-email="{{ $userEmail }}"
     data-default-to-email="{{ $defaultToEmail }}"
     data-default-to-name="{{ $defaultToName }}"
     data-default-subject="{{ $entityTitle }}">
<script type="application/json" id="egmDefaultBodyJson">@json($defaultBody)</script>
<script type="application/json" id="egmEditorStyleJson">@json($composeEditorStyle)</script>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h6 class="mb-1">Email</h6>
            <div class="text-muted small">Send emails to clients via your connected Gmail account.</div>
        </div>
        @if($gmailEnabled)
            <button type="button" class="btn btn-danger egm-compose-btn" id="egmOpenComposeBtn">
                <i class="bi bi-pencil-square"></i> Compose Email
            </button>
        @endif
    </div>

    @if(!$gmailEnabled)
        <div class="alert alert-secondary py-2 small mb-3">
            You do not have permission to send emails from this page, or this financial year is read-only.
        </div>
    @endif

    <div id="egmInlineAlert"></div>

    <hr>
    <h6 class="mb-3">Sent Emails</h6>
    <div class="egm-sent-list">
        @forelse($emailNotes as $note)
            @php
                $raw = preg_replace('/^\[Gmail Email\]\s*/', '', (string) $note->content);
                $toLine = '';
                $ccLine = '';
                $bccLine = '';
                $subject = '';
                $attachments = [];
                $bodyLines = [];
                foreach (preg_split("/\r\n|\n|\r/", $raw) as $line) {
                    if ($toLine === '' && preg_match('/^To:\s*(.+)$/i', $line, $m)) {
                        $toLine = trim($m[1]);
                        continue;
                    }
                    if ($ccLine === '' && preg_match('/^Cc:\s*(.+)$/i', $line, $m)) {
                        $ccLine = trim($m[1]);
                        continue;
                    }
                    if ($bccLine === '' && preg_match('/^Bcc:\s*(.+)$/i', $line, $m)) {
                        $bccLine = trim($m[1]);
                        continue;
                    }
                    if ($subject === '' && preg_match('/^Subject:\s*(.+)$/i', $line, $m)) {
                        $subject = trim($m[1]);
                        continue;
                    }
                    if (empty($attachments) && preg_match('/^Attachments:\s*(.+)$/i', $line, $m)) {
                        foreach (array_map('trim', explode(',', $m[1])) as $token) {
                            if ($token === '') {
                                continue;
                            }
                            $name = $token;
                            $url = null;
                            if (preg_match('/^(.+)\|(\d+)$/', $token, $tm)) {
                                $name = trim($tm[1]);
                                $fileId = (int) $tm[2];
                                $file = \App\Models\File::find($fileId);
                                if ($file && (int) $file->related_id === (int) $entityId && (string) $file->related_type === (string) $entityType) {
                                    $url = $file->downloadUrl();
                                }
                            } else {
                                // Legacy notes: try match saved gmail attachment by filename
                                $file = \App\Models\File::query()
                                    ->where('related_type', $entityType)
                                    ->where('related_id', $entityId)
                                    ->where('file_name', $name)
                                    ->where('file_path', 'like', '%gmail_attachments%')
                                    ->orderByDesc('id')
                                    ->first();
                                if ($file) {
                                    $url = $file->downloadUrl();
                                }
                            }
                            $attachments[] = ['name' => $name, 'url' => $url];
                        }
                        continue;
                    }
                    if (trim($line) !== '') {
                        $bodyLines[] = trim($line);
                    }
                }
                $snippet = trim(implode(' ', $bodyLines));
                $when = $note->noted_at ? \Carbon\Carbon::parse($note->noted_at) : ($note->created_at ? \Carbon\Carbon::parse($note->created_at) : null);
                if ($when) {
                    $timeLabel = $when->isToday()
                        ? $when->format('g:i A')
                        : ($when->isCurrentYear() ? $when->format('M j') : $when->format('M j, Y'));
                    $fullWhen = $when->format('g:i A \o\n M j, Y');
                } else {
                    $timeLabel = '-';
                    $fullWhen = '-';
                }
                $toDisplay = $toLine !== '' ? $toLine : '—';
                $toShort = collect(array_map('trim', explode(',', $toDisplay)))
                    ->filter()
                    ->map(function ($addr) {
                        if (str_contains($addr, '@')) {
                            $local = strstr($addr, '@', true);
                            return $local !== false && $local !== '' ? $local : $addr;
                        }
                        return $addr;
                    })
                    ->implode(', ');
                $sentBy = $note->created_by ? optional(\App\Models\User::find($note->created_by))->name : 'Unknown';
                $sentByInitial = strtoupper(mb_substr($sentBy !== '' ? $sentBy : 'U', 0, 1));
            @endphp
            <div class="egm-sent-row"
                 data-egm-detail="1"
                 data-to="{{ $toDisplay }}"
                 data-cc="{{ $ccLine }}"
                 data-bcc="{{ $bccLine }}"
                 data-subject="{{ $subject !== '' ? $subject : '(no subject)' }}"
                 data-body="{{ $snippet }}"
                 data-attachments='@json($attachments)'
                 data-when="{{ $fullWhen }}"
                 data-from="{{ $sentBy }}"
                 data-from-initial="{{ $sentByInitial }}">
                <div class="egm-sent-to" title="To: {{ $toDisplay }}">
                    <span class="egm-to-prefix">To:</span> {{ $toShort }}
                </div>
                <div class="egm-sent-main">
                    <div class="egm-sent-line">
                        <span class="egm-subject">{{ $subject !== '' ? $subject : '(no subject)' }}</span>
                        @if($snippet !== '')
                            <span class="egm-sep">—</span>
                            <span class="egm-snippet">{{ $snippet }}</span>
                        @endif
                    </div>
                    @if(!empty($attachments))
                        <div class="egm-sent-atts">
                            @foreach($attachments as $att)
                                @php
                                    $attName = is_array($att) ? ($att['name'] ?? '') : (string) $att;
                                    $attUrl = is_array($att) ? ($att['url'] ?? null) : null;
                                    $ext = strtolower(pathinfo($attName, PATHINFO_EXTENSION));
                                    $icon = in_array($ext, ['pdf'], true) ? 'bi-file-earmark-pdf-fill'
                                        : (in_array($ext, ['doc', 'docx'], true) ? 'bi-file-earmark-word-fill'
                                        : (in_array($ext, ['xls', 'xlsx', 'csv'], true) ? 'bi-file-earmark-excel-fill'
                                        : (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true) ? 'bi-file-earmark-image-fill'
                                        : 'bi-paperclip')));
                                    $attShort = \Illuminate\Support\Str::limit($attName, 22);
                                @endphp
                                @if($attUrl)
                                    <a class="egm-sent-att" href="{{ $attUrl }}" target="_blank" rel="noopener" title="Open {{ $attName }}" onclick="event.stopPropagation();">
                                        <i class="bi {{ $icon }}"></i>
                                        <span>{{ $attShort }}</span>
                                    </a>
                                @else
                                    <span class="egm-sent-att is-disabled" title="{{ $attName }} (file not available)">
                                        <i class="bi {{ $icon }}"></i>
                                        <span>{{ $attShort }}</span>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="egm-sent-meta">
                    <span class="egm-sent-time">{{ $timeLabel }}</span>
                    <div class="egm-sent-actions">
                        <button type="button" class="egm-view-detail-btn" title="View email details" aria-label="View email details">
                            <i class="bi bi-envelope"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="egm-sent-empty">No emails sent yet.</div>
        @endforelse
    </div>
</div>

{{-- Email detail view --}}
<div class="modal fade egm-detail-modal" id="entityGmailDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="egm-detail-top">
                <h5 id="egmDetailSubject">Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="egm-detail-sender">
                <div class="egm-detail-avatar" id="egmDetailAvatar">U</div>
                <div class="egm-detail-sender-meta">
                    <div class="egm-detail-sender-name">
                        <span id="egmDetailFrom">Unknown</span>
                        <span id="egmDetailFromHint"></span>
                    </div>
                    <div class="egm-detail-to-line" id="egmDetailToLine">to —</div>
                </div>
                <div class="egm-detail-when" id="egmDetailWhen">—</div>
            </div>
            <div class="egm-detail-body" id="egmDetailBody"></div>
            <div class="egm-detail-atts d-none" id="egmDetailAtts"></div>
        </div>
    </div>
</div>

<script>
(function () {
    function openEgmEmailDetail(row) {
        if (!row) return;
        const modalEl = document.getElementById('entityGmailDetailModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;

        const subject = row.dataset.subject || '(no subject)';
        const to = row.dataset.to || '—';
        const cc = (row.dataset.cc || '').trim();
        const bcc = (row.dataset.bcc || '').trim();
        const body = row.dataset.body || '';
        const when = row.dataset.when || '—';
        const from = row.dataset.from || 'Unknown';
        const initial = row.dataset.fromInitial || (from.charAt(0) || 'U').toUpperCase();
        const attachments = (function () {
            try {
                const raw = row.dataset.attachments || '[]';
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [];
            } catch (err) {
                return [];
            }
        })();

        document.getElementById('egmDetailSubject').textContent = subject;
        document.getElementById('egmDetailFrom').textContent = from;
        document.getElementById('egmDetailAvatar').textContent = initial;
        document.getElementById('egmDetailWhen').textContent = when;
        document.getElementById('egmDetailBody').textContent = body !== '' ? body : 'No message body.';

        let toLine = 'to ' + to;
        if (cc) toLine += '  ·  Cc: ' + cc;
        if (bcc) toLine += '  ·  Bcc: ' + bcc;
        document.getElementById('egmDetailToLine').textContent = toLine;

        const attsWrap = document.getElementById('egmDetailAtts');
        attsWrap.innerHTML = '';
        if (attachments.length) {
            attsWrap.classList.remove('d-none');
            attachments.forEach(function (att) {
                const name = (att && att.name) ? att.name : String(att || '');
                const url = (att && att.url) ? att.url : null;
                const chip = url ? document.createElement('a') : document.createElement('span');
                chip.className = 'egm-sent-att' + (url ? '' : ' is-disabled');
                chip.title = url ? ('Open ' + name) : (name + ' (file not available)');
                if (url) {
                    chip.href = url;
                    chip.target = '_blank';
                    chip.rel = 'noopener';
                }
                const icon = document.createElement('i');
                icon.className = 'bi bi-file-earmark-pdf-fill';
                const lower = name.toLowerCase();
                if (lower.endsWith('.doc') || lower.endsWith('.docx')) icon.className = 'bi bi-file-earmark-word-fill';
                else if (lower.endsWith('.xls') || lower.endsWith('.xlsx') || lower.endsWith('.csv')) icon.className = 'bi bi-file-earmark-excel-fill';
                else if (!lower.endsWith('.pdf')) icon.className = 'bi bi-paperclip';
                const label = document.createElement('span');
                label.textContent = name;
                chip.appendChild(icon);
                chip.appendChild(label);
                attsWrap.appendChild(chip);
            });
        } else {
            attsWrap.classList.add('d-none');
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.egm-view-detail-btn');
        if (!btn) return;
        const row = btn.closest('.egm-sent-row[data-egm-detail]');
        if (!row) return;
        e.preventDefault();
        e.stopPropagation();
        openEgmEmailDetail(row);
    });
})();
</script>

@if($gmailEnabled)
{{-- Compose Email Modal --}}
<div class="modal fade egm-modal" id="entityGmailComposeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="egm-header">
                <div class="egm-tab">
                    New Message
                    <button type="button" data-bs-dismiss="modal" aria-label="Close" title="Close tab" style="border:0;background:transparent;color:#fff;line-height:1;">&times;</button>
                </div>
                <div class="egm-actions">
                    <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>

            <div id="egmConnectPanel" class="egm-connect-box d-none">
                <div class="icon"><i class="bi bi-google"></i></div>
                <h6 class="fw-semibold mb-2">Connect your Gmail account</h6>
                <p class="text-muted small mb-2">Authorize Gmail once to send emails to clients from CRM.</p>
                <p class="text-muted small mb-3">
                    @if($userEmail !== '')
                        Use your CRM login email <strong>{{ $userEmail }}</strong>.
                    @endif
                </p>
                <div id="egmConnectAlert" class="mb-3"></div>
                <button type="button" class="btn btn-danger btn-sm px-4" id="egmConnectBtn"
                        data-connect-url="{{ $connectUrl }}" data-crm-email="{{ $userEmail }}">
                    <i class="bi bi-google me-1"></i> Connect Gmail
                </button>
            </div>

            <div id="egmComposePanel">
                <div class="egm-from-bar">
                    <div class="egm-from-user">
                        <div class="egm-avatar">{{ $userInitial }}</div>
                        <div class="egm-from-meta">
                            <strong id="egmFromName">{{ $userName }}</strong>
                            <span id="egmFromAddress">@if($userEmail)&lt;{{ $userEmail }}&gt;@endif</span>
                        </div>
                    </div>
                </div>

                <div class="egm-notice">
                    Emails are sent through your connected Gmail account. Use To / Cc / Bcc and attach files as needed.
                </div>

                <div id="egmModalAlert" class="px-3 pt-2"></div>

                <div class="egm-row">
                    <div class="label">To</div>
                    <div class="field">
                        <div class="egm-chip-box" data-field="to" id="egmToWrap">
                            @if($defaultToEmail)
                                <span class="egm-chip" data-email="{{ $defaultToEmail }}">
                                    <i class="bi bi-person-fill"></i>
                                    <span>{{ $defaultToName !== '' ? $defaultToName : $defaultToEmail }}</span>
                                    <button type="button" class="egm-chip-remove" aria-label="Remove">&times;</button>
                                </span>
                            @endif
                            <input type="text" id="egmToInput" placeholder="Enter name or email" autocomplete="off">
                        </div>
                        <div class="egm-suggest" id="egmToSuggest"></div>
                    </div>
                    <div class="egm-row-extra">
                        <a href="#" id="egmToggleCc">Cc</a>
                        <a href="#" id="egmToggleBcc">Bcc</a>
                    </div>
                </div>

                <div class="egm-row d-none" id="egmCcRow">
                    <div class="label">Cc</div>
                    <div class="field">
                        <div class="egm-chip-box" data-field="cc" id="egmCcWrap">
                            <input type="text" id="egmCcInput" placeholder="Enter name or email" autocomplete="off">
                        </div>
                        <div class="egm-suggest" id="egmCcSuggest"></div>
                    </div>
                </div>

                <div class="egm-row d-none" id="egmBccRow">
                    <div class="label">Bcc</div>
                    <div class="field">
                        <div class="egm-chip-box" data-field="bcc" id="egmBccWrap">
                            <input type="text" id="egmBccInput" placeholder="Enter name or email" autocomplete="off">
                        </div>
                        <div class="egm-suggest" id="egmBccSuggest"></div>
                    </div>
                </div>

                <div class="egm-row">
                    <div class="label">Subject</div>
                    <div class="field">
                        <input type="text" class="egm-subject-input" id="egmSubject" value="{{ $entityTitle }}" placeholder="Subject">
                    </div>
                </div>

                <div class="egm-editor-wrap">
                    <textarea id="egm_body" class="d-none"></textarea>
                </div>

                <div class="egm-files" id="egmFilesList"></div>

                <div class="egm-footer">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="egm-attach-btn" id="egmAttachBtn" title="Attach files">
                            <i class="bi bi-paperclip"></i>
                        </button>
                        <input type="file" id="egmAttachInput" class="d-none" multiple
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.txt,.csv,.zip">
                        <span class="small text-muted" id="egmAttachHint">Attach files (max 5, 10MB each)</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger btn-sm egm-send-btn" id="egmSendBtn">
                            <span id="egmSendBtnText"><i class="bi bi-send me-1"></i> Send</span>
                            <span id="egmSendSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('partials.quill_editor_assets')
<script>
(function () {
    const wrap = document.getElementById('entityGmailWrap');
    if (!wrap || wrap.dataset.bound === '1') return;
    wrap.dataset.bound = '1';

    const composeUrl = wrap.dataset.composeUrl || '';
    const sendUrl = wrap.dataset.sendUrl || '';
    const recipientUrl = wrap.dataset.recipientUrl || '';
    const loginEmail = wrap.dataset.crmEmail || '';
    const defaultToEmail = wrap.dataset.defaultToEmail || '';
    const defaultToName = wrap.dataset.defaultToName || '';
    const defaultSubject = wrap.dataset.defaultSubject || '';
    let defaultBody = '';
    try {
        const bodyNode = document.getElementById('egmDefaultBodyJson');
        defaultBody = bodyNode ? JSON.parse(bodyNode.textContent || '""') : '';
    } catch (e) {
        defaultBody = '';
    }

    let pendingBody = defaultBody;
    let attachmentFiles = [];
    const suggestTimers = {};

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function inlineAlert(html) {
        const el = document.getElementById('egmInlineAlert');
        if (el) el.innerHTML = html || '';
    }

    function modalAlert(html) {
        const el = document.getElementById('egmModalAlert');
        if (el) el.innerHTML = html || '';
    }

    function connectAlert(html) {
        const el = document.getElementById('egmConnectAlert');
        if (el) el.innerHTML = html || '';
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function setConnectState(connected, connectUrl, crmEmail) {
        const connectPanel = document.getElementById('egmConnectPanel');
        const composePanel = document.getElementById('egmComposePanel');
        const sendBtn = document.getElementById('egmSendBtn');
        const connectBtn = document.getElementById('egmConnectBtn');

        if (connectPanel) connectPanel.classList.toggle('d-none', connected);
        if (composePanel) composePanel.classList.toggle('d-none', !connected);
        if (sendBtn) sendBtn.disabled = !connected;
        if (connectBtn && connectUrl) connectBtn.setAttribute('data-connect-url', connectUrl);
        if (connectBtn && crmEmail) connectBtn.setAttribute('data-crm-email', crmEmail);
        if (connected) connectAlert('');
    }

    function setBody(content) {
        pendingBody = content || '';
        if (window.CrmQuill && CrmQuill.get('egm_body')) {
            CrmQuill.setHtml('egm_body', pendingBody);
        } else {
            const el = document.getElementById('egm_body');
            if (el) el.value = pendingBody;
        }
    }

    function getBody() {
        if (window.CrmQuill && CrmQuill.get('egm_body')) {
            return CrmQuill.getHtml('egm_body');
        }
        return document.getElementById('egm_body')?.value || '';
    }

    function initEditor() {
        if (!window.CrmQuill) return;
        let contentStyle = '';
        try {
            const node = document.getElementById('egmEditorStyleJson');
            contentStyle = node ? JSON.parse(node.textContent || '""') : '';
        } catch (e) {
            contentStyle = '';
        }
        CrmQuill.create('egm_body', {
            sizeClass: 'crm-quill-tall',
            html: pendingBody,
            contentStyle: contentStyle,
            placeholder: 'Write your email...'
        });
    }

    function chipWrapFor(field) {
        return document.getElementById('egm' + field.charAt(0).toUpperCase() + field.slice(1) + 'Wrap');
    }

    function suggestBoxFor(field) {
        return document.getElementById('egm' + field.charAt(0).toUpperCase() + field.slice(1) + 'Suggest');
    }

    function inputFor(field) {
        return document.getElementById('egm' + field.charAt(0).toUpperCase() + field.slice(1) + 'Input');
    }

    function addChip(field, email, label) {
        const chipWrap = chipWrapFor(field);
        const input = inputFor(field);
        if (!chipWrap || !email || !isValidEmail(email)) return;
        if (Array.from(chipWrap.querySelectorAll('.egm-chip')).some(function (chip) {
            return String(chip.dataset.email || '').toLowerCase() === String(email).toLowerCase();
        })) return;

        const chip = document.createElement('span');
        chip.className = 'egm-chip';
        chip.dataset.email = email.toLowerCase();
        chip.innerHTML = '<i class="bi bi-person-fill"></i><span>' + escapeHtml(label || email) + '</span>'
            + '<button type="button" class="egm-chip-remove" aria-label="Remove">&times;</button>';
        chipWrap.insertBefore(chip, input);
    }

    function collectEmails(field) {
        const chipWrap = chipWrapFor(field);
        if (!chipWrap) return [];
        return Array.from(chipWrap.querySelectorAll('.egm-chip')).map(function (chip) {
            return chip.dataset.email || '';
        }).filter(Boolean);
    }

    function hideSuggest(field) {
        const box = suggestBoxFor(field);
        if (box) box.classList.remove('is-open');
    }

    function showSuggest(field, results) {
        const box = suggestBoxFor(field);
        if (!box) return;
        if (!results || !results.length) {
            box.innerHTML = '<div class="px-3 py-2 small text-muted">No matches</div>';
            box.classList.add('is-open');
            return;
        }
        box.innerHTML = results.map(function (item, idx) {
            var typeLabel = item.type === 'user' ? 'User' : (item.type === 'contact' ? 'Contact' : 'Email');
            var typeClass = item.type === 'user' ? 'bg-danger' : (item.type === 'contact' ? 'bg-secondary' : 'bg-dark');
            return '<button type="button" class="egm-suggest-item' + (idx === 0 ? ' is-active' : '') + '" data-email="'
                + escapeHtml(item.email) + '" data-label="' + escapeHtml(item.name || item.label || item.email) + '">'
                + '<div class="d-flex justify-content-between align-items-center gap-2">'
                + '<div class="fw-semibold">' + escapeHtml(item.name || item.email) + '</div>'
                + '<span class="badge ' + typeClass + '" style="font-size:0.65rem;">' + typeLabel + '</span>'
                + '</div>'
                + '<div class="email">' + escapeHtml(item.email) + '</div>'
                + '</button>';
        }).join('');
        box.classList.add('is-open');
    }

    function searchRecipients(field, query) {
        if (!recipientUrl) return;
        const q = String(query || '').trim();
        if (!q) {
            hideSuggest(field);
            return;
        }
        const url = new URL(recipientUrl, window.location.origin);
        url.searchParams.set('q', q);
        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            showSuggest(field, data.results || []);
        })
        .catch(function () {
            showSuggest(field, []);
        });
    }

    function bindRecipientField(field) {
        const input = inputFor(field);
        const chipWrap = chipWrapFor(field);
        const suggest = suggestBoxFor(field);
        if (!input || !chipWrap || input.dataset.bound === '1') return;
        input.dataset.bound = '1';

        input.addEventListener('input', function () {
            clearTimeout(suggestTimers[field]);
            suggestTimers[field] = setTimeout(function () {
                searchRecipients(field, input.value);
            }, 120);
        });
        input.addEventListener('focus', function () {
            if (String(input.value || '').trim()) {
                searchRecipients(field, input.value);
            }
        });

        input.addEventListener('keydown', function (e) {
            const active = suggest ? suggest.querySelector('.egm-suggest-item.is-active') : null;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                if (!suggest || !suggest.classList.contains('is-open')) return;
                e.preventDefault();
                const items = Array.from(suggest.querySelectorAll('.egm-suggest-item'));
                if (!items.length) return;
                let idx = items.findIndex(function (el) { return el.classList.contains('is-active'); });
                items.forEach(function (el) { el.classList.remove('is-active'); });
                if (e.key === 'ArrowDown') idx = (idx + 1) % items.length;
                else idx = (idx <= 0 ? items.length - 1 : idx - 1);
                items[idx].classList.add('is-active');
                return;
            }

            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                if (active) {
                    addChip(field, active.dataset.email, active.dataset.label);
                    input.value = '';
                    hideSuggest(field);
                    return;
                }
                const value = input.value.trim().replace(/[,;]+$/, '');
                if (value && isValidEmail(value)) {
                    addChip(field, value, value);
                    input.value = '';
                    hideSuggest(field);
                }
            }

            if (e.key === 'Escape') hideSuggest(field);
        });

        chipWrap.addEventListener('click', function (e) {
            if (e.target.classList.contains('egm-chip-remove')) {
                e.target.closest('.egm-chip')?.remove();
            } else {
                input.focus();
            }
        });

        if (suggest) {
            suggest.addEventListener('mousedown', function (e) {
                const item = e.target.closest('.egm-suggest-item');
                if (!item) return;
                e.preventDefault();
                addChip(field, item.dataset.email, item.dataset.label);
                input.value = '';
                hideSuggest(field);
            });
        }

        document.addEventListener('click', function (e) {
            if (!chipWrap.contains(e.target) && !(suggest && suggest.contains(e.target))) {
                hideSuggest(field);
            }
        });
    }

    function renderAttachments() {
        const list = document.getElementById('egmFilesList');
        const hint = document.getElementById('egmAttachHint');
        if (!list) return;
        list.innerHTML = '';
        attachmentFiles.forEach(function (file, idx) {
            const chip = document.createElement('span');
            chip.className = 'egm-file-chip';
            chip.innerHTML = '<i class="bi bi-paperclip"></i><span>' + escapeHtml(file.name) + '</span>'
                + '<button type="button" data-idx="' + idx + '" aria-label="Remove">&times;</button>';
            list.appendChild(chip);
        });
        if (hint) {
            hint.textContent = attachmentFiles.length
                ? (attachmentFiles.length + ' file(s) attached')
                : 'Attach files (max 5, 10MB each)';
        }
    }

    function resetComposer() {
        ['to', 'cc', 'bcc'].forEach(function (field) {
            const chipWrap = chipWrapFor(field);
            if (!chipWrap) return;
            chipWrap.querySelectorAll('.egm-chip').forEach(function (chip) { chip.remove(); });
            hideSuggest(field);
            const input = inputFor(field);
            if (input) input.value = '';
        });

        if (defaultToEmail) addChip('to', defaultToEmail, defaultToName || defaultToEmail);
        document.getElementById('egmSubject').value = defaultSubject;
        document.getElementById('egmCcRow')?.classList.add('d-none');
        document.getElementById('egmBccRow')?.classList.add('d-none');
        attachmentFiles = [];
        renderAttachments();
        modalAlert('');
        setBody(defaultBody);
    }

    function loadComposerStatus(openModal) {
        if (!composeUrl) return;
        fetch(composeUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(async function (response) {
                const data = await response.json().catch(function () { return {}; });
                if (!response.ok || !data.success) {
                    inlineAlert('<div class="alert alert-danger py-2 small">' + escapeHtml(data.error || 'Unable to load Gmail status.') + '</div>');
                    return;
                }
                document.getElementById('egmFromName').textContent = data.from_name || '';
                const fromAddr = document.getElementById('egmFromAddress');
                if (fromAddr) fromAddr.textContent = data.from_address ? '<' + data.from_address + '>' : '';
                setConnectState(!!data.gmail_connected, data.connect_url, data.from_address || loginEmail);

                if (openModal) {
                    const modalEl = document.getElementById('entityGmailComposeModal');
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    setTimeout(initEditor, 50);
                    if (window.entityGmailPendingError) {
                        connectAlert('<div class="alert alert-danger py-2 small mb-0">' + escapeHtml(window.entityGmailPendingError) + '</div>');
                        window.entityGmailPendingError = null;
                    }
                }
            })
            .catch(function () {
                inlineAlert('<div class="alert alert-danger py-2 small">Network error while checking Gmail connection.</div>');
            });
    }

    document.getElementById('egmOpenComposeBtn')?.addEventListener('click', function () {
        resetComposer();
        loadComposerStatus(true);
    });

    document.getElementById('egmToggleCc')?.addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('egmCcRow')?.classList.toggle('d-none');
    });
    document.getElementById('egmToggleBcc')?.addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('egmBccRow')?.classList.toggle('d-none');
    });

    document.getElementById('egmAttachBtn')?.addEventListener('click', function () {
        document.getElementById('egmAttachInput')?.click();
    });

    document.getElementById('egmAttachInput')?.addEventListener('change', function (e) {
        const files = Array.from(e.target.files || []);
        files.forEach(function (file) {
            if (attachmentFiles.length >= 5) return;
            if (file.size > 10 * 1024 * 1024) {
                modalAlert('<div class="alert alert-warning py-2 small mb-0">"' + escapeHtml(file.name) + '" is larger than 10MB.</div>');
                return;
            }
            attachmentFiles.push(file);
        });
        e.target.value = '';
        renderAttachments();
    });

    document.getElementById('egmFilesList')?.addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-idx]');
        if (!btn) return;
        const idx = parseInt(btn.getAttribute('data-idx'), 10);
        if (!isNaN(idx)) {
            attachmentFiles.splice(idx, 1);
            renderAttachments();
        }
    });

    document.getElementById('egmConnectBtn')?.addEventListener('click', function () {
        const btn = this;
        const url = btn.getAttribute('data-connect-url') || '';
        const crmEmail = btn.getAttribute('data-crm-email') || loginEmail || '';
        if (!url) return;
        if (!crmEmail) {
            connectAlert('<div class="alert alert-danger py-2 small mb-0">Your CRM account does not have an email address.</div>');
            return;
        }
        const fire = (typeof Swal !== 'undefined' && Swal.fire)
            ? Swal.fire({
                title: 'Connect Gmail',
                html: 'You can connect only the Gmail account that matches your CRM login email:<br><strong>'
                    + escapeHtml(crmEmail) + '</strong>',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#ea4335',
                reverseButtons: true
            })
            : Promise.resolve({ isConfirmed: window.confirm('Connect Gmail using ' + crmEmail + '?') });
        fire.then(function (result) {
            if (result.isConfirmed) window.location.href = url;
        });
    });

    document.getElementById('egmSendBtn')?.addEventListener('click', function () {
        const toEmails = collectEmails('to');
        const ccEmails = collectEmails('cc');
        const bccEmails = collectEmails('bcc');
        const subject = (document.getElementById('egmSubject')?.value || '').trim();
        const body = (getBody() || '').trim();
        modalAlert('');

        if (!toEmails.length) {
            modalAlert('<div class="alert alert-warning py-2 small mb-0">Please add at least one recipient in To.</div>');
            return;
        }
        if (!subject || !body || body === '<p></p>' || body === '<p><br></p>') {
            modalAlert('<div class="alert alert-warning py-2 small mb-0">Subject and message are required.</div>');
            return;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('to', toEmails.join(', '));
        formData.append('cc', ccEmails.join(', '));
        formData.append('bcc', bccEmails.join(', '));
        formData.append('subject', subject);
        formData.append('body', body);
        attachmentFiles.forEach(function (file) {
            formData.append('attachments[]', file, file.name);
        });

        const btn = document.getElementById('egmSendBtn');
        const btnText = document.getElementById('egmSendBtnText');
        const spinner = document.getElementById('egmSendSpinner');
        btn.disabled = true;
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');

        fetch(sendUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData
        })
        .then(async function (response) {
            const data = await response.json().catch(function () { return {}; });
            btn.disabled = false;
            btnText.classList.remove('d-none');
            spinner.classList.add('d-none');

            if (data.needs_connect && data.connect_url) {
                setConnectState(false, data.connect_url, loginEmail);
                modalAlert('<div class="alert alert-warning py-2 small mb-0">' + escapeHtml(data.error || 'Connect Gmail to continue.') + '</div>');
                return;
            }

            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('entityGmailComposeModal'))?.hide();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Email sent',
                        text: data.message || 'Email sent via Gmail.',
                        confirmButtonColor: '#ea4335'
                    }).then(function () {
                        window.location.href = window.location.pathname + '?email_tab=1';
                    });
                } else {
                    window.location.href = window.location.pathname + '?email_tab=1';
                }
                return;
            }

            modalAlert('<div class="alert alert-danger py-2 small mb-0">' + escapeHtml(data.error || 'Failed to send via Gmail.') + '</div>');
        })
        .catch(function () {
            btn.disabled = false;
            btnText.classList.remove('d-none');
            spinner.classList.add('d-none');
            modalAlert('<div class="alert alert-danger py-2 small mb-0">Network error while sending via Gmail.</div>');
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        bindRecipientField('to');
        bindRecipientField('cc');
        bindRecipientField('bcc');

        const sessionError = @json(session('error'));
        if (sessionError) window.entityGmailPendingError = sessionError;

        const shouldOpen = @json(
            session('open_entity_gmail')
            || request()->boolean('open_entity_gmail')
            || request()->query('open_entity_gmail') === '1'
        );
        const stayOnEmailTab = @json(
            request()->boolean('email_tab')
            || request()->query('email_tab') === '1'
        );

        if (shouldOpen || stayOnEmailTab) {
            const tab = document.getElementById('email-tab');
            if (tab) tab.click();
        }

        if (shouldOpen) {
            setTimeout(function () {
                resetComposer();
                loadComposerStatus(true);
            }, 250);
        }
    });
})();
</script>
@endif
