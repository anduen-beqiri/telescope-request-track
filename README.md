# Telescope Request ID

Automatically attach a Telescope-compatible request identifier to every JSON response and propagate it via headers.

## Installation

```bash
composer require bekand/telescope-request-id
```

The package registers itself automatically thanks to Laravel's package auto-discovery.

## Configuration

Publish the configuration to customise header names, JSON keys, or disable the middleware globally.

```bash
php artisan vendor:publish --provider="BekAnd\\TelescopeRequestTrack\\TelescopeRequestTrackServiceProvider" --tag=config
```

Available options (see `config/telescope-request-id.php`):

- `enabled` (bool): Toggle the middleware globally (default `true`).
- `show_in_json` (bool): Control whether the request ID is appended to JSON payloads (default `true`).
- `key` (string): JSON key added to responses (default `request_id`).
- `header` (string): Header used for propagation (default `X-Request-Id`).
- `routes` (array): Middleware groups that should receive the request ID middleware (default `['api', 'web']`).
- `except` (array): URI patterns (same semantics as `$request->is()`) that should not receive a request ID.

## Usage

The middleware is prepended globally, so every JSON response automatically gains a request identifier. Responses that already define the configured JSON key are respected.

If a response body is a JSON list, it's wrapped into a structure like:

```json
{
  "data": [1, 2, 3],
  "request_id": "..."
}
```

### Skipping specific routes

Wrap routes with the provided `SkipRequestId` middleware to bypass augmentation:

```php
use BekAnd\TelescopeRequestTrack\Middleware\SkipRequestId;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
})->middleware(SkipRequestId::class);
```

### Telescope integration

When `laravel/telescope` is installed, the package tags request entries with the request identifier so you can filter them easily:

```
request-id: <value>
```

## Testing

```bash
composer test
```

## License

Released under the MIT License.
