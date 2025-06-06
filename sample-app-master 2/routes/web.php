<?php

use App\Models\Counter;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $value = Counter::sum('count');
    return view('welcome', ['value' => $value]);
});

// Health Check Routes for Kubernetes Probes
Route::get('/health/startup', [HealthController::class, 'startup'])->name('health.startup');
Route::get('/health/ready', [HealthController::class, 'readiness'])->name('health.readiness');
Route::get('/health/live', [HealthController::class, 'liveness'])->name('health.liveness');
