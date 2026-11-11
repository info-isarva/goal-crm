<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Illuminate\Support\Facades\Storage;

class GoogleCalendarService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('google_calendar.client_id'));
        $this->client->setClientSecret(config('google_calendar.client_secret'));
        $this->client->setRedirectUri(config('google_calendar.redirect_uri'));
        $this->client->setScopes(config('google_calendar.scopes'));

        // Load stored access token if available
        $this->loadAccessToken();
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function authenticate($code)
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
        $this->storeAccessToken($accessToken);
        return $accessToken;
    }

    public function getCalendarService()
    {
        return new Calendar($this->client);
    }

    public function getClient()
    {
        return $this->client;
    }

    protected function storeAccessToken($accessToken)
    {
        Storage::disk('local')->put('google_calendar_token.json', json_encode($accessToken));
        $this->client->setAccessToken($accessToken);
    }

    protected function loadAccessToken()
    {
        if (Storage::disk('local')->exists('google_calendar_token.json')) {
            $accessToken = json_decode(Storage::disk('local')->get('google_calendar_token.json'), true);
            $this->client->setAccessToken($accessToken);

            // Refresh the token if it's expired
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $accessToken['refresh_token'] ?? null;
                if ($refreshToken) {
                    $newAccessToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $this->storeAccessToken($newAccessToken);
                }
            }
        }
    }
}