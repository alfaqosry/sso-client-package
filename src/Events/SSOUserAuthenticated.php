<?php

namespace WebKampus\SSOClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SSOUserAuthenticated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user  User model instance
     * @param  array<string, mixed>  $userInfo  Raw data dari /api/userinfo SSO
     * @param  string  $accessToken  OAuth access token
     * @param  string|null  $refreshToken  OAuth refresh token (jika tersedia)
     */
    public function __construct(
        public $user,
        public array $userInfo,
        public string $accessToken,
        public ?string $refreshToken = null,
    ) {}
}
