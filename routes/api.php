<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Automatically loads all route files located in routes/Api/V1 directory.
|
*/

Route::prefix('v1')->group(function () {
    foreach (File::allFiles(__DIR__ . '/Api/V1') as $file) {
        require $file->getPathname();
    }
});