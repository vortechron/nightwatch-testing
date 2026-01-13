<?php

namespace Vortechron\NightwatchTesting;

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
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                NightwatchTestCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/nightwatch-testing.php' => config_path('nightwatch-testing.php'),
            ], 'nightwatch-testing-config');
        }
    }
}
