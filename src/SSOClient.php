<?php

namespace WebKampus\SSOClient;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SSOClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $url = config('sso.url', 'http://localhost:8000');
        $url = rtrim($url, '/');

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'http://' . $url;
        }

        $this->baseUrl = $url;
    }

    /**
     * Get SSO server base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Exchange authorization code for access token
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}|null
     */
    public function exchangeCode(string $code, string $redirectUri): ?array
    {
        try {
            $response = Http::asForm()->post($this->baseUrl . '/oauth/token', [
                'grant_type'    => 'authorization_code',
                'client_id'     => config('sso.client_id'),
                'client_secret' => config('sso.client_secret'),
                'redirect_uri'  => $redirectUri,
                'code'          => $code,
            ]);

            if ($response->successful() && isset($response->json()['access_token'])) {
                return $response->json();
            }

            Log::error('SSO Client: Token exchange failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SSO Client: Token exchange error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get user info dari /api/userinfo (OpenID Connect style - lebih lengkap)
     *
     * Response: {sub, username, name, email, role, profile (sains_data)}
     *
     * @return array<string, mixed>|null
     */
    public function getUserInfo(string $token): ?array
    {
        return $this->apiGet('/api/userinfo', $token);
    }

    /**
     * Get user data dari /api/user (basic Passport endpoint)
     *
     * Response: full User model attributes
     *
     * @return array<string, mixed>|null
     */
    public function getUser(string $token): ?array
    {
        return $this->apiGet('/api/user', $token);
    }

    /**
     * Get daftar fakultas dari SAINS proxy
     *
     * @return array<int, mixed>|null
     */
    public function getFakultas(string $token): ?array
    {
        return $this->apiGet('/api/sains/fakultas', $token);
    }

    /**
     * Get daftar prodi dari SAINS proxy
     *
     * @return array<int, mixed>|null
     */
    public function getProdi(string $token): ?array
    {
        return $this->apiGet('/api/sains/prodi', $token);
    }

    /**
     * Get data dosen dari SAINS proxy
     *
     * @return array<string, mixed>|null
     */
    public function getDosen(string $token, string $username = ''): ?array
    {
        return $this->apiGet('/api/sains/dosen', $token, ['username' => $username]);
    }

    /**
     * Get data mahasiswa dari SAINS proxy
     *
     * @return array<string, mixed>|null
     */
    public function getMahasiswa(string $token, string $username = ''): ?array
    {
        return $this->apiGet('/api/sains/mahasiswa', $token, ['username' => $username]);
    }

    /**
     * Get data pegawai dari SAINS proxy
     *
     * @return array<string, mixed>|null
     */
    public function getPegawai(string $token, string $username = ''): ?array
    {
        return $this->apiGet('/api/sains/pegawai', $token, ['username' => $username]);
    }

    /**
     * Get SSO access token dari session (jika tersimpan)
     */
    public static function getSessionToken(): ?string
    {
        return session('sso_access_token');
    }

    /**
     * Helper method untuk GET request ke SSO API
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    protected function apiGet(string $endpoint, string $token, array $query = []): ?array
    {
        try {
            $request = Http::withToken($token);

            $response = empty($query)
                ? $request->get($this->baseUrl . $endpoint)
                : $request->get($this->baseUrl . $endpoint, $query);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('SSO Client: API request failed', [
                'endpoint' => $endpoint,
                'status'   => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SSO Client: API request error', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);
        }

        return null;
    }
}
