<?php

use App\Http\Controllers\PassengerWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PassengerWebController::class, 'index'])->name('passenger.home');
Route::get('/ride/qr/{token}', [PassengerWebController::class, 'scanLanding'])->name('passenger.qr.landing');
Route::get('/claims', [PassengerWebController::class, 'index'])->name('passenger.claims');
Route::get('/rides', [PassengerWebController::class, 'index'])->name('passenger.rides');
