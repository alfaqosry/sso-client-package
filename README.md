# WebKampus SSO Client Package

Paket Laravel untuk autentikasi Single Sign-On (SSO) OAuth2. Paket ini mempermudah integrasi aplikasi klien (seperti LPPM, Portal Dosen, dll) dengan server SSO Utama Universitas Pahlawan.

---

## 1. Instalasi

### Cara A: Melalui Repository Lokal (Development)
Tambahkan konfigurasi `repositories` pada file `composer.json` di aplikasi Laravel Anda:

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
Lalu jalankan perintah terminal:
```bash
composer update
```

### Cara B: Dari GitHub / Packagist (Production)
Jika sudah dipublikasikan ke Packagist atau Git repository:
```bash
composer require webkampus/sso-client
```

---

## 2. Konfigurasi Environment (`.env`)

Tambahkan variabel berikut ke dalam file `.env` aplikasi klien Anda:

```env
SSO_URL=http://localhost:8000
SSO_CLIENT_ID=your_client_id_here
SSO_CLIENT_SECRET=your_client_secret_here
SSO_REDIRECT_URI=http://localhost:8001/sso/callback
```

---

## 3. Publikasi Konfigurasi (Opsional)

Anda dapat mempublikasikan file konfigurasi `sso.php` ke folder `config` aplikasi Anda menggunakan Artisan:

```bash
php artisan vendor:publish --tag=sso-config
```

---

## 4. Penggunaan / Contoh Implementasi

### A. Route Login & Callback
Biasanya, buat dua rute di file `routes/web.php` aplikasi Anda:

```php
use Illuminate\Support\Facades\Route;
use WebKampus\SSOClient\Http\Controllers\SSOController;

Route::get('/sso/redirect', [SSOController::class, 'redirectToSSO'])->name('sso.login');
Route::get('/sso/callback', [SSOController::class, 'handleSSOCallback'])->name('sso.callback');
```

### B. Tombol Login di Blade View
```html
<a href="{{ route('sso.login') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
    Login dengan SSO Universitas Pahlawan
</a>
```

---

## 5. Lisensi

The MIT License (MIT). Silakan lihat [License File](LICENSE) untuk informasi lebih lanjut.
