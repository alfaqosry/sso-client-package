<?php

namespace WebKampus\SSOClient\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SSOController extends Controller
{
    public function redirect()
    {
        $ssoUrl = config('sso.url', 'http://localhost:8000');
        $ssoUrl = rtrim($ssoUrl, '/');

        if (!str_starts_with($ssoUrl, 'http://') && !str_starts_with($ssoUrl, 'https://')) {
            $ssoUrl = 'http://' . $ssoUrl;
        }

        $query = http_build_query([
            'client_id'     => config('sso.client_id'),
            'redirect_uri'  => route('sso.callback'),
            'response_type' => 'code',
            'scope'         => '',
        ]);

        return redirect()->away($ssoUrl . '/oauth/authorize?' . $query);
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');

        $ssoUrl = config('sso.url', 'http://localhost:8000');
        $ssoUrl = rtrim($ssoUrl, '/');

        if (!str_starts_with($ssoUrl, 'http://') && !str_starts_with($ssoUrl, 'https://')) {
            $ssoUrl = 'http://' . $ssoUrl;
        }

        $response = Http::asForm()->post($ssoUrl . '/oauth/token', [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('sso.client_id'),
            'client_secret' => config('sso.client_secret'),
            'redirect_uri'  => route('sso.callback'),
            'code'          => $code,
        ]);

        $data = $response->json();

        if (!isset($data['access_token'])) {
            return redirect()->route('login')->withErrors('Login SSO gagal: Gagal menukar token akses.');
        }

        $token = $data['access_token'];

        $userInfo = Http::withToken($token)->get($ssoUrl . '/api/user')->json();

        if (!isset($userInfo['username'])) {
            return redirect()->route('login')->withErrors('Login SSO gagal: Gagal mengambil data pengguna.');
        }

        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        $user = $userModel::updateOrCreate(
            ['username' => $userInfo['username']],
            [
                'name'  => $userInfo['name'],
                'email' => $userInfo['email'] ?? null,
            ]
        );

        // Sync Spatie role if package exists
        if (!empty($userInfo['role']) && class_exists(\Spatie\Permission\Models\Role::class)) {
            try {
                $roleModel = \Spatie\Permission\Models\Role::firstOrCreate([
                    'name' => $userInfo['role'],
                    'guard_name' => 'web',
                ]);
                $user->syncRoles([$roleModel]);
            } catch (\Throwable $e) {
                Log::warning('SSO Client Package: Gagal sync role Spatie: ' . $e->getMessage());
            }
        }

        if (method_exists($user, 'wasRecentlyCreated') && $user->wasRecentlyCreated) {
            $user->update(['last_login_at' => now()]);
        }

        Auth::login($user);

        if (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('petugas-lppm'))) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended('/dasbor');
    }
}
