<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class NgrokServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Dynamically detect ngrok and force HTTPS
        if ($this->isNgrokRequest()) {
            URL::forceScheme('https');

            // Get the ngrok host from the forwarded headers
            $host = request()->header('X-Forwarded-Host') ?? request()->header('Host');

            if ($host) {
                // Update the APP_URL dynamically
                config(['app.url' => 'https://' . $host]);
                URL::forceRootUrl('https://' . $host);
            }
        }
    }

    /**
     * Check if the current request is coming through ngrok
     */
    private function isNgrokRequest(): bool
    {
        $host = request()->header('X-Forwarded-Host') ?? request()->header('Host') ?? '';

        return str_contains($host, 'ngrok') ||
            str_contains($host, 'ngrok-free.app') ||
            request()->header('X-Forwarded-Proto') === 'https';
    }
}
