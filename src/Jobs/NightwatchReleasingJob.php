<?php

namespace Vortechron\NightwatchTesting\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NightwatchReleasingJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(
        public string $message = 'Nightwatch releasing job',
        public int $attemptCount = 0
    ) {}

    public function handle(): void
    {
        $this->attemptCount++;

        // Release back to queue on first attempt, succeed on subsequent attempts
        if ($this->attemptCount < 2) {
            Log::info($this->message.' - releasing back to queue (attempt '.$this->attemptCount.')');
            $this->release(5); // Release with 5 second delay

            return;
        }

        Log::info($this->message.' - completed after '.$this->attemptCount.' attempts');
    }
}
