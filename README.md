# WebKampus SSO Client Package

Paket Laravel untuk autentikasi Single Sign-On (SSO) OAuth2. Paket ini mempermudah integrasi aplikasi klien (LPPM, Portal Dosen, E-Learning, dll) dengan **SSO UP Server** (Universitas Pahlawan).

## Fitur

- ✅ OAuth2 Authorization Code flow (Passport)
- ✅ Otomatis sync data user (username, name, email, role, sains_data, avatar)
- ✅ Configurable field mapping untuk tabel `users`
- ✅ Role-based redirect setelah login
- ✅ Integrasi Spatie laravel-permission (opsional, auto-detect)
- ✅ Event `SSOUserAuthenticated` untuk custom post-login logic
- ✅ `SSOClient` helper untuk akses SAINS proxy endpoints (fakultas, prodi, dosen, mahasiswa)
- ✅ Bundled routes (redirect, callback, logout)
- ✅ Publishable migration & config

---

## 1. Instalasi

### Cara A: Repository Lokal (Development)

Tambahkan konfigurasi `repositories` pada `composer.json` aplikasi Anda:

```json
"repositories": [
    {
        "type": "path",
        "url": "../sso-client-package"
    }
],
"require": {
    "webkampus/sso-client": "@dev"
}
```

Lalu jalankan:
```bash
composer update
```

### Cara B: Dari GitHub / Packagist (Production)

```bash
composer require webkampus/sso-client
```

---

## 2. Konfigurasi Environment (`.env`)

Tambahkan variabel berikut ke file `.env` aplikasi klien:

```env
SSO_URL=http://localhost:8000
SSO_CLIENT_ID=your_client_id_here
SSO_CLIENT_SECRET=your_client_secret_here
SSO_REDIRECT_URI=http://localhost:8001/sso/callback
SSO_REDIRECT_AFTER_LOGIN=/dashboard
SSO_SCOPE=*
```

---

## 3. Publish Config & Migration

```bash
# Publish konfigurasi
php artisan vendor:publish --tag=sso-config

# Publish migration (tambah kolom SSO ke tabel users)
php artisan vendor:publish --tag=sso-migrations
php artisan migrate
```

Migration akan menambahkan kolom berikut ke tabel `users` (jika belum ada):
- `username` (string, unique)
- `role` (string, default 'user')
- `sains_data` (json, nullable)
- `avatar` (string, nullable)
- `last_login_at` (timestamp, nullable)

---

## 4. Konfigurasi Lanjutan (`config/sso.php`)

Setelah publish config, Anda bisa customize:

```php
return [
    'url'            => env('SSO_URL', 'http://localhost:8000'),
    'client_id'      => env('SSO_CLIENT_ID'),
    'client_secret'  => env('SSO_CLIENT_SECRET'),
    'redirect_uri'   => env('SSO_REDIRECT_URI'),

    // Redirect default setelah login
    'redirect_after_login' => '/dashboard',

    // Redirect per role (opsional)
    'role_redirects' => [
        'admin'        => '/admin',
        'dosen'        => '/dasbor',
        'petugas-lppm' => '/admin',
    ],

    // Model user yang dipakai
    'user_model' => 'App\\Models\\User',

    // Mapping kolom DB ← field SSO userinfo
    'user_columns' => [
        'username'   => 'username',
        'name'       => 'name',
        'email'      => 'email',
        'role'       => 'role',
        'sains_data' => 'profile',   // 'profile' dari /api/userinfo = sains_data
        'avatar'     => 'avatar',
    ],

    // Sync role Spatie otomatis
    'sync_spatie_roles' => true,

    // OAuth scope
    'scope' => '*',

    // Prefix dan middleware route
    'route_prefix'     => 'sso',
    'route_middleware'  => ['web'],
];
```

---

## 5. Routes yang Tersedia

Package otomatis mendaftarkan route berikut:

| Route | Name | Method | Fungsi |
|---|---|---|---|
| `/sso/redirect` | `sso.redirect` | GET | Redirect ke halaman login SSO |
| `/sso/callback` | `sso.callback` | GET | Handle callback setelah login SSO |
| `/sso/logout` | `sso.logout` | POST | Logout dari aplikasi |

### Tombol Login di Blade

```html
<a href="{{ route('sso.redirect') }}" class="btn btn-primary">
    Login dengan SSO Universitas Pahlawan
</a>
```

### Tombol Logout di Blade

```html
<form method="POST" action="{{ route('sso.logout') }}">
    @csrf
    <button type="submit">Logout</button>
</form>
```

---

## 6. Event: Custom Post-Login Logic

Package akan fire event `SSOUserAuthenticated` setelah user berhasil login. Gunakan ini untuk logic spesifik aplikasi Anda (sync data dosen, publikasi, dll).

### Buat Listener

