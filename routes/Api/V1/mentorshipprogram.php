<?php

use App\Http\Controllers\Api\V1\MentorshipProgramController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('universities/{university}')
    ->scopeBindings()
    ->group(function () {
        Route::get('/mentorship-programs', [MentorshipProgramController::class, 'index'])
            ->middleware('permission:view-mentorship-programs')
            ;

        Route::post('/mentorship-programs', [MentorshipProgramController::class, 'store'])
            ->middleware('permission:create-mentorship-program')
            ;

        Route::put('/mentorship-programs/{mentorshipProgram}', [MentorshipProgramController::class, 'update'])
            ->middleware('permission:edit-mentorship-program')
            ;

        Route::post('/mentorship-programs/{mentorshipProgram}/activate', [MentorshipProgramController::class, 'activate'])
            ->middleware('permission:activate-mentorship-program')
            ;

        Route::post('/mentorship-programs/{mentorshipProgram}/close', [MentorshipProgramController::class, 'close'])
            ->middleware('permission:close-mentorship-program')
            ;
    });
