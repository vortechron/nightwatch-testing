<?php

namespace Vortechron\NightwatchTesting\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NightwatchOutgoingRequestJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $method = 'GET',
        public string $url = '',
        public string $label = ''
    ) {}

    public function handle(): void
    {
        try {
            $response = Http::timeout(10)
                ->withOptions(['allow_redirects' => false])
                ->{strtolower($this->method)}($this->url);

            Log::info("Nightwatch outgoing request: {$this->label}", [
                'url' => $this->url,
                'method' => $this->method,
                'status' => $response->status(),
            ]);
        } catch (Exception $e) {
            Log::warning("Nightwatch outgoing request failed: {$this->label}", [
                'url' => $this->url,
                'method' => $this->method,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
