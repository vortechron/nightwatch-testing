<?php

namespace Vortechron\NightwatchTesting\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Vortechron\NightwatchTesting\Jobs\NightwatchFailingJob;
use Vortechron\NightwatchTesting\Jobs\NightwatchOutgoingRequestJob;
use Vortechron\NightwatchTesting\Jobs\NightwatchReleasingJob;
use Vortechron\NightwatchTesting\Jobs\NightwatchTestJob;
use Vortechron\NightwatchTesting\Mail\NightwatchQueuedMail;
use Vortechron\NightwatchTesting\Mail\NightwatchTestMail;
use Vortechron\NightwatchTesting\Notifications\NightwatchTestNotification;

class NightwatchTestCommand extends Command
{
    protected $signature = 'nightwatch:test
        {--skip-mail : Skip sending test mail}
        {--skip-exception : Skip triggering exception}
        {--skip-requests : Skip outgoing HTTP request tests}
        {--skip-internal-requests : Skip internal API request tests}
        {--skip-failing-job : Skip failing job dispatch}
        {--skip-notifications : Skip notification tests}';

    protected $description = 'Trigger all Nightwatch monitoring events for testing with multiple variations';

    public function handle(): int
    {
        $this->info('Starting Nightwatch test events...');
        $this->newLine();

        $this->testCommands();
        $this->testNotifications();
        $this->testQueries();
        $this->testCache();
        $this->testJobs();
        $this->testMail();
        $this->testInternalRequests();
        $this->testAuthenticatedRequests();
        $this->testExceptions();
        $this->testOutgoingRequests();

        $this->newLine();
        $this->components->info('All Nightwatch test events triggered!');

        return self::SUCCESS;
    }

    /**
     * Test command execution - successful and unsuccessful
     */
    protected function testCommands(): void
    {
        $this->components->info('Commands');

        // Successful command - this command itself
        $this->components->task('Successful command (this execution)', fn () => true);

        // Run another successful command
        $this->components->task('Successful command (artisan about)', function () {
            Artisan::call('about', ['--only' => 'environment']);

            return true;
        });

        // Unsuccessful command - call with invalid option
        $this->components->task('Unsuccessful command (invalid arguments)', function () {
            try {
                Artisan::call('route:list', ['--invalid-option-xyz' => true]);
            } catch (Exception) {
                return true; // Expected to fail
            }

            return false;
        });
    }

    /**
     * Test database queries - fast and slow
     */
    protected function testQueries(): void
    {
        $this->components->info('Database Queries');

        // Get the users table name - use config or default
        $usersTable = 'users';

        // Fast queries
        $this->components->task('Fast query (simple count)', function () use ($usersTable) {
            DB::table($usersTable)->count();

            return true;
        });

        $this->components->task('Fast query (indexed lookup)', function () use ($usersTable) {
            DB::table($usersTable)->where('id', 1)->first();

            return true;
        });

        $this->components->task('Fast query (limited select)', function () use ($usersTable) {
            DB::table($usersTable)->select('id', 'email')->limit(5)->get();

            return true;
        });

        // Slow queries (simulated with SLEEP or complex operations)
        $this->components->task('Slow query (with sleep)', function () use ($usersTable) {
            // Use database-level sleep for accurate slow query detection
            try {
                DB::select('SELECT SLEEP(0.5) as delay');
            } catch (Exception) {
                // SQLite doesn't support SLEEP, use PHP sleep instead
                usleep(500000);
                DB::table($usersTable)->count();
            }

            return true;
        });

        $this->components->task('Slow query (complex aggregation)', function () use ($usersTable) {
            DB::table($usersTable)
                ->selectRaw('COUNT(*) as count, MAX(created_at) as latest, MIN(created_at) as oldest')
                ->first();

            return true;
        });
    }

