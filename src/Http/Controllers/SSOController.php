<?php

namespace WebKampus\SSOClient\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use WebKampus\SSOClient\Events\SSOUserAuthenticated;
use WebKampus\SSOClient\SSOClient;

class SSOController extends Controller
{
    /**
     * Redirect user ke halaman login SSO Server.
     */
    public function redirect()
    {
        if (Auth::check()) {
            return redirect()->to($this->resolveRedirectPath(Auth::user()));
        }

        $ssoClient = app(SSOClient::class);

        $query = http_build_query([
            'client_id'     => config('sso.client_id'),
            'redirect_uri'  => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope'         => config('sso.scope', '*'),
        ]);

        return redirect()->away($ssoClient->getBaseUrl() . '/oauth/authorize?' . $query);
    }

    /**
     * Handle callback dari SSO Server setelah user login.
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');

        if (! $code) {
            return redirect()->route('login')
                ->withErrors(['sso' => 'Login SSO gagal: Authorization code tidak ditemukan.']);
        }

        /** @var SSOClient $ssoClient */
        $ssoClient = app(SSOClient::class);

        // 1. Exchange authorization code untuk access token
        $tokenData = $ssoClient->exchangeCode($code, $this->getRedirectUri());

        if (! $tokenData) {
            return redirect()->route('login')
                ->withErrors(['sso' => 'Login SSO gagal: Tidak dapat menukar token akses.']);
        }

        $accessToken = $tokenData['access_token'];
        $refreshToken = $tokenData['refresh_token'] ?? null;

        // 2. Ambil data user dari /api/userinfo (lebih lengkap dari /api/user)
        $userInfo = $ssoClient->getUserInfo($accessToken);

        if (! $userInfo || empty($userInfo['username'])) {
            // Fallback ke /api/user jika /api/userinfo gagal
            $userInfo = $ssoClient->getUser($accessToken);
        }

        if (! $userInfo || empty($userInfo['username'])) {
            return redirect()->route('login')
                ->withErrors(['sso' => 'Login SSO gagal: Tidak dapat mengambil data pengguna.']);
        }

        // 3. Buat atau update user di database lokal
        $user = $this->syncUser($userInfo);

        // 4. Sync Spatie role jika diaktifkan
        if (config('sso.sync_spatie_roles', true)) {
            $this->syncSpatieRole($user, $userInfo);
        }

        // 5. Simpan access token di session untuk dipakai client app
        session([
            'sso_access_token'  => $accessToken,
            'sso_refresh_token' => $refreshToken,
        ]);

        // 6. Login user
        Auth::login($user);

        // 7. Fire event agar client app bisa inject logic custom
        event(new SSOUserAuthenticated($user, $userInfo, $accessToken, $refreshToken));

        // 8. Redirect ke halaman yang sesuai
        return redirect()->intended($this->resolveRedirectPath($user));
    }

    /**
     * Logout dari aplikasi lokal dan (opsional) SSO Server.
     */
    public function logout(Request $request)
    {
        // Clear SSO session data
        session()->forget(['sso_access_token', 'sso_refresh_token']);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Sync data user dari SSO ke database lokal berdasarkan config user_columns.
     *
     * @param  array<string, mixed>  $userInfo
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    protected function syncUser(array $userInfo)
    {
        $columnMap = config('sso.user_columns', []);
        $userModel = config('sso.user_model', 'App\\Models\\User');

        // Username selalu required sebagai unique identifier
        $usernameColumn = $columnMap['username'] ?? 'username';
        $username = $userInfo[$usernameColumn] ?? $userInfo['username'];

        // Build update data berdasarkan column mapping
        $updateData = [];

        foreach ($columnMap as $dbColumn => $ssoField) {
            if ($dbColumn === 'username' || $ssoField === null) {
                continue;
            }

            $value = $userInfo[$ssoField] ?? null;

            if ($value !== null) {
                $updateData[$dbColumn] = $value;
            }
        }

        // Pastikan 'name' selalu ada fallback
        if (isset($columnMap['name']) && ! isset($updateData['name'])) {
            $updateData['name'] = $username;
        }

        return $userModel::updateOrCreate(
            ['username' => $username],
            $updateData
        );
    }

    /**
     * Sync Spatie role dari SSO data.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array<string, mixed>  $userInfo
     */
    protected function syncSpatieRole($user, array $userInfo): void
    {
        $role = $userInfo['role'] ?? null;

        if (empty($role) || ! class_exists(\Spatie\Permission\Models\Role::class)) {
            return;
        }

        try {
            $roleModel = \Spatie\Permission\Models\Role::firstOrCreate([
                'name'       => $role,
                'guard_name' => 'web',
            ]);
            $user->syncRoles([$roleModel]);
        } catch (\Throwable $e) {
            Log::warning('SSO Client: Gagal sync role Spatie', [
                'role'  => $role,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Tentukan redirect path berdasarkan role user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     */
    protected function resolveRedirectPath($user): string
    {
        $roleRedirects = config('sso.role_redirects', []);

        // Cek Spatie role dulu
        if (method_exists($user, 'hasRole')) {
            foreach ($roleRedirects as $role => $path) {
                if ($user->hasRole($role)) {
                    return $path;
                }
            }
        }

        // Cek role column langsung
        $userRole = $user->role ?? null;
        if ($userRole && isset($roleRedirects[$userRole])) {
            return $roleRedirects[$userRole];
        }

        return config('sso.redirect_after_login', '/dashboard');
    }

    /**
     * Get redirect URI, prioritaskan config, fallback ke route name.
     */
    protected function getRedirectUri(): string
    {
        $configUri = config('sso.redirect_uri');

        if (! empty($configUri)) {
            return $configUri;
        }

        return route('sso.callback');
    }
}
