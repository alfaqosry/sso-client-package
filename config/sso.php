<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SSO Server URL
    |--------------------------------------------------------------------------
    |
    | Base URL dari server SSO UP (tanpa trailing slash).
    |
    */
    'url' => env('SSO_URL', 'http://localhost:8000'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Client Credentials
    |--------------------------------------------------------------------------
    |
    | Client ID dan Secret yang didaftarkan di admin panel SSO Server.
    |
    */
    'client_id' => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Redirect URI
    |--------------------------------------------------------------------------
    |
    | URI callback setelah login di SSO. Jika null, otomatis pakai route('sso.callback').
    |
    */
    'redirect_uri' => env('SSO_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Redirect After Login
    |--------------------------------------------------------------------------
    |
    | Path default setelah berhasil login. Bisa di-override per role via
    | 'role_redirects' di bawah.
    |
    */
    'redirect_after_login' => env('SSO_REDIRECT_AFTER_LOGIN', '/dashboard'),

    /*
    |--------------------------------------------------------------------------
    | Role-Based Redirects
    |--------------------------------------------------------------------------
    |
    | Mapping role ke path redirect. Jika role user cocok dengan salah satu
    | key di sini, user akan diarahkan ke path tersebut.
    | Jika tidak cocok, pakai 'redirect_after_login'.
    |
    */
    'role_redirects' => [
        // 'admin' => '/admin',
        // 'dosen' => '/dasbor',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | Model Eloquent yang digunakan untuk user. Harus extend Authenticatable.
    |
    */
    'user_model' => env('SSO_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | User Column Mapping
    |--------------------------------------------------------------------------
    |
    | Mapping antara column di tabel users (key) dengan field dari
    | /api/userinfo SSO server (value).
    |
    | Set value ke null untuk skip column yang tidak ada di tabel users.
    |
    */
    'user_columns' => [
        'username'   => 'username',
        'name'       => 'name',
        'email'      => 'email',
        'role'       => 'role',
        'sains_data' => 'profile',
        'avatar'     => 'avatar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Spatie Roles
    |--------------------------------------------------------------------------
    |
    | Jika true dan spatie/laravel-permission terinstal, role dari SSO
    | akan otomatis di-sync ke Spatie roles.
    |
    */
    'sync_spatie_roles' => true,

    /*
    |--------------------------------------------------------------------------
    | OAuth Scope
    |--------------------------------------------------------------------------
    |
    | Scope yang diminta saat authorization request.
    |
    */
    'scope' => env('SSO_SCOPE', '*'),

    /*
    |--------------------------------------------------------------------------
    | Prompt (Force Re-authentication)
    |--------------------------------------------------------------------------
    |
    | Set 'login' untuk memaksa user memasukkan kredensial lagi pada SSO Server.
    | Set null atau '' jika tidak ingin memaksa re-autentikasi.
    |
    */
    'prompt' => env('SSO_PROMPT', 'login'),

    /*
    |--------------------------------------------------------------------------
    | Routes Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix untuk route SSO. Default 'sso' menghasilkan /sso/redirect,
    | /sso/callback, /sso/logout.
    |
    */
    'route_prefix' => 'sso',

    /*
    |--------------------------------------------------------------------------
    | Routes Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware yang diterapkan pada route SSO.
    |
    */
    'route_middleware' => ['web'],
];
