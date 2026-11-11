<?php

namespace App\Services;

use App\Models\WhatsappSetting;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected ?WhatsappSetting $setting;

    public function __construct()
    {
        $this->setting = WhatsappSetting::getSettings();
    }

    /**
     * Get configured WhatsApp API endpoint URL based on Provider Type.
     */
    public function getEndpointUrl(): string
    {
        $provider = $this->setting->provider_type ?? 'isarva';
        $baseUrl  = rtrim($this->setting->whatsapp_api_url ?? config('services.whatsapp.api_url', ''), '/');

        if ($provider === 'isarva') {
            return "{$baseUrl}/api/v1/messages";
        }

        $version = ltrim($this->setting->whatsapp_api_version ?? config('services.whatsapp.api_version', ''), '/');
        $phoneId = $this->setting->whatsapp_phone_number_id ?? '';

        return "{$baseUrl}/{$version}/{$phoneId}/messages";
    }

    /**
     * Get configured WhatsApp media upload URL based on Provider Type.
     */
    public function getMediaUploadUrl(): string
    {
        $provider = $this->setting->provider_type ?? 'isarva';
        $baseUrl  = rtrim($this->setting->whatsapp_api_url ?? config('services.whatsapp.api_url', ''), '/');

        if ($provider === 'isarva') {
            return "{$baseUrl}/api/v1/media";
        }

        $version = ltrim($this->setting->whatsapp_api_version ?? config('services.whatsapp.api_version', 'v20.0'), '/');
        $phoneId = $this->setting->whatsapp_phone_number_id ?? '';

        return "{$baseUrl}/{$version}/{$phoneId}/media";
    }

    /**
     * Format a simple array of scalar parameter values into Meta WhatsApp Cloud API body components.
     * e.g. ['John Doe', 'CRM'] -> [ ['type' => 'body', 'parameters' => [ ['type'=>'text','text'=>'John Doe'], ... ] ] ]
     *
     * @param array $parameters
     * @return array
     */
    public function formatBodyComponents(array $parameters): array
    {
        if (empty($parameters)) {
            return [];
        }

        $formattedParams = [];
        foreach ($parameters as $param) {
            $formattedParams[] = [
                'type' => 'text',
                'text' => (string) trim($param),
            ];
        }

        return [
            [
                'type'       => 'body',
                'parameters' => $formattedParams,
            ],
        ];
    }

    /**
     * Send a template WhatsApp message.
     *
     * @param string $toPhoneNumber Recipient phone number in international format (e.g. "+919876543210")
     * @param string|null $templateName Specific template ID/name, or defaults to saved setting
     * @param array $components Components array OR simple parameter values array (e.g. ['John', 'CRM Alert'])
     * @param string|null $language Code language (default: saved setting or 'en_US')
     * @return array Response array with 'success' boolean and 'data' or 'error' message
     */
    public function sendTemplateMessage(string $toPhoneNumber, ?string $templateName = null, array $components = [], ?string $language = null): array
    {
        if (!$this->setting || !$this->setting->is_active) {
            return [
                'success'  => false,
                'disabled' => true,
                'error'    => 'WhatsApp Integration is currently disabled. Please enable WhatsApp Integration in settings.',
            ];
        }

        $provider = $this->setting->provider_type ?? 'isarva';
        $template = $templateName ?: $this->setting->whatsapp_template_id;
        $langCode = $language ?: ($this->setting->whatsapp_language_code ?? 'en_US');
        $token    = $this->setting->active_token ?? $this->setting->whatsapp_access_token;

        if (empty($token)) {
            return [
                'success' => false,
                'error'   => 'WhatsApp Access Token / API Key is missing in settings.',
            ];
        }

        if (empty($template)) {
            return [
                'success' => false,
                'error'   => 'WhatsApp Template ID is not configured.',
            ];
        }

        // Clean and format E.164 phone number
        $cleanPhone = preg_replace('/[^0-9]/', '', $toPhoneNumber);
        $e164Phone = '+' . $cleanPhone;

        if ($provider === 'isarva') {
            // IsarvaCRM (WACRM) API Format
            $bodyParams = [];
            if (!empty($components)) {
                if (isset($components[0]['type']) && $components[0]['type'] === 'body') {
                    foreach ($components[0]['parameters'] as $p) {
                        $bodyParams[] = $p['text'] ?? '';
                    }
                } else {
                    foreach ($components as $val) {
                        if (is_scalar($val)) {
                            $bodyParams[] = (string) $val;
                        }
                    }
                }
            }

            $templateData = [
                'name'     => $template,
                'language' => $langCode,
            ];
            if (!empty($bodyParams)) {
                $templateData['body'] = array_values($bodyParams);
            }

            $payload = [
                'to'       => $e164Phone,
                'type'     => 'template',
                'template' => $templateData,
            ];

            
            if (!empty($this->setting->whatsapp_phone_number_id)) {
                $payload['whatsapp_phone_number_id'] = $this->setting->whatsapp_phone_number_id;
            }

            

            return $this->executeApiCall($payload);
        }

        // Other Vendor (Direct Meta Cloud API) Format
        if (!empty($components) && !isset($components[0]['type'])) {
            $components = $this->formatBodyComponents($components);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $cleanPhone,
            'type'              => 'template',
            'template'          => [
                'name'     => $template,
                'language' => [
                    'code' => $langCode,
                ],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        
        return $this->executeApiCall($payload);
    }

    /**
     * Send a quotation template message with optional PDF document header.
     *
     * Expected short professional body variables (order):
     * 1 contact name, 2 company, 3 quote no, 4 client org, 5 amount, 6 sender
     *
     * @param string $toPhoneNumber
     * @param array $bodyParams Scalar body parameter values
     * @param string|null $documentUrl Public HTTPS URL to the PDF
     * @param string|null $documentFilename Display filename for the PDF
     * @param string|null $templateName Override quotation template name
     * @param string|null $language Override quotation language
     * @param string|null $documentPath Local absolute path to PDF (preferred — uploaded to WhatsApp media API)
     * @return array
     */
    public function sendQuotationTemplateMessage(
        string $toPhoneNumber,
        array $bodyParams = [],
        ?string $documentUrl = null,
        ?string $documentFilename = null,
        ?string $templateName = null,
        ?string $language = null,
        ?string $documentPath = null
    ): array {
        if (!$this->setting || !$this->setting->is_active) {
            return [
                'success'  => false,
                'disabled' => true,
                'error'    => 'WhatsApp Integration is currently disabled. Please enable WhatsApp Integration in settings.',
            ];
        }

        $template = $templateName
            ?: ($this->setting->quotation_template_id ?: null);

        if (empty($template)) {
            return [
                'success' => false,
                'error'   => 'Quotation WhatsApp Template Name is not configured for the active provider. Set it in WhatsApp Settings.',
            ];
        }

        $langCode = $language
            ?: ($this->setting->quotation_language_code
                ?: ($this->setting->whatsapp_language_code ?? 'en_US'));

        $provider = $this->setting->provider_type ?? 'isarva';
        $filename = $documentFilename ?: 'quotation.pdf';

        $mediaId = null;
        $uploadError = null;

        // Isarva: PDF must be uploaded via /api/v1/media — no public URL fallback.
        if ($provider === 'isarva') {
            if (empty($documentPath) || !is_file($documentPath)) {
                return [
                    'success' => false,
                    'error'   => 'Quotation PDF file is missing. Cannot upload to Isarva /api/v1/media.',
                ];
            }

            $upload = $this->uploadDocumentMedia($documentPath, $filename);
            if (!empty($upload['media_id'])) {
                $mediaId = $upload['media_id'];
            } else {
                $uploadError = $upload['error'] ?? 'Media upload to /api/v1/media failed.';
                Log::error('Isarva quotation PDF upload failed.', [
                    'error' => $uploadError,
                    'path'  => $documentPath,
                    'url'   => $this->getMediaUploadUrl(),
                ]);

                return [
                    'success' => false,
                    'error'   => 'Failed to upload quotation PDF via Isarva /api/v1/media: ' . $uploadError,
                    'details' => $upload['details'] ?? null,
                ];
            }
        } elseif (!empty($documentPath) && is_file($documentPath)) {
            $upload = $this->uploadDocumentMedia($documentPath, $filename);
            if (!empty($upload['media_id'])) {
                $mediaId = $upload['media_id'];
            } else {
                Log::warning('WhatsApp PDF media upload failed; falling back to public link.', [
                    'error'    => $upload['error'] ?? null,
                    'path'     => $documentPath,
                    'provider' => $provider,
                ]);
            }
        }

        $publicDocumentUrl = $this->resolvePublicDocumentUrl($documentUrl);

        $documentParam = null;
        if ($mediaId) {
            $documentParam = [
                'id'       => $mediaId,
                'filename' => $filename,
            ];
        } elseif ($provider !== 'isarva' && !empty($publicDocumentUrl)) {
            $documentParam = [
                'link'     => $publicDocumentUrl,
                'filename' => $filename,
            ];
        }

        $payloadBody = array_values(array_map(static fn ($v) => (string) trim((string) $v), $bodyParams));

        $bodyTextParams = [];
        foreach ($bodyParams as $param) {
            $bodyTextParams[] = [
                'type' => 'text',
                'text' => (string) trim((string) $param),
            ];
        }

        // Isarva: header document in components only; body as simple string array
        $isarvaComponents = [];
        if ($documentParam) {
            $isarvaComponents[] = [
                'type'       => 'header',
                'parameters' => [
                    [
                        'type'     => 'document',
                        'document' => $documentParam,
                    ],
                ],
            ];
        }

        // Meta: header + body in components
        $metaComponents = $isarvaComponents;
        if (!empty($bodyTextParams)) {
            $metaComponents[] = [
                'type'       => 'body',
                'parameters' => $bodyTextParams,
            ];
        }

        if ($provider === 'isarva') {
            $cleanPhone = preg_replace('/[^0-9]/', '', $toPhoneNumber);

            if (!$documentParam || empty($documentParam['id'])) {
                return [
                    'success' => false,
                    'error'   => 'Quotation PDF media id is missing after /api/v1/media upload.',
                ];
            }

            // Isarva: upload PDF via /api/v1/media, then send template with document.id in header.
            $templateData = [
                'name'     => $template,
                'language' => $langCode,
                'body'     => $payloadBody,
                'components' => [
                    [
                        'type'       => 'header',
                        'parameters' => [
                            [
                                'type'     => 'document',
                                'document' => $documentParam,
                            ],
                        ],
                    ],
                ],
            ];

            $payload = [
                'to'       => '+' . $cleanPhone,
                'type'     => 'template',
                'template' => $templateData,
            ];

            if (!empty($this->setting->whatsapp_phone_number_id)) {
                $payload['whatsapp_phone_number_id'] = $this->setting->whatsapp_phone_number_id;
            }

            Log::info('WhatsApp quotation payload (isarva)', [
                'media_id'  => $mediaId,
                'upload_url'=> $this->getMediaUploadUrl(),
                'template'  => $template,
                'payload'   => $payload,
            ]);

            return $this->executeApiCall($payload);
        }

        // Direct Meta Cloud API
        Log::info('WhatsApp quotation payload (meta)', [
            'has_media_id' => !empty($mediaId),
            'has_link'     => !empty($documentUrl),
            'template'     => $template,
        ]);

        return $this->sendTemplateMessage($toPhoneNumber, $template, $metaComponents, $langCode);
    }

    /**
     * Ensure document URL is publicly reachable (Meta/WACRM cannot fetch localhost).
     */
    protected function resolvePublicDocumentUrl(?string $documentUrl): ?string
    {
        if (empty($documentUrl)) {
            return null;
        }

        $documentUrl = trim($documentUrl);
        $appUrl = rtrim((string) config('app.url'), '/');

        // Replace localhost / 127.0.0.1 with configured APP_URL when available
        if (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?#i', $documentUrl) && !empty($appUrl)) {
            $path = parse_url($documentUrl, PHP_URL_PATH) ?: '';
            $query = parse_url($documentUrl, PHP_URL_QUERY);
            $documentUrl = $appUrl . $path . ($query ? '?' . $query : '');
        }

        // Prefer HTTPS for production document fetch
        if (str_starts_with($documentUrl, 'http://') && !preg_match('#^http://(localhost|127\.0\.0\.1)#i', $documentUrl)) {
            $documentUrl = 'https://' . substr($documentUrl, 7);
        }

        return $documentUrl;
    }

    /**
     * Upload a PDF/document to WhatsApp media API and return media_id.
     *
     * @return array{media_id?: string, error?: string}
     */
    public function uploadDocumentMedia(string $filePath, string $filename = 'quotation.pdf'): array
    {
        if (!is_file($filePath)) {
            return ['error' => 'PDF file not found for upload.'];
        }

        $token = $this->setting->active_token ?? $this->setting->whatsapp_access_token;
        if (empty($token)) {
            return ['error' => 'WhatsApp Access Token / API Key is missing.'];
        }

        $provider = $this->setting->provider_type ?? 'isarva';
        $uploadUrl = $this->getMediaUploadUrl();

        if ($provider !== 'isarva' && empty($this->setting->whatsapp_phone_number_id)) {
            return ['error' => 'Meta Phone Number ID is required to upload quotation PDF.'];
        }

        try {
            $request = Http::withToken($token)
                ->withoutVerifying()
                ->attach('file', file_get_contents($filePath), $filename, ['Content-Type' => 'application/pdf']);

            if ($provider === 'isarva') {
                // Isarva WACRM media upload
                $response = $request->post($uploadUrl, [
                    'type'     => 'document',
                    'filename' => $filename,
                ]);
            } else {
                // Direct Meta Cloud API media upload
                $response = $request->post($uploadUrl, [
                    'messaging_product' => 'whatsapp',
                    'type'              => 'application/pdf',
                ]);
            }

            if ($response->successful()) {
                $json = $response->json();
                $mediaId = $json['id']
                    ?? $json['media_id']
                    ?? ($json['data']['id'] ?? null)
                    ?? ($json['data']['media_id'] ?? null);

                if ($mediaId) {
                    Log::info('WhatsApp PDF uploaded successfully.', [
                        'provider'  => $provider,
                        'url'       => $uploadUrl,
                        'media_id'  => (string) $mediaId,
                        'filename'  => $filename,
                    ]);

                    return ['media_id' => (string) $mediaId];
                }

                return ['error' => 'Media upload succeeded but media id was missing in response.', 'details' => $json];
            }

            $errorData = $response->json();
            $msg = $errorData['error']['message']
                ?? $errorData['message']
                ?? $response->body()
                ?? 'Media upload failed.';

            Log::error('WhatsApp media upload error: ' . $msg, [
                'url'      => $uploadUrl,
                'status'   => $response->status(),
                'response' => $errorData,
            ]);

            return ['error' => $msg, 'details' => $errorData];
        } catch (\Exception $e) {
            Log::error('WhatsApp media upload exception: ' . $e->getMessage(), [
                'url' => $uploadUrl,
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Send a plain text WhatsApp message.
     *
     * @param string $toPhoneNumber
     * @param string $messageBody
     * @return array
     */
    public function sendTextMessage(string $toPhoneNumber, string $messageBody): array
    {
        if (!$this->setting || !$this->setting->is_active) {
            return [
                'success'  => false,
                'disabled' => true,
                'error'    => 'WhatsApp Integration is currently disabled. Please enable WhatsApp Integration in settings.',
            ];
        }

        $provider = $this->setting->provider_type ?? 'isarva';
        $token    = $this->setting->active_token ?? $this->setting->whatsapp_access_token;

        if (empty($token)) {
            return [
                'success' => false,
                'error'   => 'WhatsApp Access Token / API Key is missing in settings.',
            ];
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $toPhoneNumber);
        $e164Phone = '+' . $cleanPhone;

        if ($provider === 'isarva') {
            $payload = [
                'to'   => $e164Phone,
                'type' => 'text',
                'text' => $messageBody,
            ];
            if (!empty($this->setting->whatsapp_phone_number_id)) {
                $payload['whatsapp_phone_number_id'] = $this->setting->whatsapp_phone_number_id;
            }
            return $this->executeApiCall($payload);
        }

        // Other Vendor (Direct Meta Cloud API)
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $cleanPhone,
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $messageBody,
            ],
        ];

        return $this->executeApiCall($payload);
    }

    /**
     * Send an OTP template message.
     *
     * @param string $toPhoneNumber
     * @param string $otpCode
     * @param string|null $templateName
     * @param string|null $language
     * @return array
     */
    public function sendOtpTemplateMessage(string $toPhoneNumber, string $otpCode, ?string $templateName = null, ?string $language = null): array
    {
        $provider = $this->setting->provider_type ?? 'isarva';

        if ($provider === 'isarva') {
            return $this->sendTemplateMessage($toPhoneNumber, $templateName, [$otpCode], $language);
        }

        // Direct Meta Cloud API Format
        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => $otpCode,
                    ],
                ],
            ],
            [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => $otpCode,
                    ],
                ],
            ],
        ];

        $res = $this->sendTemplateMessage($toPhoneNumber, $templateName, $components, $language);

        if (!$res['success']) {
            $bodyOnlyComponents = $this->formatBodyComponents([$otpCode]);
            return $this->sendTemplateMessage($toPhoneNumber, $templateName, $bodyOnlyComponents, $language);
        }

        return $res;
    }

    /**
     * Send a test message (either template or text) to test credentials.
     *
     * @param string $toPhoneNumber
     * @param array $testParams Optional array of parameters if the template uses variables
     * @return array
     */
    public function sendTestMessage(string $toPhoneNumber, array $testParams = []): array
    {
        $template = $this->setting->whatsapp_template_id;

        if (!empty($template)) {
            if (empty($testParams) && (str_contains(strtolower($template), 'otp') || str_contains(strtolower($template), 'login'))) {
                return $this->sendOtpTemplateMessage($toPhoneNumber, '123456', $template);
            }

            return $this->sendTemplateMessage($toPhoneNumber, null, $testParams);
        }

        $testText = "Hello! This is a test message from " . config('app.name', 'CRM') . " WhatsApp API integration.";
        return $this->sendTextMessage($toPhoneNumber, $testText);
    }

    /**
     * Execute HTTP POST call to configured API endpoint.
     */
    protected function executeApiCall(array $payload): array
    {
        $url   = $this->getEndpointUrl();
        $token = $this->setting->active_token ?? $this->setting->whatsapp_access_token;

        try {
            $response = Http::withToken($token)
                ->withoutVerifying()
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully.', [
                    'url'  => $url,
                    'resp' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'data'    => $response->json(),
                ];
            }

            $errorData = $response->json();
            $errorCode = $errorData['error']['code'] ?? null;
            $rawErrorMessage = $errorData['error']['message'] ?? $response->body() ?? 'Failed to send WhatsApp message.';

            if ($errorCode == 132000 || str_contains($rawErrorMessage, 'Number of parameters does not match')) {
                $templateName = $payload['template']['name'] ?? 'configured template';
                $errorMessage = "Template Parameter Mismatch (#132000): The template '{$templateName}' expects variable parameters (e.g. {{1}}, {{2}}). Please pass matching template parameters.";
            } else {
                $errorMessage = "API Error ({$response->status()}): {$rawErrorMessage}";
            }

            Log::error('WhatsApp API call error: ' . $rawErrorMessage, [
                'url'      => $url,
                'status'   => $response->status(),
                'response' => $errorData,
            ]);

            if (class_exists(ActivityLogger::class)) {
                try {
                    ActivityLogger::log([
                        'user_id' => auth()->id(),
                        'module'  => 'WhatsApp',
                        'action'  => 'whatsapp_api_call_error',
                        'details' => json_encode($errorData),
                    ]);
                } catch (\Throwable $th) {
                    // Ignore logger errors
                }
            }

            return [
                'success' => false,
                'error'   => $errorMessage,
                'details' => $errorData,
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp API Connection Exception: ' . $e->getMessage());

            $errMsg = $e->getMessage();
            if (str_contains($errMsg, 'cURL error 35') || str_contains($errMsg, 'SSL_ERROR_SYSCALL') || str_contains($errMsg, 'SSL_connect')) {
                $errMsg = "SSL/Network Connection Error (cURL 35): Unable to complete SSL handshake to '{$url}'. Please check if your WhatsApp Host URL in Settings is active and correct.";
            }

            return [
                'success' => false,
                'error'   => $errMsg,
            ];
        }
    }
}
