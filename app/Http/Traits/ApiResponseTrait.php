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
     *
     * @param  LengthAwarePaginator|AnonymousResourceCollection  $resource
     * @param  string  $message
     * @return JsonResponse
     */
    protected function paginated(LengthAwarePaginator|AnonymousResourceCollection $resource, string $message = 'Retrieved successfully.'): JsonResponse
    {
        // Extract the underlying paginator
        $paginator = $resource instanceof AnonymousResourceCollection
            ? $resource->resource
            : $resource;

        // If it's a resource collection, resolve it to get just the array of items.
        // If it's a raw paginator, grab the items directly.
        $items = $resource instanceof AnonymousResourceCollection
            ? $resource->resolve()
            : $paginator->items();

        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $items,
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
