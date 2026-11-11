<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register()
    {
        // ...existing code...
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'Something went wrong.',
            ], $this->isHttpException($exception) ? $exception->getStatusCode() : 500);
        }

        // Show a clear flash message for web 403 denials (instead of a blank/generic error).
        if ($this->isHttpException($exception) && (int) $exception->getStatusCode() === 403) {
            $message = trim((string) $exception->getMessage());
            if ($message === '') {
                $message = 'Permission denied. You do not have access for this action.';
            }

            $target = auth()->check() ? route('settings.index') : route('login');

            return redirect()->to($target)->with('error', $message);
        }

        return parent::render($request, $exception);
    }
}
