<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\File;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Person;
use App\Models\User;
use App\Services\EmailComposeSettingsService;
use App\Services\GoogleGmailService;
use App\Support\SafeUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CrmEntityGmailController extends Controller
{
    public function recipientSearch(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 1) {
            return response()->json(['results' => []]);
        }

        $like = '%' . $q . '%';
        $results = collect();

        // users.name + users.email
        $users = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->where(function ($query) {
                $query->where('status', 1)->orWhereNull('status');
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'email']);

        foreach ($users as $user) {
            $name = trim((string) ($user->name ?? ''));
            $email = strtolower(trim((string) $user->email));
            if ($email === '') {
                continue;
            }
            $results->push([
                'id' => 'user-' . $user->id,
                'type' => 'user',
                'name' => $name !== '' ? $name : $email,
                'email' => $email,
                'label' => ($name !== '' ? $name : $email) . ' <' . $email . '>',
            ]);
        }

        // people.first_name + people.last_name + people.email
        $people = Person::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($query) use ($like, $q) {
                $query->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", [$like]);

                $parts = preg_split('/\s+/', $q) ?: [];
                if (count($parts) >= 2) {
                    $first = $parts[0];
                    $last = implode(' ', array_slice($parts, 1));
                    $query->orWhere(function ($inner) use ($first, $last) {
                        $inner->where('first_name', 'like', '%' . $first . '%')
                            ->where('last_name', 'like', '%' . $last . '%');
                    });
                }
            })
            ->orderBy('first_name')
            ->limit(15)
            ->get(['id', 'first_name', 'last_name', 'email']);

        foreach ($people as $person) {
            $firstName = trim((string) ($person->first_name ?? ''));
            $lastName = trim((string) ($person->last_name ?? ''));
            $name = trim($firstName . ' ' . $lastName);
            $email = strtolower(trim((string) $person->email));
            if ($email === '') {
                continue;
            }
            $results->push([
                'id' => 'contact-' . $person->id,
                'type' => 'contact',
                'name' => $name !== '' ? $name : $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'label' => ($name !== '' ? $name : $email) . ' <' . $email . '>',
            ]);
        }

        $results = $results
            ->unique(fn ($row) => $row['type'] . ':' . strtolower($row['email']))
            ->values()
            ->take(25);

        if (filter_var($q, FILTER_VALIDATE_EMAIL)) {
            $exists = $results->contains(fn ($row) => strtolower($row['email']) === strtolower($q));
            if (!$exists) {
                $results->prepend([
                    'id' => null,
                    'type' => 'email',
                    'name' => $q,
                    'email' => strtolower($q),
                    'label' => $q,
                ]);
            }
        }

        return response()->json(['results' => $results->values()]);
    }

    public function leadCompose(Request $request, $id, GoogleGmailService $gmailService)
    {
        return $this->compose($request, 'lead', (int) $id, $gmailService);
    }

    public function dealCompose(Request $request, $id, GoogleGmailService $gmailService)
    {
        return $this->compose($request, 'deal', (int) $id, $gmailService);
    }

    public function leadSend(Request $request, $id, GoogleGmailService $gmailService)
    {
        return $this->send($request, 'lead', (int) $id, $gmailService);
    }

    public function dealSend(Request $request, $id, GoogleGmailService $gmailService)
    {
        return $this->send($request, 'deal', (int) $id, $gmailService);
    }

    protected function compose(Request $request, string $relatedType, int $id, GoogleGmailService $gmailService)
    {
        $user = auth()->user();
        if (!$this->canSendGmail($user, $relatedType)) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }
        if (!\App\Support\RecordAccess::canAccessRelated($user, $relatedType, $id)) {
            return response()->json(['success' => false, 'error' => 'You do not have access to this record.'], 403);
        }

        if (empty($user->email)) {
            return response()->json([
                'success' => false,
                'error' => 'Your account does not have an email address. Update your profile first.',
            ], 422);
        }

        $entity = $this->findEntity($relatedType, $id);
        $person = $entity->person;
        $toEmail = trim((string) optional($person)->email);
        $toName = trim(
            (optional($person)->first_name ?? '') . ' ' . (optional($person)->last_name ?? '')
        );
        $title = (string) ($entity->title ?? 'Record');

        $showRoute = $relatedType === 'lead'
            ? route('leads.show', $id)
            : route('deals.show', $id);

        return response()->json([
            'success' => true,
            'related_type' => $relatedType,
            'related_id' => $id,
            'to_email' => $toEmail,
            'to_name' => $toName,
            'to_label' => $toName !== '' ? ($toName . ' (' . $toEmail . ')') : $toEmail,
            'subject' => $title,
            'body' => '<p>Dear ' . e($toName !== '' ? $toName : 'Customer') . ',</p><p></p><p>Best regards,<br>' . e($user->name ?: config('app.name')) . '</p>',
            'modal_title' => $title,
            'send_url' => $relatedType === 'lead'
                ? route('leads.send_gmail', $id)
                : route('deals.send_gmail', $id),
            'connect_url' => route('google.gmail.connect', [
                'return' => $showRoute . '?open_entity_gmail=1',
            ]),
            'gmail_connected' => $gmailService->userHasGmailAccess($user),
            'from_address' => $user->email,
            'from_name' => trim((string) ($user->name ?? '')) ?: config('app.name'),
            'crm_login_email' => $user->email,
        ]);
    }

    protected function send(Request $request, string $relatedType, int $id, GoogleGmailService $gmailService)
    {
        $user = auth()->user();
        if (!$this->canSendGmail($user, $relatedType)) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }
        if (!\App\Support\RecordAccess::canAccessRelated($user, $relatedType, $id)) {
            return response()->json(['success' => false, 'error' => 'You do not have access to this record.'], 403);
        }

        $showRoute = $relatedType === 'lead'
            ? route('leads.show', $id)
            : route('deals.show', $id);

        if (!$gmailService->userHasGmailAccess($user)) {
            return response()->json([
                'success' => false,
                'needs_connect' => true,
                'connect_url' => route('google.gmail.connect', [
                    'return' => $showRoute . '?open_entity_gmail=1',
                ]),
                'error' => 'Connect your Gmail account before sending. Use the same email as your CRM login (' . $user->email . ').',
            ], 422);
        }

        $validated = $request->validate([
            'to' => 'required|string|max:2000',
            'cc' => 'nullable|string|max:2000',
            'bcc' => 'nullable|string|max:2000',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:50000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,webp,txt,csv,zip',
        ]);

        $toList = $this->parseEmailList($validated['to']);
        if (empty($toList)) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter at least one valid recipient email address.',
            ], 422);
        }

        $ccList = $this->parseEmailList($validated['cc'] ?? '');
        $bccList = $this->parseEmailList($validated['bcc'] ?? '');
        $entity = $this->findEntity($relatedType, $id);

        $htmlBody = $validated['body'];
        if (strip_tags($htmlBody) === $htmlBody) {
            $htmlBody = nl2br(e($htmlBody));
        }
        $htmlBody = app(EmailComposeSettingsService::class)->finalizeOutgoingHtml($user, $htmlBody);

        $attachments = [];
        $pendingAttachmentSaves = []; // saved to disk after successful send
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }
                $binary = @file_get_contents($file->getRealPath());
                if ($binary === false || $binary === '') {
                    continue;
                }
                $extension = SafeUpload::documentExtension($file);
                if (!$extension) {
                    continue;
                }
                $filename = str_replace(',', '_', SafeUpload::displayName($file));
                $attachments[] = [
                    'filename' => $filename,
                    'mime' => $file->getMimeType() ?: 'application/octet-stream',
                    'content' => $binary,
                ];
                $pendingAttachmentSaves[] = [
                    'filename' => $filename,
                    'extension' => $extension,
                    'content' => $binary,
                ];
            }
        }

        try {
            $gmailService->sendMessage(
                $user,
                $toList,
                $ccList,
                $bccList,
                $validated['subject'],
                $htmlBody,
                $attachments
            );
        } catch (\Throwable $e) {
            $message = $e->getMessage() ?: 'Failed to send via Gmail.';
            $needsReconnect = str_contains(strtolower($message), 'insufficient')
                || str_contains(strtolower($message), 'permission')
                || str_contains(strtolower($message), 'gmail send permission')
                || str_contains(strtolower($message), 'reconnect');

            if ($needsReconnect) {
                try {
                    $gmailService->clearUserGmailTokens($user);
                } catch (\Throwable $ignored) {
                }

                return response()->json([
                    'success' => false,
                    'needs_connect' => true,
                    'connect_url' => route('google.gmail.connect', [
                        'return' => $showRoute . '?open_entity_gmail=1',
                    ]),
                    'error' => 'Gmail permission is missing or expired. Please reconnect Gmail and allow “Send email on your behalf”.',
                ], 422);
            }

            Log::error('Entity Gmail send failed', [
                'related_type' => $relatedType,
                'related_id' => $id,
                'from' => $user->email,
                'error' => $message,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send via Gmail. Please try again or reconnect Gmail.',
            ], 422);
        }

        $attachmentTokens = [];
        foreach ($pendingAttachmentSaves as $pending) {
            $filePath = File::storeContents($pending['content'], $relatedType, $id, $pending['extension']);

            $fileRecord = File::create([
                'file_type' => 'file upload',
                'file_path' => $filePath,
                'file_name' => $pending['filename'],
                'related_type' => $relatedType,
                'related_id' => $id,
                'uploaded_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $attachmentTokens[] = $pending['filename'] . '|' . $fileRecord->id;
        }

        $plainBody = trim(preg_replace('/\s+/', ' ', strip_tags($htmlBody)));
        $noteContent = '[Gmail Email]' . "\n"
            . 'To: ' . implode(', ', $toList) . "\n"
            . (!empty($ccList) ? 'Cc: ' . implode(', ', $ccList) . "\n" : '')
            . (!empty($bccList) ? 'Bcc: ' . implode(', ', $bccList) . "\n" : '')
            . 'Subject: ' . $validated['subject'] . "\n"
            . (!empty($attachmentTokens) ? 'Attachments: ' . implode(', ', $attachmentTokens) . "\n" : '')
            . "\n"
            . Str::limit($plainBody, 800);

        Note::create([
            'content' => $noteContent,
            'related_type' => $relatedType,
            'related_id' => $entity->id,
            'noted_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email sent successfully via Gmail.',
        ]);
    }

    protected function canSendGmail($user, string $relatedType): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->canSendQuotationEmail()) {
            return true;
        }

        if (!\App\Models\Company::current()->isEmailSendEnabled()) {
            return false;
        }

        return $relatedType === 'lead'
            ? $user->hasCrmPermission('edit_crm_leads_guard')
            : $user->hasCrmPermission('edit_crm_deals_guard');
    }

    protected function findEntity(string $relatedType, int $id)
    {
        if ($relatedType === 'lead') {
            return Lead::with('person')->findOrFail($id);
        }

        return Deal::with('person')->findOrFail($id);
    }

    /**
     * @return array<int, string>
     */
    protected function parseEmailList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parts = preg_split('/[,;\s]+/', $value) ?: [];
        $emails = [];
        foreach ($parts as $part) {
            $email = strtolower(trim($part));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }
}