    /**
     * Test cache operations - writes, hits, misses, deletes
     */
    protected function testCache(): void
    {
        $this->components->info('Cache Operations');

        $testKey = 'nightwatch_test_'.now()->timestamp;
        $missingKey = 'nightwatch_missing_'.now()->timestamp;

        // First, clean up any existing test keys to ensure clean state
        Cache::forget($testKey);
        Cache::forget($testKey.'_forever');
        Cache::forget($testKey.'_add');
        Cache::forget($testKey.'_hit1');
        Cache::forget($testKey.'_hit2');
        Cache::forget($testKey.'_hit3');

        // WRITE - Store values first
        $this->components->task('Cache WRITE (put)', function () use ($testKey) {
            Cache::put($testKey, 'test_value', 60);

            return true;
        });

        $this->components->task('Cache WRITE (forever)', function () use ($testKey) {
            Cache::forever($testKey.'_forever', 'permanent_value');

            return true;
        });

        $this->components->task('Cache WRITE (add - new key)', function () use ($testKey) {
            Cache::add($testKey.'_add', 'added_value', 60);

            return true;
        });

        // Write additional keys specifically for hit testing
        $this->components->task('Cache WRITE (keys for hit testing)', function () use ($testKey) {
            Cache::put($testKey.'_hit1', 'hit_value_1', 60);
            Cache::put($testKey.'_hit2', 'hit_value_2', 60);
            Cache::put($testKey.'_hit3', 'hit_value_3', 60);

            return true;
        });

        // HIT - Multiple explicit get() calls on existing keys
        $this->components->task('Cache HIT (get main key)', function () use ($testKey) {
            $value = Cache::get($testKey);

            return $value === 'test_value';
        });

        $this->components->task('Cache HIT (get forever key)', function () use ($testKey) {
            $value = Cache::get($testKey.'_forever');

            return $value === 'permanent_value';
        });

        $this->components->task('Cache HIT (get added key)', function () use ($testKey) {
            $value = Cache::get($testKey.'_add');

            return $value === 'added_value';
        });

        $this->components->task('Cache HIT (get hit1 key)', function () use ($testKey) {
            $value = Cache::get($testKey.'_hit1');

            return $value === 'hit_value_1';
        });

        $this->components->task('Cache HIT (get hit2 key)', function () use ($testKey) {
            $value = Cache::get($testKey.'_hit2');

            return $value === 'hit_value_2';
        });

        $this->components->task('Cache HIT (get hit3 key)', function () use ($testKey) {
            $value = Cache::get($testKey.'_hit3');

            return $value === 'hit_value_3';
        });

        // Multiple hits on same key to ensure hits are recorded
        $this->components->task('Cache HIT (multiple reads same key)', function () use ($testKey) {
            Cache::get($testKey);
            Cache::get($testKey);
            Cache::get($testKey);

            return true;
        });

        // Pull retrieves and removes - still a hit before delete
        $this->components->task('Cache HIT (pull - get then delete)', function () use ($testKey) {
            $value = Cache::pull($testKey.'_hit3');

            return $value === 'hit_value_3';
        });

        // MISS - Get non-existent keys
        $this->components->task('Cache MISS (get non-existent)', function () use ($missingKey) {
            $value = Cache::get($missingKey);

            return $value === null;
        });

        $this->components->task('Cache MISS (get another non-existent)', function () use ($missingKey) {
            $value = Cache::get($missingKey.'_does_not_exist');

            return $value === null;
        });

        $this->components->task('Cache MISS (get with default)', function () use ($missingKey) {
            $value = Cache::get($missingKey.'_with_default', 'default_value');

            return $value === 'default_value';
        });

        // Remember on new key - triggers miss then write
        $this->components->task('Cache MISS then WRITE (remember new)', function () use ($missingKey) {
            $value = Cache::remember($missingKey.'_remember', 60, fn () => 'new_remembered_value');

            return $value === 'new_remembered_value';
        });

        // Now remember on existing key should be a hit
        $this->components->task('Cache HIT (remember existing)', function () use ($missingKey) {
            $value = Cache::remember($missingKey.'_remember', 60, fn () => 'should_not_be_called');

            return $value === 'new_remembered_value';
        });

        // DELETE
        $this->components->task('Cache DELETE (forget)', function () use ($testKey) {
            Cache::forget($testKey);

            return true;
        });

        $this->components->task('Cache DELETE (forget forever key)', function () use ($testKey) {
            Cache::forget($testKey.'_forever');

            return true;
        });

        $this->components->task('Cache DELETE (cleanup remaining keys)', function () use ($testKey, $missingKey) {
            Cache::forget($testKey.'_add');
            Cache::forget($testKey.'_hit1');
            Cache::forget($testKey.'_hit2');
            Cache::forget($missingKey.'_remember');

            return true;
        });
    }

