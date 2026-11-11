<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        // Comma separated OAuth client ids of the Android / iOS apps (mobile Google login)
        'mobile_client_ids' => env('GOOGLE_MOBILE_CLIENT_IDS'),
    ],

    // Shared secret required by the public website lead API (POST /api/leads)
    'lead_api' => [
        'key' => env('LEAD_API_KEY'),
    ],

    'whatsapp' => [
        'provider_type'            => env('WHATSAPP_PROVIDER_TYPE', 'isarva'),
        'access_token'             => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id'          => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_url'                  => env('WHATSAPP_API_URL'),
        'api_version'              => env('WHATSAPP_API_VERSION'),
        'template_id'              => env('WHATSAPP_TEMPLATE_ID'),
        'language_code'            => env('WHATSAPP_LANGUAGE_CODE'),
        'quotation_template_id'    => env('WHATSAPP_QUOTATION_TEMPLATE_ID', 'quotation_share'),
        'quotation_language_code'  => env('WHATSAPP_QUOTATION_LANGUAGE_CODE'),
    ],

    'email' => [
        'provider_type' => env('EMAIL_PROVIDER_TYPE', 'smtp'),
        'host'          => env('MAIL_HOST'),
        'port'          => env('MAIL_PORT', 587),
        'encryption'    => env('MAIL_ENCRYPTION', 'tls'),
        'username'      => env('MAIL_USERNAME'),
        'password'      => env('MAIL_PASSWORD'),
        'from_address'  => env('MAIL_FROM_ADDRESS'),
        'from_name'     => env('MAIL_FROM_NAME'),
        'sendmail_path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
    ],

];
