<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return a success API response with automated Pagination Meta detection.
     */
    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $code = 200
    ): JsonResponse {

        $paginator = null;

        // Auto-extract pagination instance whether passed directly or wrapped inside a ResourceCollection
        if ($data instanceof Paginator || $data instanceof CursorPaginator) {
            $paginator = $data;
        } elseif ($data instanceof ResourceCollection && ($data->resource instanceof Paginator || $data->resource instanceof CursorPaginator)) {
            $paginator = $data->resource;
        }

        if ($paginator) {
            $meta = array_merge($this->getPaginationMeta($paginator), $meta);
        }

        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
            'meta'    => empty($meta) ? null : $meta,
        ], $code);
    }

    /**
     * Return an error API response.
     */
    protected function errorResponse(string $message, mixed $errors = null, int $code = 400): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
        ], $code);
    }

    /**
     * Extract structured pagination metrics from a paginator instance.
     *//**
 * Extract structured pagination metrics from a paginator instance.
 */
private function getPaginationMeta(Paginator|CursorPaginator $paginator): array
{
    if ($paginator instanceof CursorPaginator) {
        return [
            'per_page'      => $paginator->perPage(),
            'next_cursor'   => $paginator->nextCursor()?->encode(),
            'prev_cursor'   => $paginator->previousCursor()?->encode(),
            'has_more'      => $paginator->hasMorePages(),
            'path'          => $paginator->path(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ];
    }

    return [
        'current_page'  => $paginator->currentPage(),
        'last_page'     => method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null,
        'per_page'      => $paginator->perPage(),
        'total'         => method_exists($paginator, 'total') ? $paginator->total() : null,
        'path'          => $paginator->path(),
        'next_page_url' => $paginator->nextPageUrl(),
        'prev_page_url' => $paginator->previousPageUrl(),
    ];
}
}
