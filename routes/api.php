<?php

use App\Http\Controllers\Api\RoomApiController;
use App\Http\Controllers\Api\TenantApiController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {

    Route::apiResource('rooms', RoomApiController::class);

    Route::apiResource('tenants', TenantApiController::class);

});