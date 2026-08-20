<?php

namespace App\Support;

use App\Models\ErrorLog;
use Throwable;

/**
 * Captures reportable exceptions into the error_logs table for the admin Error Logs page.
 * Best-effort only — it must never break the app, so everything is guarded.
 */
class ErrorLogger
{
    private static bool $capturing = false;

    /** Exception types that are normal request flow (not real errors) — never captured. */
    private const SKIP = [
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \App\Exceptions\RtsFileInvalid::class,
        \App\Exceptions\RtsUploadCanceled::class,
        \App\Exceptions\RtsNeedsConfirmation::class,
    ];

    public static function capture(Throwable $e): void
    {
        if (self::$capturing || self::shouldSkip($e)) {
            return;
        }

        self::$capturing = true;
        try {
            ErrorLog::create([
                'level'      => 'error',
                'exception'  => get_class($e),
                'message'    => mb_substr($e->getMessage(), 0, 2000),
                'file'       => mb_substr($e->getFile(), 0, 255),
                'line'       => $e->getLine(),
                'trace'      => mb_substr($e->getTraceAsString(), 0, 10000),
                'url'        => self::safeUrl(),
                'method'     => self::safeMethod(),
                'user_id'    => self::safeUser(),
                'created_at' => now(),
            ]);
        } catch (Throwable $ignored) {
            // never let logging break the app
        } finally {
            self::$capturing = false;
        }
    }

    private static function shouldSkip(Throwable $e): bool
    {
        foreach (self::SKIP as $class) {
            if ($e instanceof $class) {
                return true;
            }
        }

        // Skip 4xx HTTP exceptions (client errors like 404/403); keep 5xx.
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            $code = $e->getStatusCode();
            if ($code >= 400 && $code < 500) {
                return true;
            }
        }

        return false;
    }

    private static function safeUrl(): ?string
    {
        try {
            return app()->runningInConsole() ? null : mb_substr(request()->fullUrl(), 0, 255);
        } catch (Throwable) {
            return null;
        }
    }

    private static function safeMethod(): ?string
    {
        try {
            return app()->runningInConsole() ? null : request()->method();
        } catch (Throwable) {
            return null;
        }
    }

    private static function safeUser(): ?int
    {
        try {
            return auth()->id();
        } catch (Throwable) {
            return null;
        }
    }
}
