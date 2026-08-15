<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Enterprise-grade Exception Mapper for handling API errors securely and uniformly.
 */
class ApiExceptionHandler
{
    /**
     * Maps any caught exception into a standardized, safe JSON API response.
     *
     * @param Throwable $e
     * @return JsonResponse
     */
    public static function map(Throwable $e): JsonResponse
    {
        return match (true) {
            //  Validation Errors
            $e instanceof ValidationException
                => self::handleValidation($e),

            //  Authentication Failures (Missing or Invalid Bearer Token)
            $e instanceof AuthenticationException
                => self::respond('Unauthenticated. Please provide a valid Token.', 401),

            //  Authorization & Policy Failures (403 Forbidden)
            $e instanceof AuthorizationException,
            $e instanceof AccessDeniedHttpException
                => self::respond($e->getMessage() ?: 'Forbidden. Insufficient permissions.', 403),

            //  Resource Not Found (404 - Model Binding Failures)
            $e instanceof ModelNotFoundException
                => self::respond('The requested resource was not found.', 404),

            //  Route / Endpoint Not Found (404)
            $e instanceof NotFoundHttpException
                => self::respond('The requested API endpoint was not found.', 404),

            //  Invalid HTTP Method (405)
            $e instanceof MethodNotAllowedHttpException
                => self::respond('HTTP method is not allowed for this route.', 405),

            //  Too many requests (429)
            $e instanceof ThrottleRequestsException
                => self::respond('Too many requests. Please try again later.', 429),

            //  Database Query Errors (500 - Logged securely, message masked)
            $e instanceof QueryException
                => self::handleDatabase($e),

            //  Custom Business Rules Exceptions (App-level domain rules)
            $e instanceof AppException
                => self::respond($e->getMessage(), $e->getCode() ?: 400),
                // Spatie handle
                $e instanceof \Spatie\Permission\Exceptions\UnauthorizedException
             => self::respond('Forbidden. Insufficient permissions.', 403),


            //  Fallback for all unhandled system exceptions
            default
                => self::handleUnhandled($e),
        };
    }

    /**
     * Handle validation exceptions to return field-specific errors.
     */
    private static function handleValidation(ValidationException $e): JsonResponse
    {
        return self::respond('Validation failed.', 422, $e->errors());
    }

    /**
     * Securely handle database errors. Details logged internally, public response masked.
     */
    private static function handleDatabase(QueryException $e): JsonResponse
    {
        Log::error('Database Exception: ' . $e->getMessage(), [
            'sql'  => $e->getSql(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return self::respond('A database operation error occurred. Please try again later.', 500);
    }

    /**
     * Fallback for uncaught system crashes. Absolute 0% leak of stack traces or internals.
     */
    private static function handleUnhandled(Throwable $e): JsonResponse
    {
        Log::critical('Unhandled System Exception: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);

        // Security Guarantee: Never expose $e->getMessage() to API output
        return self::respond('An unexpected server error occurred.', 500);
    }

    /**
     * Standard response factory enforcing API spec structure {status, message, data, errors}.
     */
    private static function respond(string $message, int $statusCode, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
        ], $statusCode);
    }
}
