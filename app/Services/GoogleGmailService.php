<?php

namespace App\Services;

use App\Models\User;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;

class GoogleGmailService
{
    public const GMAIL_SEND_SCOPE = 'https://www.googleapis.com/auth/gmail.send';

    public function getClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setAccessType('offline');
        $client->addScope(self::GMAIL_SEND_SCOPE);

        return $client;
    }

    public static function scopesIncludeGmailSend(array $scopes): bool
    {
        $joined = strtolower(implode(' ', $scopes));

        return str_contains($joined, 'gmail.send')
            || str_contains($joined, 'gmail.compose')
            || str_contains($joined, 'gmail.modify')
            || str_contains($joined, 'https://mail.google.com/');
    }

    public function accessTokenHasGmailSendScope(string $accessToken): bool
    {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 8]);
            $response = $client->get('https://oauth2.googleapis.com/tokeninfo', [
                'query' => ['access_token' => $accessToken],
                'http_errors' => false,
            ]);
            $data = json_decode((string) $response->getBody(), true) ?: [];
            $scope = (string) ($data['scope'] ?? '');

            return self::scopesIncludeGmailSend(preg_split('/\s+/', $scope) ?: []);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function storeOAuthTokensFromSocialite(User $user, $googleUser): array
    {
        $scopes = array_filter(array_map('trim', (array) ($googleUser->approvedScopes ?? [])));
        $refreshToken = $googleUser->refreshToken ?? null;
        $accessToken = $googleUser->token ?? null;
        $googleEmail = strtolower(trim((string) $googleUser->getEmail()));
        $crmEmail = strtolower(trim((string) $user->email));

        $payload = [
            'google_id' => $googleUser->getId(),
        ];
        if (!empty($accessToken)) {
            $payload['google_token'] = $accessToken;
        }
        if (!empty($refreshToken)) {
            $payload['google_refresh_token'] = $refreshToken;
        }

        if (count($payload) > 1) {
            $user->update($payload);
        }

        $gmailConnected = $crmEmail !== ''
            && $googleEmail !== ''
            && $crmEmail === $googleEmail
            && !empty($refreshToken)
            && !empty($accessToken)
            && self::scopesIncludeGmailSend($scopes)
            && $this->accessTokenHasGmailSendScope((string) $accessToken);

        return [
            'gmail_connected' => $gmailConnected,
            'scopes' => $scopes,
        ];
    }

    public function syncOAuthSessionFromSocialite($googleUser, bool $gmailConnected, array $scopes = []): void
    {
        session([
            'google_token' => $googleUser->token ?? null,
            'google_refresh_token' => $googleUser->refreshToken ?? null,
            'google_user_id' => $googleUser->getId(),
        ]);

        if ($gmailConnected) {
            session(['google_gmail_scopes' => implode(' ', $scopes)]);
        }
    }

    public function userHasGmailAccess(?User $user = null): bool
    {
        $user = $user ?: auth()->user();

        if (!$user || empty($user->google_refresh_token) || empty($user->google_token)) {
            return false;
        }

        return $this->accessTokenHasGmailSendScope((string) $user->google_token)
            || $this->refreshAndCheckGmailScope($user);
    }

    protected function refreshAndCheckGmailScope(User $user): bool
    {
        try {
            $client = $this->getClient();
            $newToken = $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
            if (isset($newToken['error']) || empty($newToken['access_token'])) {
                return false;
            }

            $user->update(['google_token' => $newToken['access_token']]);
            $scope = (string) ($newToken['scope'] ?? '');
            if ($scope !== '') {
                return self::scopesIncludeGmailSend(preg_split('/\s+/', $scope) ?: []);
            }

            return $this->accessTokenHasGmailSendScope($newToken['access_token']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function clearUserGmailTokens(User $user): void
    {
        $user->update([
            'google_token' => null,
            'google_refresh_token' => null,
        ]);
        session()->forget(['google_token', 'google_refresh_token', 'google_user_id', 'google_gmail_scopes']);
    }

    public function authorizeClientForUser(User $user): Google_Client
    {
        if (empty($user->google_refresh_token) || empty($user->google_token)) {
            throw new \RuntimeException('Gmail is not connected. Please connect your Google account first.');
        }

        $client = $this->getClient();
        $client->setAccessToken([
            'access_token'  => $user->google_token,
            'refresh_token' => $user->google_refresh_token,
        ]);

        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);

            if (isset($newToken['error'])) {
                $this->clearUserGmailTokens($user);
                throw new \RuntimeException($newToken['error_description'] ?? 'Gmail session expired. Please reconnect Google.');
            }

            $accessToken = $newToken['access_token'] ?? null;
            if ($accessToken) {
                $user->update(['google_token' => $accessToken]);
                $client->setAccessToken(array_merge($newToken, [
                    'refresh_token' => $user->google_refresh_token,
                ]));
            }
        }

        $token = $client->getAccessToken();
        $accessToken = is_array($token) ? ($token['access_token'] ?? null) : null;
        if (!$accessToken || !$this->accessTokenHasGmailSendScope($accessToken)) {
            $this->clearUserGmailTokens($user);
            throw new \RuntimeException(
                'Gmail send permission is missing. Open Settings → Gmail Account Setup and click Connect Gmail again (allow Send email).'
            );
        }

        return $client;
    }

    /**
     * @param  array<int, string>  $to
     * @param  array<int, string>  $cc
     * @param  array<int, string>  $bcc
     * @param  array<int, array{filename?: string, mime?: string, content: string, content_id?: string, inline?: bool}>  $attachments
     */
    public function sendMessage(
        User $user,
        array $to,
        array $cc,
        array $bcc,
        string $subject,
        string $htmlBody,
        array $attachments = []
    ): void {
        $client = $this->authorizeClientForUser($user);
        $fromEmail = trim((string) $user->email);
        $fromName = trim((string) ($user->name ?? '')) ?: config('app.name');

        if ($fromEmail === '') {
            throw new \RuntimeException('Your profile does not have an email address.');
        }

        $raw = $this->buildRawMessage($fromEmail, $fromName, $to, $cc, $bcc, $subject, $htmlBody, $attachments);

        $gmail = new Google_Service_Gmail($client);
        $message = new Google_Service_Gmail_Message();
        $message->setRaw($this->base64UrlEncode($raw));

        $gmail->users_messages->send('me', $message);
    }

    /**
     * @param  array<int, string>  $to
     * @param  array<int, string>  $cc
     * @param  array<int, string>  $bcc
     * @param  array<int, array{filename?: string, mime?: string, content: string, content_id?: string, inline?: bool}>  $attachments
     */
    protected function buildRawMessage(
        string $fromEmail,
        string $fromName,
        array $to,
        array $cc,
        array $bcc,
        string $subject,
        string $htmlBody,
        array $attachments
    ): string {
        $encodedSubject = $this->encodeHeader($subject);
        $fromHeader = $this->formatAddress($fromEmail, $fromName);

        $headers = [
            'MIME-Version: 1.0',
            'From: ' . $fromHeader,
            'To: ' . implode(', ', array_map(fn ($email) => $this->formatAddress($email), $to)),
            'Subject: ' . $encodedSubject,
        ];

        if (!empty($cc)) {
            $headers[] = 'Cc: ' . implode(', ', array_map(fn ($email) => $this->formatAddress($email), $cc));
        }
        if (!empty($bcc)) {
            $headers[] = 'Bcc: ' . implode(', ', array_map(fn ($email) => $this->formatAddress($email), $bcc));
        }

        $inlineParts = [];
        $fileParts = [];
        foreach ($attachments as $attachment) {
            if (!empty($attachment['inline']) && !empty($attachment['content_id'])) {
                $inlineParts[] = $attachment;
            } else {
                $fileParts[] = $attachment;
            }
        }

        if (empty($inlineParts) && empty($fileParts)) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: base64';

            return implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody));
        }

        $relatedBody = $this->buildRelatedHtmlPart($htmlBody, $inlineParts);

        if (empty($fileParts)) {
            $headers[] = 'Content-Type: multipart/related; boundary="' . $relatedBody['boundary'] . '"';

            return implode("\r\n", $headers) . "\r\n\r\n" . $relatedBody['body'];
        }

        $mixedBoundary = 'crm_gmail_mixed_' . bin2hex(random_bytes(8));
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"';

        $body = implode("\r\n", $headers) . "\r\n\r\n";
        $body .= '--' . $mixedBoundary . "\r\n";
        $body .= 'Content-Type: multipart/related; boundary="' . $relatedBody['boundary'] . "\"\r\n\r\n";
        $body .= $relatedBody['body'] . "\r\n";

        foreach ($fileParts as $attachment) {
            $filename = $attachment['filename'] ?? 'attachment';
            $mime = $attachment['mime'] ?? 'application/octet-stream';
            $content = $attachment['content'] ?? '';

            $body .= '--' . $mixedBoundary . "\r\n";
            $body .= 'Content-Type: ' . $mime . '; name="' . $this->escapeFilename($filename) . "\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= 'Content-Disposition: attachment; filename="' . $this->escapeFilename($filename) . "\"\r\n\r\n";
            $body .= chunk_split(base64_encode($content)) . "\r\n";
        }

        $body .= '--' . $mixedBoundary . '--';

        return $body;
    }

    /**
     * @param  array<int, array{filename?: string, mime?: string, content: string, content_id: string}>  $inlineParts
     * @return array{boundary: string, body: string}
     */
    protected function buildRelatedHtmlPart(string $htmlBody, array $inlineParts): array
    {
        $boundary = 'crm_gmail_related_' . bin2hex(random_bytes(8));

        $body = '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";

        foreach ($inlineParts as $part) {
            $filename = $part['filename'] ?? 'logo';
            $mime = $part['mime'] ?? 'image/png';
            $contentId = trim((string) ($part['content_id'] ?? ''), '<>');
            $content = $part['content'] ?? '';

            $body .= '--' . $boundary . "\r\n";
            $body .= 'Content-Type: ' . $mime . '; name="' . $this->escapeFilename($filename) . "\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= 'Content-ID: <' . $contentId . ">\r\n";
            $body .= "Content-Disposition: inline; filename=\"" . $this->escapeFilename($filename) . "\"\r\n\r\n";
            $body .= chunk_split(base64_encode($content)) . "\r\n";
        }

        $body .= '--' . $boundary . '--';

        return [
            'boundary' => $boundary,
            'body' => $body,
        ];
    }

    protected function formatAddress(string $email, ?string $name = null): string
    {
        $email = trim($email);
        $name = trim((string) $name);

        if ($name === '') {
            return $email;
        }

        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    protected function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }

        return $value;
    }

    protected function escapeFilename(string $filename): string
    {
        return str_replace(['"', "\r", "\n"], '', $filename);
    }

    protected function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
