<?php

use App\Http\Controllers\CounterController;
use App\Http\Controllers\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// Existing Counter API routes
Route::get('counter/add', [CounterController::class, 'add']);
Route::get('counter/count', [CounterController::class, 'get']);

// Health Check API Routes for Kubernetes Probes (accessible via /api/health/*)
Route::get('health/startup', [HealthController::class, 'startup'])->name('api.health.startup');
Route::get('health/ready', [HealthController::class, 'readiness'])->name('api.health.readiness');
Route::get('health/live', [HealthController::class, 'liveness'])->name('api.health.liveness');
