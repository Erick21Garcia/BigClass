<?php

use Illuminate\Support\Facades\Route;
use Modules\Institucion\Http\Controllers\InstitucionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('institucions', InstitucionController::class)->names('institucion');
});