    /**
     * Test job dispatching - processed, failed, released
     */
    protected function testJobs(): void
    {
        $this->components->info('Jobs');

        // PROCESSED - Normal successful job
        $this->components->task('Job PROCESSED (dispatch successful)', function () {
            NightwatchTestJob::dispatch('Nightwatch test - successful job at '.now()->toDateTimeString());

            return true;
        });

        $this->components->task('Job PROCESSED (dispatch another)', function () {
            NightwatchTestJob::dispatch('Nightwatch test - another successful job');

            return true;
        });

        // RELEASED - Job that releases itself back to queue
        $this->components->task('Job RELEASED (dispatch releasing job)', function () {
            NightwatchReleasingJob::dispatch('Nightwatch test - releasing job');

            return true;
        });

        // FAILED - Job that throws an exception
        if (! $this->option('skip-failing-job')) {
            $this->components->task('Job FAILED (dispatch failing job)', function () {
                NightwatchFailingJob::dispatch('Nightwatch test - failing job');

                return true;
            });
        } else {
            $this->components->warn('Skipping failing job test');
        }
    }

    /**
     * Test notifications
     */
    protected function testNotifications(): void
    {
        $this->components->info('Notifications');

        if ($this->option('skip-notifications')) {
            $this->components->warn('Skipping notification tests');

            return;
        }

        $this->components->task('Send notification', function () {
            $userModel = config('nightwatch-testing.user_model', 'App\\Models\\User');

            if (! class_exists($userModel)) {
                $this->components->warn("User model {$userModel} not found");

                return false;
            }

            $user = $userModel::first();
            if ($user) {
                $user->notify(new NightwatchTestNotification('Test notification at '.now()->toDateTimeString()));

                return true;
            }

            return false;
        });
    }

    /**
     * Test mail sending - sent, queued, failed
     */
    protected function testMail(): void
    {
        $this->components->info('Mail');

        if ($this->option('skip-mail')) {
            $this->components->warn('Skipping mail tests');

            return;
        }

        $mailTo = config('nightwatch-testing.mail.to', 'nightwatch-test@example.com');
        $mailCc = config('nightwatch-testing.mail.cc', 'nightwatch-cc@example.com');

        // SENT - Synchronous mail
        $this->components->task('Mail SENT (synchronous)', function () use ($mailTo) {
            Mail::to($mailTo)->send(
                new NightwatchTestMail('Sync email at '.now()->toDateTimeString())
            );

            return true;
        });

        // QUEUED - Queued mail
        $this->components->task('Mail QUEUED (async)', function () use ($mailTo) {
            Mail::to($mailTo)->queue(
                new NightwatchQueuedMail('Queued email at '.now()->toDateTimeString())
            );

            return true;
        });

        // SENT with multiple recipients
        $this->components->task('Mail SENT (multiple recipients)', function () use ($mailTo, $mailCc) {
            Mail::to([$mailTo, 'recipient2@example.com'])
                ->cc($mailCc)
                ->send(new NightwatchTestMail('Multi-recipient email'));

            return true;
        });

        // QUEUED with delay
        $this->components->task('Mail QUEUED (with delay)', function () use ($mailTo) {
            Mail::to($mailTo)->later(
                now()->addSeconds(5),
                new NightwatchQueuedMail('Delayed queued email')
            );

            return true;
        });
    }

    /**
     * Test internal API requests - incoming requests to app endpoints
     */
    protected function testInternalRequests(): void
    {
        $this->components->info('Internal API Requests (Incoming)');

        if ($this->option('skip-internal-requests')) {
            $this->components->warn('Skipping internal API request tests');

            return;
        }

        $baseUrl = config('app.url');
        $endpoints = config('nightwatch-testing.internal_endpoints', []);

        // 2XX Success endpoints
        foreach ($endpoints['success'] ?? [] as $endpoint) {
            $this->components->task("Internal 2XX ({$endpoint['method']} {$endpoint['path']})", function () use ($baseUrl, $endpoint) {
                try {
                    $response = Http::timeout(5)->{strtolower($endpoint['method'])}($baseUrl.$endpoint['path']);

                    return $response->successful();
                } catch (Exception) {
                    return false;
                }
            });
        }

        // 3XX Redirect endpoints
        foreach ($endpoints['redirect'] ?? [] as $endpoint) {
            $this->components->task("Internal 3XX ({$endpoint['method']} {$endpoint['path']})", function () use ($baseUrl, $endpoint) {
                try {
                    $response = Http::timeout(5)
                        ->withOptions(['allow_redirects' => false])
                        ->{strtolower($endpoint['method'])}($baseUrl.$endpoint['path']);

                    return $response->redirect() || $response->status() === 302;
                } catch (Exception) {
                    return false;
                }
            });
        }

        // 4XX Client error endpoints
        foreach ($endpoints['client_error'] ?? [] as $endpoint) {
            $expectedStatuses = $endpoint['expected_status'] ?? [400, 401, 403, 404, 422];
            $this->components->task("Internal 4XX ({$endpoint['method']} {$endpoint['path']})", function () use ($baseUrl, $endpoint, $expectedStatuses) {
                try {
                    $response = Http::timeout(5)
                        ->acceptJson()
                        ->{strtolower($endpoint['method'])}($baseUrl.$endpoint['path']);

                    return in_array($response->status(), $expectedStatuses);
                } catch (Exception) {
                    return false;
                }
            });
        }
    }

