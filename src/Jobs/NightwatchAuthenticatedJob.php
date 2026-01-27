<?php

namespace Vortechron\NightwatchTesting\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NightwatchAuthenticatedJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $message = 'Nightwatch authenticated job executed'
    ) {}

    public function handle(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $user = Auth::guard($guard)->user();

        Log::info($this->message, [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'auth_check' => Auth::guard($guard)->check(),
            'guard' => $guard,
        ]);
    }
}
