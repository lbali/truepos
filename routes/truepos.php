<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TruePos\Http\Controllers\ThreeDSecureController;

Route::post(
    config('truepos.threed_callback_route', '/truepos/3d/callback'),
    [ThreeDSecureController::class, 'callback']
)
    ->name('truepos.threed.callback')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware(\TruePos\Http\Middleware\VerifyThreeDSecureCallback::class);