    /**
     * Test authenticated API requests - using Sanctum token
     */
    protected function testAuthenticatedRequests(): void
    {
        $this->components->info('Authenticated API Requests');

        if ($this->option('skip-internal-requests')) {
            $this->components->warn('Skipping authenticated API request tests');

            return;
        }

        $baseUrl = config('app.url');
        $userModel = config('nightwatch-testing.user_model', 'App\\Models\\User');
        $endpoints = config('nightwatch-testing.authenticated_endpoints', []);

        if (empty($endpoints)) {
            $this->components->warn('No authenticated endpoints configured - skipping');

            return;
        }

        if (! class_exists($userModel)) {
            $this->components->warn("User model {$userModel} not found - skipping authenticated tests");

            return;
        }

        $user = $userModel::first();

        if (! $user) {
            $this->components->warn('No user found - skipping authenticated request tests');

            return;
        }

        // Check if user model has createToken method (Sanctum)
        if (! method_exists($user, 'createToken')) {
            $this->components->warn('User model does not have createToken method (Sanctum required) - skipping');

            return;
        }

        // Create a temporary Sanctum token for testing
        $token = $user->createToken('nightwatch-test')->plainTextToken;

        // Test authenticated endpoints
        foreach ($endpoints as $endpoint) {
            $method = strtolower($endpoint['method'] ?? 'GET');
            $path = $endpoint['path'] ?? '';
            $label = $endpoint['label'] ?? "Authenticated ({$method} {$path})";

            $this->components->task($label, function () use ($baseUrl, $token, $method, $path) {
                try {
                    $response = Http::timeout(5)
                        ->withToken($token)
                        ->acceptJson()
                        ->{$method}($baseUrl.$path);

                    return $response->successful();
                } catch (Exception) {
                    return false;
                }
            });
        }

        // Clean up the test token
        $this->components->task('Cleanup test token', function () use ($user) {
            $user->tokens()->where('name', 'nightwatch-test')->delete();

            return true;
        });
    }

    /**
     * Test outgoing HTTP requests - 2XX, 3XX, 4XX, 5XX responses (dispatched as jobs)
     */
    protected function testOutgoingRequests(): void
    {
        $this->components->info('Outgoing HTTP Requests (Jobs)');

        if ($this->option('skip-requests')) {
            $this->components->warn('Skipping outgoing HTTP request tests');

            return;
        }

        $baseUrl = config('nightwatch-testing.outgoing_request_url', 'https://httpbin.org');

        $requests = [
            // One request per status category
            ['method' => 'GET', 'path' => '/status/200', 'label' => 'Request 2XX (200 OK)'],
            ['method' => 'GET', 'path' => '/status/301', 'label' => 'Request 3XX (301 Moved Permanently)'],
            ['method' => 'GET', 'path' => '/status/404', 'label' => 'Request 4XX (404 Not Found)'],
            ['method' => 'GET', 'path' => '/status/500', 'label' => 'Request 5XX (500 Internal Server Error)'],
            ['method' => 'GET', 'path' => '/delay/10', 'label' => 'Request Timeout (10s delay)'],
        ];

        foreach ($requests as $request) {
            $this->components->task("Dispatch job: {$request['label']}", function () use ($baseUrl, $request) {
                NightwatchOutgoingRequestJob::dispatch(
                    $request['method'],
                    $baseUrl.$request['path'],
                    $request['label']
                );

                return true;
            });
        }
    }

    /**
     * Test exception reporting
     */
    protected function testExceptions(): void
    {
        $this->components->info('Exceptions');

        if (! $this->option('skip-exception')) {
            $this->components->task('Trigger exception', function () {
                try {
                    throw new Exception('Nightwatch test exception at '.now()->toDateTimeString());
                } catch (Exception $e) {
                    report($e);

                    return true;
                }
            });
        } else {
            $this->components->warn('Skipping exception test');
        }
    }
}
