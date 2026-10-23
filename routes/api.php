<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes registered here use Laravel's stateless API middleware and the
| automatic /api URL prefix. Add application endpoints as needed.
|
*/

Route::get('/status', function () {
    return response()->json([
        'status' => 'ok',
    ]);
})->name('api.status');
