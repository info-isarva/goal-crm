<?php

namespace App\Http\Middleware;

use App\Helpers\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldLog($request, $response)) {
            return $response;
        }

        $routeName = optional($request->route())->getName();
        $path = '/' . ltrim($request->path(), '/');

        ActivityLogger::log([
            'type' => 'request',
            'module' => $this->resolveModule($routeName, $path),
            'action' => $routeName ?: strtoupper($request->method()) . ' ' . $path,
            'details' => [
                'method' => $request->method(),
                'path' => $path,
                'route' => $routeName,
                'status' => $response->getStatusCode(),
                'input' => ActivityLogger::redact($request->except([
                    'password',
                    'password_confirmation',
                    'current_password',
                    '_token',
                ])),
            ],
        ]);

        return $response;
    }

    protected function shouldLog(Request $request, Response $response): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $status = $response->getStatusCode();
        if ($status >= 400) {
            return false;
        }

        $routeName = (string) optional($request->route())->getName();
        if ($routeName !== '' && str_starts_with($routeName, 'activity-logs.')) {
            return false;
        }

        $method = strtoupper($request->method());
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        // Also capture export/download style GET actions.
        if ($method === 'GET') {
            $haystack = strtolower($routeName . ' ' . $request->path());
            return (bool) preg_match('/(export|download|import\.page|excel|pdf|csv)/', $haystack);
        }

        return false;
    }

    protected function resolveModule(?string $routeName, string $path): string
    {
        $name = (string) $routeName;
        if ($name !== '') {
            $first = explode('.', $name)[0] ?? '';
            if ($first === 'goal') {
                $parts = explode('.', $name);
                return 'Goal ' . ucfirst(str_replace('-', ' ', $parts[1] ?? 'Module'));
            }
            if ($first !== '') {
                return ucfirst(str_replace(['-', '_'], ' ', $first));
            }
        }

        $segment = explode('/', trim($path, '/'))[0] ?? 'App';
        if ($segment === 'goal') {
            $second = explode('/', trim($path, '/'))[1] ?? 'module';
            return 'Goal ' . ucfirst(str_replace('-', ' ', $second));
        }

        return ucfirst(str_replace(['-', '_'], ' ', $segment ?: 'App'));
    }
}
