<?php

namespace Vortechron\NightwatchTesting;

use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Vortechron\NightwatchTesting\Commands\NightwatchTestCommand;

class NightwatchTestingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nightwatch-testing.php', 'nightwatch-testing');
    }

    public function boot(): void
    {
        $this->app['config']->set('nightwatch-testing.detected_guard', $this->detectAuthGuard());

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        $this->app['config']->set('cache.stores.failing', [
            'driver' => 'failing',
        ]);

        Cache::extend('failing', fn () => Cache::repository(new class extends ArrayStore
        {
            public function put($key, $value, $seconds)
            {
                return false;
            }

            public function forget($key)
            {
                return false;
            }
        }));

        if ($this->app->runningInConsole()) {
            $this->commands([
                NightwatchTestCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/nightwatch-testing.php' => config_path('nightwatch-testing.php'),
            ], 'nightwatch-testing-config');
        }
    }

    protected function detectAuthGuard(): ?string
    {
        if (class_exists(\Laravel\Sanctum\HasApiTokens::class)) {
            logger()->info('[Nightwatch] Detected auth guard: sanctum');
            return 'sanctum';
        }

        if (class_exists(\Laravel\Passport\HasApiTokens::class)) {
            logger()->info('[Nightwatch] Detected auth guard: api (Passport)');
            return 'api';
        }

        logger()->warning('[Nightwatch] No auth guard detected (Sanctum/Passport not found)');
        return null;
    }
}
