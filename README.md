# Nightwatch Testing

A Laravel package for testing [Laravel Nightwatch](https://nightwatch.dev) monitoring events with comprehensive variations.

## Installation

### Local Development (Path Repository)

Add the package to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/nightwatch-testing"
        }
    ],
    "require-dev": {
        "vortechron/nightwatch-testing": "@dev"
    }
}
```

Then run:

```bash
composer update vortechron/nightwatch-testing
```

### Via Packagist (Coming Soon)

```bash
composer require --dev vortechron/nightwatch-testing
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=nightwatch-testing-config
```

This will create a `config/nightwatch-testing.php` file with the following options:

```php
return [
    // User model for notification testing
    'user_model' => 'App\\Models\\User',

    // Internal endpoints to test
    'internal_endpoints' => [
        'success' => [
            ['method' => 'GET', 'path' => '/health', 'description' => 'Health check'],
        ],
        'redirect' => [
            ['method' => 'GET', 'path' => '/settings', 'description' => 'Settings redirect'],
        ],
        'client_error' => [
            ['method' => 'GET', 'path' => '/api/user', 'expected_status' => [401, 403]],
        ],
    ],

    // Mail test recipients
    'mail' => [
        'to' => 'nightwatch-test@example.com',
        'cc' => 'nightwatch-cc@example.com',
    ],

    // Outgoing request test URL
    'outgoing_request_url' => 'https://httpbin.org',
];
```

## Usage

Run all Nightwatch test events:

```bash
php artisan nightwatch:test
```

### Available Options

| Option | Description |
|--------|-------------|
| `--skip-mail` | Skip sending test mail |
| `--skip-exception` | Skip triggering exception |
| `--skip-requests` | Skip outgoing HTTP request tests |
| `--skip-internal-requests` | Skip internal API request tests |
| `--skip-failing-job` | Skip failing job dispatch |
| `--skip-notifications` | Skip notification tests |

### Examples

```bash
# Run all tests
php artisan nightwatch:test

# Skip mail and external requests
php artisan nightwatch:test --skip-mail --skip-requests

# Only test cache and queries
php artisan nightwatch:test --skip-mail --skip-requests --skip-internal-requests --skip-exception --skip-failing-job --skip-notifications
```

## Test Events Triggered

### Commands
- Successful command execution
- Unsuccessful command (invalid arguments)

### Database Queries
- Fast queries (count, indexed lookup, limited select)
- Slow queries (with sleep, complex aggregation)

### Cache Operations
- **WRITE**: `put`, `forever`, `add`
- **HIT**: Multiple `get` calls on existing keys
- **MISS**: `get` on non-existent keys
- **DELETE**: `forget`, cleanup

### Jobs
- **PROCESSED**: Successful job dispatch
- **RELEASED**: Job that releases back to queue
- **FAILED**: Job that throws exception

### Notifications
- Database notification to first user

### Mail
- **SENT**: Synchronous mail
- **QUEUED**: Async mail via queue
- **SENT**: Multiple recipients with CC
- **QUEUED**: Delayed mail

### HTTP Requests (Internal)
- 2XX Success endpoints
- 3XX Redirect endpoints
- 4XX Client error endpoints

### HTTP Requests (Outgoing)
- 2XX: 200, 201, 204
- 3XX: 301, 302
- 4XX: 400, 401, 403, 404, 422, 429
- 5XX: 500, 502, 503, 504

### Exceptions
- Reported exception via `report()`

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## License

MIT
