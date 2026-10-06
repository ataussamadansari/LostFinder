<?php

use App\Http\Controllers\Api\V1\QrPublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/q/{token}', [QrPublicController::class, 'showWeb'])
    ->middleware('throttle:30,1')
    ->name('qr.public.web');
