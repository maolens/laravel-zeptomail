<?php

namespace ZohoMail\LaravelZeptoMail;

use Illuminate\Mail\MailManager;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use ZohoMail\LaravelZeptoMail\Transport\ZeptoMailTransport;

class ZeptoMailServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/zeptomail.php',
            'zeptomail'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerMailTransport();
        $this->registerPublishing();
    }

    /**
     * Register the ZeptoMail mail transport.
     */
    protected function registerMailTransport(): void
    {
        $this->app->make(MailManager::class)->extend('zeptomail', function (array $config) {
            // Merge package config with mailer-specific config
            $config = array_merge(
                $this->app['config']->get('zeptomail', []),
                $config
            );

            return new ZeptoMailTransport(
                apiKey: Arr::get($config, 'api_key') ?? throw new \RuntimeException('ZeptoMail API key is required'),
                region: Arr::get($config, 'region'),
                customEndpoint: Arr::get($config, 'endpoint'),
                apiVersion: Arr::get($config, 'api_version'),
                timeout: Arr::get($config, 'timeout'),
                loggingEnabled: Arr::get($config, 'logging'),
                regionDomains: Arr::get($config, 'region_domains')
            );
        });
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/zeptomail.php' => config_path('zeptomail.php'),
            ], 'zeptomail-config');
        }
    }
}