```php
// app/Listeners/HandleSSOLogin.php

namespace App\Listeners;

use WebKampus\SSOClient\Events\SSOUserAuthenticated;

class HandleSSOLogin
{
    public function handle(SSOUserAuthenticated $event): void
    {
        $user      = $event->user;
        $userInfo  = $event->userInfo;
        $token     = $event->accessToken;

        // Contoh: Sync data dosen dari SAINS
        if ($user->role === 'dosen') {
            $user->dosen()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name'     => $userInfo['name'],
                    'nidn'     => $userInfo['profile']['nidn'] ?? $user->username,
                    'prodi'    => $userInfo['profile']['prodi'] ?? null,
                    'fakultas' => $userInfo['profile']['fakultas'] ?? null,
                ]
            );
        }
    }
}
```

### Daftarkan Listener

Di `app/Providers/EventServiceProvider.php` (Laravel 10) atau `bootstrap/app.php` (Laravel 11+):

```php
// Laravel 11+
use WebKampus\SSOClient\Events\SSOUserAuthenticated;
use App\Listeners\HandleSSOLogin;

->withEvents(discover: [
    __DIR__.'/../app/Listeners',
])

// Atau manual:
Event::listen(SSOUserAuthenticated::class, HandleSSOLogin::class);
```

---

## 7. SSOClient Helper

Gunakan `SSOClient` untuk mengakses SAINS proxy endpoints dari SSO Server:

```php
use WebKampus\SSOClient\SSOClient;

// Inject via constructor atau resolve dari container
$ssoClient = app(SSOClient::class);

// Ambil token dari session (tersimpan otomatis saat login)
$token = SSOClient::getSessionToken();

// Panggil SAINS endpoints
$fakultas  = $ssoClient->getFakultas($token);
$prodi     = $ssoClient->getProdi($token);
$dosen     = $ssoClient->getDosen($token, 'username_dosen');
$mahasiswa = $ssoClient->getMahasiswa($token, 'nim_mahasiswa');

// Atau ambil user info lengkap
$userInfo = $ssoClient->getUserInfo($token);
```

---

## 8. Contoh Implementasi di LPPM

### `.env`
```env
SSO_URL=http://sso.universitaspahlawan.ac.id
SSO_CLIENT_ID=9f1a2b3c-xxxx-xxxx-xxxx
SSO_CLIENT_SECRET=abc123secret
SSO_REDIRECT_URI=http://lppm.universitaspahlawan.ac.id/sso/callback
SSO_REDIRECT_AFTER_LOGIN=/dasbor
```

### `config/sso.php` (setelah publish)
```php
'role_redirects' => [
    'admin'        => '/admin',
    'petugas-lppm' => '/admin',
    'dosen'        => '/dasbor',
],
```

### `app/Listeners/SyncDosenOnSSOLogin.php`
```php
namespace App\Listeners;

use App\Services\SainsApiService;
use WebKampus\SSOClient\Events\SSOUserAuthenticated;
use WebKampus\SSOClient\SSOClient;

class SyncDosenOnSSOLogin
{
    public function handle(SSOUserAuthenticated $event): void
    {
        $user     = $event->user;
        $userInfo = $event->userInfo;
        $token    = $event->accessToken;

        // Sync data dosen dari profile SSO
        if ($user->hasRole('dosen')) {
            $user->dosen()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name'     => $userInfo['name'] ?? $user->name,
                    'email'    => $userInfo['email'] ?? null,
                    'nidn'     => $userInfo['profile']['nidn'] ?? $user->username,
                    'prodi'    => $userInfo['profile']['prodi'] ?? null,
                    'fakultas' => $userInfo['profile']['fakultas'] ?? null,
                ]
            );

            // Sync detail dosen via SAINS API
            app(SainsApiService::class)->syncDosen($user, $token);
        }

        // Sync publikasi saat login pertama
        if ($user->last_login_at === null) {
            app(\App\Services\PublikasiSyncService::class)->sync($user);
        }

        $user->update(['last_login_at' => now()]);
    }
}
```

---

## 9. Struktur Package

```
sso-client-package/
├── composer.json
├── config/
│   └── sso.php                          # Konfigurasi lengkap
├── database/
│   └── migrations/
│       └── 2026_01_01_000000_add_sso_columns_to_users_table.php
├── routes/
│   └── web.php                          # Route: redirect, callback, logout
├── src/
│   ├── Events/
│   │   └── SSOUserAuthenticated.php     # Event post-login
│   ├── Http/
│   │   └── Controllers/
│   │       └── SSOController.php        # OAuth flow controller
│   ├── SSOClient.php                    # API helper class
│   └── SSOClientServiceProvider.php     # Service provider
├── LICENSE
└── README.md
```

---

## 10. Lisensi

The MIT License (MIT). Silakan lihat [License File](LICENSE) untuk informasi lebih lanjut.
