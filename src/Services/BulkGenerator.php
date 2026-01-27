<?php

namespace Vortechron\NightwatchTesting\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Vortechron\NightwatchTesting\Jobs\NightwatchFailingJob;
use Vortechron\NightwatchTesting\Jobs\NightwatchReleasingJob;
use Vortechron\NightwatchTesting\Jobs\NightwatchTestJob;
use Vortechron\NightwatchTesting\Mail\NightwatchQueuedMail;
use Vortechron\NightwatchTesting\Mail\NightwatchTestMail;
use Vortechron\NightwatchTesting\Notifications\NightwatchTestNotification;

class BulkGenerator
{
    /**
     * Generate bulk entries for a given type.
     */
    public function generate(string $type, int $count, array $options = [], ?callable $onProgress = null): int
    {
        switch ($type) {
            case 'queries':
                return $this->generateQueries($count, $onProgress);
            case 'cache':
                return $this->generateCache($count, $onProgress);
            case 'jobs':
                return $this->generateJobs($count, $options, $onProgress);
            case 'mail':
                return $this->generateMail($count, $options, $onProgress);
            case 'notifications':
                return $this->generateNotifications($count, $options, $onProgress);
            case 'exceptions':
                return $this->generateExceptions($count, $options, $onProgress);
            case 'all':
                $total = 0;
                $types = ['queries', 'cache', 'jobs', 'mail', 'notifications', 'exceptions'];
                foreach ($types as $t) {
                    $total += $this->generate($t, $count, $options, $onProgress);
                }
                return $total;
            default:
                return 0;
        }
    }

    protected function generateQueries(int $count, ?callable $onProgress): int
    {
        $usersTable = 'users';
        $slowInterval = config('nightwatch-testing.bulk.slow_query_interval', 5);
        $slowDelay = config('nightwatch-testing.bulk.slow_query_delay', 0.3);

        for ($i = 0; $i < $count; $i++) {
            DB::table($usersTable)->where('id', '>', $i)->first();
            DB::table($usersTable)->selectRaw('COUNT(*), MAX(id)')->first();

            if ($i % $slowInterval === 0) {
                try {
                    DB::select("SELECT SLEEP({$slowDelay})");
                } catch (Exception) {
                    usleep($slowDelay * 1000000);
                    DB::table($usersTable)->count();
                }
            }

            if ($onProgress) $onProgress();
        }

        return $count;
    }

    protected function generateCache(int $count, ?callable $onProgress): int
    {
        for ($i = 0; $i < $count; $i++) {
            $key = "nightwatch_bulk_{$i}_" . now()->timestamp . "_" . uniqid();
            Cache::put($key, "value_{$i}", 60);       // WRITE
            Cache::get($key);                          // HIT
            Cache::get("missing_{$i}_" . uniqid());   // MISS
            Cache::forget($key);                       // DELETE

            if ($onProgress) $onProgress();
        }

        return $count;
    }

    protected function generateJobs(int $count, array $options, ?callable $onProgress): int
    {
        for ($i = 0; $i < $count; $i++) {
            NightwatchTestJob::dispatch("Bulk job #{$i}");
            if ($i % 3 === 0) NightwatchReleasingJob::dispatch("Bulk release #{$i}");
            if ($i % 5 === 0 && !($options['skip-failing-job'] ?? false)) {
                NightwatchFailingJob::dispatch("Bulk fail #{$i}");
            }

            if ($onProgress) $onProgress();
        }

        return $count;
    }

    protected function generateMail(int $count, array $options, ?callable $onProgress): int
    {
        if ($options['skip-mail'] ?? false) return 0;

        $mailTo = config('nightwatch-testing.mail.to', 'nightwatch-test@example.com');

        for ($i = 0; $i < $count; $i++) {
            $subject = "Nightwatch Bulk Test #{$i} - " . now()->format('H:i:s');
            if ($i % 2 === 0) {
                Mail::to($mailTo)->send(new NightwatchTestMail($subject));
            } else {
                Mail::to($mailTo)->queue(new NightwatchQueuedMail($subject));
            }

            if ($onProgress) $onProgress();
        }

        return $count;
    }

    protected function generateNotifications(int $count, array $options, ?callable $onProgress): int
    {
        if ($options['skip-notifications'] ?? false) return 0;

        $userModel = config('nightwatch-testing.user_model', 'App\\Models\\User');
        if (!class_exists($userModel)) return 0;

        $user = $userModel::first();
        if (!$user) return 0;

        for ($i = 0; $i < $count; $i++) {
            $user->notify(new NightwatchTestNotification("Bulk notification #{$i}"));
            if ($onProgress) $onProgress();
        }

        return $count;
    }

    protected function generateExceptions(int $count, array $options, ?callable $onProgress): int
    {
        if ($options['skip-exception'] ?? false) return 0;

        for ($i = 0; $i < $count; $i++) {
            try {
                throw new Exception("Bulk exception #{$i} - " . now()->toDateTimeString());
            } catch (Exception $e) {
                report($e);
            }
            if ($onProgress) $onProgress();
        }

        return $count;
    }
}
