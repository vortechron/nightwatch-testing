<?php

namespace Vortechron\NightwatchTesting\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NightwatchFailingJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    public function __construct(
        public string $message = 'Nightwatch failing job'
    ) {}

    public function handle(): void
    {
        throw new Exception($this->message.' - intentionally failed at '.now()->toDateTimeString());
    }
}
