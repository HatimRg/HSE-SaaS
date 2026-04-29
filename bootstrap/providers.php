<?php

return [
    // Core Foundation Providers (Must be first)
    Illuminate\Foundation\Providers\FoundationServiceProvider::class,
    Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,

    // Core Service Providers
    Illuminate\Filesystem\FilesystemServiceProvider::class,
    Illuminate\Cache\CacheServiceProvider::class,
    Illuminate\Database\DatabaseServiceProvider::class,
    Illuminate\Cookie\CookieServiceProvider::class,
    Illuminate\Encryption\EncryptionServiceProvider::class,
    Illuminate\Hashing\HashServiceProvider::class,
    Illuminate\Session\SessionServiceProvider::class,
    Illuminate\Validation\ValidationServiceProvider::class,
    Illuminate\View\ViewServiceProvider::class,
    Illuminate\Pagination\PaginationServiceProvider::class,
    Illuminate\Translation\TranslationServiceProvider::class,

    // Auth & Security
    Illuminate\Auth\AuthServiceProvider::class,
    Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,

    // Communication
    Illuminate\Mail\MailServiceProvider::class,
    Illuminate\Notifications\NotificationServiceProvider::class,
    Illuminate\Broadcasting\BroadcastServiceProvider::class,

    // Queue & Bus
    Illuminate\Bus\BusServiceProvider::class,
    Illuminate\Queue\QueueServiceProvider::class,
    Illuminate\Redis\RedisServiceProvider::class,
    Illuminate\Pipeline\PipelineServiceProvider::class,

    // Application Service Providers
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
];
