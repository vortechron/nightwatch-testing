<?php

namespace Vortechron\NightwatchTesting\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NightwatchTestJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $message = 'Nightwatch test job executed'
    ) {}

    public function handle(): void
    {
        Log::info($this->message);
    }
}
