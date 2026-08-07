<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DeviceController;
use App\Http\Controllers\Web\SmsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes for SMS Gateway Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/dashboard', DashboardController::class);

// Device Management Routes
Route::resource('devices', DeviceController::class)->except(['create', 'edit', 'show']);
Route::post('devices/{device}/regenerate-token', [DeviceController::class, 'regenerateToken'])->name('devices.regenerate-token');

// SMS Routes
Route::resource('sms', SmsController::class)->only(['index', 'show', 'destroy']);
Route::patch('sms/{sm}/toggle-processed', [SmsController::class, 'toggleProcessed'])->name('sms.toggle-processed');
