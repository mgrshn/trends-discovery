<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;

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

    // Этап 5: Projects
    // Route::apiResource('/projects', ProjectController::class);
    // Route::post('/projects/{project}/topics', [ProjectController::class, 'addTopic']);
});
