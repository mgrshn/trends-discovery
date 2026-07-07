<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProjectController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All product routes in a single group — auth middleware can be added here later.
*/

Route::get('/health', [HealthController::class, 'index']);

Route::prefix('v1')->group(function () {
    // Этап 1: Trend Analysis
    Route::get('/analysis', [AnalysisController::class, 'show']);

    // Этап 2: Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/categories', [DashboardController::class, 'categories']);

    // Этап 4: Catalog
    Route::get('/catalog', [CatalogController::class, 'index']);
    Route::get('/catalog/categories', [CatalogController::class, 'categories']);

    // Этап 5: Projects / Trend Tracking
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::get('/projects/{id}/topics', [ProjectController::class, 'topics']);
    Route::post('/projects/{id}/topics', [ProjectController::class, 'addTopic']);
    Route::delete('/projects/{id}/topics/{topicId}', [ProjectController::class, 'removeTopic']);
    Route::get('/projects/{id}/export', [ProjectController::class, 'export']);
});
