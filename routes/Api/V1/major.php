<?php

use App\Models\University;
use App\Models\Scopes\UniversityScope;
use App\Http\Controllers\Api\V1\MajorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Major API Routes
|--------------------------------------------------------------------------
*/

Route::bind('university', function ($value) {
    return University::withoutGlobalScope(UniversityScope::class)->findOrFail($value);
});

Route::prefix('/universities/{university}/faculties/{faculty}')
    ->middleware(['auth:sanctum'])
    ->scopeBindings()
    ->group(function () {
        
        /**
         * List all majors (Any Authenticated User)
         * @see MajorController::index
         */
        Route::get('/majors', [MajorController::class, 'index'])
            ->name('api.v1.majors.index');

        /**
         * Create a specific major (Super Admin or Uni Admin only)
         * @see MajorController::store
         */
        Route::post('/majors', [MajorController::class, 'store'])
            ->middleware(['role:super_admin|uni_admin'])
            ->name('api.v1.majors.store');
            
    });