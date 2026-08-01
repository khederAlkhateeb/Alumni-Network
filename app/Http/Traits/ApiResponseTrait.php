<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/*
|--------------------------------------------------------------------------
| API Response Trait
|--------------------------------------------------------------------------
|
| Provides a consistent JSON response structure across all API v1
| controllers: {status, message, data, meta?}.
|
*/

trait ApiResponseTrait
{
    /**
     * Return a successful JSON response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @return JsonResponse
     */
    protected function success(mixed $data = null, string $message = 'Success.', int $code = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Return a successful JSON response for a paginated resource.
     * Accepts either a raw LengthAwarePaginator or an
     * AnonymousResourceCollection (e.g. EventResource::collection($paginator)).
     *
     * @param  LengthAwarePaginator|AnonymousResourceCollection  $resource
     * @param  string  $message
     * @return JsonResponse
     */
    protected function paginated(LengthAwarePaginator|AnonymousResourceCollection $resource, string $message = 'Retrieved successfully.'): JsonResponse
    {
        // Extract the underlying paginator whether we received a resource
        // collection or a raw paginator directly.
        $paginator = $resource instanceof AnonymousResourceCollection
            ? $resource->resource
            : $resource;

        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $resource,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return an error JSON response.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  mixed  $errors
     * @return JsonResponse
     */
    protected function error(string $message = 'Something went wrong.', int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }
}
