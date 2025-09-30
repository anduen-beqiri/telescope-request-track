<?php

namespace BekAnd\TelescopeRequestTrack;

use function is_array;
use function strcasecmp;
use function config_path;
use function function_exists;
use Illuminate\Routing\Router;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use BekAnd\TelescopeRequestTrack\Middleware\SkipRequestId;
use BekAnd\TelescopeRequestTrack\Middleware\AttachRequestId;

class TelescopeRequestTrackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/telescope-request-id.php',
            'telescope-request-id'
        );
    }

    public function boot(Router $router): void
    {
        $this->publishConfig();

        $this->app->booted(function () use ($router) {
            $this->registerMiddleware($router);
        });

        $this->registerTelescopeTagging();
    }

    protected function publishConfig(): void
    {
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__ . '/../config/telescope-request-id.php' => config_path('telescope-request-id.php'),
            ], 'config');
        }
    }

    protected function registerMiddleware(Router $router): void
    {
        $routes = Helpers::config('routes', []);

        if (! is_array($routes)) {
            return;
        }

        foreach ($routes as $route) {
            $router->pushMiddlewareToGroup($route, AttachRequestId::class);
        }

        if ($this->app->bound(Router::class)) {
            $router->aliasMiddleware('skip-request-id', SkipRequestId::class);
        }
    }

    protected function registerTelescopeTagging(): void
    {
        if (! class_exists(Telescope::class)) {
            return;
        }

        Telescope::tag(function (IncomingEntry $entry): array {
            if ($entry->type !== 'request') {
                return [];
            }

            $requestId = $this->extractRequestIdFromEntry($entry);

            return $requestId ? ['request-id:' . $requestId] : [];
        });
    }

    protected function extractRequestIdFromEntry(IncomingEntry $entry): ?string
    {
        $headers = $entry->content['headers'] ?? [];

        if (! is_array($headers)) {
            return null;
        }

        $target = Helpers::headerName();

        foreach ($headers as $name => $values) {
            if (strcasecmp((string) $name, $target) !== 0) {
                continue;
            }

            if (is_array($values)) {
                return $values[0] ?? null;
            }

            return $values ?: null;
        }

        return null;
    }
}
